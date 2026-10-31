<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_licenses', function (Blueprint $table) {
            if (! Schema::hasColumn('product_licenses', 'plan_code')) {
                $table->string('plan_code', 30)->nullable()->after('product')->index();
            }
            if (! Schema::hasColumn('product_licenses', 'plan_label')) {
                $table->string('plan_label', 80)->nullable()->after('plan_code');
            }
            if (! Schema::hasColumn('product_licenses', 'plan_months')) {
                $table->unsignedSmallInteger('plan_months')->nullable()->after('plan_label');
            }
            if (! Schema::hasColumn('product_licenses', 'price_toman')) {
                $table->unsignedBigInteger('price_toman')->default(0)->after('plan_months');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_licenses', function (Blueprint $table) {
            foreach (['plan_code', 'plan_label', 'plan_months', 'price_toman'] as $col) {
                if (Schema::hasColumn('product_licenses', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
