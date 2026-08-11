<?php

namespace Plugins\AuthCustomers;

use App\Models\Setting;
use App\Support\BasePlugin;
use Illuminate\Support\Facades\Schema;

class Plugin extends BasePlugin
{
    public const SETTINGS_KEY = 'auth_customers_settings';

    public function id(): string
    {
        return 'auth-customers';
    }

    public function name(): string
    {
        return 'ثبت‌نام و پنل مشتریان حرفه‌ای';
    }

    public function description(): string
    {
        return 'ثبت‌نام کامل، ورود دو مرحله‌ای، Authenticator، تأیید موبایل/SMS، پنل سفارش و پیش‌خرید';
    }

    public function version(): string
    {
        return '2.5.0';
    }

    public function isCore(): bool
    {
        return true;
    }

    public function boot(): void
    {
        static::ensureSchema();
        \Plugins\AuthCustomers\src\Support\WalletSupport::ensureSchema();
        \Plugins\AuthCustomers\src\Support\TicketSupport::ensureSchema();
        parent::boot();
    }

    public static function ensureSchema(): void
    {
        try {
            if (Schema::hasTable('users')) {
                foreach ([
                    'username' => fn ($t) => $t->string('username', 50)->nullable()->unique(),
                    'phone_verified_at' => fn ($t) => $t->timestamp('phone_verified_at')->nullable(),
                    'national_id' => fn ($t) => $t->string('national_id', 20)->nullable(),
                    'address' => fn ($t) => $t->string('address', 500)->nullable(),
                    'city' => fn ($t) => $t->string('city', 120)->nullable(),
                    'province' => fn ($t) => $t->string('province', 80)->nullable(),
                    'postal_code' => fn ($t) => $t->string('postal_code', 20)->nullable(),
                    'two_factor_enabled' => fn ($t) => $t->boolean('two_factor_enabled')->default(false),
                    'two_factor_secret' => fn ($t) => $t->string('two_factor_secret', 64)->nullable(),
                    'two_factor_method' => fn ($t) => $t->string('two_factor_method', 30)->default('none'),
                    'birth_date' => fn ($t) => $t->date('birth_date')->nullable(),
                    'terms_accepted_at' => fn ($t) => $t->timestamp('terms_accepted_at')->nullable(),
                    'last_name' => fn ($t) => $t->string('last_name', 120)->nullable(),
                    'bank_name' => fn ($t) => $t->string('bank_name', 80)->nullable(),
                    'bank_card' => fn ($t) => $t->string('bank_card', 24)->nullable(),
                    'bank_iban' => fn ($t) => $t->string('bank_iban', 34)->nullable(),
                    'bank_account_holder' => fn ($t) => $t->string('bank_account_holder', 160)->nullable(),
                ] as $col => $cb) {
                    if (! Schema::hasColumn('users', $col)) {
                        Schema::table('users', function ($table) use ($cb) {
                            $cb($table);
                        });
                    }
                }
            }

            if (! Schema::hasTable('auth_otps')) {
                Schema::create('auth_otps', function ($table) {
                    $table->id();
                    $table->unsignedBigInteger('user_id')->nullable()->index();
                    $table->string('channel', 20)->default('sms');
                    $table->string('destination', 120)->index();
                    $table->string('purpose', 40)->index();
                    $table->string('code', 255);
                    $table->unsignedTinyInteger('attempts')->default(0);
                    $table->timestamp('expires_at');
                    $table->timestamp('consumed_at')->nullable();
                    $table->timestamps();
                });
            } else {
                try {
                    if (! Schema::hasColumn('auth_otps', 'attempts')) {
                        \Illuminate\Support\Facades\DB::statement('ALTER TABLE auth_otps ADD attempts TINYINT UNSIGNED NOT NULL DEFAULT 0');
                    }
                    \Illuminate\Support\Facades\DB::statement('ALTER TABLE auth_otps MODIFY code VARCHAR(255) NOT NULL');
                } catch (\Throwable) {
                    //
                }
            }

            if (! Schema::hasTable('user_preorders')) {
                Schema::create('user_preorders', function ($table) {
                    $table->id();
                    $table->unsignedBigInteger('user_id')->index();
                    $table->unsignedBigInteger('product_id')->nullable()->index();
                    $table->string('product_name')->nullable();
                    $table->string('product_url')->nullable();
                    $table->unsignedInteger('qty')->default(1);
                    $table->text('note')->nullable();
                    $table->string('status', 30)->default('pending');
                    $table->timestamps();
                });
            }
        } catch (\Throwable) {
            //
        }
    }

