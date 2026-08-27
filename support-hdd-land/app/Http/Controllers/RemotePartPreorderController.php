<?php

namespace App\Http\Controllers;

use App\Models\CustomerMessage;
use App\Models\Reception;
use App\Models\RemotePartPreorder;
use App\Models\SmsLog;
use App\Services\AccountingService;
use App\Services\NiazpardazSmsService;
use App\Support\RemotePartPreorderSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RemotePartPreorderController extends Controller
{
    public function index(Request $request)
    {
        $status = trim((string) $request->get('status', 'open'));
        $q = trim((string) $request->get('q', ''));

        $query = RemotePartPreorder::query()->with(['customer', 'reviewer', 'reception']);

        if ($status === 'open') {
            $query->whereIn('status', [
                RemotePartPreorder::STATUS_PENDING_ARRIVAL,
                RemotePartPreorder::STATUS_ARRIVED,
            ]);
        } elseif ($status !== '' && array_key_exists($status, RemotePartPreorder::STATUSES)) {
            $query->where('status', $status);
        } else {
            $status = '';
        }

        if ($q !== '') {
            $like = '%'.$q.'%';
            $query->where(function ($inner) use ($like) {
                $inner->where('code', 'like', $like)
                    ->orWhere('tracking_code', 'like', $like)
                    ->orWhere('part_title', 'like', $like)
                    ->orWhere('serial_number', 'like', $like)
                    ->orWhere('origin_city', 'like', $like)
                    ->orWhereHas('customer', function ($c) use ($like) {
                        $c->where('name', 'like', $like)->orWhere('phone', 'like', $like);
                    });
            });
        }

        $preorders = $query->latest('id')->paginate(20)->withQueryString();

        $stats = [
            'pending_arrival' => RemotePartPreorder::query()->where('status', RemotePartPreorder::STATUS_PENDING_ARRIVAL)->count(),
            'arrived' => RemotePartPreorder::query()->where('status', RemotePartPreorder::STATUS_ARRIVED)->count(),
            'matched' => RemotePartPreorder::query()->where('status', RemotePartPreorder::STATUS_MATCHED)->whereDate('reviewed_at', now('Asia/Tehran')->toDateString())->count(),
            'open' => RemotePartPreorder::query()->whereIn('status', [
                RemotePartPreorder::STATUS_PENDING_ARRIVAL,
                RemotePartPreorder::STATUS_ARRIVED,
            ])->count(),
        ];

        return view('remote-preorders.index', [
            'preorders' => $preorders,
            'stats' => $stats,
            'status' => $status,
            'q' => $q,
            'statusLabels' => RemotePartPreorder::STATUSES,
            'enabled' => RemotePartPreorderSettings::isEnabled(),
        ]);
    }

    public function show(RemotePartPreorder $preorder)
    {
        $preorder->load(['customer', 'reviewer', 'reception']);

        return view('remote-preorders.show', [
            'preorder' => $preorder,
            'statusLabels' => RemotePartPreorder::STATUSES,
            'matchResults' => RemotePartPreorder::MATCH_RESULTS,
            'officePhone' => RemotePartPreorderSettings::officePhone(),
        ]);
    }

    public function markArrived(RemotePartPreorder $preorder)
    {
        abort_unless($preorder->status === RemotePartPreorder::STATUS_PENDING_ARRIVAL, 422);

        $preorder->forceFill([
            'status' => RemotePartPreorder::STATUS_ARRIVED,
            'arrived_at' => now('Asia/Tehran'),
        ])->save();

        return back()->with('success', 'وضعیت به «بار رسیده» تغییر کرد. حالا مشخصات را بررسی کنید.');
    }

    public function updateSpecs(Request $request, RemotePartPreorder $preorder)
    {
        abort_unless($preorder->canConvert(), 422, 'این پیش‌سفارش قابل ویرایش نیست.');

        $data = $this->validatedSpecs($request);
        $preorder->forceFill($data)->save();

        return back()->with('success', 'مشخصات ویرایش و ذخیره شد. پس از بررسی نهایی، قبض را تأیید یا رد کنید.');
    }

    public function convert(Request $request, RemotePartPreorder $preorder, NiazpardazSmsService $sms)
    {
        abort_unless($preorder->canConvert(), 422, 'این پیش‌سفارش قابل تبدیل به قبض نیست.');

        $data = $request->validate([
            'match_result' => ['required', Rule::in(array_keys(RemotePartPreorder::MATCH_RESULTS))],
            'part_title' => ['required', 'string', 'max:160'],
            'serial_number' => ['nullable', 'string', 'max:120'],
            'brand_model' => ['nullable', 'string', 'max:160'],
            'admin_note' => ['nullable', 'string', 'max:1000'],
            'reported_fault' => ['nullable', 'string', 'max:2000'],
            'notify_customer' => ['nullable', 'boolean'],
        ]);

        $specs = [
            'part_title' => trim($data['part_title']),
            'serial_number' => isset($data['serial_number']) ? strtoupper(trim((string) $data['serial_number'])) : null,
            'brand_model' => isset($data['brand_model']) ? strtoupper(trim((string) $data['brand_model'])) : null,
            'description' => isset($data['reported_fault']) ? trim((string) $data['reported_fault']) : $preorder->description,
            'admin_note' => isset($data['admin_note']) ? trim((string) $data['admin_note']) : null,
        ];
        $notify = $request->boolean('notify_customer', true);

        if ($data['match_result'] !== RemotePartPreorder::MATCH_OK) {
            $preorder->forceFill(array_merge($specs, [
                'status' => RemotePartPreorder::STATUS_REJECTED,
                'match_result' => $data['match_result'],
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now('Asia/Tehran'),
                'arrived_at' => $preorder->arrived_at ?: now('Asia/Tehran'),
            ]))->save();

            if ($notify) {
                $this->notifyCustomerDecision($preorder->fresh(['customer']), approved: false, reception: null, sms: $sms);
            }

            return redirect()
                ->route('remote-preorders.show', $preorder)
                ->with('success', 'پیش‌سفارش تأیید نشد و به مشتری اطلاع داده شد (کارتابل'.($notify ? ' + پیامک' : '').').');
        }

        $reception = DB::transaction(function () use ($preorder, $specs, $data) {
            $locked = RemotePartPreorder::query()->lockForUpdate()->findOrFail($preorder->id);
            if (! $locked->canConvert()) {
                return null;
            }

            $serial = (string) ($specs['serial_number'] ?? '');
            $brandModel = (string) ($specs['brand_model'] ?? '');
            $productName = $brandModel !== '' ? $brandModel : (string) $specs['part_title'];

            $photoPath = null;
            foreach ($locked->photoList() as $photo) {
                $src = $photo['path'] ?? null;
                if (! $src || ! Storage::disk('local')->exists($src)) {
                    continue;
                }
                $ext = pathinfo($src, PATHINFO_EXTENSION) ?: 'jpg';
                $dest = 'receptions/preorder-'.$locked->id.'-'.uniqid('', true).'.'.$ext;
                Storage::disk('public')->put($dest, Storage::disk('local')->get($src));
                $photoPath = $dest;
                break;
            }

            $reception = Reception::create([
                'ticket_no' => Reception::nextTicketNo(),
                'receipt_no' => Reception::nextReceiptNo(),
                'customer_id' => $locked->customer_id,
                'created_by' => Auth::id(),
                'product_name' => $productName,
                'brand' => null,
                'model' => $brandModel !== '' ? $brandModel : null,
                'serial_number' => $serial !== '' ? $serial : null,
                'delivered_by' => $locked->customer?->name,
                'photo_path' => $photoPath,
                'appearance_notes' => $specs['admin_note'] ?? null,
                'reported_fault' => $specs['description'] ?? $locked->description,
                'accessories' => 'پیش‌سفارش '.$locked->code.($locked->tracking_code ? ' · باربری '.$locked->tracking_code : ''),
                'status' => 'received',
                'deposit' => 0,
                'pos_amount' => 0,
                'admission_fee' => 0,
                'estimated_cost' => 0,
                'payment_method' => 'cash',
                'paid_amount' => 0,
                'received_at' => now('Asia/Tehran'),
            ]);

            $reception->recalculateTotals();

            try {
                app(AccountingService::class)->syncReceptionRevenue($reception->fresh());
            } catch (\Throwable $e) {
            }

            $locked->forceFill(array_merge($specs, [
                'status' => RemotePartPreorder::STATUS_MATCHED,
                'match_result' => RemotePartPreorder::MATCH_OK,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now('Asia/Tehran'),
                'arrived_at' => $locked->arrived_at ?: now('Asia/Tehran'),
                'reception_id' => $reception->id,
            ]))->save();

            return $reception;
        });

        if (! $reception) {
            return back()->with('error', 'این پیش‌سفارش قبلاً تبدیل شده است.');
        }

        $preorder = $preorder->fresh(['customer', 'reception']);
        if ($notify) {
            $this->notifyCustomerDecision($preorder, approved: true, reception: $reception, sms: $sms);
        }

        return redirect()
            ->route('receptions.show', $reception)
            ->with('success', 'قبض تأیید و صادر شد. به کارتابل مشتری'.($notify ? ' و پیامک' : '').' اطلاع داده شد.');
    }

    public function photo(RemotePartPreorder $preorder): StreamedResponse
    {
        $path = (string) request()->query('path', '');
        abort_unless($preorder->hasPhoto($path), 404);

        $mime = Storage::disk('local')->mimeType($path) ?: 'application/octet-stream';
        $name = basename($path);
        foreach ($preorder->photoList() as $photo) {
            if (($photo['path'] ?? '') === $path && filled($photo['original_name'] ?? null)) {
                $name = $photo['original_name'];
                break;
            }
        }

        return Storage::disk('local')->response($path, $name, ['Content-Type' => $mime]);
    }

    public function settings()
    {
        return view('remote-preorders.settings', [
            'settings' => RemotePartPreorderSettings::all(),
        ]);
    }

    public function saveSettings(Request $request)
    {
        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'min_photos' => ['required', 'integer', 'min:1', 'max:8'],
            'max_photos' => ['required', 'integer', 'min:1', 'max:8'],
            'instructions' => ['nullable', 'string', 'max:1000'],
            'office_phone' => ['required', 'string', 'max:30'],
        ]);

        if ((int) $data['max_photos'] < (int) $data['min_photos']) {
            return back()->withErrors(['max_photos' => 'حداکثر عکس نمی‌تواند کمتر از حداقل باشد.'])->withInput();
        }

        RemotePartPreorderSettings::save([
            'enabled' => $request->boolean('enabled'),
            'min_photos' => (int) $data['min_photos'],
            'max_photos' => (int) $data['max_photos'],
            'instructions' => $data['instructions'] ?? '',
            'office_phone' => $data['office_phone'],
        ]);

        return back()->with('success', 'تنظیمات پیش‌سفارش قطعه ذخیره شد.');
    }

    /** @return array{part_title:string,serial_number:?string,brand_model:?string,description:?string,admin_note:?string} */
    private function validatedSpecs(Request $request): array
    {
        $data = $request->validate([
            'part_title' => ['required', 'string', 'max:160'],
            'serial_number' => ['nullable', 'string', 'max:120'],
            'brand_model' => ['nullable', 'string', 'max:160'],
            'reported_fault' => ['nullable', 'string', 'max:2000'],
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        return [
            'part_title' => trim($data['part_title']),
            'serial_number' => isset($data['serial_number']) ? strtoupper(trim((string) $data['serial_number'])) : null,
            'brand_model' => isset($data['brand_model']) ? strtoupper(trim((string) $data['brand_model'])) : null,
            'description' => isset($data['reported_fault']) ? trim((string) $data['reported_fault']) : null,
            'admin_note' => isset($data['admin_note']) ? trim((string) $data['admin_note']) : null,
        ];
    }

    private function notifyCustomerDecision(
        RemotePartPreorder $preorder,
        bool $approved,
        ?Reception $reception,
        NiazpardazSmsService $sms
    ): void {
        $customer = $preorder->customer;
        if (! $customer) {
            return;
        }

        $office = RemotePartPreorderSettings::officePhone();
        $shop = shop_name();

        if ($approved && $reception) {
            $body = "قبض شما برای پیش‌سفارش {$preorder->code} تأیید و صادر شد.\n"
                ."شماره قبض: {$reception->ticket_no}\n"
                .'می‌توانید وضعیت را از کارتابل مشتری پیگیری کنید.'
                ."\nتلفن دفتر: {$office}";
            $smsText = "{$shop}\nقبض تأیید شد\n{$reception->ticket_no}\nپیش‌سفارش {$preorder->code}\nتلفن دفتر: {$office}";
        } else {
            $reason = $preorder->admin_note ?: 'لطفاً با دفتر تماس بگیرید.';
            $body = "پیش‌سفارش {$preorder->code} تأیید نشد و نیاز به تماس با دفتر دارد.\n"
                ."توضیح: {$reason}\n"
                ."تلفن دفتر: {$office}";
            $smsText = "{$shop}\nپیش‌سفارش {$preorder->code} تأیید نشد\nنیاز به تماس با دفتر\n{$office}";
        }

        CustomerMessage::query()->create([
            'customer_id' => $customer->id,
            'reception_id' => $reception?->id,
            'remote_part_preorder_id' => $preorder->id,
            'body' => $body,
            'priority' => $approved ? 'normal' : 'urgent',
            'direction' => CustomerMessage::DIRECTION_OUTBOUND,
            'staff_read_at' => now('Asia/Tehran'),
            'handled_by' => Auth::id(),
        ]);

        $phone = \App\Models\User::normalizePhone((string) $customer->phone);
        if (! $phone) {
            return;
        }

        try {
            $result = $sms->send((string) $phone, $smsText);
            SmsLog::create([
                'customer_id' => $customer->id,
                'reception_id' => $reception?->id,
                'sent_by' => Auth::id(),
                'phone' => $phone,
                'status_key' => $approved ? 'remote_preorder_approved' : 'remote_preorder_rejected',
                'audience' => 'customer',
                'message' => $smsText,
                'ok' => (bool) ($result['ok'] ?? false),
                'provider_message' => $result['message'] ?? null,
            ]);
        } catch (\Throwable $e) {
            try {
                SmsLog::create([
                    'customer_id' => $customer->id,
                    'reception_id' => $reception?->id,
                    'sent_by' => Auth::id(),
                    'phone' => $phone,
                    'status_key' => $approved ? 'remote_preorder_approved' : 'remote_preorder_rejected',
                    'audience' => 'customer',
                    'message' => $smsText,
                    'ok' => false,
                    'provider_message' => $e->getMessage(),
                ]);
            } catch (\Throwable $ignored) {
            }
        }
    }
}
