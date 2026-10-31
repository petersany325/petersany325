<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'permissions')) {
                $table->json('permissions')->nullable()->after('role');
            }
            if (! Schema::hasColumn('users', 'can_login_otp')) {
                $table->boolean('can_login_otp')->default(true)->after('permissions');
            }
            if (! Schema::hasColumn('users', 'can_login_password')) {
                $table->boolean('can_login_password')->default(true)->after('can_login_otp');
            }
        });

        // Make email nullable for OTP-only staff (MySQL / SQLite compatible)
        try {
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE users MODIFY email VARCHAR(255) NULL');
            }
        } catch (\Throwable $e) {
            // ignore if already nullable
        }

        try {
            Schema::table('users', function (Blueprint $table) {
                $table->unique('phone');
            });
        } catch (\Throwable $e) {
            // index may already exist
        }

        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('login_otps', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20)->index();
            $table->string('code', 10);
            $table->timestamp('expires_at');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_otps');
        Schema::dropIfExists('app_settings');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['permissions', 'can_login_otp', 'can_login_password']);
        });
    }
};
