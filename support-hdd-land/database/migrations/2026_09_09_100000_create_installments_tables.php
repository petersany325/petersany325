<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('installment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reception_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title')->nullable();
            $table->unsignedBigInteger('total_amount')->default(0);
            $table->unsignedBigInteger('down_payment')->default(0);
            $table->unsignedSmallInteger('installment_count')->default(0);
            $table->unsignedSmallInteger('interval_days')->default(30);
            $table->string('schedule_mode', 20)->default('auto'); // auto|manual
            $table->date('start_date')->nullable();
            $table->string('status', 20)->default('active'); // active|completed|cancelled
            $table->text('notes')->nullable();
            $table->string('guarantor_name')->nullable();
            $table->string('guarantor_phone', 20)->nullable();
            $table->string('guarantor_national_code', 20)->nullable();
            $table->string('guarantor_address')->nullable();
            $table->text('guarantor_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_id', 'status']);
            $table->index('status');
        });

        Schema::create('installment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('installment_plan_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence')->default(1);
            $table->date('due_date');
            $table->unsignedBigInteger('amount')->default(0);
            $table->unsignedBigInteger('paid_amount')->default(0);
            $table->string('status', 20)->default('pending'); // pending|partial|paid|overdue|cancelled
            $table->text('notes')->nullable();
            $table->timestamp('reminded_before_at')->nullable();
            $table->timestamp('reminded_due_at')->nullable();
            $table->json('after_reminders')->nullable();
            $table->timestamps();

            $table->index(['installment_plan_id', 'sequence']);
            $table->index(['due_date', 'status']);
        });

        Schema::create('installment_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('installment_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('installment_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('check_number', 64)->nullable();
            $table->string('bank_name')->nullable();
            $table->string('branch')->nullable();
            $table->string('account_no', 64)->nullable();
            $table->unsignedBigInteger('amount')->default(0);
            $table->date('due_date')->nullable();
            $table->string('holder_name')->nullable();
            $table->string('status', 20)->default('held'); // held|deposited|cleared|bounced|returned
            $table->boolean('is_custody')->default(true); // امانی
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['installment_plan_id', 'status']);
        });

        Schema::create('installment_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('installment_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('installment_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('installment_check_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('amount');
            $table->string('method', 20)->default('cash'); // cash|card|transfer|check
            $table->timestamp('paid_at')->nullable();
            $table->text('note')->nullable();
            $table->boolean('thanks_sms_sent')->default(false);
            $table->timestamps();

            $table->index(['installment_plan_id', 'paid_at']);
            $table->index('installment_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installment_payments');
        Schema::dropIfExists('installment_checks');
        Schema::dropIfExists('installment_items');
        Schema::dropIfExists('installment_plans');
    }
};
