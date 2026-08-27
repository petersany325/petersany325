<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receptions', function (Blueprint $table) {
            if (! Schema::hasColumn('receptions', 'custody')) {
                $table->string('custody')->default('front_desk')->after('status');
            }
            if (! Schema::hasColumn('receptions', 'custody_technician_id')) {
                $table->foreignId('custody_technician_id')->nullable()->after('technician_id')
                    ->constrained('technicians')->nullOnDelete();
            }
        });

        Schema::create('device_handoffs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reception_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('to_technician_id')->nullable()->constrained('technicians')->nullOnDelete();
            $table->string('direction', 32); // to_bench | to_front_desk
            $table->string('serial_snapshot')->nullable();
            $table->string('status', 24)->default('pending'); // pending|accepted|rejected|cancelled
            $table->text('note')->nullable();
            $table->text('response_note')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index(['to_user_id', 'status']);
            $table->index(['reception_id', 'status']);
        });

        Schema::create('customer_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reception_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->string('priority', 16)->default('normal'); // normal|urgent
            $table->timestamp('staff_read_at')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['staff_read_at', 'created_at']);
        });

        Schema::create('staff_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40);
            $table->string('title');
            $table->text('body')->nullable();
            $table->json('data')->nullable();
            $table->string('link')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_notifications');
        Schema::dropIfExists('customer_messages');
        Schema::dropIfExists('device_handoffs');

        Schema::table('receptions', function (Blueprint $table) {
            if (Schema::hasColumn('receptions', 'custody_technician_id')) {
                $table->dropConstrainedForeignId('custody_technician_id');
            }
            if (Schema::hasColumn('receptions', 'custody')) {
                $table->dropColumn('custody');
            }
        });
    }
};
