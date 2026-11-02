<?php

use App\Models\AppSetting;
use App\Models\LookupOption;
use App\Support\CostApprovalSettings;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lookup_options')) {
            $extras = [
                'service_type' => ['جراحی هارد', 'جراحی PCB', 'بازیابی اطلاعات'],
                'repair_type' => ['دیتا ریکاوری', 'جراحی'],
            ];
            foreach ($extras as $group => $names) {
                foreach ($names as $i => $name) {
                    LookupOption::query()->firstOrCreate(
                        ['group_key' => $group, 'name' => $name],
                        ['sort_order' => 50 + $i, 'is_active' => true]
                    );
                }
            }
        }

        if (Schema::hasTable('app_settings') && AppSetting::getValue(CostApprovalSettings::SETTING_KEY) === null) {
            CostApprovalSettings::setEnabledServices(CostApprovalSettings::DEFAULTS);
        }
    }

    public function down(): void
    {
        // keep seeded lookups / settings
    }
};
