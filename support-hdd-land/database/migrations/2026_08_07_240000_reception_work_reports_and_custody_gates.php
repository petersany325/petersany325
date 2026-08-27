<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('reception_work_reports')) {
            Schema::create('reception_work_reports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('reception_id')->constrained('receptions')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('technician_id')->nullable()->constrained('technicians')->nullOnDelete();
                $table->string('summary', 500);
                $table->text('details')->nullable();
                $table->boolean('needs_part')->default(false);
                $table->string('result_status', 40)->nullable();
                $table->timestamps();
                $table->index(['reception_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reception_work_reports');
    }
};
