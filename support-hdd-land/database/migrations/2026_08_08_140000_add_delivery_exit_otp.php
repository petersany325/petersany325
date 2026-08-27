<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receptions', function (Blueprint $table) {
            if (! Schema::hasColumn('receptions', 'exit_otp_required')) {
                $table->boolean('exit_otp_required')->default(false)->after('settlement_note');
            }
            if (! Schema::hasColumn('receptions', 'exit_otp_verified_at')) {
                $table->timestamp('exit_otp_verified_at')->nullable()->after('exit_otp_required');
            }
            if (! Schema::hasColumn('receptions', 'exit_otp_bypass_reason')) {
                $table->string('exit_otp_bypass_reason')->nullable()->after('exit_otp_verified_at');
            }
        });

        if (! Schema::hasTable('reception_exit_otps')) {
            Schema::create('reception_exit_otps', function (Blueprint $table) {
                $table->id();
                $table->foreignId('reception_id')->constrained('receptions')->cascadeOnDelete();
                $table->string('phone', 20);
                $table->string('code', 10);
                $table->timestamp('expires_at');
                $table->unsignedTinyInteger('attempts')->default(0);
                $table->timestamp('verified_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['reception_id', 'verified_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reception_exit_otps');
        Schema::table('receptions', function (Blueprint $table) {
            foreach (['exit_otp_bypass_reason', 'exit_otp_verified_at', 'exit_otp_required'] as $col) {
                if (Schema::hasColumn('receptions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
