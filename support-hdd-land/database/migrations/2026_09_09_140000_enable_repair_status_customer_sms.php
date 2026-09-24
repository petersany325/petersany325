<?php

use App\Models\SmsStatusRule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Ensure customer SMS templates stay active for all repair stages,
 * so technicians' status changes notify the customer.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sms_status_rules')) {
            return;
        }

        $defaults = [
            'repairing' => "سلام {customer_name}\nتعمیر دستگاه شما ({device} / سریال {serial}) آغاز شد.\nقبض: {ticket_no}",
            'waiting_part' => "سلام {customer_name}\nدستگاه شما ({device}) منتظر قطعه است.\nقبض: {ticket_no}",
            'ready' => "سلام {customer_name}\nدستگاه شما ({device}) آماده تحویل است.\nقبض: {ticket_no}",
            'unrepairable' => "سلام {customer_name}\nمتأسفانه دستگاه شما ({device}) غیرقابل تعمیر تشخیص داده شد.\nقبض: {ticket_no}",
        ];

        foreach ($defaults as $statusKey => $template) {
            $rule = SmsStatusRule::query()->where('status_key', $statusKey)->orderBy('id')->first();
            if (! $rule) {
                SmsStatusRule::query()->create([
                    'code' => $statusKey,
                    'title' => match ($statusKey) {
                        'repairing' => 'در حال تعمیر',
                        'waiting_part' => 'منتظر قطعه',
                        'ready' => 'آماده تحویل',
                        'unrepairable' => 'غیرقابل تعمیر',
                        default => $statusKey,
                    },
                    'summary' => $statusKey,
                    'status_key' => $statusKey,
                    'stage_type' => 'run',
                    'result_type' => $statusKey === 'unrepairable' ? 'fail' : 'active',
                    'color' => 'blue',
                    'message_template' => $template,
                    'auto_send' => true,
                    'send_coworker' => false,
                    'is_active' => true,
                    'is_hidden' => false,
                    'on_create' => false,
                    'sort_order' => 10,
                ]);
                continue;
            }

            $updates = [
                'is_active' => true,
                'auto_send' => true,
            ];
            if (trim((string) $rule->message_template) === '') {
                $updates['message_template'] = $template;
            }
            $rule->forceFill($updates)->save();
        }
    }

    public function down(): void
    {
        // keep SMS rules
    }
};
