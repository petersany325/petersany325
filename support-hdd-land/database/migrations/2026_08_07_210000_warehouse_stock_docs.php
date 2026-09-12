<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_movements', 'doc_no')) {
                $table->string('doc_no', 40)->nullable()->after('id')->index();
            }
            if (! Schema::hasColumn('stock_movements', 'doc_type')) {
                $table->string('doc_type', 30)->nullable()->after('type')->index();
            }
            if (! Schema::hasColumn('stock_movements', 'unit_cost')) {
                $table->unsignedBigInteger('unit_cost')->default(0)->after('quantity');
            }
            if (! Schema::hasColumn('stock_movements', 'total_cost')) {
                $table->unsignedBigInteger('total_cost')->default(0)->after('unit_cost');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            foreach (['doc_no', 'doc_type', 'unit_cost', 'total_cost'] as $col) {
                if (Schema::hasColumn('stock_movements', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
