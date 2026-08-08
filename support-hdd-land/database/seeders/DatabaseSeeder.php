<?php

namespace Database\Seeders;

use App\Models\FaultType;
use App\Models\LookupOption;
use App\Models\ReferralSource;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Product defaults only (no seller live customers/receptions).
 * Web installer uses WebInstaller::seedFresh() which also sets admin + shop name.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'مدیر',
                'phone' => '09120000000',
                'password' => 'password',
                'role' => 'admin',
                'permissions' => \App\Support\Permissions::defaultsForRole('admin'),
                'can_login_otp' => true,
                'can_login_password' => true,
                'is_active' => true,
            ]
        );

        collect(['اینستاگرام', 'گوگل', 'معرفی دوستان', 'تابلو مغازه', 'سایت'])
            ->each(fn ($name) => ReferralSource::query()->firstOrCreate(['name' => $name]));

        $lookupSeed = [
            'admission_type' => ['حضوری', 'پستی', 'پیک', 'نمایندگی'],
            'service_type' => ['تعمیر', 'بازیابی اطلاعات', 'تعویض قطعه', 'عیب‌یابی', 'نصب سیستم'],
            'repair_type' => ['سخت‌افزاری', 'نرم‌افزاری', 'دیتا ریکاوری', 'گارانتی'],
            'warranty_type' => ['فاقد گارانتی و بیمه', 'گارانتی شرکتی', 'گارانتی تعمیرگاه', 'بیمه'],
            'hdd_capacity' => ['120GB', '250GB', '320GB', '500GB', '1TB', '2TB', '4TB'],
            'brand_model' => ['WD My Passport', 'Seagate Backup Plus', 'Toshiba Canvio', 'Samsung T7', 'Laptop Generic'],
        ];
        foreach ($lookupSeed as $group => $names) {
            foreach ($names as $i => $name) {
                LookupOption::query()->firstOrCreate(
                    ['group_key' => $group, 'name' => $name],
                    ['sort_order' => $i + 1, 'is_active' => true]
                );
            }
        }

        collect([
            'روشن نمی‌شود',
            'صدای غیرعادی',
            'عدم شناسایی',
            'بازیابی اطلاعات',
            'آسیب فیزیکی',
            'نرم‌افزاری',
        ])->each(fn ($name) => FaultType::query()->firstOrCreate(['name' => $name]));
    }
}
