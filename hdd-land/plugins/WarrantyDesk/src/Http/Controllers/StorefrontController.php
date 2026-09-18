<?php

namespace Plugins\WarrantyDesk\src\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Plugins\WarrantyDesk\Plugin;
use Plugins\WarrantyDesk\src\Models\WarrantyRequest;
use Plugins\WarrantyDesk\src\Support\PageCopy;
use Plugins\WarrantyDesk\src\Support\SmsHook;

class StorefrontController extends Controller
{
    public function show()
    {
        Plugin::ensureSchema();
        $copy = PageCopy::get();

        return view('warranty-desk::storefront.page', [
            'copy' => $copy,
            'packages' => PageCopy::packages($copy),
            'types' => PageCopy::applicantTypes(),
        ]);
    }

    public function store(Request $request)
    {
        Plugin::ensureSchema();
        $copy = PageCopy::get();
        $types = implode(',', array_keys(PageCopy::applicantTypes()));

        $data = $request->validate([
            'applicant_type' => 'required|in:'.$types,
            'org_name' => 'required|string|max:190',
            'contact_name' => 'required|string|max:120',
            'mobile' => 'required|string|max:30',
            'phone' => 'nullable|string|max:30',
            'city' => 'nullable|string|max:80',
            'product_kind' => 'nullable|string|max:120',
            'brand_model' => 'nullable|string|max:190',
            'qty' => 'nullable|integer|min:1|max:9999',
            'serials' => 'nullable|string|max:4000',
            'notes' => 'nullable|string|max:2000',
            'package_name' => 'nullable|string|max:160',
        ], [
            'org_name.required' => 'نام فروشگاه / شرکت / سازمان را بنویسید.',
            'contact_name.required' => 'نام مسئول پیگیری لازم است.',
            'mobile.required' => 'موبایل برای پیامک وضعیت لازم است.',
        ]);

        $row = WarrantyRequest::query()->create([
            'public_code' => WarrantyRequest::makeCode(),
            'user_id' => optional($request->user())->id,
            'applicant_type' => $data['applicant_type'],
            'org_name' => trim($data['org_name']),
            'contact_name' => trim($data['contact_name']),
            'mobile' => preg_replace('/\s+/', '', $data['mobile']) ?: $data['mobile'],
            'phone' => trim((string) ($data['phone'] ?? '')) ?: null,
            'city' => trim((string) ($data['city'] ?? '')) ?: null,
            'product_kind' => trim((string) ($data['product_kind'] ?? '')) ?: null,
            'brand_model' => trim((string) ($data['brand_model'] ?? '')) ?: null,
            'qty' => (int) ($data['qty'] ?? 1) ?: 1,
            'serials' => trim((string) ($data['serials'] ?? '')) ?: null,
            'notes' => trim((string) ($data['notes'] ?? '')) ?: null,
            'package_name' => trim((string) ($data['package_name'] ?? '')) ?: null,
            'status' => 'submitted',
        ]);

        if (! empty($copy['sms_on_status'])) {
            $ok = SmsHook::notifyStatus($row->mobile, $row->public_code, $row->statusLabel(), 'ثبت شد');
            $row->appendSmsLog($ok ? 'ارسال شد: ثبت اولیه' : 'ارسال ناموفق: ثبت اولیه');
            $row->save();
        }

        return redirect()
            ->to(url('/warranty-register/'.$row->public_code))
            ->with('success', $copy['success']);
    }

    public function status(Request $request, string $code = '')
    {
        Plugin::ensureSchema();
        $copy = PageCopy::get();
        $code = strtoupper(trim($code !== '' ? $code : (string) $request->query('code', '')));
        $row = null;
        if ($code !== '') {
            $row = WarrantyRequest::query()->where('public_code', $code)->first();
        }

        return view('warranty-desk::storefront.status', [
            'copy' => $copy,
            'row' => $row,
            'code' => $code,
            'notFound' => $code !== '' && ! $row,
        ]);
    }
}
