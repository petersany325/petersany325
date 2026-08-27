<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Route;

class NavMenu
{
    /**
     * Grouped menus with optional children, filtered by permission.
     *
     * @return list<array{key:string,label:string,route:?string,match:string,mark:string,hint:string,children:list<array>}>
     */
    public static function forUser(User $user): array
    {
        $homeRoute = $user->isIntern() ? 'intern.portal' : 'dashboard';
        $homeMatch = $user->isIntern() ? 'intern.*|dashboard' : 'dashboard';

        $groups = [
            [
                'key' => 'home',
                'label' => $user->isIntern() ? 'پرتال کارآموز' : 'میز کار',
                'permission' => 'dashboard',
                'route' => $homeRoute,
                'match' => $homeMatch,
                'mark' => $user->isIntern() ? 'آ' : 'م',
                'hint' => $user->isIntern() ? 'خدمات شرکت و ثبت کار' : 'شورت‌کارت‌ها و خلاصه',
                'children' => [],
            ],
            [
                'key' => 'reception',
                'label' => 'پذیرش',
                'permission' => 'receptions',
                'route' => null,
                'match' => 'receptions.*|deliveries.*|trash.*',
                'mark' => 'پ',
                'hint' => 'قبض، جستجو، تحویل',
                'children' => [
                    ['label' => 'پذیرش جدید', 'route' => 'receptions.create', 'match' => 'receptions.create', 'hint' => 'ثبت قبض تکی/گروهی', 'mark' => 'جد'],
                    ['label' => 'جستجوی قبض', 'route' => 'receptions.search', 'match' => 'receptions.search', 'hint' => 'سریال، موبایل، شماره', 'mark' => 'ج'],
                    ['label' => 'لیست قبض‌ها', 'route' => 'receptions.index', 'match' => 'receptions.index|receptions.show|receptions.edit', 'hint' => 'همه پذیرش‌ها', 'mark' => 'لی'],
                    ['label' => 'تحویل گروهی', 'route' => 'deliveries.group', 'match' => 'deliveries.*', 'hint' => 'خروج چند قبض', 'mark' => 'تح'],
                    ['label' => 'سطل زباله', 'route' => 'trash.index', 'match' => 'trash.*', 'hint' => 'بازیابی یا حذف دائم', 'mark' => 'سط'],
                ],
            ],
            [
                'key' => 'handoffs',
                'label' => 'ارجاع / کارتابل تعمیر',
                'permission' => 'handoffs',
                'route' => 'handoffs.index',
                'match' => 'handoffs.*',
                'mark' => 'ا',
                'hint' => 'دریافت دستگاه و هاردهای دست تعمیر',
                'children' => [
                    ['label' => 'کارتابل ارجاع', 'route' => 'handoffs.index', 'match' => 'handoffs.index', 'hint' => 'جستجو، تأیید دریافت، دست تعمیر', 'mark' => 'ک'],
                    ['label' => 'گزارش ارجاع / محل', 'route' => 'reports.custody', 'match' => 'reports.custody', 'hint' => 'گزارش سریال و قبض', 'mark' => 'گ', 'permission' => 'reports.custody'],
                ],
            ],
            [
                'key' => 'notifications',
                'label' => 'اعلان‌ها',
                'permission' => 'notifications',
                'route' => 'notifications.index',
                'match' => 'notifications.*',
                'mark' => 'ن',
                'hint' => 'پیام مشتری و اعلان ارجاع',
                'children' => [],
            ],
            [
                'key' => 'daily_logs',
                'label' => 'دفتر روز',
                'permission' => 'daily_logs',
                'route' => 'daily-logs.index',
                'match' => 'daily-logs.index|daily-logs.report',
                'mark' => 'ر',
                'hint' => 'ثبت کار و رویداد روزانه',
                'any_of' => ['daily_logs', 'daily_logs.manage'],
                'children' => [
                    ['label' => 'ثبت امروز', 'route' => 'daily-logs.index', 'match' => 'daily-logs.index', 'hint' => 'رویدادهای روز جاری', 'mark' => 'ام', 'permission' => 'daily_logs'],
                    ['label' => 'گزارش همه', 'route' => 'daily-logs.report', 'match' => 'daily-logs.report', 'hint' => 'مرور کارمندان', 'mark' => 'گ', 'permission' => 'daily_logs.manage'],
                ],
            ],
            [
                'key' => 'cost_approvals',
                'label' => 'تأیید هزینه',
                'permission' => 'receptions',
                'route' => null,
                'match' => 'cost-approvals.*',
                'mark' => 'ت',
                'hint' => 'جراحی، بازیابی، لینک تأیید',
                'children' => [
                    ['label' => 'کارتابل تأییدها', 'route' => 'cost-approvals.index', 'match' => 'cost-approvals.index', 'hint' => 'در انتظار و تاریخچه', 'mark' => 'ک'],
                    ['label' => 'خدمات مشمول', 'route' => 'cost-approvals.settings', 'match' => 'cost-approvals.settings', 'hint' => 'جراحی / بازیابی و…', 'mark' => 'خ'],
                ],
            ],
            [
                'key' => 'customers',
                'label' => 'مشتریان',
                'permission' => 'customers',
                'route' => 'customers.index',
                'match' => 'customers.*',
                'mark' => 'ش',
                'hint' => 'فهرست و پرونده مشتری',
                'children' => [
                    ['label' => 'فهرست مشتریان', 'route' => 'customers.index', 'match' => 'customers.index|customers.show|customers.edit', 'hint' => 'جستجو، ویرایش، حذف', 'mark' => 'ف'],
                    ['label' => 'مشتری جدید', 'route' => 'customers.create', 'match' => 'customers.create', 'hint' => 'نام و موبایل یکتا', 'mark' => '+'],
                ],
            ],
            [
                'key' => 'parts',
                'label' => 'انبار',
                'permission' => 'parts',
                'route' => 'parts.index',
                'match' => 'parts.*|warehouses.*',
                'mark' => 'ق',
                'hint' => 'انبار حسابداری قطعات',
                'children' => [
                    ['label' => 'میز انبار', 'route' => 'parts.index', 'match' => 'parts.index|parts.show|parts.edit', 'hint' => 'موجودی و ارزش', 'mark' => 'م'],
                    ['label' => 'انبارهای چندگانه', 'route' => 'warehouses.index', 'match' => 'warehouses.*', 'hint' => 'تعریف انبار ۱ و ۲…', 'mark' => 'چ'],
                    ['label' => 'رسید ورود', 'route' => 'parts.receipt', 'match' => 'parts.receipt*', 'hint' => 'خرید / ورود', 'mark' => 'ر'],
                    ['label' => 'حواله خروج', 'route' => 'parts.issue', 'match' => 'parts.issue*', 'hint' => 'خروج غیرقبض', 'mark' => 'ح'],
                    ['label' => 'کارتکس / گردش', 'route' => 'parts.movements', 'match' => 'parts.movements', 'hint' => 'دفتر انبار', 'mark' => 'ک'],
                    ['label' => 'ارزش موجودی', 'route' => 'parts.valuation', 'match' => 'parts.valuation', 'hint' => 'تراز ریالی', 'mark' => 'ا'],
                    ['label' => 'کالای جدید', 'route' => 'parts.create', 'match' => 'parts.create', 'hint' => 'تعریف کارت کالا', 'mark' => '+'],
                ],
            ],
            [
                'key' => 'employees',
                'label' => 'کارمندان',
                'permission' => null,
                'route' => null,
                'match' => 'employees.*|technicians.*|interns.*|staff-sms.*',
                'mark' => 'ک',
                'hint' => 'کارتابل، کارآموز، SMS',
                'any_of' => ['employees', 'technicians'],
                'children' => [
                    ['label' => 'کارتابل کارمند', 'route' => 'employees.index', 'match' => 'employees.index|employees.edit', 'hint' => 'لیست، وظیفه، دسترسی', 'mark' => 'ک', 'permission' => 'employees'],
                    ['label' => 'کارمند جدید', 'route' => 'employees.create', 'match' => 'employees.create', 'hint' => 'پذیرش / حسابدار / تعمیرکار…', 'mark' => '+', 'permission' => 'employees'],
                    ['label' => 'کارتابل کارآموز', 'route' => 'interns.index', 'match' => 'interns.index|interns.edit', 'hint' => 'دسترسی و پرتال ورود', 'mark' => 'آ', 'permission' => 'employees', 'sep' => true],
                    ['label' => 'کارآموز جدید', 'route' => 'interns.create', 'match' => 'interns.create', 'hint' => 'ثبت + فعال‌سازی ورود', 'mark' => '+', 'permission' => 'employees'],
                    ['label' => 'پرتال کارآموز (پیش‌نمایش)', 'route' => 'intern.portal', 'match' => 'intern.portal', 'hint' => 'نمای ورود کارآموز', 'mark' => 'پ', 'permission' => 'employees'],
                    ['label' => 'متن SMS خوش‌آمد', 'route' => 'staff-sms.templates', 'match' => 'staff-sms.*', 'hint' => 'کارمند و کارآموز', 'mark' => 'پ', 'permission' => 'employees'],
                    ['label' => 'تخصص و کمیسیون تعمیرکار', 'route' => 'technicians.index', 'match' => 'technicians.index|technicians.edit', 'hint' => 'هارد، بازیابی، قیمت/٪', 'mark' => 'ت', 'permission' => 'technicians', 'sep' => true],
                    ['label' => 'تعمیرکار جدید (قیمت)', 'route' => 'technicians.create', 'match' => 'technicians.create', 'hint' => 'تخصص + کمیسیون', 'mark' => '+', 'permission' => 'technicians'],
                ],
            ],
            [
                'key' => 'sms',
                'label' => 'پیامک‌ها',
                'permission' => null,
                'route' => null,
                'match' => 'sms-statuses.*|reports.sms',
                'mark' => 'پ',
                'hint' => 'وضعیت دستگاه و گزارش پیامک',
                'any_of' => ['sms.statuses', 'reports.sms'],
                'children' => [
                    ['label' => 'گزارش پیامک قبض‌ها', 'route' => 'reports.sms', 'match' => 'reports.sms', 'hint' => 'همه پیامک‌های ارسال‌شده', 'mark' => 'گ', 'permission' => 'reports.sms'],
                    ['label' => 'تعریف وضعیت / قالب', 'route' => 'sms-statuses.index', 'match' => 'sms-statuses.*', 'hint' => 'وضعیت دستگاه و متن SMS', 'mark' => 'و', 'permission' => 'sms.statuses'],
                ],
            ],
            [
                'key' => 'accounting',
                'label' => 'حسابداری',
                'permission' => 'reports.accounting',
                'route' => 'accounting.index',
                'match' => 'accounting.*|reports.accounting',
                'mark' => 'ح',
                'hint' => 'اسناد، خزانه، بدهکاران',
                'children' => [
                    ['label' => 'میز حسابداری', 'route' => 'accounting.index', 'match' => 'accounting.index', 'hint' => 'خلاصه خزانه و درآمد', 'mark' => 'م', 'permission' => 'reports.accounting'],
                    ['label' => 'اسناد روزنامه', 'route' => 'accounting.journals', 'match' => 'accounting.journals|accounting.show', 'hint' => 'لیست اسناد', 'mark' => 'ا', 'permission' => 'reports.accounting'],
                    ['label' => 'سرفصل حساب‌ها', 'route' => 'accounting.accounts', 'match' => 'accounting.accounts', 'hint' => 'کدینگ', 'mark' => 'س', 'permission' => 'reports.accounting'],
                    ['label' => 'دفتر معین', 'route' => 'accounting.ledger', 'match' => 'accounting.ledger', 'hint' => 'گردش حساب', 'mark' => 'د', 'permission' => 'reports.accounting'],
                    ['label' => 'تراز آزمایشی', 'route' => 'accounting.trial', 'match' => 'accounting.trial', 'hint' => 'تراز دوره', 'mark' => 'ت', 'permission' => 'reports.accounting'],
                    ['label' => 'بدهکاران', 'route' => 'accounting.receivables', 'match' => 'accounting.receivables', 'hint' => 'مانده مشتریان', 'mark' => 'ب', 'permission' => 'reports.accounting'],
                    ['label' => 'سند دستی', 'route' => 'accounting.manual', 'match' => 'accounting.manual', 'hint' => 'ثبت آزاد', 'mark' => '+', 'permission' => 'reports.accounting'],
                ],
            ],
            [
                'key' => 'reports',
                'label' => 'گزارش‌ها',
                'permission' => null,
                'route' => null,
                'match' => 'reports.*',
                'mark' => 'گ',
                'hint' => 'عملکرد و مشتریان',
                'any_of' => [
                    'reports.technicians',
                    'reports.customers',
                    'reports.parts',
                    'reports.operations',
                    'reports.custody',
                    'reports.payments',
                    'reports.sms',
                    'reports.messages',
                ],
                'children' => [
                    ['label' => 'عملکرد تعمیرکاران', 'route' => 'reports.technicians', 'match' => 'reports.technicians*', 'permission' => 'reports.technicians', 'hint' => '', 'mark' => 'ت'],
                    ['label' => 'گزارش مشتریان', 'route' => 'reports.customers', 'match' => 'reports.customers*', 'permission' => 'reports.customers', 'hint' => '', 'mark' => 'ش'],
                    ['label' => 'کالای خرج‌شده', 'route' => 'reports.parts-used', 'match' => 'reports.parts-used', 'permission' => 'reports.parts', 'hint' => '', 'mark' => 'ق'],
                    ['label' => 'عملیات کارگاه', 'route' => 'reports.operations', 'match' => 'reports.operations', 'permission' => 'reports.operations', 'hint' => '', 'mark' => 'ع'],
                    ['label' => 'ارجاع / محل دستگاه', 'route' => 'reports.custody', 'match' => 'reports.custody', 'permission' => 'reports.custody', 'hint' => '', 'mark' => 'ا'],
                    ['label' => 'صندوق و دریافت‌ها', 'route' => 'reports.payments', 'match' => 'reports.payments', 'permission' => 'reports.payments', 'hint' => '', 'mark' => 'ص'],
                    ['label' => 'تأیید فیش بانکی', 'route' => 'payment-receipts.index', 'match' => 'payment-receipts.*', 'permission' => 'reports.payments', 'hint' => 'فیش کارت‌به‌کارت پرتال', 'mark' => 'ف'],
                    ['label' => 'پیام مشتری', 'route' => 'reports.messages', 'match' => 'reports.messages', 'permission' => 'reports.messages', 'hint' => '', 'mark' => 'م'],
                    ['label' => 'گزارش پیامک', 'route' => 'reports.sms', 'match' => 'reports.sms', 'permission' => 'reports.sms', 'hint' => 'از منوی پیامک‌ها هم هست', 'mark' => 'پ'],
                ],
            ],
            [
                'key' => 'system_tools',
                'label' => 'ابزارهای سیستم',
                'permission' => 'system.tools',
                'route' => 'system-tools.index',
                'match' => 'system-tools.*',
                'mark' => 'س',
                'hint' => 'کش، تعمیر و بازسازی دیتابیس',
                'children' => [
                    ['label' => 'نگهداری و بکاپ', 'route' => 'system-tools.index', 'match' => 'system-tools.*', 'hint' => 'کش، تعمیر، بکاپ و ریستور', 'mark' => 'ن'],
                    ['label' => 'لایسنس نصب مشتریان', 'route' => 'licenses.index', 'match' => 'licenses.*', 'hint' => 'صدور سریال نصب', 'mark' => 'ل'],
                ],
            ],
            [
                'key' => 'settings',
                'label' => 'تنظیمات',
                'permission' => null,
                'route' => null,
                'match' => 'settings.*|profile.*|daily-logs.settings',
                'mark' => 'ظ',
                'hint' => 'سیستم و منوها',
                'any_of' => ['settings', 'profile', 'daily_logs.manage'],
                'children' => [
                    ['label' => 'تنظیمات سیستم', 'route' => 'settings.index', 'match' => 'settings.*', 'hint' => 'منو، فاکتور، SMS، بکاپ', 'mark' => 'ظ', 'permission' => 'settings'],
                    ['label' => 'تنظیمات دفتر روز', 'route' => 'daily-logs.settings', 'match' => 'daily-logs.settings', 'hint' => 'دسته و قوانین — فقط ادمین', 'mark' => 'ر', 'permission' => 'daily_logs.manage'],
                    ['label' => 'پروفایل من', 'route' => 'profile.edit', 'match' => 'profile.*', 'hint' => 'نام و رمز', 'mark' => 'پ', 'permission' => 'profile'],
                ],
            ],
        ];

        $out = [];
        foreach ($groups as $group) {
            if (! empty($group['any_of'])) {
                $allowed = collect($group['any_of'])->contains(fn ($p) => $user->canAccess($p));
                if (! $allowed) {
                    continue;
                }
            } elseif (! empty($group['permission']) && ! $user->canAccess($group['permission'])) {
                continue;
            }

            $children = [];
            foreach ($group['children'] as $child) {
                $perm = $child['permission'] ?? $group['permission'] ?? null;
                if ($perm && ! $user->canAccess($perm)) {
                    continue;
                }
                if (! empty($child['route']) && ! Route::has($child['route'])) {
                    continue;
                }
                $children[] = $child;
            }

            if (! empty($group['any_of']) && $children === [] && empty($group['route'])) {
                continue;
            }

            $group['children'] = $children;
            if (empty($group['route']) && $children !== []) {
                $group['route'] = $children[0]['route'];
            }
            $out[] = $group;
        }

        return $out;
    }

