<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Protect remote-part preorders (and their photos) from cascade-delete
 * when a customer record is removed.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('remote_part_preorders')) {
            return;
        }

        // Drop existing FK (name may vary by MySQL version)
        $this->dropCustomerForeign();

        Schema::table('remote_part_preorders', function (Blueprint $table) {
            $table->foreign('customer_id')
                ->references('id')
                ->on('customers')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('remote_part_preorders')) {
            return;
        }

        $this->dropCustomerForeign();

        Schema::table('remote_part_preorders', function (Blueprint $table) {
            $table->foreign('customer_id')
                ->references('id')
                ->on('customers')
                ->cascadeOnDelete();
        });
    }

    private function dropCustomerForeign(): void
    {
        try {
            Schema::table('remote_part_preorders', function (Blueprint $table) {
                $table->dropForeign(['customer_id']);
            });

            return;
        } catch (Throwable $e) {
            // Fallback: discover constraint name on MySQL
        }

        try {
            $db = Schema::getConnection()->getDatabaseName();
            $row = DB::selectOne(
                'SELECT CONSTRAINT_NAME AS name FROM information_schema.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL
                 LIMIT 1',
                [$db, 'remote_part_preorders', 'customer_id']
            );
            $name = is_object($row) ? (string) ($row->name ?? '') : '';
            if ($name !== '') {
                DB::statement('ALTER TABLE remote_part_preorders DROP FOREIGN KEY `'.$name.'`');
            }
        } catch (Throwable $e) {
            // leave as-is; migrate will fail loudly if FK still blocks
        }
    }
};
