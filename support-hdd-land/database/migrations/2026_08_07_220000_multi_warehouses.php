<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('warehouses')) {
            Schema::create('warehouses', function (Blueprint $table) {
                $table->id();
                $table->string('code', 30)->nullable()->unique();
                $table->string('name', 120);
                $table->string('location', 180)->nullable();
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->text('note')->nullable();
                $table->timestamps();
            });
        }

        $defaultId = null;
        if (Schema::hasTable('warehouses') && DB::table('warehouses')->count() === 0) {
            $defaultId = DB::table('warehouses')->insertGetId([
                'code' => 'WH-01',
                'name' => 'انبار اصلی',
                'location' => 'تعمیرگاه',
                'is_default' => true,
                'is_active' => true,
                'note' => 'انبار پیش‌فرض سیستم',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $defaultId = DB::table('warehouses')->where('is_default', true)->value('id')
                ?: DB::table('warehouses')->orderBy('id')->value('id');
        }

        if (Schema::hasTable('parts') && ! Schema::hasColumn('parts', 'warehouse_id')) {
            Schema::table('parts', function (Blueprint $table) {
                $table->foreignId('warehouse_id')->nullable()->after('id')->constrained('warehouses')->nullOnDelete();
            });
            if ($defaultId) {
                DB::table('parts')->whereNull('warehouse_id')->update(['warehouse_id' => $defaultId]);
            }
        }

        if (Schema::hasTable('stock_movements') && ! Schema::hasColumn('stock_movements', 'warehouse_id')) {
            Schema::table('stock_movements', function (Blueprint $table) {
                $table->foreignId('warehouse_id')->nullable()->after('part_id')->constrained('warehouses')->nullOnDelete();
            });
            if ($defaultId) {
                DB::table('stock_movements')->whereNull('warehouse_id')->update(['warehouse_id' => $defaultId]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('stock_movements') && Schema::hasColumn('stock_movements', 'warehouse_id')) {
            Schema::table('stock_movements', function (Blueprint $table) {
                $table->dropConstrainedForeignId('warehouse_id');
            });
        }
        if (Schema::hasTable('parts') && Schema::hasColumn('parts', 'warehouse_id')) {
            Schema::table('parts', function (Blueprint $table) {
                $table->dropConstrainedForeignId('warehouse_id');
            });
        }
        Schema::dropIfExists('warehouses');
    }
};
