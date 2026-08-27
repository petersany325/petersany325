<?php

namespace App\Support;

class Permissions
{
    public const ALL = [
        'dashboard' => 'داشبورد',
        'receptions' => 'قبض‌ها و پذیرش',
        'handoffs' => 'ارجاع دستگاه / کارتابل تعمیر',
        'notifications' => 'اعلان‌ها و پیام مشتری',
        'daily_logs' => 'دفتر روز / ثبت کار روزانه',
        'daily_logs.manage' => 'مدیریت دفتر روز (گزارش و تنظیمات)',
        'customers' => 'مشتریان',
        'parts' => 'قطعات و انبار',
        'technicians' => 'تعمیرکاران',
        'employees' => 'کارمندان و دسترسی‌ها',
        'sms.statuses' => 'تعریف تغییر وضعیت / پیامک',
        'reports.accounting' => 'حسابداری',
        'reports.operations' => 'گزارش عملیات کارگاه',
        'reports.custody' => 'گزارش ارجاع / محل دستگاه',
        'reports.payments' => 'گزارش صندوق و دریافت‌ها',
        'reports.technicians' => 'گزارش عملکرد تعمیرکاران',
        'reports.customers' => 'گزارش کاربران',
        'reports.parts' => 'گزارش کالای خرج‌شده',
        'reports.sms' => 'گزارش پیامک',
        'reports.messages' => 'گزارش پیام مشتری',
        'settings' => 'تنظیمات سیستم',
        'system.tools' => 'ابزارهای سیستم (کش / دیتابیس)',
        'profile' => 'پروفایل و تغییر رمز',
    ];

    /** وظیفه / نقش با ظاهر گرافیکی و دسترسی پیش‌فرض */
    public const ROLES = [
        'admin' => [
            'label' => 'مدیر',
            'hint' => 'دسترسی کامل به همه بخش‌ها',
            'tone' => 'slate',
            'mark' => 'م',
        ],
        'receptionist' => [
            'label' => 'پذیرش',
            'hint' => 'ثبت قبض، مشتری، قطعه و پیامک وضعیت',
            'tone' => 'blue',
            'mark' => 'پ',
        ],
        'technician' => [
            'label' => 'تعمیرکار',
            'hint' => 'کارتابل تعمیر، قبض‌ها و قطعات',
            'tone' => 'teal',
            'mark' => 'ت',
        ],
        'accountant' => [
            'label' => 'حسابدار',
            'hint' => 'گزارش مالی، مشتریان و قطعات',
            'tone' => 'amber',
            'mark' => 'ح',
        ],
        'employee' => [
            'label' => 'کارمند',
            'hint' => 'دسترسی پایه — داشبورد و پروفایل',
            'tone' => 'green',
            'mark' => 'ک',
        ],
        'intern' => [
            'label' => 'کارآموز',
            'hint' => 'پرتال کارآموز — دفتر روز و خدمات تعریف‌شده شرکت',
            'tone' => 'violet',
            'mark' => 'آ',
        ],
    ];

    /** دسترسی‌هایی که مدیر معمولاً برای کارآموز روشن می‌کند */
    public const INTERN_MANAGEABLE = [
        'dashboard' => 'میز کار',
        'daily_logs' => 'دفتر روزانه',
        'notifications' => 'اعلان‌ها',
        'handoffs' => 'ارجاع دستگاه / کارتابل تعمیر',
        'receptions' => 'قبض‌ها و پذیرش (مشاهده/کمک)',
        'customers' => 'مشتریان',
        'parts' => 'انبار',
        'reports.custody' => 'گزارش ارجاع / محل دستگاه',
        'profile' => 'پروفایل',
    ];

    public static function defaultsForRole(string $role): array
    {
        return match ($role) {
            'admin' => array_keys(self::ALL),
            'receptionist' => [
                'dashboard', 'receptions', 'handoffs', 'notifications', 'daily_logs', 'customers', 'parts', 'sms.statuses',
                'reports.operations', 'reports.custody', 'reports.sms', 'reports.messages', 'profile',
            ],
            'technician' => [
                'dashboard', 'receptions', 'handoffs', 'notifications', 'daily_logs', 'parts',
                'reports.custody', 'reports.technicians', 'profile',
            ],
            'accountant' => [
                'dashboard', 'reports.accounting', 'reports.operations', 'reports.payments',
                'reports.technicians', 'reports.customers', 'reports.parts',
                'customers', 'notifications', 'daily_logs', 'profile',
            ],
            'intern' => ['dashboard', 'daily_logs', 'notifications', 'profile'],
            default => ['dashboard', 'notifications', 'daily_logs', 'profile'],
        };
    }

    public static function roleMeta(string $role): array
    {
        return self::ROLES[$role] ?? [
            'label' => $role,
            'hint' => '',
            'tone' => 'slate',
            'mark' => '؟',
        ];
    }

    /** @return array<string, list<string>> */
    public static function defaultsMap(): array
    {
        $map = [];
        foreach (array_keys(self::ROLES) as $role) {
            $map[$role] = self::defaultsForRole($role);
        }

        return $map;
    }
}