    /** @return array<string, mixed> */
    public static function defaults(): array
    {
        return [
            // Registration
            'enable_registration' => true,
            'auto_login_after_register' => true,
            'unique_phone' => true,
            'verify_phone_on_register' => true,
            'sms_auto_send_on_register' => true,
            'verify_email_on_register' => false,
            'blocked_email_domains' => 'mailinator.com,tempmail.com,10minutemail.com',
            'password_min_length' => 8,
            'password_require_mixed' => false,
            'password_require_number' => true,
            'password_require_symbol' => false,

            // Field visibility / required (form matrix)
            'show_name' => true,
            'require_name' => true,
            'show_last_name' => true,
            'require_last_name' => false,
            'show_email' => true,
            'require_email' => true,
            'show_phone' => true,
            'require_phone' => true,
            'show_national_id' => false,
            'require_national_id' => false,
            'show_city' => true,
            'require_city' => false,
            'show_province' => true,
            'require_province' => true,
            'show_postal_code' => true,
            'require_postal_code' => false,
            'show_address' => true,
            'require_address' => false,
            'show_birth_date' => false,
            'require_birth_date' => false,

            // مشخصات بانکی برای عودت وجه
            'show_bank_fields' => true,
            'require_bank_fields' => false,
            'show_bank_name' => true,
            'require_bank_name' => false,
            'show_bank_card' => true,
            'require_bank_card' => false,
            'show_bank_iban' => true,
            'require_bank_iban' => false,
            'show_bank_account_holder' => true,
            'require_bank_account_holder' => false,
            'bank_refund_note' => 'در صورت عودت وجه، مبلغ به شماره شبا / کارت اعلام‌شده واریز می‌شود.',
            'bank_refund_enabled' => true,
            'require_terms' => true,

            // Legal pages (editable in admin)
            'terms_url' => '/terms',
            'privacy_url' => '/privacy',
            'terms_title' => 'قوانین و مقررات',
            'terms_body' => "با ثبت‌نام در فروشگاه، شرایط استفاده و خرید را می‌پذیرید.\n\n۱. اطلاعات کاربری باید صحیح باشد.\n۲. مسئولیت حفظ رمز عبور با کاربر است.\n۳. قیمت‌ها و موجودی ممکن است تغییر کند.",
            'privacy_title' => 'حریم خصوصی',
            'privacy_body' => "اطلاعات شما فقط برای پردازش سفارش و پشتیبانی استفاده می‌شود و بدون مجوز در اختیار شخص ثالث قرار نمی‌گیرد.",

            // 2FA
            'enable_email_otp' => true,
            'enable_sms_otp' => true,
            'enable_authenticator' => true,
            'force_2fa' => false,
            'default_2fa_method' => 'email',
            'shop_name_2fa' => 'HDD Land',
            'totp_window' => 1,
            'totp_digits' => 6,
            'totp_period' => 30,
            'otp_ttl' => 5,
            'otp_length' => 6,
            'otp_max_attempts' => 5,
            'otp_resend_cooldown' => 60,
            'otp_rate_limit_per_hour' => 10,
            'remember_device_days' => 0,
            'lockout_minutes' => 15,

            // SMS gateway
            'sms_driver' => 'log',
            'sms_api_key' => '',
            'sms_api_secret' => '',
            'sms_username' => '',
            'sms_password' => '',
            'sms_sender' => '',
            'sms_url' => '',
            'sms_pattern' => 'کد تأیید شما: {code} — اعتبار {ttl} دقیقه',
            'sms_pattern_verify_phone' => 'کد تأیید موبایل: {code}',
            'sms_pattern_password_reset' => 'کد بازیابی رمز: {code}',
            'sms_pattern_login_2fa' => 'کد ورود: {code}',
            'sms_pattern_login_sms' => 'کد ورود: {code}',
            'email_otp_subject' => 'کد تأیید ورود',
            'email_otp_pattern' => "کد تأیید شما: {code}\nاعتبار: {ttl} دقیقه",
            'sms_timeout' => 15,
            'sms_phone_format' => 'local',
            'sms_country_code' => '98',

            // SMS notifications
            'sms_on_password_reset' => true,
            'sms_on_order_placed' => true,
            'sms_on_order_paid' => true,
            'sms_order_placed_pattern' => 'سفارش {order} ثبت شد. مبلغ {total} تومان — {shop}',
            'sms_order_paid_pattern' => 'پرداخت سفارش {order} تأیید شد. مبلغ {total} تومان — {shop}',
        ];
    }

