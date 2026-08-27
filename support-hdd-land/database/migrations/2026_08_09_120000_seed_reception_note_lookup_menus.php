<?php

use App\Models\LookupOption;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lookup_options')) {
            return;
        }

        $seed = [
            'reported_fault' => ['روشن نمی‌شود', 'صدای غیرعادی', 'عدم شناسایی', 'کندی شدید', 'آسیب فیزیکی', 'نیاز به بازیابی اطلاعات'],
            'accessories' => ['ندارد', 'کابل USB', 'آداپتور', 'جعبه', 'کابل + جعبه'],
            'appearance' => ['سالم و بدون خط و خش', 'خط و خش سطحی', 'ضرب‌دیدگی', 'برچسب کنده شده', 'وضعیت متوسط'],
        ];

        foreach ($seed as $group => $names) {
            foreach ($names as $i => $name) {
                LookupOption::query()->firstOrCreate(
                    ['group_key' => $group, 'name' => $name],
                    ['sort_order' => $i + 1, 'is_active' => true]
                );
            }
        }
    }

    public function down(): void
    {
        // keep seeded lookup menus
    }
};
