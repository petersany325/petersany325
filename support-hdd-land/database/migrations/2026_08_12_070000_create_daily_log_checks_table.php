<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('daily_log_checks')) {
            Schema::create('daily_log_checks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->date('work_date');
                $table->string('status', 20)->default('reviewed'); // reviewed | issue
                $table->string('note', 255)->nullable();
                $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('checked_at')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'work_date']);
                $table->index(['work_date', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_log_checks');
    }
};
