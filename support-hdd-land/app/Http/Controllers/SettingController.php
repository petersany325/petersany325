<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\FaultType;
use App\Models\LookupOption;
use App\Models\ReferralSource;
use App\Models\User;
use App\Services\NiazpardazSmsService;
use App\Support\BackupSettings;
use App\Support\BankTransferSettings;
use App\Support\CalendarSettings;
use App\Support\PaymentGateways;
use App\Support\Permissions;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingController extends Controller
{
    private const TABS = ['general', 'lookups', 'faults', 'referrals', 'invoice', 'payments', 'sms', 'backup', 'users'];

    public function index(Request $request)
    {
        $lookups = [];
        foreach (LookupOption::GROUPS as $key => $label) {
            $lookups[$key] = [
                'label' => $label,
                'items' => LookupOption::query()->where('group_key', $key)->orderBy('sort_order')->orderBy('name')->get(),
            ];
        }

        $tab = (string) $request->query('tab', session('settings_tab', 'general'));
        if (! in_array($tab, self::TABS, true)) {
            $tab = 'general';
        }

        return view('settings.index', [
            'activeTab' => $tab,
            'calendar' => CalendarSettings::all(),
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
                'shop_name' => AppSetting::getValue('invoice_shop_name', (string) config('app.name', 'تعمیرگاه')),
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
            'bankTransfer' => BankTransferSettings::all(),
            'portalUrl' => url('/cartable'),
            'permissions' => Permissions::ALL,
            'backup' => BackupSettings::all(),
            'backupScopes' => BackupSettings::SCOPES,
            'backupIntervals' => BackupSettings::INTERVALS,
            'backupWeekdays' => BackupSettings::WEEKDAYS,
            'backupProtocols' => BackupSettings::PROTOCOLS,
            'backupCloudLabels' => BackupSettings::CLOUD_LABELS,
            'backupCronUrl' => url('/cron/backup?token='.BackupSettings::all()['cron_token']),
            'googleRedirectUri' => url('/settings/backup/cloud/google/callback'),
            'onedriveRedirectUri' => url('/settings/backup/cloud/onedrive/callback'),
        ]);
    }

    private function resolveTab(Request $request, string $fallback = 'lookups'): string
    {
        $tab = (string) $request->input('settings_tab', $fallback);
        if (! in_array($tab, self::TABS, true)) {
            $tab = $fallback;
        }

        return in_array($tab, self::TABS, true) ? $tab : 'lookups';
    }

    private function settingsRedirect(Request $request, string $fallbackTab, string $flash, string $message)
    {
        $tab = $this->resolveTab($request, $fallbackTab);

        return redirect()
            ->route('settings.index', ['tab' => $tab])
            ->withFragment($tab)
            ->with($flash, $message)
            ->with('settings_tab', $tab);
    }

    public function updateGeneral(Request $request)
    {
        $data = $request->validate([
            'calendar_type' => ['required', Rule::in(['jalali', 'gregorian'])],
            'calendar_digits' => ['required', Rule::in(['fa', 'en'])],
        ]);

        CalendarSettings::save(
            (string) $data['calendar_type'],
            (string) $data['calendar_digits']
        );

        return $this->settingsRedirect($request, 'general', 'success', 'تنظیمات تقویم ذخیره شد.');
    }

    public function storeFaultType(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        FaultType::create($data);

        return $this->settingsRedirect($request, 'faults', 'success', 'نوع ایراد ثبت شد.');
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

        return $this->settingsRedirect($request, 'faults', 'success', 'نوع ایراد ویرایش شد.');
    }

    public function destroyFaultType(Request $request, FaultType $faultType)
    {
        $inUse = \App\Models\Reception::query()->where('fault_type_id', $faultType->id)->exists();
        if ($inUse) {
            $faultType->update(['is_active' => false]);

            return $this->settingsRedirect($request, 'faults', 'success', 'این نوع ایراد در قبض‌ها استفاده شده؛ به‌جای حذف، غیرفعال شد.');
        }

        $faultType->delete();

        return $this->settingsRedirect($request, 'faults', 'success', 'نوع ایراد حذف شد.');
    }

    public function storeReferralSource(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
        ]);

        ReferralSource::create($data);

        return $this->settingsRedirect($request, 'referrals', 'success', 'نحوه آشنایی ثبت شد.');
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

        return $this->settingsRedirect($request, 'referrals', 'success', 'نحوه آشنایی ویرایش شد.');
    }

    public function destroyReferralSource(Request $request, ReferralSource $referralSource)
    {
        $inUse = \App\Models\Customer::query()->where('referral_source_id', $referralSource->id)->exists();
        if ($inUse) {
            $referralSource->update(['is_active' => false]);

            return $this->settingsRedirect($request, 'referrals', 'success', 'این نحوه آشنایی برای مشتری ثبت شده؛ به‌جای حذف، غیرفعال شد.');
        }

        $referralSource->delete();

        return $this->settingsRedirect($request, 'referrals', 'success', 'نحوه آشنایی حذف شد.');
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

        return $this->settingsRedirect($request, 'sms', 'success', 'تنظیمات پنل نیازپرداز ذخیره شد.');
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
            return $this->settingsRedirect($request, 'sms', 'success', $result['message'].' ('.$phone.')');
        }

        return $this->settingsRedirect($request, 'sms', 'error', $result['message'] ?? 'ارسال تست ناموفق بود.')
            ->withErrors(['test_phone' => $result['message'] ?? 'ارسال تست ناموفق بود.'])
            ->withInput();
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

        return $this->settingsRedirect($request, 'lookups', 'success', 'منوی پذیرش جدید اضافه شد.');
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

        return $this->settingsRedirect($request, 'lookups', 'success', 'منوی پذیرش ویرایش شد.');
    }

    public function destroyLookup(Request $request, LookupOption $lookup)
    {
        $lookup->delete();

        return $this->settingsRedirect($request, 'lookups', 'success', 'مورد منو حذف شد.');
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

        return $this->settingsRedirect($request, 'invoice', 'success', 'تنظیمات فاکتور و چاپ ذخیره شد.');
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
            'bank_transfer_enabled' => ['nullable', 'boolean'],
            'bank_card_number' => ['nullable', 'string', 'max:32'],
            'bank_card_holder' => ['nullable', 'string', 'max:120'],
            'bank_name' => ['nullable', 'string', 'max:120'],
            'bank_iban' => ['nullable', 'string', 'max:34'],
            'bank_transfer_instructions' => ['nullable', 'string', 'max:1000'],
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

        $cardDigits = preg_replace('/\D+/', '', (string) ($data['bank_card_number'] ?? '')) ?? '';
        AppSetting::setValue('bank_transfer_enabled', $request->boolean('bank_transfer_enabled') ? '1' : '0');
        AppSetting::setValue('bank_card_number', $cardDigits);
        AppSetting::setValue('bank_card_holder', trim((string) ($data['bank_card_holder'] ?? '')));
        AppSetting::setValue('bank_name', trim((string) ($data['bank_name'] ?? '')));
        AppSetting::setValue('bank_iban', trim((string) ($data['bank_iban'] ?? '')));
        AppSetting::setValue('bank_transfer_instructions', trim((string) ($data['bank_transfer_instructions'] ?? '')));

        return $this->settingsRedirect($request, 'payments', 'success', 'تنظیمات پرداخت ذخیره شد.');
    }

    public function runBackupNow(\App\Services\DatabaseBackupService $backups)
    {
        @set_time_limit(0);
        $result = $backups->runFullNow();

        return $this->settingsRedirect(
            request(),
            'backup',
            ($result['ok'] ?? false) ? 'success' : 'error',
            $result['message'] ?? 'بکاپ انجام نشد.'
        );
    }

    public function updateBackup(Request $request)
    {
        $data = $request->validate([
            'enabled' => ['nullable'],
            'scope' => ['nullable', 'in:full,accounting'],
            'interval' => ['nullable', 'in:daily,weekly,monthly'],
            'weekday' => ['nullable', 'integer', 'min:0', 'max:6'],
            'hour' => ['nullable', 'integer', 'min:0', 'max:23'],
            'keep_local' => ['nullable', 'integer', 'min:1', 'max:60'],
            'remote_enabled' => ['nullable'],
            'remote_protocol' => ['nullable', 'in:ftp,ftps'],
            'remote_host' => ['nullable', 'string', 'max:200'],
            'remote_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'remote_user' => ['nullable', 'string', 'max:120'],
            'remote_password' => ['nullable', 'string', 'max:200'],
            'remote_path' => ['nullable', 'string', 'max:255'],
            'clouds' => ['nullable', 'array'],
            'clouds.google.enabled' => ['nullable'],
            'clouds.google.client_id' => ['nullable', 'string', 'max:300'],
            'clouds.google.client_secret' => ['nullable', 'string', 'max:300'],
            'clouds.google.folder' => ['nullable', 'string', 'max:160'],
            'clouds.onedrive.enabled' => ['nullable'],
            'clouds.onedrive.client_id' => ['nullable', 'string', 'max:300'],
            'clouds.onedrive.client_secret' => ['nullable', 'string', 'max:300'],
            'clouds.onedrive.folder' => ['nullable', 'string', 'max:160'],
            'clouds.mega.enabled' => ['nullable'],
            'clouds.mega.email' => ['nullable', 'string', 'max:190'],
            'clouds.mega.password' => ['nullable', 'string', 'max:200'],
            'clouds.mega.folder' => ['nullable', 'string', 'max:160'],
        ]);

        BackupSettings::save([
            'enabled' => $request->boolean('enabled'),
            'scope' => $data['scope'] ?? 'full',
            'interval' => $data['interval'] ?? 'daily',
            'weekday' => $data['weekday'] ?? 5,
            'hour' => $data['hour'] ?? 3,
            'keep_local' => $data['keep_local'] ?? 14,
            'remote_enabled' => $request->boolean('remote_enabled'),
            'remote_protocol' => $data['remote_protocol'] ?? 'ftp',
            'remote_host' => $data['remote_host'] ?? '',
            'remote_port' => $data['remote_port'] ?? 21,
            'remote_user' => $data['remote_user'] ?? '',
            'remote_password' => $data['remote_password'] ?? null,
            'remote_path' => $data['remote_path'] ?? '/backups',
            'clouds' => [
                'google' => [
                    'enabled' => $request->boolean('clouds.google.enabled'),
                    'client_id' => $data['clouds']['google']['client_id'] ?? '',
                    'client_secret' => $data['clouds']['google']['client_secret'] ?? null,
                    'folder' => $data['clouds']['google']['folder'] ?? 'HDDLAND-Backups',
                ],
                'onedrive' => [
                    'enabled' => $request->boolean('clouds.onedrive.enabled'),
                    'client_id' => $data['clouds']['onedrive']['client_id'] ?? '',
                    'client_secret' => $data['clouds']['onedrive']['client_secret'] ?? null,
                    'folder' => $data['clouds']['onedrive']['folder'] ?? 'HDDLAND-Backups',
                ],
                'mega' => [
                    'enabled' => $request->boolean('clouds.mega.enabled'),
                    'email' => $data['clouds']['mega']['email'] ?? '',
                    'password' => $data['clouds']['mega']['password'] ?? null,
                    'folder' => $data['clouds']['mega']['folder'] ?? 'HDDLAND-Backups',
                ],
            ],
        ]);

        return $this->settingsRedirect($request, 'backup', 'success', 'تنظیمات بکاپ خودکار ذخیره شد.');
    }
}
