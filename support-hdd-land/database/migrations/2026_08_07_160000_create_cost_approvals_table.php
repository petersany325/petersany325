<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cost_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reception_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->unsignedBigInteger('amount')->default(0);
            $table->unsignedBigInteger('labor_cost')->default(0);
            $table->unsignedBigInteger('parts_cost')->default(0);
            $table->unsignedBigInteger('discount')->default(0);
            $table->string('description', 1000)->nullable();
            $table->text('terms_text')->nullable();
            $table->string('status', 20)->default('draft')->index(); // draft,sent,viewed,approved,rejected,expired,superseded
            $table->string('token_hash', 64)->unique();
            $table->string('approval_code', 32)->nullable()->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->string('reject_reason', 500)->nullable();
            $table->string('viewer_ip', 64)->nullable();
            $table->string('viewer_ua', 500)->nullable();
            $table->string('decision_ip', 64)->nullable();
            $table->string('decision_ua', 500)->nullable();
            $table->json('snapshot')->nullable();
            $table->timestamps();

            $table->index(['reception_id', 'version']);
            $table->index(['reception_id', 'status']);
        });

        Schema::table('receptions', function (Blueprint $table) {
            if (! Schema::hasColumn('receptions', 'cost_approval_status')) {
                $table->string('cost_approval_status', 20)->nullable()->after('cost_confirmed_at');
            }
            if (! Schema::hasColumn('receptions', 'customer_cost_approved_at')) {
                $table->timestamp('customer_cost_approved_at')->nullable()->after('cost_approval_status');
            }
            if (! Schema::hasColumn('receptions', 'customer_cost_approved_amount')) {
                $table->unsignedBigInteger('customer_cost_approved_amount')->nullable()->after('customer_cost_approved_at');
            }
        });

        Schema::table('sms_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('sms_logs', 'cost_approval_id')) {
                $table->foreignId('cost_approval_id')->nullable()->after('sms_status_rule_id')->constrained('cost_approvals')->nullOnDelete();
            }
        });

        // Update price SMS template to include approval link placeholder when missing
        if (Schema::hasTable('sms_status_rules')) {
            $rule = DB::table('sms_status_rules')->where('code', 'price_set')->first();
            if ($rule && ! str_contains((string) $rule->message_template, '{approval_url}')) {
                DB::table('sms_status_rules')->where('id', $rule->id)->update([
                    'message_template' => "سلام {customer_name}\nقبض {ticket_no}\nسریال {serial}\nمبلغ پیشنهادی: {amount} تومان\nبرای تأیید هزینه این لینک را باز کنید:\n{approval_url}\n{shop_name}",
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sms_logs') && Schema::hasColumn('sms_logs', 'cost_approval_id')) {
            Schema::table('sms_logs', function (Blueprint $table) {
                $table->dropConstrainedForeignId('cost_approval_id');
            });
        }

        Schema::dropIfExists('cost_approvals');

        Schema::table('receptions', function (Blueprint $table) {
            foreach (['customer_cost_approved_amount', 'customer_cost_approved_at', 'cost_approval_status'] as $col) {
                if (Schema::hasColumn('receptions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
