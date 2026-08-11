<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receptions', function (Blueprint $table) {
            $table->date('sale_date')->nullable()->after('warranty_end_date');
        });
    }

    public function down(): void
    {
        Schema::table('receptions', function (Blueprint $table) {
            $table->dropColumn('sale_date');
        });
    }
};
