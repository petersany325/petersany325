<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('desktop_devices')) {
            Schema::create('desktop_devices', function (Blueprint $table) {
                $table->id();
                $table->string('name', 80);
                $table->string('token_hash', 64)->unique();
                $table->text('token_encrypted')->nullable();
                $table->timestamp('last_seen_at')->nullable();
                $table->unsignedBigInteger('last_cursor')->default(0);
                $table->timestamp('revoked_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->string('prefix', 12)->nullable();
                $table->json('scopes')->nullable();
                $table->string('last_ip', 64)->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('desktop_devices')) {
            $missing = array_values(array_filter(
                ['prefix', 'scopes', 'last_ip', 'expires_at', 'token_encrypted'],
                fn (string $column) => ! Schema::hasColumn('desktop_devices', $column)
            ));
            if ($missing !== []) {
                Schema::table('desktop_devices', function (Blueprint $table) use ($missing) {
                    if (in_array('prefix', $missing, true)) {
                        $table->string('prefix', 12)->nullable();
                    }
                    if (in_array('scopes', $missing, true)) {
                        $table->json('scopes')->nullable();
                    }
                    if (in_array('last_ip', $missing, true)) {
                        $table->string('last_ip', 64)->nullable();
                    }
                    if (in_array('expires_at', $missing, true)) {
                        $table->timestamp('expires_at')->nullable();
                    }
                    if (in_array('token_encrypted', $missing, true)) {
                        $table->text('token_encrypted')->nullable();
                    }
                });
            }
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
