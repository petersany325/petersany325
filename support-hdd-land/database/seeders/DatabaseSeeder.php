<?php

namespace Database\Seeders;

use App\Models\LookupOption;
use App\Models\Customer;
use App\Models\FaultType;
use App\Models\Part;
use App\Models\Payment;
use App\Models\Reception;
use App\Models\ReceptionPart;
use App\Models\ReferralSource;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@saramin-hard.ir'],
            [
                'name' => 'مدیر سرزمین هارد',
                'phone' => '09120000000',
                'password' => 'password',
                'role' => 'admin',
                'permissions' => \App\Support\Permissions::defaultsForRole('admin'),
                'can_login_otp' => true,
                'can_login_password' => true,
                'is_active' => true,
            ]
        );

        $sources = collect(['اینستاگرام', 'گوگل', 'معرفی دوستان', 'تابلو مغازه', 'سایت'])
            ->map(fn ($name) => ReferralSource::query()->firstOrCreate(['name' => $name]));

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

        $faults = collect([
            'روشن نمی‌شود',
            'صدای غیرعادی',
            'عدم شناسایی',
            'بازیابی اطلاعات',
            'آسیب فیزیکی',
            'نرم‌افزاری',
        ])->map(fn ($name) => FaultType::query()->firstOrCreate(['name' => $name]));

        $tech1 = Technician::query()->firstOrCreate(
            ['name' => 'علی رضایی'],
            ['phone' => '09121112233', 'specialty' => 'هارد و دیتا ریکاوری', 'commission_percent' => 30]
        );
        $tech2 = Technician::query()->firstOrCreate(
            ['name' => 'سارا محمدی'],
            ['phone' => '09123334455', 'specialty' => 'لپ‌تاپ و SSD', 'commission_percent' => 25]
        );

        $parts = [
            ['code' => 'PCB-01', 'name' => 'برد هارد', 'brand' => 'WD', 'model' => 'Universal', 'stock' => 12, 'purchase_price' => 800000, 'sale_price' => 1200000, 'min_stock' => 2],
            ['code' => 'HEAD-02', 'name' => 'هد هارد', 'brand' => 'Seagate', 'model' => '7200', 'stock' => 8, 'purchase_price' => 1500000, 'sale_price' => 2200000, 'min_stock' => 1],
            ['code' => 'SSD-128', 'name' => 'SSD 128GB', 'brand' => 'Kingston', 'model' => 'A400', 'stock' => 15, 'purchase_price' => 900000, 'sale_price' => 1350000, 'min_stock' => 3],
            ['code' => 'CABLE-SATA', 'name' => 'کابل ساتا', 'brand' => 'Generic', 'model' => 'SATA3', 'stock' => 40, 'purchase_price' => 50000, 'sale_price' => 120000, 'min_stock' => 10],
        ];

        foreach ($parts as $part) {
            Part::query()->updateOrCreate(['code' => $part['code']], $part);
        }

        $customer = Customer::query()->firstOrCreate(
            ['phone' => '09121234567'],
            [
                'name' => 'رضا کریمی',
                'national_code' => '0012345678',
                'job' => 'کارمند',
                'address' => 'تهران، خیابان انقلاب',
                'referral_source_id' => $sources->first()->id,
            ]
        );

        $reception = Reception::query()->firstOrCreate(
            ['ticket_no' => 'SH-DEMO-0001'],
            [
                'customer_id' => $customer->id,
                'technician_id' => $tech1->id,
                'fault_type_id' => $faults->first()->id,
                'created_by' => $admin->id,
                'product_name' => 'هارد اکسترنال',
                'brand' => 'WD',
                'model' => 'My Passport 1TB',
                'serial_number' => 'WXD123456789',
                'accessories' => 'کابل USB',
                'reported_fault' => 'شناسایی نمی‌شود',
                'status' => 'repairing',
                'deposit' => 500000,
                'labor_cost' => 1500000,
                'paid_amount' => 500000,
                'received_at' => now()->subDay(),
            ]
        );

        if ($reception->parts()->count() === 0) {
            $part = Part::query()->where('code', 'PCB-01')->first();
            ReceptionPart::create([
                'reception_id' => $reception->id,
                'part_id' => $part->id,
                'part_name' => $part->name,
                'quantity' => 1,
                'unit_price' => $part->sale_price,
                'total_price' => $part->sale_price,
                'used_at' => now()->toDateString(),
            ]);
            $part->decrement('stock');
        }

        if ($reception->payments()->count() === 0) {
            Payment::create([
                'reception_id' => $reception->id,
                'customer_id' => $customer->id,
                'received_by' => $admin->id,
                'type' => 'deposit',
                'method' => 'cash',
                'amount' => 500000,
                'note' => 'بیعانه دمو',
                'paid_at' => now()->subDay(),
            ]);
        }

        $reception->recalculateTotals();
    }
}
