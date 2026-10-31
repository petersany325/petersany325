<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_status_rules', function (Blueprint $table) {
            $table->id();
            $table->string('code', 60)->unique();
            $table->string('title');
            $table->string('summary', 80)->nullable();
            $table->string('status_key', 60)->index();
            $table->string('stage_type', 20)->default('run'); // run, suspend, done
            $table->string('result_type', 20)->default('active'); // active, success, fail
            $table->string('color', 20)->default('blue');
            $table->string('description', 500)->nullable();
            $table->text('message_template')->nullable();
            $table->boolean('auto_send')->default(true);
            $table->text('coworker_message_template')->nullable();
            $table->boolean('send_coworker')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_hidden')->default(false);
            $table->boolean('on_create')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reception_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sms_status_rule_id')->nullable()->constrained('sms_status_rules')->nullOnDelete();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('phone', 20);
            $table->string('status_key', 60)->nullable();
            $table->string('audience', 20)->default('customer'); // customer, coworker
            $table->text('message');
            $table->boolean('ok')->default(false);
            $table->string('provider_message', 500)->nullable();
            $table->timestamps();
        });

        $defaults = [
            [
                'code' => 'received',
                'title' => 'پذیرش شده',
                'summary' => 'پذیرش',
                'status_key' => 'received',
                'stage_type' => 'run',
                'result_type' => 'active',
                'color' => 'blue',
                'description' => 'دستگاه تازه پذیرش شده',
                'message_template' => "سلام {customer_name}\nدستگاه شما در {shop_name} پذیرش شد.\nنوع: {device}\nسریال: {serial}\nخرابی: {fault}\nقبض: {ticket_no}",
                'auto_send' => true,
                'send_coworker' => false,
                'coworker_message_template' => null,
                'on_create' => true,
                'sort_order' => 1,
            ],
            [
                'code' => 'repairing',
                'title' => 'در حال تعمیر',
                'summary' => 'تعمیر',
                'status_key' => 'repairing',
                'stage_type' => 'run',
                'result_type' => 'active',
                'color' => 'orange',
                'description' => 'دستگاه در حال تعمیر است',
                'message_template' => "سلام {customer_name}\nتعمیر دستگاه شما ({device} / سریال {serial}) آغاز شد.\nقبض: {ticket_no}",
                'auto_send' => true,
                'send_coworker' => false,
                'coworker_message_template' => null,
                'on_create' => false,
                'sort_order' => 2,
            ],
            [
                'code' => 'waiting_part',
                'title' => 'منتظر قطعه',
                'summary' => 'قطعه',
                'status_key' => 'waiting_part',
                'stage_type' => 'suspend',
                'result_type' => 'active',
                'color' => 'amber',
                'description' => 'نیاز به قطعه تشخیص داده شد',
                'message_template' => "سلام {customer_name}\nبرای دستگاه {device} (سریال {serial}) نیاز به قطعه تشخیص داده شد.\nخرابی: {fault}\nقبض: {ticket_no}",
                'auto_send' => true,
                'send_coworker' => true,
                'coworker_message_template' => "قبض {ticket_no} — {customer_name}\nدستگاه {device} سریال {serial}\nوضعیت: نیاز به قطعه\nخرابی: {fault}",
                'on_create' => false,
                'sort_order' => 3,
            ],
            [
                'code' => 'ready',
                'title' => 'تعمیر شده',
                'summary' => 'آماده',
                'status_key' => 'ready',
                'stage_type' => 'done',
                'result_type' => 'success',
                'color' => 'green',
                'description' => 'دستگاه تعمیر و آماده تحویل است',
                'message_template' => "سلام {customer_name}\nدستگاه شما ({device} / سریال {serial}) آماده تحویل است.\nقبض: {ticket_no}",
                'auto_send' => true,
                'send_coworker' => false,
                'coworker_message_template' => null,
                'on_create' => false,
                'sort_order' => 4,
            ],
            [
                'code' => 'unrepairable',
                'title' => 'غیرقابل تعمیر',
                'summary' => 'غیرقابل',
                'status_key' => 'unrepairable',
                'stage_type' => 'done',
                'result_type' => 'fail',
                'color' => 'red',
                'description' => 'دستگاه قابل تعمیر نیست',
                'message_template' => "سلام {customer_name}\nمتاسفانه دستگاه {device} (سریال {serial}) غیرقابل تعمیر تشخیص داده شد.\nقبض: {ticket_no}",
                'auto_send' => true,
                'send_coworker' => false,
                'coworker_message_template' => null,
                'on_create' => false,
                'sort_order' => 5,
            ],
            [
                'code' => 'delivered',
                'title' => 'تحویل شده',
                'summary' => 'تحویل',
                'status_key' => 'delivered',
                'stage_type' => 'done',
                'result_type' => 'success',
                'color' => 'teal',
                'description' => 'دستگاه به مشتری تحویل شد',
                'message_template' => "سلام {customer_name}\nدستگاه {device} (سریال {serial}) تحویل داده شد.\nقبض: {ticket_no}\nاز اعتماد شما سپاسگزاریم.",
                'auto_send' => false,
                'send_coworker' => false,
                'coworker_message_template' => null,
                'on_create' => false,
                'sort_order' => 6,
            ],
            [
                'code' => 'cancelled',
                'title' => 'لغو شده',
                'summary' => 'لغو',
                'status_key' => 'cancelled',
                'stage_type' => 'done',
                'result_type' => 'fail',
                'color' => 'slate',
                'description' => 'پذیرش لغو شد',
                'message_template' => "سلام {customer_name}\nپذیرش دستگاه {device} (سریال {serial}) لغو شد.\nقبض: {ticket_no}",
                'auto_send' => true,
                'send_coworker' => false,
                'coworker_message_template' => null,
                'on_create' => false,
                'sort_order' => 7,
            ],
        ];

        $now = now();
        foreach ($defaults as $row) {
            DB::table('sms_status_rules')->insert(array_merge($row, [
                'is_active' => true,
                'is_hidden' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }

        DB::table('app_settings')->updateOrInsert(
            ['key' => 'sms_master_enabled'],
            ['value' => '1', 'updated_at' => $now, 'created_at' => $now]
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
        Schema::dropIfExists('sms_status_rules');
    }
};
