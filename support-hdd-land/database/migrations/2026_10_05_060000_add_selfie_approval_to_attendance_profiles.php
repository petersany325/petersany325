<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('attendance_profiles', 'selfie_status')) {
                $table->string('selfie_status', 20)->default('none')->after('reference_photo_path');
            }
            if (! Schema::hasColumn('attendance_profiles', 'face_detected')) {
                $table->boolean('face_detected')->default(false)->after('selfie_status');
            }
            if (! Schema::hasColumn('attendance_profiles', 'selfie_approved_at')) {
                $table->timestamp('selfie_approved_at')->nullable()->after('face_detected');
            }
            if (! Schema::hasColumn('attendance_profiles', 'selfie_approved_by')) {
                $table->foreignId('selfie_approved_by')->nullable()->after('selfie_approved_at')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('attendance_profiles', 'selfie_reject_reason')) {
                $table->string('selfie_reject_reason', 255)->nullable()->after('selfie_approved_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendance_profiles', function (Blueprint $table) {
            foreach (['selfie_reject_reason', 'selfie_approved_by', 'selfie_approved_at', 'face_detected', 'selfie_status'] as $col) {
                if (Schema::hasColumn('attendance_profiles', $col)) {
                    if ($col === 'selfie_approved_by') {
                        $table->dropConstrainedForeignId('selfie_approved_by');
                    } else {
                        $table->dropColumn($col);
                    }
                }
            }
        });
    }
};