    /**
     * @return list<array{label:string,route:string,hint:string,mark:string,group:string,tone:string}>
     */
    public static function shortcuts(User $user): array
    {
        $cards = [];
        foreach (self::forUser($user) as $group) {
            if (empty($group['route'])) {
                continue;
            }
            $hint = $group['hint'];
            if ($group['children']) {
                $hint = collect($group['children'])->pluck('label')->implode(' · ');
            }
            $cards[] = [
                'label' => $group['label'],
                'route' => $group['route'],
                'hint' => $hint,
                'mark' => $group['mark'],
                'group' => $group['label'],
                'tone' => self::tone($group['key']),
            ];
        }

        return $cards;
    }

    public static function tone(string $key): string
    {
        return match ($key) {
            'home' => 'slate',
            'reception' => 'blue',
            'handoffs' => 'green',
            'notifications' => 'violet',
            'daily_logs' => 'amber',
            'cost_approvals' => 'amber',
            'customers' => 'teal',
            'parts' => 'amber',
            'technicians' => 'green',
            'employees' => 'teal',
            'sms' => 'violet',
            'accounting' => 'teal',
            'reports' => 'green',
            'system_tools' => 'teal',
            'settings' => 'slate',
            default => 'slate',
        };
    }

    /** Short label for mobile tabbar. */
    public static function shortLabel(string $key, string $fallback = ''): string
    {
        return match ($key) {
            'home' => 'میز',
            'reception' => 'پذیرش',
            'handoffs' => 'ارجاع',
            'notifications' => 'اعلان',
            'daily_logs' => 'دفتر',
            'cost_approvals' => 'تأیید',
            'customers' => 'مشتری',
            'parts' => 'انبار',
            'employees' => 'کارمند',
            'sms' => 'پیامک',
            'accounting' => 'حساب',
            'reports' => 'گزارش',
            'system_tools' => 'ابزار',
            'settings' => 'تنظیم',
            default => $fallback !== '' ? mb_substr($fallback, 0, 8) : 'منو',
        };
    }

