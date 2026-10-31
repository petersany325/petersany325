<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndexIfMissing('receptions', 'receptions_receipt_no_index', function (Blueprint $table) {
            $table->index('receipt_no', 'receptions_receipt_no_index');
        });
        $this->addIndexIfMissing('receptions', 'receptions_status_index', function (Blueprint $table) {
            $table->index('status', 'receptions_status_index');
        });
        $this->addIndexIfMissing('receptions', 'receptions_status_id_index', function (Blueprint $table) {
            $table->index(['status', 'id'], 'receptions_status_id_index');
        });
        if (Schema::hasColumn('receptions', 'custody') && Schema::hasColumn('receptions', 'custody_technician_id')) {
            $this->addIndexIfMissing('receptions', 'receptions_custody_tech_index', function (Blueprint $table) {
                $table->index(['custody', 'custody_technician_id'], 'receptions_custody_tech_index');
            });
        }
        $this->addIndexIfMissing('payments', 'payments_paid_at_index', function (Blueprint $table) {
            $table->index('paid_at', 'payments_paid_at_index');
        });
    }

    public function down(): void
    {
        foreach ([
            ['receptions', 'receptions_receipt_no_index'],
            ['receptions', 'receptions_status_index'],
            ['receptions', 'receptions_status_id_index'],
            ['receptions', 'receptions_custody_tech_index'],
            ['payments', 'payments_paid_at_index'],
        ] as [$table, $index]) {
            try {
                Schema::table($table, function (Blueprint $blueprint) use ($index) {
                    $blueprint->dropIndex($index);
                });
            } catch (\Throwable $e) {
            }
        }
    }

    private function addIndexIfMissing(string $table, string $index, callable $callback): void
    {
        if ($this->indexExists($table, $index)) {
            return;
        }
        try {
            Schema::table($table, $callback);
        } catch (\Throwable $e) {
            // Index may already exist under another name; ignore to keep deploy safe.
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        try {
            $db = DB::getDatabaseName();
            $row = DB::selectOne(
                'SELECT 1 AS ok FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
                [$db, $table, $index]
            );

            return (bool) $row;
        } catch (\Throwable $e) {
            return false;
        }
    }
};
