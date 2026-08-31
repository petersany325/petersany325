<?php

namespace Plugins\StaffHR;

use App\Support\BasePlugin;
use Plugins\Support\JsonSettings;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class Plugin extends BasePlugin
{
    public const SETTINGS_KEY = 'staff_hr_settings';

    public function id(): string
    {
        return 'staff-hr';
    }

    public function name(): string
    {
        return 'کارمندان';
    }

    public function description(): string
    {
        return 'ثبت‌نام توسط ادمین، نقش و دسترسی، پنل کارمند، سود و کمیسیون';
    }

    public function version(): string
    {
        return '2.0.1';
    }

    public function isCore(): bool
    {
        return true;
    }

    public function boot(): void
    {
        static::loadClasses();
        static::ensureSchema();
        static::ensureCommerceColumns();
        parent::boot();
        // روت‌های /staff و /staff/login از AdminCore ثبت می‌شوند
    }

    /** لود صریح کلاس‌ها — هاست اشتراکی بدون composer dump */
    public static function loadClasses(): void
    {
        // StaffAcl / StaffReports داخل همین Plugin.php هستند؛ فقط کنترلرها را از فایل جدا لود کن
        $base = __DIR__.DIRECTORY_SEPARATOR.'src';
        $files = [
            $base.DIRECTORY_SEPARATOR.'Http'.DIRECTORY_SEPARATOR.'Controllers'.DIRECTORY_SEPARATOR.'Admin'.DIRECTORY_SEPARATOR.'StaffController.php',
            $base.DIRECTORY_SEPARATOR.'Http'.DIRECTORY_SEPARATOR.'Controllers'.DIRECTORY_SEPARATOR.'Staff'.DIRECTORY_SEPARATOR.'PanelController.php',
        ];
        foreach ($files as $f) {
            if (is_file($f)) {
                try {
                    require_once $f;
                } catch (\Throwable) {
                    //
                }
            }
        }
    }

    public static function ensureSchema(): void
    {
        try {
            if (! Schema::hasTable('staff_members')) {
                Schema::create('staff_members', function ($table) {
                    $table->id();
                    $table->unsignedBigInteger('user_id')->nullable()->unique();
                    $table->string('name');
                    $table->string('email', 190)->nullable();
                    $table->string('phone', 30)->nullable();
                    $table->string('role', 40)->default('seller')->index();
                    $table->string('department', 80)->nullable();
                    $table->boolean('is_active')->default(true);
                    $table->date('hired_at')->nullable();
                    $table->unsignedInteger('base_salary')->default(0);
                    $table->decimal('commission_rate', 5, 2)->default(0);
                    $table->json('permissions')->nullable();
                    $table->boolean('can_see_profit')->default(false);
                    $table->timestamp('last_login_at')->nullable();
                    $table->text('notes')->nullable();
                    $table->timestamps();
                });
            } else {
                $cols = [
                    'permissions' => fn ($t) => $t->json('permissions')->nullable(),
                    'can_see_profit' => fn ($t) => $t->boolean('can_see_profit')->default(false),
                    'last_login_at' => fn ($t) => $t->timestamp('last_login_at')->nullable(),
                ];
                foreach ($cols as $col => $cb) {
                    if (! Schema::hasColumn('staff_members', $col)) {
                        Schema::table('staff_members', function ($t) use ($cb) {
                            $cb($t);
                        });
                    }
                }
            }
        } catch (\Throwable) {
            //
        }
    }

    public static function ensureCommerceColumns(): void
    {
        try {
            if (Schema::hasTable('products') && ! Schema::hasColumn('products', 'cost_price')) {
                Schema::table('products', function ($t) {
                    $t->unsignedBigInteger('cost_price')->nullable();
                });
            }
            if (Schema::hasTable('order_items') && ! Schema::hasColumn('order_items', 'unit_cost')) {
                Schema::table('order_items', function ($t) {
                    $t->unsignedBigInteger('unit_cost')->nullable();
                });
            }
            if (Schema::hasTable('orders')) {
                if (! Schema::hasColumn('orders', 'sold_by_user_id')) {
                    Schema::table('orders', function ($t) {
                        $t->unsignedBigInteger('sold_by_user_id')->nullable()->index();
                    });
                }
                if (! Schema::hasColumn('orders', 'commission_rate')) {
                    Schema::table('orders', function ($t) {
                        $t->decimal('commission_rate', 5, 2)->nullable();
                    });
                }
                if (! Schema::hasColumn('orders', 'commission_amount')) {
                    Schema::table('orders', function ($t) {
                        $t->unsignedBigInteger('commission_amount')->default(0);
                    });
                }
            }
        } catch (\Throwable) {
            //
        }
    }

    /** @return array<string,mixed> */
    public static function defaults(): array
    {
        // بدون وابستگی به فایل خارجی — تا اگر Support آپلود نشده باشد سایت بالا بیاید
        $roles = implode("\n", [
            'sales_manager|مدیر فروش',
            'seller|فروشنده',
            'support|پشتیبانی تیکت',
            'accountant|حسابداری',
            'warehouse|انباردار',
            'full_access|دسترسی کامل پنل',
        ]);

        return [
            'enabled' => true,
            'allow_self_register' => false,
            'default_role' => 'seller',
            'roles' => $roles,
            'track_commission' => true,
            'default_commission_rate' => 2.0,
            'require_phone' => true,
            'show_salary' => false,
            'departments' => 'فروش,انبار,پشتیبانی,مالی',
            'login_secret' => '',
            'force_sms_login' => true,
            'referral_enabled' => true,
            'referral_cookie_days' => 14,
            'referral_min_subtotal' => 0,
            'referral_credit_on_paid' => true,
            'referral_block_self' => true,
            'referral_show_checkout' => true,
            'referral_customer_hint' => 'اگر کارمند فروشگاه شما را معرفی کرده، کد معرف را وارد کنید.',
        ];
    }

    /** @return array<string,mixed> */
    public static function settings(): array
    {
        $s = JsonSettings::get(self::SETTINGS_KEY, static::defaults());
        if (empty($s['login_secret'])) {
            $s['login_secret'] = bin2hex(random_bytes(16));
            try {
                static::saveSettings(array_merge($s, ['login_secret' => $s['login_secret']]));
            } catch (\Throwable) {
                //
            }
        }

        return $s;
    }

    public static function loginUrl(): string
    {
        $s = static::settings();
        $secret = (string) ($s['login_secret'] ?? '');

        return $secret !== '' ? url('/staff/enter/'.$secret) : url('/staff/login');
    }

    /** @param  array<string,mixed>  $data */
    public static function saveSettings(array $data): void
    {
        $data['allow_self_register'] = false;
        if (! empty($data['regenerate_login_secret'])) {
            $data['login_secret'] = bin2hex(random_bytes(16));
        }
        JsonSettings::save(self::SETTINGS_KEY, static::defaults(), $data, [
            'enabled', 'track_commission', 'require_phone', 'show_salary', 'force_sms_login',
            'referral_enabled', 'referral_credit_on_paid', 'referral_block_self', 'referral_show_checkout',
        ], [
            'allow_self_register' => fn () => false,
            'default_role' => fn ($v) => mb_substr((string) $v, 0, 40),
            'roles' => fn ($v) => mb_substr((string) $v, 0, 2000),
            'default_commission_rate' => fn ($v) => max(0, min(50, (float) $v)),
            'departments' => fn ($v) => mb_substr((string) $v, 0, 500),
            'login_secret' => fn ($v) => preg_replace('/[^a-zA-Z0-9]/', '', (string) $v) ?: bin2hex(random_bytes(16)),
            'referral_cookie_days' => fn ($v) => max(1, min(90, (int) $v)),
            'referral_min_subtotal' => fn ($v) => max(0, (int) $v),
            'referral_customer_hint' => fn ($v) => mb_substr((string) $v, 0, 500),
        ]);
    }
}