    public static function fieldShown(string $key): bool
    {
        $s = static::settings();

        return ! empty($s['show_'.$key]);
    }

    public static function fieldRequired(string $key): bool
    {
        $s = static::settings();

        return static::fieldShown($key) && ! empty($s['require_'.$key]);
    }

    /** @return array<string, mixed> */
    public static function settings(): array
    {
        $raw = Setting::getValue(self::SETTINGS_KEY, null);
        $decoded = [];
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true) ?: [];
        } elseif (is_array($raw)) {
            $decoded = $raw;
        }

        return array_merge(static::defaults(), is_array($decoded) ? $decoded : []);
    }

    /** @param  array<string, mixed>  $data */
    public static function saveSettings(array $data): void
    {
        $cur = static::settings();
        $tab = (string) ($data['tab'] ?? 'all');

        $driver = (string) ($data['sms_driver'] ?? $cur['sms_driver']);
        if (! in_array($driver, ['log', 'kavenegar', 'melipayamak', 'niazpardaz', 'farazsms', 'custom'], true)) {
            $driver = 'log';
        }
        $default2fa = (string) ($data['default_2fa_method'] ?? $cur['default_2fa_method']);
        if (! in_array($default2fa, ['sms', 'email', 'authenticator'], true)) {
            $default2fa = 'email';
        }
        $phoneFormat = (string) ($data['sms_phone_format'] ?? $cur['sms_phone_format']);
        if (! in_array($phoneFormat, ['local', 'e164'], true)) {
            $phoneFormat = 'local';
        }

        $patch = [];
        $bool = static function (array $keys) use ($data): array {
            $out = [];
            foreach ($keys as $k) {
                $out[$k] = ! empty($data[$k]);
            }

            return $out;
        };

        if ($tab === 'register' || $tab === 'all') {
            $patch = array_merge($patch, $bool([
                'enable_registration', 'auto_login_after_register', 'unique_phone',
                'verify_phone_on_register', 'sms_auto_send_on_register', 'verify_email_on_register',
                'password_require_mixed', 'password_require_number', 'password_require_symbol',
            ]), [
                'blocked_email_domains' => mb_substr((string) ($data['blocked_email_domains'] ?? ''), 0, 1000),
                'password_min_length' => max(8, min(32, (int) ($data['password_min_length'] ?? 8))),
            ]);
        }

        if ($tab === 'fields' || $tab === 'all') {
            $fieldKeys = [
                'show_name', 'require_name', 'show_last_name', 'require_last_name',
                'show_email', 'require_email', 'show_phone', 'require_phone',
                'show_national_id', 'require_national_id', 'show_city', 'require_city',
                'show_province', 'require_province',
                'show_postal_code', 'require_postal_code', 'show_address', 'require_address',
                'show_birth_date', 'require_birth_date', 'require_terms',
            ];
            $patch = array_merge($patch, $bool($fieldKeys));
            // سازگاری با کلیدهای قدیمی
            $patch['require_phone'] = ! empty($data['require_phone']);
            $patch['require_national_id'] = ! empty($data['require_national_id']);
            $patch['require_city'] = ! empty($data['require_city']);
            $patch['require_province'] = ! empty($data['require_province']);
            $patch['require_address'] = ! empty($data['require_address']);
        }

        if ($tab === 'refund' || $tab === 'all') {
            $patch = array_merge($patch, $bool([
                'bank_refund_enabled', 'show_bank_fields', 'require_bank_fields',
                'show_bank_name', 'require_bank_name',
                'show_bank_card', 'require_bank_card',
                'show_bank_iban', 'require_bank_iban',
                'show_bank_account_holder', 'require_bank_account_holder',
            ]), [
                'bank_refund_note' => mb_substr(trim((string) ($data['bank_refund_note'] ?? '')), 0, 500),
            ]);
        }

        if ($tab === 'terms' || $tab === 'all') {
            $patch = array_merge($patch, [
                'terms_url' => mb_substr((string) ($data['terms_url'] ?? '/terms'), 0, 255),
                'privacy_url' => mb_substr((string) ($data['privacy_url'] ?? '/privacy'), 0, 255),
                'terms_title' => mb_substr((string) ($data['terms_title'] ?? 'قوانین'), 0, 120),
                'privacy_title' => mb_substr((string) ($data['privacy_title'] ?? 'حریم خصوصی'), 0, 120),
                'terms_body' => mb_substr((string) ($data['terms_body'] ?? ''), 0, 20000),
                'privacy_body' => mb_substr((string) ($data['privacy_body'] ?? ''), 0, 20000),
            ]);
        }

        if ($tab === '2fa' || $tab === 'all') {
            $patch = array_merge($patch, $bool([
                'enable_email_otp', 'enable_sms_otp', 'enable_authenticator', 'force_2fa',
            ]), [
                'default_2fa_method' => $default2fa,
                'shop_name_2fa' => mb_substr((string) ($data['shop_name_2fa'] ?? 'HDD Land'), 0, 64),
                'totp_window' => max(0, min(2, (int) ($data['totp_window'] ?? 1))),
                'totp_digits' => 6,
                'totp_period' => 30,
                'otp_ttl' => max(2, min(30, (int) ($data['otp_ttl'] ?? 5))),
                'otp_length' => max(4, min(8, (int) ($data['otp_length'] ?? 6))),
                'otp_max_attempts' => max(3, min(10, (int) ($data['otp_max_attempts'] ?? 5))),
                'otp_resend_cooldown' => max(30, min(300, (int) ($data['otp_resend_cooldown'] ?? 60))),
                'otp_rate_limit_per_hour' => max(3, min(50, (int) ($data['otp_rate_limit_per_hour'] ?? 10))),
                'remember_device_days' => max(0, min(90, (int) ($data['remember_device_days'] ?? 0))),
                'lockout_minutes' => max(5, min(120, (int) ($data['lockout_minutes'] ?? 15))),
            ]);
        }

        if ($tab === 'sms' || $tab === 'all') {
            $patch = array_merge($patch, [
                'sms_driver' => $driver,
                'sms_api_key' => (string) ($data['sms_api_key'] ?? ''),
                'sms_api_secret' => (string) ($data['sms_api_secret'] ?? ''),
                'sms_username' => (string) ($data['sms_username'] ?? ''),
                'sms_password' => (string) ($data['sms_password'] ?? ''),
                'sms_sender' => (string) ($data['sms_sender'] ?? ''),
                'sms_url' => (string) ($data['sms_url'] ?? ''),
                'sms_pattern' => (string) ($data['sms_pattern'] ?? 'کد تأیید شما: {code}'),
                'sms_pattern_verify_phone' => (string) ($data['sms_pattern_verify_phone'] ?? ''),
                'sms_pattern_password_reset' => (string) ($data['sms_pattern_password_reset'] ?? ''),
                'sms_pattern_login_2fa' => (string) ($data['sms_pattern_login_2fa'] ?? ''),
                'sms_pattern_login_sms' => (string) ($data['sms_pattern_login_sms'] ?? ''),
                'email_otp_subject' => mb_substr((string) ($data['email_otp_subject'] ?? 'کد تأیید ورود'), 0, 120),
                'email_otp_pattern' => (string) ($data['email_otp_pattern'] ?? 'کد تأیید شما: {code}'),
                'sms_timeout' => max(5, min(60, (int) ($data['sms_timeout'] ?? 15))),
                'sms_phone_format' => $phoneFormat,
                'sms_country_code' => preg_replace('/\D+/', '', (string) ($data['sms_country_code'] ?? '98')) ?: '98',
            ]);
        }

        if ($tab === 'notify' || $tab === 'all') {
            $patch = array_merge($patch, $bool([
                'sms_on_password_reset', 'sms_on_order_placed', 'sms_on_order_paid',
            ]), [
                'sms_order_placed_pattern' => mb_substr((string) ($data['sms_order_placed_pattern'] ?? ''), 0, 500),
                'sms_order_paid_pattern' => mb_substr((string) ($data['sms_order_paid_pattern'] ?? ''), 0, 500),
            ]);
        }

        Setting::setValue(self::SETTINGS_KEY, array_merge($cur, $patch));
    }
}
