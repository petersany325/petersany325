<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'alias')) {
                $table->string('alias', 120)->nullable()->after('name');
            }
            if (! Schema::hasColumn('customers', 'gender')) {
                $table->string('gender', 20)->nullable()->after('alias');
            }
            if (! Schema::hasColumn('customers', 'is_blacklisted')) {
                $table->boolean('is_blacklisted')->default(false)->after('notes');
            }
            if (! Schema::hasColumn('customers', 'blacklist_reason')) {
                $table->string('blacklist_reason', 500)->nullable()->after('is_blacklisted');
            }
            if (! Schema::hasColumn('customers', 'blacklisted_at')) {
                $table->timestamp('blacklisted_at')->nullable()->after('blacklist_reason');
            }
        });

        Schema::table('receptions', function (Blueprint $table) {
            if (! Schema::hasColumn('receptions', 'lock_code')) {
                $table->string('lock_code', 120)->nullable()->after('serial_number');
            }
        });

        if (! Schema::hasTable('device_blacklists')) {
            Schema::create('device_blacklists', function (Blueprint $table) {
                $table->id();
                $table->string('serial_number', 120)->nullable()->index();
                $table->string('brand', 120)->nullable();
                $table->string('model', 120)->nullable();
                $table->string('reason', 500)->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('device_blacklists');

        Schema::table('receptions', function (Blueprint $table) {
            if (Schema::hasColumn('receptions', 'lock_code')) {
                $table->dropColumn('lock_code');
            }
        });

        Schema::table('customers', function (Blueprint $table) {
            foreach (['alias', 'gender', 'is_blacklisted', 'blacklist_reason', 'blacklisted_at'] as $col) {
                if (Schema::hasColumn('customers', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