namespace Plugins\StaffHR\src\Support;

if (! class_exists(StaffAcl::class, false)) {
    class StaffAcl
    {
        /** @return array<string,string> */
        public static function permissionLabels(): array
        {
            return [
                'sales' => 'فروش و سفارش‌ها',
                'orders' => 'مشاهده/مدیریت سفارش',
                'serials' => 'سریال و گارانتی',
                'products.view' => 'مشاهده محصولات',
                'products.create' => 'افزودن محصول',
                'products.edit' => 'ویرایش محصول',
                'products.delete' => 'حذف محصول',
                'support' => 'پشتیبانی / تیکت',
                'accounting' => 'حسابداری',
                'reports' => 'گزارش فروش و کار',
                'media' => 'کتابخانه رسانه',
                'system_tools' => 'تعمیر و نگهداری',
                'site.mega_menu' => 'مدیریت مگامenu',
                'site.theme_builder' => 'استودیو قالب و بنرساز',
                'site.theme_templates' => 'نصب و به‌روزرسانی قالب',
                'site.page_builder' => 'صفحه‌ساز Elementor',
                'site.homepage' => 'بنر و تنظیمات صفحه اول',
                'site.footer' => 'تنظیمات فوتر',
                'site.webapp' => 'تنظیمات وب‌اپ / PWA',
                'site.shop_settings' => 'تنظیمات عمومی فروشگاه',
                'full_site' => 'دسترسی گسترده پنل کارمند',
            ];
        }

        /** @return array<string,array{label:string,permissions:list<string>}> */
        public static function rolePresets(): array
        {
            return [
                'sales_manager' => [
                    'label' => 'مدیر فروش',
                    'permissions' => [
                        'sales', 'orders', 'serials', 'products.view', 'products.create', 'products.edit',
                        'reports', 'media',
                    ],
                ],
                'seller' => [
                    'label' => 'فروشنده',
                    'permissions' => ['sales', 'orders', 'serials', 'products.view', 'reports'],
                ],
                'support' => [
                    'label' => 'پشتیبانی تیکت',
                    'permissions' => ['support', 'orders'],
                ],
                'accountant' => [
                    'label' => 'حسابداری',
                    'permissions' => ['accounting', 'orders', 'reports'],
                ],
                'warehouse' => [
                    'label' => 'انباردار',
                    'permissions' => [
                        'products.view', 'products.create', 'products.edit', 'products.delete',
                        'serials', 'media',
                    ],
                ],
                'technician' => [
                    'label' => 'تعمیر و نگهداری',
                    'permissions' => ['system_tools', 'reports'],
                ],
                'site_designer' => [
                    'label' => 'مدیر ظاهر سایت',
                    'permissions' => [
                        'site.mega_menu', 'site.theme_builder', 'site.theme_templates', 'site.page_builder',
                        'site.homepage', 'site.footer', 'site.webapp',
                    ],
                ],
                'full_access' => [
                    'label' => 'دسترسی کامل پنل',
                    'permissions' => array_keys(self::permissionLabels()),
                ],
            ];
        }

        /** @return list<string> */
        public static function permissionsForRole(string $role): array
        {
            return self::rolePresets()[$role]['permissions'] ?? ['reports'];
        }

        /** @param  mixed  $raw @return list<string> */
        public static function normalizePermissions($raw): array
        {
            if (is_string($raw)) {
                $decoded = json_decode($raw, true);
                $raw = is_array($decoded) ? $decoded : [];
            }
            if (! is_array($raw)) {
                return [];
            }
            $allowed = array_keys(self::permissionLabels());
            $out = [];
            foreach ($raw as $p) {
                $p = (string) $p;
                if (in_array($p, $allowed, true)) {
                    $out[] = $p;
                }
            }

            return array_values(array_unique($out));
        }

        public static function hasPermission(?object $staff, string $permission): bool
        {
            if (! $staff || empty($staff->is_active)) {
                return false;
            }
            $perms = self::normalizePermissions($staff->permissions ?? []);
            if (in_array('full_site', $perms, true) || in_array($permission, $perms, true)) {
                return true;
            }

            return false;
        }
    }
}

