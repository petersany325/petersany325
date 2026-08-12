<?php

namespace App\Http\Controllers;

use App\Models\Reception;
use App\Models\RemotePartPreorder;
use App\Services\AccountingService;
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
        ]);
    }

    public function markArrived(RemotePartPreorder $preorder)
    {
        abort_unless($preorder->status === RemotePartPreorder::STATUS_PENDING_ARRIVAL, 422);

        $preorder->forceFill([
            'status' => RemotePartPreorder::STATUS_ARRIVED,
            'arrived_at' => now('Asia/Tehran'),
        ])->save();

        return back()->with('success', 'وضعیت به «بار رسیده» تغییر کرد.');
    }

    public function convert(Request $request, RemotePartPreorder $preorder)
    {
        abort_unless($preorder->canConvert(), 422, 'این پیش‌سفارش قابل تبدیل به قبض نیست.');

        $data = $request->validate([
            'match_result' => ['required', Rule::in(array_keys(RemotePartPreorder::MATCH_RESULTS))],
            'serial_number' => ['nullable', 'string', 'max:120'],
            'brand_model' => ['nullable', 'string', 'max:160'],
            'admin_note' => ['nullable', 'string', 'max:1000'],
            'reported_fault' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($data['match_result'] !== RemotePartPreorder::MATCH_OK) {
            $preorder->forceFill([
                'status' => RemotePartPreorder::STATUS_REJECTED,
                'match_result' => $data['match_result'],
                'admin_note' => $data['admin_note'] ?? null,
                'serial_number' => isset($data['serial_number']) ? strtoupper(trim((string) $data['serial_number'])) : $preorder->serial_number,
                'brand_model' => isset($data['brand_model']) ? strtoupper(trim((string) $data['brand_model'])) : $preorder->brand_model,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now('Asia/Tehran'),
            ])->save();

            return back()->with('success', 'نتیجه تطبیق ثبت شد (بدون ساخت قبض).');
        }

        $reception = DB::transaction(function () use ($preorder, $data) {
            $locked = RemotePartPreorder::query()->lockForUpdate()->findOrFail($preorder->id);
            if (! $locked->canConvert()) {
                return null;
            }

            $serial = strtoupper(trim((string) ($data['serial_number'] ?? $locked->serial_number ?? '')));
            $brandModel = strtoupper(trim((string) ($data['brand_model'] ?? $locked->brand_model ?? '')));
            $productName = $brandModel !== '' ? $brandModel : $locked->part_title;

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
                'appearance_notes' => $data['admin_note'] ?? null,
                'reported_fault' => $data['reported_fault'] ?? $locked->description,
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

            $locked->forceFill([
                'status' => RemotePartPreorder::STATUS_MATCHED,
                'match_result' => RemotePartPreorder::MATCH_OK,
                'admin_note' => $data['admin_note'] ?? null,
                'serial_number' => $serial !== '' ? $serial : $locked->serial_number,
                'brand_model' => $brandModel !== '' ? $brandModel : $locked->brand_model,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now('Asia/Tehran'),
                'arrived_at' => $locked->arrived_at ?: now('Asia/Tehran'),
                'reception_id' => $reception->id,
            ])->save();

            return $reception;
        });

        if (! $reception) {
            return back()->with('error', 'این پیش‌سفارش قبلاً تبدیل شده است.');
        }

        return redirect()
            ->route('receptions.show', $reception)
            ->with('success', 'قبض از روی پیش‌سفارش ساخته شد.');
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
        ]);

        if ((int) $data['max_photos'] < (int) $data['min_photos']) {
            return back()->withErrors(['max_photos' => 'حداکثر عکس نمی‌تواند کمتر از حداقل باشد.'])->withInput();
        }

        RemotePartPreorderSettings::save([
            'enabled' => $request->boolean('enabled'),
            'min_photos' => (int) $data['min_photos'],
            'max_photos' => (int) $data['max_photos'],
            'instructions' => $data['instructions'] ?? '',
        ]);

        return back()->with('success', 'تنظیمات پیش‌سفارش قطعه ذخیره شد.');
    }
}
