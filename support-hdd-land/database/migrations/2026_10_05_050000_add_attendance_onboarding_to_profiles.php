<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('attendance_profiles', 'onboarding_completed_at')) {
                $table->timestamp('onboarding_completed_at')->nullable()->after('enrolled_by');
            }
            if (! Schema::hasColumn('attendance_profiles', 'phone_gps_lat')) {
                $table->decimal('phone_gps_lat', 10, 7)->nullable()->after('onboarding_completed_at');
            }
            if (! Schema::hasColumn('attendance_profiles', 'phone_gps_lng')) {
                $table->decimal('phone_gps_lng', 10, 7)->nullable()->after('phone_gps_lat');
            }
            if (! Schema::hasColumn('attendance_profiles', 'phone_gps_accuracy_m')) {
                $table->decimal('phone_gps_accuracy_m', 8, 2)->nullable()->after('phone_gps_lng');
            }
            if (! Schema::hasColumn('attendance_profiles', 'phone_gps_captured_at')) {
                $table->timestamp('phone_gps_captured_at')->nullable()->after('phone_gps_accuracy_m');
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendance_profiles', function (Blueprint $table) {
            foreach (['onboarding_completed_at', 'phone_gps_lat', 'phone_gps_lng', 'phone_gps_accuracy_m', 'phone_gps_captured_at'] as $col) {
                if (Schema::hasColumn('attendance_profiles', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
