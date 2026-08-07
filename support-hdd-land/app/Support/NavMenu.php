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
        $groups = [
            [
                'key' => 'home',
                'label' => 'میز کار',
                'permission' => 'dashboard',
                'route' => 'dashboard',
                'match' => 'dashboard',
                'mark' => 'م',
                'hint' => 'شورت‌کارت‌ها و خلاصه',
                'children' => [],
            ],
            [
                'key' => 'reception',
                'label' => 'پذیرش',
                'permission' => 'receptions',
                'route' => null,
                'match' => 'receptions.*|deliveries.*|handoffs.*',
                'mark' => 'پ',
                'hint' => 'قبض، جستجو، تحویل',
                'children' => [
                    ['label' => 'پذیرش جدید', 'route' => 'receptions.create', 'match' => 'receptions.create', 'hint' => 'ثبت قبض تکی/گروهی', 'mark' => 'جد'],
                    ['label' => 'جستجوی قبض', 'route' => 'receptions.search', 'match' => 'receptions.search', 'hint' => 'سریال، موبایل، شماره', 'mark' => 'ج'],
                    ['label' => 'لیست قبض‌ها', 'route' => 'receptions.index', 'match' => 'receptions.index|receptions.show', 'hint' => 'همه پذیرش‌ها', 'mark' => 'لی'],
                    ['label' => 'تحویل گروهی', 'route' => 'deliveries.group', 'match' => 'deliveries.*', 'hint' => 'خروج چند قبض', 'mark' => 'تح'],
                    ['label' => 'کارتابل ارجاع', 'route' => 'handoffs.index', 'match' => 'handoffs.*', 'hint' => 'تأیید دریافت دستگاه', 'mark' => 'ارج', 'permission' => 'handoffs'],
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
                    ['label' => 'فهرست مشتریان', 'route' => 'customers.index', 'match' => 'customers.index|customers.show', 'hint' => 'جستجو و مشاهده', 'mark' => 'ف'],
                    ['label' => 'مشتری جدید', 'route' => 'customers.create', 'match' => 'customers.create', 'hint' => 'ثبت سریع', 'mark' => '+'],
                ],
            ],
            [
                'key' => 'parts',
                'label' => 'انبار',
                'permission' => 'parts',
                'route' => 'parts.index',
                'match' => 'parts.*',
                'mark' => 'ق',
                'hint' => 'قطعات و موجودی',
                'children' => [
                    ['label' => 'فهرست قطعات', 'route' => 'parts.index', 'match' => 'parts.index|parts.edit', 'hint' => 'موجودی انبار', 'mark' => 'ف'],
                    ['label' => 'قطعه جدید', 'route' => 'parts.create', 'match' => 'parts.create', 'hint' => 'افزودن به انبار', 'mark' => '+'],
                ],
            ],
            [
                'key' => 'technicians',
                'label' => 'تعمیرکاران',
                'permission' => 'technicians',
                'route' => 'technicians.index',
                'match' => 'technicians.*',
                'mark' => 'ت',
                'hint' => 'تعریف تعمیرکار',
                'children' => [
                    ['label' => 'فهرست تعمیرکاران', 'route' => 'technicians.index', 'match' => 'technicians.index|technicians.edit', 'hint' => '', 'mark' => 'ف'],
                    ['label' => 'تعمیرکار جدید', 'route' => 'technicians.create', 'match' => 'technicians.create', 'hint' => '', 'mark' => '+'],
                ],
            ],
            [
                'key' => 'employees',
                'label' => 'کارمندان',
                'permission' => 'employees',
                'route' => 'employees.index',
                'match' => 'employees.*',
                'mark' => 'ک',
                'hint' => 'کارتابل، SMS، دسترسی',
                'children' => [
                    ['label' => 'کارتابل کارمند', 'route' => 'employees.index', 'match' => 'employees.index', 'hint' => 'لیست و وضعیت ورود', 'mark' => 'ک'],
                    ['label' => 'کارمند جدید', 'route' => 'employees.create', 'match' => 'employees.create', 'hint' => 'وظیفه + دسترسی', 'mark' => '+'],
                ],
            ],
            [
                'key' => 'sms',
                'label' => 'وضعیت / پیامک',
                'permission' => 'sms.statuses',
                'route' => 'sms-statuses.index',
                'match' => 'sms-statuses.*',
                'mark' => 'و',
                'hint' => 'تعریف وضعیت دستگاه',
                'children' => [],
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
                    // ترتیب قبلی حفظ شده؛ گزارش‌های جدید بعد از آن‌ها
                    ['label' => 'عملکرد تعمیرکاران', 'route' => 'reports.technicians', 'match' => 'reports.technicians', 'permission' => 'reports.technicians', 'hint' => '', 'mark' => 'ت'],
                    ['label' => 'گزارش مشتریان', 'route' => 'reports.customers', 'match' => 'reports.customers', 'permission' => 'reports.customers', 'hint' => '', 'mark' => 'ش'],
                    ['label' => 'کالای خرج‌شده', 'route' => 'reports.parts-used', 'match' => 'reports.parts-used', 'permission' => 'reports.parts', 'hint' => '', 'mark' => 'ق'],
                    ['label' => 'عملیات کارگاه', 'route' => 'reports.operations', 'match' => 'reports.operations', 'permission' => 'reports.operations', 'hint' => '', 'mark' => 'ع'],
                    ['label' => 'ارجاع / محل دستگاه', 'route' => 'reports.custody', 'match' => 'reports.custody', 'permission' => 'reports.custody', 'hint' => '', 'mark' => 'ا'],
                    ['label' => 'صندوق و دریافت‌ها', 'route' => 'reports.payments', 'match' => 'reports.payments', 'permission' => 'reports.payments', 'hint' => '', 'mark' => 'ص'],
                    ['label' => 'پیامک وضعیت', 'route' => 'reports.sms', 'match' => 'reports.sms', 'permission' => 'reports.sms', 'hint' => '', 'mark' => 'پ'],
                    ['label' => 'پیام مشتری', 'route' => 'reports.messages', 'match' => 'reports.messages', 'permission' => 'reports.messages', 'hint' => '', 'mark' => 'م'],
                ],
            ],
            [
                'key' => 'settings',
                'label' => 'تنظیمات',
                'permission' => null,
                'route' => null,
                'match' => 'settings.*|profile.*|notifications.*',
                'mark' => 'ظ',
                'hint' => 'سیستم و منوها',
                'any_of' => ['settings', 'profile', 'notifications'],
                'children' => [
                    ['label' => 'تنظیمات سیستم', 'route' => 'settings.index', 'match' => 'settings.*', 'hint' => 'منو، فاکتور، SMS', 'mark' => 'ظ', 'permission' => 'settings'],
                    ['label' => 'اعلان‌ها', 'route' => 'notifications.index', 'match' => 'notifications.*', 'hint' => 'پیام مشتری و ارجاع', 'mark' => 'ن', 'permission' => 'notifications'],
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

            // parent-only groups that lost all children via any_of filtering
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
     * Flat shortcut cards for dashboard — one compact card per menu group.
     *
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
            'customers' => 'teal',
            'parts' => 'amber',
            'technicians' => 'green',
            'employees' => 'teal',
            'sms' => 'violet',
            'accounting' => 'teal',
            'reports' => 'green',
            'settings' => 'slate',
            default => 'slate',
        };
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
