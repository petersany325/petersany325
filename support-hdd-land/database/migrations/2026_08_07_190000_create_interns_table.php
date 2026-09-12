<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('interns')) {
            Schema::create('interns', function (Blueprint $table) {
                $table->id();
                $table->string('name', 120);
                $table->string('phone', 20);
                $table->string('email', 120)->nullable();
                $table->string('national_code', 20)->nullable();
                $table->date('start_date');
                $table->date('end_date');
                $table->string('department', 120)->nullable();
                $table->text('notes')->nullable();
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['phone']);
                $table->index(['start_date', 'end_date']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('interns');
    }
};
