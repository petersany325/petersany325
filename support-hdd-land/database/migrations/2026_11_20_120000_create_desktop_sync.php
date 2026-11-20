<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /** @var list<string> */
    private array $tables = [
        'referral_sources',
        'fault_types',
        'lookup_options',
        'technicians',
        'customers',
        'parts',
        'receptions',
        'payments',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('desktop_devices')) {
            Schema::create('desktop_devices', function (Blueprint $table) {
                $table->id();
                $table->string('name', 80);
                $table->string('token_hash', 64)->unique();
                $table->timestamp('last_seen_at')->nullable();
                $table->unsignedBigInteger('last_cursor')->default(0);
                $table->timestamp('revoked_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('sync_changes')) {
            Schema::create('sync_changes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('desktop_device_id')->nullable()->index();
                $table->string('resource', 40)->index();
                $table->string('sync_key', 64)->index();
                $table->string('op', 16);
                $table->json('payload');
                $table->timestamp('record_updated_at')->nullable();
                $table->timestamp('created_at')->nullable();
            });
        }

        foreach ($this->tables as $name) {
            if (! Schema::hasTable($name) || Schema::hasColumn($name, 'sync_key')) {
                continue;
            }
            Schema::table($name, function (Blueprint $table) {
                $table->string('sync_key', 64)->nullable()->unique();
            });
            DB::table($name)->whereNull('sync_key')->orderBy('id')->chunkById(200, function ($rows) use ($name) {
                foreach ($rows as $row) {
                    DB::table($name)->where('id', $row->id)->update([
                        'sync_key' => (string) Str::uuid(),
                    ]);
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_changes');
        Schema::dropIfExists('desktop_devices');

        foreach ($this->tables as $name) {
            if (Schema::hasTable($name) && Schema::hasColumn($name, 'sync_key')) {
                Schema::table($name, function (Blueprint $table) {
                    $table->dropUnique([$name.'_sync_key_unique']);
                    $table->dropColumn('sync_key');
                });
            }
        }
    }
};
