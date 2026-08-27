<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receptions', function (Blueprint $table) {
            if (! Schema::hasColumn('receptions', 'settlement_mode')) {
                $table->string('settlement_mode', 24)->nullable()->after('paid_amount');
            }
            if (! Schema::hasColumn('receptions', 'settled_at')) {
                $table->timestamp('settled_at')->nullable()->after('settlement_mode');
            }
            if (! Schema::hasColumn('receptions', 'settlement_note')) {
                $table->string('settlement_note', 500)->nullable()->after('settled_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('receptions', function (Blueprint $table) {
            foreach (['settlement_note', 'settled_at', 'settlement_mode'] as $col) {
                if (Schema::hasColumn('receptions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
