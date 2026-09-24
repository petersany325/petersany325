<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('reception_status_logs')) {
            Schema::create('reception_status_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('reception_id')->constrained('receptions')->cascadeOnDelete();
                $table->string('from_status', 40)->nullable();
                $table->string('to_status', 40);
                $table->string('event_type', 40)->default('status_change');
                $table->string('title', 180)->nullable();
                $table->text('note')->nullable();
                $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->index(['reception_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('reception_cost_stages')) {
            Schema::create('reception_cost_stages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('reception_id')->constrained('receptions')->cascadeOnDelete();
                $table->string('stage_key', 40);
                $table->string('stage_label', 120);
                $table->unsignedBigInteger('amount')->default(0);
                $table->string('status', 30)->default('draft'); // draft|pending_approval|approved|rejected|waived
                $table->unsignedInteger('sort_order')->default(0);
                $table->text('note')->nullable();
                $table->foreignId('cost_approval_id')->nullable()->constrained('cost_approvals')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['reception_id', 'stage_key']);
            });
        }

        Schema::table('receptions', function (Blueprint $table) {
            if (! Schema::hasColumn('receptions', 'discount_reason')) {
                $table->string('discount_reason', 255)->nullable()->after('discount');
            }
            if (! Schema::hasColumn('receptions', 'stages_cost')) {
                $table->unsignedBigInteger('stages_cost')->default(0)->after('parts_cost');
            }
            if (! Schema::hasColumn('receptions', 'delivery_cancelled_at')) {
                $table->timestamp('delivery_cancelled_at')->nullable()->after('delivered_at');
            }
            if (! Schema::hasColumn('receptions', 'delivery_cancel_count')) {
                $table->unsignedInteger('delivery_cancel_count')->default(0)->after('delivery_cancelled_at');
            }
        });

        if (Schema::hasTable('cost_approvals') && ! Schema::hasColumn('cost_approvals', 'reception_cost_stage_id')) {
            Schema::table('cost_approvals', function (Blueprint $table) {
                $table->foreignId('reception_cost_stage_id')->nullable()->after('reception_id')
                    ->constrained('reception_cost_stages')->nullOnDelete();
                $table->string('stage_key', 40)->nullable()->after('reception_cost_stage_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('cost_approvals') && Schema::hasColumn('cost_approvals', 'reception_cost_stage_id')) {
            Schema::table('cost_approvals', function (Blueprint $table) {
                $table->dropConstrainedForeignId('reception_cost_stage_id');
                $table->dropColumn('stage_key');
            });
        }

        Schema::table('receptions', function (Blueprint $table) {
            foreach (['discount_reason', 'stages_cost', 'delivery_cancelled_at', 'delivery_cancel_count'] as $col) {
                if (Schema::hasColumn('receptions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::dropIfExists('reception_cost_stages');
        Schema::dropIfExists('reception_status_logs');
    }
};
