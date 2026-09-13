<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_code')->unique();
            $table->string('pickup_name');
            $table->string('pickup_phone', 20);
            $table->unsignedInteger('ticket_count')->default(0);
            $table->unsignedBigInteger('total_amount')->default(0);
            $table->text('note')->nullable();
            $table->boolean('sms_sent')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });

        Schema::table('receptions', function (Blueprint $table) {
            if (! Schema::hasColumn('receptions', 'delivery_batch_id')) {
                $table->foreignId('delivery_batch_id')->nullable()->after('batch_code')->constrained('delivery_batches')->nullOnDelete();
            }
            if (! Schema::hasColumn('receptions', 'pickup_name')) {
                $table->string('pickup_name')->nullable()->after('delivered_by');
            }
            if (! Schema::hasColumn('receptions', 'pickup_phone')) {
                $table->string('pickup_phone', 20)->nullable()->after('pickup_name');
            }
            if (! Schema::hasColumn('receptions', 'cost_confirmed_at')) {
                $table->timestamp('cost_confirmed_at')->nullable()->after('total_amount');
            }
        });

        if (Schema::hasTable('sms_status_rules')) {
            $exists = DB::table('sms_status_rules')->where('code', 'price_set')->exists();
            if (! $exists) {
                $now = now();
                DB::table('sms_status_rules')->insert([
                    'code' => 'price_set',
                    'title' => 'مبلغ مشخص شد',
                    'summary' => 'مبلغ',
                    'status_key' => 'price_set',
                    'stage_type' => 'run',
                    'result_type' => 'active',
                    'color' => 'amber',
                    'description' => 'پس از مشخص شدن مبلغ قطعه/تعمیر برای مشتری پیامک قیمت ارسال شود',
                    'message_template' => "سلام {customer_name}\nقبض {ticket_no}\nسریال {serial}\nمبلغ تعمیر/قطعه: {amount} تومان\n{shop_name}",
                    'auto_send' => true,
                    'send_coworker' => false,
                    'coworker_message_template' => null,
                    'is_active' => true,
                    'is_hidden' => true,
                    'on_create' => false,
                    'sort_order' => 15,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            // add on_price column if missing
        }

        if (Schema::hasTable('sms_status_rules') && ! Schema::hasColumn('sms_status_rules', 'on_price')) {
            Schema::table('sms_status_rules', function (Blueprint $table) {
                $table->boolean('on_price')->default(false)->after('on_create');
            });
            DB::table('sms_status_rules')->where('code', 'price_set')->update(['on_price' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('receptions', function (Blueprint $table) {
            if (Schema::hasColumn('receptions', 'delivery_batch_id')) {
                $table->dropConstrainedForeignId('delivery_batch_id');
            }
            foreach (['pickup_name', 'pickup_phone', 'cost_confirmed_at'] as $col) {
                if (Schema::hasColumn('receptions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
        Schema::dropIfExists('delivery_batches');
        if (Schema::hasColumn('sms_status_rules', 'on_price')) {
            Schema::table('sms_status_rules', function (Blueprint $table) {
                $table->dropColumn('on_price');
            });
        }
    }
};
