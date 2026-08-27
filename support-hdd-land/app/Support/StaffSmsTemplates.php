<?php

namespace App\Support;

use App\Models\AppSetting;
use App\Models\Intern;
use App\Models\User;

class StaffSmsTemplates
{
    public const EMPLOYEE_KEY = 'sms_template_employee_welcome';

    public const INTERN_KEY = 'sms_template_intern_welcome';

    public static function employeeDefault(): string
    {
        return "سلام {name} عزیز\n"
            ."شما کارمند {shop} هستید.\n"
            ."نقش: {role}\n"
            ."برای ورود به کارتابل روی لینک زیر بزنید:\n"
            ."{login_url}\n"
            .'ورود با پیامک فعال است.';
    }

    public static function internDefault(): string
    {
        return "سلام {name} عزیز\n"
            ."شما کارآموز {shop} هستید.\n"
            ."دوره کارآموزی شما از {start_date} تا {end_date} تأیید شد.\n"
            ."موفق و پیروز باشید.\n"
            .'{shop}';
    }

    public static function employeeTemplate(): string
    {
        $raw = trim((string) AppSetting::getValue(self::EMPLOYEE_KEY, ''));

        return $raw !== '' ? $raw : self::employeeDefault();
    }

    public static function internTemplate(): string
    {
        $raw = trim((string) AppSetting::getValue(self::INTERN_KEY, ''));

        return $raw !== '' ? $raw : self::internDefault();
    }

    public static function save(?string $employee, ?string $intern): void
    {
        if ($employee !== null) {
            AppSetting::setValue(self::EMPLOYEE_KEY, trim($employee) !== '' ? trim($employee) : self::employeeDefault());
        }
        if ($intern !== null) {
            AppSetting::setValue(self::INTERN_KEY, trim($intern) !== '' ? trim($intern) : self::internDefault());
        }
    }

    public static function renderEmployee(User $user): string
    {
        $shop = trim((string) AppSetting::getValue('invoice_shop_name', 'سرزمین هارد')) ?: 'سرزمین هارد';

        return strtr(self::employeeTemplate(), [
            '{name}' => trim((string) $user->name) ?: 'همکار',
            '{shop}' => $shop,
            '{role}' => $user->roleLabel(),
            '{phone}' => (string) $user->phone,
            '{login_url}' => url('/login?otp=1'),
        ]);
    }

    public static function renderIntern(Intern $intern): string
    {
        $shop = trim((string) AppSetting::getValue('invoice_shop_name', 'سرزمین هارد')) ?: 'سرزمین هارد';
        $start = $intern->start_date ? jalali_date($intern->start_date) : '—';
        $end = $intern->end_date ? jalali_date($intern->end_date) : '—';

        return strtr(self::internTemplate(), [
            '{name}' => trim((string) $intern->name) ?: 'کارآموز',
            '{shop}' => $shop,
            '{phone}' => (string) $intern->phone,
            '{start_date}' => $start,
            '{end_date}' => $end,
            '{notes}' => trim((string) ($intern->notes ?? '')),
        ]);
    }
}