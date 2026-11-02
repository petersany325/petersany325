<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\RemotePartPreorder;
use App\Services\StaffNotifier;
use App\Support\RemotePartPreorderSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RemotePartPreorderController extends Controller
{
    public function index(Request $request)
    {
        $customer = $this->customer($request);
        $enabled = RemotePartPreorderSettings::isEnabled();

        $preorders = RemotePartPreorder::query()
            ->where('customer_id', $customer->id)
            ->latest('id')
            ->paginate(12);

        return view('portal.preorders.index', compact('customer', 'preorders', 'enabled'));
    }

    public function create(Request $request)
    {
        $customer = $this->customer($request);
        abort_unless(RemotePartPreorderSettings::isEnabled(), 404);

        $settings = RemotePartPreorderSettings::all();

        return view('portal.preorders.create', compact('customer', 'settings'));
    }

    public function store(Request $request, StaffNotifier $notifier)
    {
        $customer = $this->customer($request);
        abort_unless(RemotePartPreorderSettings::isEnabled(), 404);

        $settings = RemotePartPreorderSettings::all();
        $min = $settings['min_photos'];
        $max = $settings['max_photos'];

        $data = $request->validate([
            'part_title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'tracking_code' => ['nullable', 'string', 'max:80'],
            'origin_city' => ['nullable', 'string', 'max:80'],
            'serial_number' => ['nullable', 'string', 'max:120'],
            'brand_model' => ['nullable', 'string', 'max:160'],
            'photos' => ['required', 'array', 'min:'.$min, 'max:'.$max],
            'photos.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ], [
            'part_title.required' => 'نوع قطعه / دستگاه را بنویسید.',
            'photos.required' => 'حداقل یک عکس از قطعه لازم است.',
            'photos.min' => 'حداقل '.$min.' عکس لازم است.',
            'photos.max' => 'حداکثر '.$max.' عکس مجاز است.',
            'photos.*.mimes' => 'فرمت مجاز عکس: JPG، PNG یا WEBP.',
        ]);

        $preorder = DB::transaction(function () use ($data, $customer, $request) {
            $row = RemotePartPreorder::query()->create([
                'code' => RemotePartPreorder::nextCode(),
                'customer_id' => $customer->id,
                'part_title' => trim($data['part_title']),
                'description' => isset($data['description']) ? trim((string) $data['description']) : null,
                'tracking_code' => isset($data['tracking_code']) ? strtoupper(trim((string) $data['tracking_code'])) : null,
                'origin_city' => isset($data['origin_city']) ? trim((string) $data['origin_city']) : null,
                'serial_number' => isset($data['serial_number']) ? strtoupper(trim((string) $data['serial_number'])) : null,
                'brand_model' => isset($data['brand_model']) ? strtoupper(trim((string) $data['brand_model'])) : null,
                'status' => RemotePartPreorder::STATUS_PENDING_ARRIVAL,
                'photos' => [],
            ]);

            $photos = [];
            foreach ($request->file('photos', []) as $i => $file) {
                if (! $file) {
                    continue;
                }
                $path = $file->store('remote-part-preorders/'.$row->id, 'local');
                $photos[] = [
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'label' => $i === 0 ? 'اصلی' : null,
                ];
            }
            $row->forceFill(['photos' => $photos])->save();

            return $row->fresh();
        });

        $notifier->notifyMany(
            $notifier->deskUsers(),
            'remote_part_preorder',
            'پیش‌سفارش قطعه جدید',
            $customer->name.' · '.$preorder->part_title.' · '.$preorder->code,
            route('remote-preorders.show', $preorder)
        );

        return redirect()
            ->route('portal.preorders.show', $preorder)
            ->with('success', 'پیش‌سفارش ثبت شد. کد '.$preorder->code.' را روی بسته بنویسید.');
    }

    public function show(Request $request, RemotePartPreorder $preorder)
    {
        $customer = $this->customer($request);
        abort_unless((int) $preorder->customer_id === (int) $customer->id, 404);
        $preorder->load('reception');

        return view('portal.preorders.show', compact('customer', 'preorder'));
    }

    public function photo(Request $request, RemotePartPreorder $preorder): StreamedResponse
    {
        $customer = $this->customer($request);
        abort_unless((int) $preorder->customer_id === (int) $customer->id, 404);

        $path = (string) $request->query('path', '');
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

    private function customer(Request $request): Customer
    {
        /** @var Customer $customer */
        $customer = $request->attributes->get('portalCustomer');

        return $customer;
    }
}
