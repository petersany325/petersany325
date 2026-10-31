<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('daily_log_categories')) {
            Schema::create('daily_log_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name', 120);
                $table->string('hint', 255)->nullable();
                $table->string('mark', 8)->default('•');
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('ask_quantity')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('daily_log_entries')) {
            Schema::create('daily_log_entries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->date('work_date');
                $table->foreignId('daily_log_category_id')->nullable()->constrained('daily_log_categories')->nullOnDelete();
                $table->string('category_name', 120)->nullable();
                $table->string('title', 180);
                $table->text('body')->nullable();
                $table->unsignedInteger('quantity')->nullable();
                $table->unsignedInteger('minutes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['user_id', 'work_date']);
                $table->index(['work_date']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_log_entries');
        Schema::dropIfExists('daily_log_categories');
    }
};
