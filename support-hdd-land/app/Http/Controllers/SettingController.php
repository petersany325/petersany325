<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\FaultType;
use App\Models\LookupOption;
use App\Models\ReferralSource;
use App\Models\User;
use App\Services\NiazpardazSmsService;
use App\Support\PaymentGateways;
use App\Support\Permissions;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingController extends Controller
{
    public function index()
    {
        $lookups = [];
        foreach (LookupOption::GROUPS as $key => $label) {
            $lookups[$key] = [
                'label' => $label,
                'items' => LookupOption::query()->where('group_key', $key)->orderBy('sort_order')->orderBy('name')->get(),
            ];
        }

        return view('settings.index', [
            'faultTypes' => FaultType::orderBy('name')->get(),
            'referralSources' => ReferralSource::orderBy('name')->get(),
            'users' => User::orderBy('name')->get(),
            'lookups' => $lookups,
            'sms' => [
                'username' => AppSetting::getValue('niazpardaz_username', env('NIAZPARDAZ_USERNAME')),
                'password' => AppSetting::getValue('niazpardaz_password', env('NIAZPARDAZ_PASSWORD')),
                'api_key' => AppSetting::getValue('niazpardaz_api_key', env('NIAZPARDAZ_API_KEY')),
                'from' => AppSetting::getValue('niazpardaz_from', env('NIAZPARDAZ_FROM_NUMBER')),
            ],
            'invoice' => [
                'shop_name' => AppSetting::getValue('invoice_shop_name', 'سرزمین هارد'),
                'phones' => AppSetting::getValue('invoice_phones', ''),
                'address' => AppSetting::getValue('invoice_address', ''),
                'footer' => AppSetting::getValue('invoice_footer', 'مدیریت تعمیرکاران — قبض پذیرش'),
                'terms' => AppSetting::getValue('invoice_terms', ''),
                'auto_print' => AppSetting::getValue('invoice_auto_print', '0') === '1',
                'show_logo' => AppSetting::getValue('invoice_show_logo', '1') !== '0',
                'font_size' => (int) AppSetting::getValue('invoice_font_size', '11'),
                'page_size' => AppSetting::getValue('invoice_page_size', 'A4'),
                'margin_mm' => (int) AppSetting::getValue('invoice_margin_mm', '10'),
                'show_deposit' => AppSetting::getValue('invoice_show_deposit', '1') !== '0',
                'show_estimated_cost' => AppSetting::getValue('invoice_show_estimated_cost', '1') !== '0',
                'show_accessories' => AppSetting::getValue('invoice_show_accessories', '1') !== '0',
                'show_appearance' => AppSetting::getValue('invoice_show_appearance', '1') !== '0',
                'show_technician' => AppSetting::getValue('invoice_show_technician', '1') !== '0',
                'show_warranty' => AppSetting::getValue('invoice_show_warranty', '1') !== '0',
                'show_parts' => AppSetting::getValue('invoice_show_parts', '1') !== '0',
                'show_payments' => AppSetting::getValue('invoice_show_payments', '1') !== '0',
                'show_fault' => AppSetting::getValue('invoice_show_fault', '1') !== '0',
                'show_serial' => AppSetting::getValue('invoice_show_serial', '1') !== '0',
            ],
            'paymentGateways' => PaymentGateways::all(),
            'paymentLinksShow' => [
                'reception' => PaymentGateways::showOnReception(),
                'invoice' => PaymentGateways::showOnInvoice(),
                'otp_debug' => AppSetting::getValue('portal_otp_debug', '0') === '1',
            ],
            'zarinpal' => PaymentGateways::zarinpal(),
            'portalUrl' => url('/cartable'),
            'permissions' => Permissions::ALL,
        ]);
    }

    public function storeFaultType(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        FaultType::create($data);

        return back()->with('success', 'نوع ایراد ثبت شد.');
    }

    public function updateFaultType(Request $request, FaultType $faultType)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $faultType->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? $faultType->description,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'نوع ایراد ویرایش شد.');
    }

    public function destroyFaultType(FaultType $faultType)
    {
        $inUse = \App\Models\Reception::query()->where('fault_type_id', $faultType->id)->exists();
        if ($inUse) {
            $faultType->update(['is_active' => false]);

            return back()->with('success', 'این نوع ایراد در قبض‌ها استفاده شده؛ به‌جای حذف، غیرفعال شد.');
        }

        $faultType->delete();

        return back()->with('success', 'نوع ایراد حذف شد.');
    }

    public function storeReferralSource(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
        ]);

        ReferralSource::create($data);

        return back()->with('success', 'نحوه آشنایی ثبت شد.');
    }

    public function updateReferralSource(Request $request, ReferralSource $referralSource)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $referralSource->update([
            'name' => $data['name'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'نحوه آشنایی ویرایش شد.');
    }

    public function destroyReferralSource(ReferralSource $referralSource)
    {
        $inUse = \App\Models\Customer::query()->where('referral_source_id', $referralSource->id)->exists();
        if ($inUse) {
            $referralSource->update(['is_active' => false]);

            return back()->with('success', 'این نحوه آشنایی برای مشتری ثبت شده؛ به‌جای حذف، غیرفعال شد.');
        }

        $referralSource->delete();

        return back()->with('success', 'نحوه آشنایی حذف شد.');
    }

    public function storeUser(Request $request)
    {
        return redirect()->route('employees.create');
    }

    public function updateSms(Request $request)
    {
        $data = $request->validate([
            'niazpardaz_username' => ['nullable', 'string', 'max:120'],
            'niazpardaz_password' => ['nullable', 'string', 'max:120'],
            'niazpardaz_api_key' => ['nullable', 'string', 'max:255'],
            'niazpardaz_from' => ['nullable', 'string', 'max:30'],
        ]);

        foreach ($data as $key => $value) {
            if ($value !== null && $value !== '') {
                AppSetting::setValue($key, $value);
            }
        }

        return back()->with('success', 'تنظیمات پنل نیازپرداز ذخیره شد.');
    }

    public function testSms(Request $request, NiazpardazSmsService $sms)
    {
        $data = $request->validate([
            'test_phone' => ['required', 'string', 'max:20'],
        ]);

        $phone = preg_replace('/\D+/', '', strtr($data['test_phone'], [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ])) ?? '';

        if (str_starts_with($phone, '98') && strlen($phone) >= 12) {
            $phone = '0'.substr($phone, 2);
        }
        if (strlen($phone) === 10 && str_starts_with($phone, '9')) {
            $phone = '0'.$phone;
        }

        $result = $sms->sendTest($phone);

        if ($result['ok'] ?? false) {
            return back()->with('success', $result['message'].' ('.$phone.')');
        }

        return back()->withErrors(['test_phone' => $result['message'] ?? 'ارسال تست ناموفق بود.'])->withInput();
    }

    public function storeLookup(Request $request)
    {
        $data = $request->validate([
            'group_key' => ['required', Rule::in(array_keys(LookupOption::GROUPS))],
            'name' => ['required', 'string', 'max:120'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        LookupOption::create([
            'group_key' => $data['group_key'],
            'name' => $data['name'],
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'منوی پذیرش جدید اضافه شد.');
    }

    public function updateLookup(Request $request, LookupOption $lookup)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $lookup->update([
            'name' => $data['name'],
            'sort_order' => (int) ($data['sort_order'] ?? $lookup->sort_order),
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'منوی پذیرش ویرایش شد.');
    }

    public function destroyLookup(LookupOption $lookup)
    {
        $lookup->delete();

        return back()->with('success', 'مورد منو حذف شد.');
    }

    public function updateInvoice(Request $request)
    {
        $data = $request->validate([
            'invoice_shop_name' => ['nullable', 'string', 'max:160'],
            'invoice_phones' => ['nullable', 'string', 'max:160'],
            'invoice_address' => ['nullable', 'string', 'max:500'],
            'invoice_footer' => ['nullable', 'string', 'max:500'],
            'invoice_terms' => ['nullable', 'string', 'max:10000'],
            'invoice_auto_print' => ['nullable', 'boolean'],
            'invoice_show_logo' => ['nullable', 'boolean'],
            'invoice_font_size' => ['nullable', 'integer', 'min:8', 'max:18'],
            'invoice_page_size' => ['nullable', 'in:A4,A5,Letter'],
            'invoice_margin_mm' => ['nullable', 'integer', 'min:4', 'max:25'],
            'invoice_show_deposit' => ['nullable', 'boolean'],
            'invoice_show_estimated_cost' => ['nullable', 'boolean'],
            'invoice_show_accessories' => ['nullable', 'boolean'],
            'invoice_show_appearance' => ['nullable', 'boolean'],
            'invoice_show_technician' => ['nullable', 'boolean'],
            'invoice_show_warranty' => ['nullable', 'boolean'],
            'invoice_show_parts' => ['nullable', 'boolean'],
            'invoice_show_payments' => ['nullable', 'boolean'],
            'invoice_show_fault' => ['nullable', 'boolean'],
            'invoice_show_serial' => ['nullable', 'boolean'],
        ]);

        $textKeys = [
            'invoice_shop_name',
            'invoice_phones',
            'invoice_address',
            'invoice_footer',
            'invoice_terms',
        ];
        foreach ($textKeys as $key) {
            AppSetting::setValue($key, (string) ($data[$key] ?? ''));
        }

        AppSetting::setValue('invoice_font_size', (string) ((int) ($data['invoice_font_size'] ?? 11)));
        AppSetting::setValue('invoice_page_size', (string) ($data['invoice_page_size'] ?? 'A4'));
        AppSetting::setValue('invoice_margin_mm', (string) ((int) ($data['invoice_margin_mm'] ?? 10)));

        $boolKeys = [
            'invoice_auto_print',
            'invoice_show_logo',
            'invoice_show_deposit',
            'invoice_show_estimated_cost',
            'invoice_show_accessories',
            'invoice_show_appearance',
            'invoice_show_technician',
            'invoice_show_warranty',
            'invoice_show_parts',
            'invoice_show_payments',
            'invoice_show_fault',
            'invoice_show_serial',
        ];
        foreach ($boolKeys as $key) {
            AppSetting::setValue($key, $request->boolean($key) ? '1' : '0');
        }

        return back()->with('success', 'تنظیمات فاکتور و چاپ ذخیره شد.');
    }

    public function updatePayments(Request $request)
    {
        $rules = [
            'pay_links_show_reception' => ['nullable', 'boolean'],
            'pay_links_show_invoice' => ['nullable', 'boolean'],
            'portal_otp_debug' => ['nullable', 'boolean'],
            'zarinpal_merchant_id' => ['nullable', 'string', 'max:64'],
            'zarinpal_sandbox' => ['nullable', 'boolean'],
            'zarinpal_currency' => ['nullable', 'in:IRT,IRR'],
        ];
        foreach (PaymentGateways::definitions() as $def) {
            $rules[PaymentGateways::settingKey($def['key'])] = ['nullable', 'string', 'max:500'];
        }

        $data = $request->validate($rules);

        foreach (PaymentGateways::definitions() as $def) {
            $key = PaymentGateways::settingKey($def['key']);
            $url = trim((string) ($data[$key] ?? ''));
            AppSetting::setValue($key, $url);
        }

        AppSetting::setValue('pay_links_show_reception', $request->boolean('pay_links_show_reception') ? '1' : '0');
        AppSetting::setValue('pay_links_show_invoice', $request->boolean('pay_links_show_invoice') ? '1' : '0');
        AppSetting::setValue('portal_otp_debug', $request->boolean('portal_otp_debug') ? '1' : '0');
        AppSetting::setValue('zarinpal_merchant_id', trim((string) ($data['zarinpal_merchant_id'] ?? '')));
        AppSetting::setValue('zarinpal_sandbox', $request->boolean('zarinpal_sandbox') ? '1' : '0');
        AppSetting::setValue('zarinpal_currency', (string) ($data['zarinpal_currency'] ?? 'IRT'));

        return back()->with('success', 'تنظیمات پرداخت ذخیره شد.');
    }
}
