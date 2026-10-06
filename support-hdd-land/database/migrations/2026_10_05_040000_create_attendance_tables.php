<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('reference_photo_path')->nullable();
            $table->string('device_fingerprint', 191)->nullable();
            $table->timestamp('enrolled_at')->nullable();
            $table->foreignId('enrolled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('attendance_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20); // check_in | check_out
            $table->string('method', 20); // selfie | otp
            $table->string('status', 20)->default('accepted'); // accepted | rejected | flagged
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('accuracy_m', 8, 2)->nullable();
            $table->unsignedInteger('distance_m')->nullable();
            $table->boolean('inside_geofence')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('ip', 64)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('device_fingerprint', 191)->nullable();
            $table->boolean('otp_verified')->default(false);
            $table->string('flag_reason', 255)->nullable();
            $table->text('note')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['user_id', 'occurred_at']);
            $table->index(['type', 'status', 'occurred_at']);
        });

        Schema::create('attendance_otps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('phone', 20);
            $table->string('code', 10);
            $table->string('purpose', 20); // check_in | check_out
            $table->timestamp('expires_at');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'purpose']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_otps');
        Schema::dropIfExists('attendance_events');
        Schema::dropIfExists('attendance_profiles');
    }
};
