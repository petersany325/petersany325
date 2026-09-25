<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_licenses', function (Blueprint $table) {
            if (! Schema::hasColumn('product_licenses', 'address')) {
                $table->string('address', 500)->nullable()->after('customer_phone');
            }
        });

        Schema::table('partners', function (Blueprint $table) {
            if (! Schema::hasColumn('partners', 'address')) {
                $table->string('address', 500)->nullable()->after('phone');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_licenses', function (Blueprint $table) {
            if (Schema::hasColumn('product_licenses', 'address')) {
                $table->dropColumn('address');
            }
        });
        Schema::table('partners', function (Blueprint $table) {
            if (Schema::hasColumn('partners', 'address')) {
                $table->dropColumn('address');
            }
        });
    }
};
