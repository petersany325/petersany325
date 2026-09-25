<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_licenses', function (Blueprint $table) {
            if (! Schema::hasColumn('product_licenses', 'org_name')) {
                $table->string('org_name', 160)->nullable()->after('customer_name')->index();
            }
            if (! Schema::hasColumn('product_licenses', 'network_visible')) {
                $table->boolean('network_visible')->default(true)->after('status')->index();
            }
        });

        // Backfill: use customer_name as default public org name for network search.
        if (Schema::hasColumn('product_licenses', 'org_name')) {
            DB::table('product_licenses')
                ->whereNull('org_name')
                ->whereNotNull('customer_name')
                ->where('customer_name', '!=', '')
                ->update(['org_name' => DB::raw('customer_name')]);
        }

        Schema::table('partners', function (Blueprint $table) {
            if (! Schema::hasColumn('partners', 'org_name')) {
                $table->string('org_name', 160)->nullable()->after('shop_name')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_licenses', function (Blueprint $table) {
            foreach (['org_name', 'network_visible'] as $col) {
                if (Schema::hasColumn('product_licenses', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
        Schema::table('partners', function (Blueprint $table) {
            if (Schema::hasColumn('partners', 'org_name')) {
                $table->dropColumn('org_name');
            }
        });
    }
};
