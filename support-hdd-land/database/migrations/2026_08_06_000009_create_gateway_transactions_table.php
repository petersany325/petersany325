<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gateway_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('gateway', 32)->default('zarinpal');
            $table->foreignId('reception_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('amount'); // تومان
            $table->string('currency', 8)->default('IRT');
            $table->string('authority', 64)->nullable()->index();
            $table->string('ref_id', 64)->nullable();
            $table->string('card_pan', 32)->nullable();
            $table->string('status', 24)->default('pending')->index(); // pending, paid, failed, cancelled
            $table->string('source', 24)->default('portal'); // portal, staff
            $table->json('meta')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gateway_transactions');
    }
};
