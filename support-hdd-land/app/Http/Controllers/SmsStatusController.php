<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Reception;
use App\Models\SmsLog;
use App\Models\SmsStatusRule;
use App\Services\NiazpardazSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SmsStatusController extends Controller
{
    public function index()
    {
        return view('sms-statuses.index', [
            'rules' => SmsStatusRule::query()->orderBy('sort_order')->orderBy('id')->get(),
            'logs' => SmsLog::query()->with(['customer', 'reception', 'rule'])->latest()->limit(40)->get(),
            'masterEnabled' => AppSetting::getValue('sms_master_enabled', '1') !== '0',
            'placeholders' => SmsStatusRule::PLACEHOLDERS,
            'colors' => SmsStatusRule::COLORS,
            'stageTypes' => SmsStatusRule::STAGE_TYPES,
            'resultTypes' => SmsStatusRule::RESULT_TYPES,
            'sendModes' => SmsStatusRule::SEND_MODES,
            'baseStatuses' => Reception::STATUSES,
            'sms' => [
                'username' => AppSetting::getValue('niazpardaz_username', env('NIAZPARDAZ_USERNAME')),
                'from' => AppSetting::getValue('niazpardaz_from', env('NIAZPARDAZ_FROM_NUMBER')),
                'api_key' => AppSetting::getValue('niazpardaz_api_key', env('NIAZPARDAZ_API_KEY')),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $statusKey = $this->uniqueStatusKey($data['status_key'] ?: Str::slug($data['title'], '_'));

        $created = SmsStatusRule::create($this->payload($request, $data, $statusKey, true));

        if ($created->on_create) {
            SmsStatusRule::query()->where('id', '!=', $created->id)->update(['on_create' => false]);
        }

        SmsStatusRule::clearStatusCache();

        return back()->with('success', 'وضعیت جدید افزوده شد.');
    }

    public function update(Request $request, SmsStatusRule $smsStatus)
    {
        $data = $this->validated($request);
        $statusKey = $data['status_key'] ?: $smsStatus->status_key;

        if ($statusKey !== $smsStatus->status_key
            && SmsStatusRule::where('status_key', $statusKey)->where('id', '!=', $smsStatus->id)->exists()) {
            return back()->withErrors(['status_key' => 'این کلید وضعیت قبلاً استفاده شده است.'])->withInput();
        }

        $smsStatus->update($this->payload($request, $data, $statusKey, false, $smsStatus));

        if ($smsStatus->on_create) {
            SmsStatusRule::query()->where('id', '!=', $smsStatus->id)->update(['on_create' => false]);
        }

        SmsStatusRule::clearStatusCache();

        return back()->with('success', 'وضعیت ویرایش شد.');
    }

    public function destroy(SmsStatusRule $smsStatus)
    {
        $smsStatus->delete();
        SmsStatusRule::clearStatusCache();

        return back()->with('success', 'وضعیت حذف شد.');
    }

    public function hide(SmsStatusRule $smsStatus)
    {
        $smsStatus->update(['is_hidden' => ! $smsStatus->is_hidden]);
        SmsStatusRule::clearStatusCache();

        return back()->with('success', $smsStatus->is_hidden ? 'وضعیت مخفی شد.' : 'وضعیت از حالت مخفی خارج شد.');
    }

    public function updateMaster(Request $request)
    {
        AppSetting::setValue('sms_master_enabled', $request->boolean('sms_master_enabled') ? '1' : '0');

        return back()->with('success', $request->boolean('sms_master_enabled')
            ? 'ارسال پیامک وضعیت در کل سیستم روشن شد.'
            : 'ارسال پیامک وضعیت در کل سیستم خاموش شد.');
    }

    public function updateGateway(Request $request)
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

        return back()->with('success', 'تنظیمات درگاه پیامک ذخیره شد.');
    }

    public function test(Request $request, NiazpardazSmsService $sms)
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

    private function uniqueStatusKey(string $statusKey): string
    {
        if ($statusKey === '') {
            $statusKey = 'status_'.now()->format('His');
        }
        $base = $statusKey;
        $i = 1;
        while (SmsStatusRule::where('status_key', $statusKey)->exists()) {
            $statusKey = $base.'_'.$i;
            $i++;
        }

        return $statusKey;
    }

    private function payload(Request $request, array $data, string $statusKey, bool $creating, ?SmsStatusRule $existing = null): array
    {
        $payload = [
            'title' => $data['title'],
            'summary' => $data['summary'] ?? null,
            'status_key' => $statusKey,
            'stage_type' => $data['stage_type'] ?? 'run',
            'result_type' => $data['result_type'] ?? 'active',
            'color' => $data['color'] ?? 'blue',
            'description' => $data['description'] ?? null,
            'message_template' => $data['message_template'] ?? '',
            'send_mode' => $data['send_mode'],
            // Keep auto_send for older UI/logic: always=true, never/ask=false.
            'auto_send' => $data['send_mode'] === SmsStatusRule::SEND_ALWAYS,
            'coworker_message_template' => $data['coworker_message_template'] ?? null,
            'send_coworker' => $request->boolean('send_coworker'),
            'is_active' => $request->boolean('is_active', true),
            'is_hidden' => $request->boolean('is_hidden'),
            'on_create' => $request->boolean('on_create'),
            'on_price' => $request->boolean('on_price'),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];

        if ($creating) {
            $payload['code'] = SmsStatusRule::makeCode($data['title'], $statusKey);
        }

        return $payload;
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'summary' => ['nullable', 'string', 'max:80'],
            'status_key' => ['nullable', 'string', 'max:60', 'regex:/^[a-z0-9_\-]+$/i'],
            'stage_type' => ['required', Rule::in(array_keys(SmsStatusRule::STAGE_TYPES))],
            'result_type' => ['required', Rule::in(array_keys(SmsStatusRule::RESULT_TYPES))],
            'color' => ['nullable', Rule::in(array_keys(SmsStatusRule::COLORS))],
            'description' => ['nullable', 'string', 'max:500'],
            'message_template' => ['nullable', 'string', 'max:1000'],
            'coworker_message_template' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'send_mode' => ['nullable', Rule::in(array_keys(SmsStatusRule::SEND_MODES))],
            'auto_send' => ['nullable', 'boolean'],
            'send_coworker' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'is_hidden' => ['nullable', 'boolean'],
            'on_create' => ['nullable', 'boolean'],
            'on_price' => ['nullable', 'boolean'],
        ]);

        $data['send_mode'] = SmsStatusRule::normalizeSendMode(
            $data['send_mode'] ?? $request->input('auto_send'),
            $request->has('auto_send') ? $request->boolean('auto_send') : true
        );

        return $data;
    }
}
