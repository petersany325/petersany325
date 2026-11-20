<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('desktop_devices')) {
            Schema::table('desktop_devices', function (Blueprint $table) {
                if (! Schema::hasColumn('desktop_devices', 'prefix')) {
                    $table->string('prefix', 12)->nullable();
                }
                if (! Schema::hasColumn('desktop_devices', 'scopes')) {
                    $table->json('scopes')->nullable();
                }
                if (! Schema::hasColumn('desktop_devices', 'last_ip')) {
                    $table->string('last_ip', 64)->nullable();
                }
                if (! Schema::hasColumn('desktop_devices', 'expires_at')) {
                    $table->timestamp('expires_at')->nullable();
                }
            });
        }

        if (! Schema::hasTable('desktop_sync_map')) {
            Schema::create('desktop_sync_map', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('token_id')->index();
                $table->string('table_name', 64);
                $table->unsignedBigInteger('row_id');
                $table->string('desktop_uid', 80);
                $table->string('last_origin', 16)->default('desktop');
                $table->timestamp('last_synced_at')->nullable();
                $table->unique(['token_id', 'table_name', 'desktop_uid'], 'desktop_sync_map_uid');
                $table->index(['table_name', 'row_id']);
            });
        }

        if (! Schema::hasTable('desktop_sync_tombstones')) {
            Schema::create('desktop_sync_tombstones', function (Blueprint $table) {
                $table->id();
                $table->string('table_name', 64);
                $table->unsignedBigInteger('row_id');
                $table->timestamp('deleted_at')->index();
            });
        }

        if (! Schema::hasTable('desktop_sync_receipts')) {
            Schema::create('desktop_sync_receipts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('token_id');
                $table->uuid('batch_id');
                $table->string('desktop_uid', 80);
                $table->json('result');
                $table->timestamp('created_at')->nullable();
                $table->unique(['token_id', 'batch_id', 'desktop_uid'], 'desktop_sync_receipts_item');
            });
        }

        if (! Schema::hasTable('desktop_sync_log')) {
            Schema::create('desktop_sync_log', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('token_id')->nullable()->index();
                $table->string('endpoint', 32);
                $table->string('table_name', 64)->nullable();
                $table->unsignedBigInteger('row_id')->nullable();
                $table->string('op', 16)->nullable();
                $table->string('status', 24)->nullable();
                $table->json('before')->nullable();
                $table->json('after')->nullable();
                $table->string('ip', 64)->nullable();
                $table->timestamp('created_at')->nullable()->index();
            });
        }

        foreach ([
            'customers', 'referral_sources', 'receptions', 'reception_status_logs',
            'reception_work_reports', 'reception_parts', 'reception_cost_stages',
            'cost_approvals', 'device_handoffs', 'payments', 'payment_receipts',
            'customer_messages', 'users', 'technicians', 'interns', 'lookup_options',
            'fault_types', 'daily_log_categories', 'daily_log_entries', 'sms_status_rules',
            'app_settings', 'parts',
        ] as $name) {
            if (! Schema::hasTable($name) || ! Schema::hasColumn($name, 'updated_at')) {
                continue;
            }
            try {
                Schema::table($name, function (Blueprint $table) use ($name) {
                    $table->index('updated_at', $name.'_updated_at_idx');
                });
            } catch (\Throwable) {
                // Index already exists on this install.
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('desktop_sync_log');
        Schema::dropIfExists('desktop_sync_receipts');
        Schema::dropIfExists('desktop_sync_tombstones');
        Schema::dropIfExists('desktop_sync_map');

        if (Schema::hasTable('desktop_devices')) {
            Schema::table('desktop_devices', function (Blueprint $table) {
                foreach (['prefix', 'scopes', 'last_ip', 'expires_at'] as $column) {
                    if (Schema::hasColumn('desktop_devices', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
