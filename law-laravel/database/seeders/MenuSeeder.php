<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        // Standard law-firm top nav (5–7 items + CTA)
        $header = [
            ['label' => 'خانه', 'url' => '/', 'style' => 'regular', 'sort_order' => 1],
            ['label' => 'حوزه‌های تخصصی', 'url' => '/#services', 'style' => 'regular', 'sort_order' => 2],
            ['label' => 'وکلا', 'url' => '/team', 'style' => 'regular', 'sort_order' => 3],
            ['label' => 'درباره مؤسسه', 'url' => '/#about', 'style' => 'regular', 'sort_order' => 4],
            ['label' => 'مقالات', 'url' => '/blog', 'style' => 'fancy', 'sort_order' => 5],
            ['label' => 'تماس', 'url' => '/#contact', 'style' => 'regular', 'sort_order' => 6],
            ['label' => 'درخواست نوبت', 'url' => '/#appointment', 'style' => 'cta', 'sort_order' => 7],
        ];

        $footer = [
            ['label' => 'حوزه‌های تخصصی', 'url' => '/#services', 'style' => 'regular', 'sort_order' => 1],
            ['label' => 'تیم حقوقی', 'url' => '/team', 'style' => 'regular', 'sort_order' => 2],
            ['label' => 'مقالات حقوقی', 'url' => '/blog', 'style' => 'regular', 'sort_order' => 3],
            ['label' => 'سوالات متداول', 'url' => '/faq', 'style' => 'regular', 'sort_order' => 4],
            ['label' => 'درخواست نوبت', 'url' => '/#appointment', 'style' => 'cta', 'sort_order' => 5],
            ['label' => 'حریم خصوصی', 'url' => '/p/privacy', 'style' => 'regular', 'sort_order' => 6],
            ['label' => 'شرایط استفاده', 'url' => '/p/terms', 'style' => 'regular', 'sort_order' => 7],
        ];

        if (Menu::query()->count() === 0) {
            foreach ($header as $row) {
                Menu::query()->create($row + ['location' => 'header', 'is_active' => true]);
            }
            foreach ($footer as $row) {
                Menu::query()->create($row + ['location' => 'footer', 'is_active' => true]);
            }
        }

        Setting::many([
            'footer_about' => Setting::get('footer_about') ?: 'مؤسسه حقوقی آریان؛ همراهی حرفه‌ای در پرونده‌های حقوقی، کیفری و تجاری.',
            'footer_copyright' => Setting::get('footer_copyright') ?: '© مؤسسه حقوقی آریان — تمامی حقوق محفوظ است.',
            'footer_disclaimer' => Setting::get('footer_disclaimer') ?: 'محتوای سایت جنبه اطلاع‌رسانی دارد و جایگزین مشاوره اختصاصی وکیل نیست.',
            'cta_text' => Setting::get('cta_text') ?: 'درخواست نوبت',
            'show_phone_in_header' => Setting::get('show_phone_in_header') ?: '1',
            'email' => Setting::get('email') ?: 'info@hoghoghibabol.ir',
            'mobile' => Setting::get('mobile') ?: '',
        ]);
    }
}
