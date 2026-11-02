<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('technicians', function (Blueprint $table) {
            if (! Schema::hasColumn('technicians', 'monthly_salary')) {
                $table->unsignedBigInteger('monthly_salary')->default(0)->after('commission_percent');
            }
        });
    }

    public function down(): void
    {
        Schema::table('technicians', function (Blueprint $table) {
            if (Schema::hasColumn('technicians', 'monthly_salary')) {
                $table->dropColumn('monthly_salary');
            }
        });
    }
};
