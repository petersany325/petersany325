<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_licenses', function (Blueprint $table) {
            if (! Schema::hasColumn('product_licenses', 'customer_email')) {
                $table->string('customer_email', 160)->nullable()->after('customer_phone');
            }
            if (! Schema::hasColumn('product_licenses', 'notes')) {
                $table->text('notes')->nullable()->after('meta');
            }
            if (! Schema::hasColumn('product_licenses', 'check_count')) {
                $table->unsignedInteger('check_count')->default(0)->after('last_check_at');
            }
            if (! Schema::hasColumn('product_licenses', 'last_check_ip')) {
                $table->string('last_check_ip', 45)->nullable()->after('check_count');
            }
            if (! Schema::hasColumn('product_licenses', 'last_check_version')) {
                $table->string('last_check_version', 30)->nullable()->after('last_check_ip');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_licenses', function (Blueprint $table) {
            foreach (['customer_email', 'notes', 'check_count', 'last_check_ip', 'last_check_version'] as $col) {
                if (Schema::hasColumn('product_licenses', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
