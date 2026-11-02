<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receptions', function (Blueprint $table) {
            if (! Schema::hasColumn('receptions', 'capacity_changed')) {
                $table->boolean('capacity_changed')->default(false)->after('hdd_capacity');
            }
            if (! Schema::hasColumn('receptions', 'hdd_capacity_after')) {
                $table->string('hdd_capacity_after')->nullable()->after('capacity_changed');
            }
        });
    }

    public function down(): void
    {
        Schema::table('receptions', function (Blueprint $table) {
            if (Schema::hasColumn('receptions', 'hdd_capacity_after')) {
                $table->dropColumn('hdd_capacity_after');
            }
            if (Schema::hasColumn('receptions', 'capacity_changed')) {
                $table->dropColumn('capacity_changed');
            }
        });
    }
};
