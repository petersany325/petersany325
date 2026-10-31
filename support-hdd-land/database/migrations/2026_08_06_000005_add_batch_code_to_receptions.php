<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receptions', function (Blueprint $table) {
            if (! Schema::hasColumn('receptions', 'batch_code')) {
                $table->string('batch_code')->nullable()->after('receipt_no')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('receptions', function (Blueprint $table) {
            if (Schema::hasColumn('receptions', 'batch_code')) {
                $table->dropColumn('batch_code');
            }
        });
    }
};
