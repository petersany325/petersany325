<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portal_invite_batches', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('filter', 32)->default('never_sent'); // all|never_sent|failed
            $table->string('status', 16)->default('pending'); // pending|running|done|cancelled
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('sent_ok')->default(0);
            $table->unsignedInteger('sent_fail')->default(0);
            $table->unsignedInteger('cursor')->default(0);
            $table->json('customer_ids')->nullable();
            $table->text('template_snapshot')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        Schema::create('portal_invite_sends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->nullable()->constrained('portal_invite_batches')->nullOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('phone', 32);
            $table->text('message');
            $table->boolean('ok')->default(false);
            $table->string('provider_message', 500)->nullable();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('sms_log_id')->nullable()->constrained('sms_logs')->nullOnDelete();
            $table->timestamps();

            $table->index(['customer_id', 'ok']);
            $table->index(['batch_id', 'ok']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_invite_sends');
        Schema::dropIfExists('portal_invite_batches');
    }
};
