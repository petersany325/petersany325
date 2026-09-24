<?php

use App\Models\AppSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('daily_log_categories') && ! Schema::hasColumn('daily_log_categories', 'requires_receipt')) {
            Schema::table('daily_log_categories', function (Blueprint $table) {
                $table->boolean('requires_receipt')->default(false)->after('ask_quantity');
            });
        }

        if (Schema::hasTable('daily_log_entries') && ! Schema::hasColumn('daily_log_entries', 'reception_id')) {
            Schema::table('daily_log_entries', function (Blueprint $table) {
                $table->foreignId('reception_id')->nullable()->after('daily_log_category_id')
                    ->constrained('receptions')->nullOnDelete();
                $table->index('reception_id');
            });
        }

        if (Schema::hasTable('daily_log_categories')) {
            // Enable receipt picker on existing ticket/repair-related categories.
            DB::table('daily_log_categories')
                ->where(function ($q) {
                    $q->where('name', 'like', '%قبض%')
                        ->orWhere('name', 'like', '%تعمیر%')
                        ->orWhere('name', 'like', '%قطعه%');
                })
                ->update(['requires_receipt' => true]);

            $exists = DB::table('daily_log_categories')->where('name', 'تعمیر قطعات')->exists();
            if (! $exists) {
                $now = now();
                DB::table('daily_log_categories')->insert([
                    'name' => 'تعمیر قطعات',
                    'hint' => 'کار روی قبض — جستجو و انتخاب قبض الزامی',
                    'mark' => 'ق',
                    'sort_order' => 35,
                    'ask_quantity' => true,
                    'requires_receipt' => true,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            // Keep quantity visible by default for repair work.
            if (class_exists(AppSetting::class)) {
                AppSetting::setValue('daily_log_show_quantity', '1');
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('daily_log_entries') && Schema::hasColumn('daily_log_entries', 'reception_id')) {
            Schema::table('daily_log_entries', function (Blueprint $table) {
                $table->dropConstrainedForeignId('reception_id');
            });
        }
        if (Schema::hasTable('daily_log_categories') && Schema::hasColumn('daily_log_categories', 'requires_receipt')) {
            Schema::table('daily_log_categories', function (Blueprint $table) {
                $table->dropColumn('requires_receipt');
            });
        }
    }
};
