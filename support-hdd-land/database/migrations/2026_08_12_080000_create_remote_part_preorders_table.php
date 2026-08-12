<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remote_part_preorders', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('part_title', 160);
            $table->text('description')->nullable();
            $table->string('tracking_code', 80)->nullable();
            $table->string('origin_city', 80)->nullable();
            $table->string('serial_number', 120)->nullable();
            $table->string('brand_model', 160)->nullable();
            $table->string('status', 32)->default('pending_arrival')->index();
            $table->json('photos')->nullable();
            $table->text('admin_note')->nullable();
            $table->string('match_result', 32)->nullable();
            $table->timestamp('arrived_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reception_id')->nullable()->constrained('receptions')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remote_part_preorders');
    }
};