if (! class_exists(StaffReports::class, false)) {
    class StaffReports
    {
        /** @return array{gross:int,cost:int,profit:int,margin:float,orders:int,commission:int,subtotal:int} */
        public static function summarize(?int $userId = null, $from = null, $to = null): array
        {
            $empty = [
                'gross' => 0, 'cost' => 0, 'profit' => 0, 'margin' => 0.0,
                'orders' => 0, 'commission' => 0, 'subtotal' => 0,
            ];
            try {
                if (! \Illuminate\Support\Facades\Schema::hasTable('orders')) {
                    return $empty;
                }
                $q = \Illuminate\Support\Facades\DB::table('orders')->where('status', '!=', 'cancelled');
                if ($from) {
                    $q->where('created_at', '>=', $from);
                }
                if ($to) {
                    $q->where('created_at', '<=', $to);
                }
                if ($userId && \Illuminate\Support\Facades\Schema::hasColumn('orders', 'sold_by_user_id')) {
                    $q->where('sold_by_user_id', $userId);
                }
                $orders = $q->get();
                if ($orders->isEmpty()) {
                    return $empty;
                }
                $orderIds = $orders->pluck('id')->all();
                $gross = (int) $orders->sum('total');
                $subtotal = (int) $orders->sum('subtotal');
                $commission = \Illuminate\Support\Facades\Schema::hasColumn('orders', 'commission_amount')
                    ? (int) $orders->sum('commission_amount') : 0;
                $cost = 0;
                if (\Illuminate\Support\Facades\Schema::hasTable('order_items')
                    && \Illuminate\Support\Facades\Schema::hasColumn('order_items', 'unit_cost')) {
                    $cost = (int) \Illuminate\Support\Facades\DB::table('order_items')
                        ->whereIn('order_id', $orderIds)
                        ->selectRaw('COALESCE(SUM(COALESCE(unit_cost,0) * quantity),0) as c')
                        ->value('c');
                }
                $profit = $gross - $cost;

                return [
                    'gross' => $gross,
                    'cost' => $cost,
                    'profit' => $profit,
                    'margin' => $gross > 0 ? round(($profit / $gross) * 100, 2) : 0.0,
                    'orders' => $orders->count(),
                    'commission' => $commission,
                    'subtotal' => $subtotal,
                ];
            } catch (\Throwable) {
                return $empty;
            }
        }

        public static function byDay(?int $userId = null, int $days = 30): \Illuminate\Support\Collection
        {
            try {
                $from = now()->subDays(max(1, $days))->startOfDay();
                if (! \Illuminate\Support\Facades\Schema::hasTable('orders')) {
                    return collect();
                }
                $q = \Illuminate\Support\Facades\DB::table('orders')->where('status', '!=', 'cancelled')->where('created_at', '>=', $from);
                if ($userId && \Illuminate\Support\Facades\Schema::hasColumn('orders', 'sold_by_user_id')) {
                    $q->where('sold_by_user_id', $userId);
                }
                $orders = $q->get();
                $orderIds = $orders->pluck('id')->all();
                $costByOrder = [];
                if ($orderIds && \Illuminate\Support\Facades\Schema::hasTable('order_items')
                    && \Illuminate\Support\Facades\Schema::hasColumn('order_items', 'unit_cost')) {
                    $rows = \Illuminate\Support\Facades\DB::table('order_items')
                        ->whereIn('order_id', $orderIds)
                        ->selectRaw('order_id, SUM(COALESCE(unit_cost,0) * quantity) as c')
                        ->groupBy('order_id')->get();
                    foreach ($rows as $r) {
                        $costByOrder[(int) $r->order_id] = (int) $r->c;
                    }
                }

                return $orders->groupBy(fn ($o) => substr((string) $o->created_at, 0, 10))
                    ->map(function ($g, $date) use ($costByOrder) {
                        $gross = (int) $g->sum('total');
                        $cost = 0;
                        foreach ($g as $o) {
                            $cost += $costByOrder[(int) $o->id] ?? 0;
                        }
                        $profit = $gross - $cost;
                        $commission = isset($g->first()->commission_amount) ? (int) $g->sum('commission_amount') : 0;

                        return [
                            'date' => $date,
                            'orders' => $g->count(),
                            'gross' => $gross,
                            'cost' => $cost,
                            'profit' => $profit,
                            'commission' => $commission,
                            'margin' => $gross > 0 ? round(($profit / $gross) * 100, 2) : 0.0,
                        ];
                    })->sortKeysDesc();
            } catch (\Throwable) {
                return collect();
            }
        }

        public static function staffLeaderboard(int $days = 30): \Illuminate\Support\Collection
        {
            try {
                if (! \Illuminate\Support\Facades\Schema::hasTable('staff_members')
                    || ! \Illuminate\Support\Facades\Schema::hasTable('orders')
                    || ! \Illuminate\Support\Facades\Schema::hasColumn('orders', 'sold_by_user_id')) {
                    return collect();
                }
                $from = now()->subDays(max(1, $days))->startOfDay();
                $staff = \Illuminate\Support\Facades\DB::table('staff_members')->where('is_active', 1)->whereNotNull('user_id')->get();

                return $staff->map(function ($s) use ($from, $days) {
                    $sum = self::summarize((int) $s->user_id, $from);
                    $sum['staff'] = $s;
                    $sum['by_day'] = self::byDay((int) $s->user_id, $days);

                    return (object) $sum;
                })->sortByDesc('gross')->values();
            } catch (\Throwable) {
                return collect();
            }
        }

        /**
         * داده نمودار رشد ماهانه به تفکیک نام کارمند
         * @return array{labels:list<string>,datasets:list<array{label:string,data:list<int>,profit:list<int>}>}
         */
        public static function monthlyGrowthChart(int $months = 6): array
        {
            $months = max(3, min(12, $months));
            $labels = [];
            $monthKeys = [];
            for ($i = $months - 1; $i >= 0; $i--) {
                $d = now()->startOfMonth()->subMonths($i);
                $monthKeys[] = $d->format('Y-m');
                $labels[] = $d->format('Y/m');
            }
            $datasets = [];
            try {
                if (! \Illuminate\Support\Facades\Schema::hasTable('staff_members')
                    || ! \Illuminate\Support\Facades\Schema::hasTable('orders')
                    || ! \Illuminate\Support\Facades\Schema::hasColumn('orders', 'sold_by_user_id')) {
                    return ['labels' => $labels, 'datasets' => []];
                }
                $from = now()->startOfMonth()->subMonths($months - 1);
                $staff = \Illuminate\Support\Facades\DB::table('staff_members')
                    ->where('is_active', 1)->whereNotNull('user_id')->orderBy('name')->get();
                $orders = \Illuminate\Support\Facades\DB::table('orders')
                    ->where('status', '!=', 'cancelled')
                    ->where('created_at', '>=', $from)
                    ->whereNotNull('sold_by_user_id')
                    ->get();
                $orderIds = $orders->pluck('id')->all();
                $costByOrder = [];
                if ($orderIds && \Illuminate\Support\Facades\Schema::hasTable('order_items')
                    && \Illuminate\Support\Facades\Schema::hasColumn('order_items', 'unit_cost')) {
                    foreach (\Illuminate\Support\Facades\DB::table('order_items')
                        ->whereIn('order_id', $orderIds)
                        ->selectRaw('order_id, SUM(COALESCE(unit_cost,0)*quantity) as c')
                        ->groupBy('order_id')->get() as $r) {
                        $costByOrder[(int) $r->order_id] = (int) $r->c;
                    }
                }
                $palette = ['#e23d12', '#2563eb', '#16a34a', '#ca8a04', '#7c3aed', '#0891b2', '#db2777', '#475569'];
                $ci = 0;
                foreach ($staff as $s) {
                    $uid = (int) $s->user_id;
                    $mine = $orders->where('sold_by_user_id', $uid);
                    $grossData = [];
                    $profitData = [];
                    foreach ($monthKeys as $mk) {
                        $g = $mine->filter(fn ($o) => str_starts_with((string) $o->created_at, $mk));
                        $gross = (int) $g->sum('total');
                        $cost = 0;
                        foreach ($g as $o) {
                            $cost += $costByOrder[(int) $o->id] ?? 0;
                        }
                        $grossData[] = $gross;
                        $profitData[] = $gross - $cost;
                    }
                    if (array_sum($grossData) === 0) {
                        continue;
                    }
                    $color = $palette[$ci % count($palette)];
                    $ci++;
                    $datasets[] = [
                        'label' => (string) $s->name,
                        'data' => $grossData,
                        'profit' => $profitData,
                        'borderColor' => $color,
                        'backgroundColor' => $color.'33',
                    ];
                }
            } catch (\Throwable) {
                //
            }

            return ['labels' => $labels, 'datasets' => $datasets];
        }

        public static function attachCommissionToOrder(int $orderId, ?int $userId = null): void
        {
            try {
                if (! \Illuminate\Support\Facades\Schema::hasTable('orders')
                    || ! \Illuminate\Support\Facades\Schema::hasColumn('orders', 'commission_amount')) {
                    return;
                }
                $order = \Illuminate\Support\Facades\DB::table('orders')->where('id', $orderId)->first();
                if (! $order) {
                    return;
                }
                $uid = $userId ?? (\Illuminate\Support\Facades\Schema::hasColumn('orders', 'sold_by_user_id') ? ($order->sold_by_user_id ?? null) : null);
                $rate = 0.0;
                if ($uid && \Illuminate\Support\Facades\Schema::hasTable('staff_members')) {
                    $staff = \Illuminate\Support\Facades\DB::table('staff_members')->where('user_id', $uid)->where('is_active', 1)->first();
                    if ($staff) {
                        $rate = (float) ($staff->commission_rate ?? 0);
                    }
                }
                $amount = (int) round(((int) ($order->subtotal ?? 0)) * $rate / 100);
                try {
                    if (class_exists(\Plugins\AdminCore\src\Support\AccountingLedger::class) && $rate > 0) {
                        $base = \Plugins\AdminCore\src\Support\AccountingLedger::commissionBaseAmount((int) $orderId);
                        $amount = (int) round($base * $rate / 100);
                    } elseif (class_exists(\Plugins\Accounting\src\Support\AccountingLedger::class) && $rate > 0) {
                        $base = \Plugins\Accounting\src\Support\AccountingLedger::commissionBaseAmount((int) $orderId);
                        $amount = (int) round($base * $rate / 100);
                    } elseif (\Illuminate\Support\Facades\Schema::hasTable('order_items')
                        && \Illuminate\Support\Facades\Schema::hasColumn('order_items', 'unit_cost')) {
                        $cost = (int) \Illuminate\Support\Facades\DB::table('order_items')
                            ->where('order_id', $orderId)
                            ->selectRaw('COALESCE(SUM(COALESCE(unit_cost,0) * quantity),0) as c')
                            ->value('c');
                        $profit = max(0, (int) ($order->subtotal ?? 0) - $cost);
                        $amount = (int) round($profit * $rate / 100);
                    }
                } catch (\Throwable) {
                    //
                }
                $upd = ['commission_rate' => $rate, 'commission_amount' => $amount, 'updated_at' => now()];
                if ($uid && \Illuminate\Support\Facades\Schema::hasColumn('orders', 'sold_by_user_id')) {
                    $upd['sold_by_user_id'] = $uid;
                }
                \Illuminate\Support\Facades\DB::table('orders')->where('id', $orderId)->update($upd);
            } catch (\Throwable) {
                //
            }
        }
    }
}