    /**
     * Primary bottom-tab items for mobile staff shell (max 4 + «بیشتر»).
     *
     * @return list<array{key:string,label:string,mark:string,route:?string,match:string,tone:string}>
     */
    public static function mobilePrimary(User $user, ?array $groups = null): array
    {
        $groups = collect($groups ?? self::forUser($user))->keyBy('key');
        $order = [
            'home', 'daily_logs', 'reception', 'handoffs', 'notifications',
            'customers', 'parts', 'cost_approvals', 'accounting', 'reports',
        ];

        $tabs = [];
        foreach ($order as $key) {
            if (! $groups->has($key)) {
                continue;
            }
            $g = $groups->get($key);
            $route = $g['route'] ?? null;
            if (! $route && ! empty($g['children'][0]['route'])) {
                $route = $g['children'][0]['route'];
            }
            if (! $route || ! Route::has($route)) {
                continue;
            }
            $tabs[] = [
                'key' => $key,
                'label' => self::shortLabel($key, $g['label']),
                'mark' => $g['mark'],
                'route' => $route,
                'match' => $g['match'],
                'tone' => self::tone($key),
            ];
            if (count($tabs) >= 3) {
                break;
            }
        }

        return $tabs;
    }

    public static function isActive(string $match): bool
    {
        $patterns = array_values(array_filter(explode('|', $match)));
        if ($patterns === []) {
            return false;
        }

        return request()->routeIs(...$patterns);
    }
}
