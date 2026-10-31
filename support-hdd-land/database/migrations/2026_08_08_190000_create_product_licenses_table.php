<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_licenses', function (Blueprint $table) {
            $table->id();
            $table->string('license_key', 64)->unique();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone', 30)->nullable();
            $table->string('domain')->nullable()->index();
            $table->string('product', 60)->default('hddland-repair');
            $table->string('status', 20)->default('unused')->index(); // unused|active|revoked|expired
            $table->string('token', 128)->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_check_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_licenses');
    }
};
