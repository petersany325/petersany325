<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receptions', function (Blueprint $table) {
            if (! Schema::hasColumn('receptions', 'partner_receipt_role')) {
                $table->string('partner_receipt_role', 20)->nullable()->after('partner_flow')->index();
            }
            if (! Schema::hasColumn('receptions', 'partner_primary_reception_id')) {
                $table->foreignId('partner_primary_reception_id')->nullable()->after('partner_receipt_role')
                    ->constrained('receptions')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('receptions', function (Blueprint $table) {
            if (Schema::hasColumn('receptions', 'partner_primary_reception_id')) {
                $table->dropConstrainedForeignId('partner_primary_reception_id');
            }
            if (Schema::hasColumn('receptions', 'partner_receipt_role')) {
                $table->dropColumn('partner_receipt_role');
            }
        });
    }
};
