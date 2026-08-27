<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\CostApproval;
use App\Models\Reception;
use App\Models\SmsLog;
use App\Models\SmsStatusRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use App\Support\CostApprovalSettings;
use App\Services\ReceptionLifecycleService;

class CostApprovalService
{
    public const LINK_TTL_HOURS = 48;

    public function __construct(
        private NiazpardazSmsService $sms,
        private SmsNotificationService $smsNotifications,
        private StaffNotifier $notifier,
    ) {
    }

    public function defaultTerms(): string
    {
        $custom = AppSetting::getValue('cost_approval_terms');
        if (is_string($custom) && trim($custom) !== '') {
            return $custom;
        }

        $shop = AppSetting::getValue('invoice_shop_name', (string) config('app.name', 'تعمیرگاه'));

        return "با تأیید این مبلغ، اجازه می‌دهم تعمیرگاه {$shop} طبق شرح کار اعلام‌شده اقدام کند.\n"
            ."تأیید هزینه به‌معنی ضمانت موفقیت بازیابی/تعمیر نیست.\n"
            ."اگر مبلغ نهایی از این رقم بیشتر شود، تأیید جداگانه لازم است.";
    }

    public function receptionRequiresApproval(Reception $reception): bool
    {
        return CostApprovalSettings::receptionRequiresApproval($reception);
    }

    /**
     * Create a new approval version, supersede older open ones, send SMS with one-time link.
     *
     * @return array{ok:bool,message:string,approval?:CostApproval,url?:string,log?:SmsLog}
     */
    public function requestAndSend(
        Reception $reception,
        ?string $description = null,
        bool $sendSms = true,
        bool $force = false,
        ?\App\Models\ReceptionCostStage $stage = null
    ): array
    {
        $reception->loadMissing(['customer', 'faultType', 'technician', 'parts', 'costStages']);

        if (! $force && ! $this->receptionRequiresApproval($reception) && ! $stage) {
            return [
                'ok' => false,
                'message' => 'این خدمت در فهرست مشمول تأیید هزینه نیست. از منوی «تأیید هزینه → خدمات مشمول» تنظیم کنید یا با اجبار ارسال کنید.',
            ];
        }
        if (! $stage && ! $reception->hasCostSet() && (int) $reception->estimated_cost <= 0) {
            return ['ok' => false, 'message' => 'ابتدا مبلغ/برآورد هزینه را روی قبض ثبت کنید.'];
        }
        if ($stage && (int) $stage->amount <= 0) {
            return ['ok' => false, 'message' => 'مبلغ مرحله هزینه باید بیشتر از صفر باشد.'];
        }

        if (! $reception->customer?->phone) {
            return ['ok' => false, 'message' => 'شماره موبایل مشتری برای ارسال لینک موجود نیست.'];
        }

        $plainToken = Str::random(48);
        if ($stage) {
            $amount = (int) $stage->amount;
        } else {
            $amount = (int) $reception->total_amount > 0
                ? (int) $reception->total_amount
                : (int) $reception->estimated_cost;
        }

        $approval = DB::transaction(function () use ($reception, $description, $plainToken, $amount, $stage) {
            $openQuery = CostApproval::query()
                ->where('reception_id', $reception->id)
                ->whereIn('status', [CostApproval::STATUS_DRAFT, CostApproval::STATUS_SENT, CostApproval::STATUS_VIEWED]);

            if ($stage) {
                $openQuery->where(function ($q) use ($stage) {
                    $q->where('reception_cost_stage_id', $stage->id)
                        ->orWhere(function ($q2) use ($stage) {
                            $q2->whereNull('reception_cost_stage_id')
                                ->where('stage_key', $stage->stage_key);
                        });
                });
            } else {
                $openQuery->whereNull('reception_cost_stage_id');
            }

            $openQuery->update(['status' => CostApproval::STATUS_SUPERSEDED]);

            $version = (int) CostApproval::query()->where('reception_id', $reception->id)->max('version') + 1;
            $desc = trim((string) ($description
                ?: ($stage?->stage_label)
                ?: $reception->final_fault
                ?: $reception->reported_fault
                ?: $reception->technician_notes));

            $approval = CostApproval::create([
                'reception_id' => $reception->id,
                'customer_id' => $reception->customer_id,
                'created_by' => Auth::id(),
                'version' => max(1, $version),
                'reception_cost_stage_id' => $stage?->id,
                'stage_key' => $stage?->stage_key,
                'amount' => $amount,
                'labor_cost' => (int) $reception->labor_cost,
                'parts_cost' => (int) $reception->parts_cost,
                'discount' => (int) $reception->discount,
                'description' => $desc !== '' ? $desc : 'اعلام هزینه تعمیر / بازیابی',
                'terms_text' => $this->defaultTerms(),
                'status' => CostApproval::STATUS_SENT,
                'token_hash' => hash('sha256', $plainToken),
                'approval_code' => 'APP-'.strtoupper(Str::random(5)),
                'expires_at' => now()->addHours(self::LINK_TTL_HOURS),
                'sent_at' => now(),
                'snapshot' => [
                    'ticket_no' => $reception->ticket_no,
                    'receipt_no' => $reception->receipt_no,
                    'product_name' => $reception->product_name,
                    'brand' => $reception->brand,
                    'model' => $reception->model,
                    'serial_number' => $reception->serial_number,
                    'customer_name' => $reception->customer?->name,
                    'customer_phone' => $reception->customer?->phone,
                    'stages_cost' => (int) $reception->stages_cost,
                    'stage' => $stage ? [
                        'id' => $stage->id,
                        'key' => $stage->stage_key,
                        'label' => $stage->stage_label,
                        'amount' => (int) $stage->amount,
                    ] : null,
                    'cost_stages' => $reception->costStages->map(fn ($s) => [
                        'label' => $s->stage_label,
                        'amount' => (int) $s->amount,
                        'status' => $s->status,
                    ])->values()->all(),
                    'parts' => $reception->parts->map(fn ($p) => [
                        'name' => $p->part_name,
                        'qty' => $p->quantity,
                        'total' => $p->total_price,
                    ])->values()->all(),
                ],
            ]);

            if ($stage) {
                $stage->forceFill([
                    'status' => 'pending_approval',
                    'cost_approval_id' => $approval->id,
                ])->save();
            }

            $reception->forceFill([
                'cost_approval_status' => CostApproval::STATUS_SENT,
            ])->save();

            return $approval;
        });

        $url = url('/a/'.$plainToken);

        $smsResult = ['ok' => false, 'message' => 'ارسال پیامک انجام نشد.', 'log' => null];
        if ($sendSms) {
            $smsResult = $this->sendApprovalSms($reception, $approval, $url);
        }

        $msg = $stage
            ? 'لینک تأیید مرحله «'.$stage->stage_label.'» ساخته شد (نسخه '.$approval->version.').'
            : 'لینک تأیید هزینه ساخته شد (نسخه '.$approval->version.').';
        if ($sendSms) {
            $msg .= ($smsResult['ok'] ?? false)
                ? ' پیامک لینک برای مشتری ارسال شد.'
                : ' پیامک ناموفق: '.($smsResult['message'] ?? '');
        }

        return [
            'ok' => true,
            'message' => $msg,
            'approval' => $approval->fresh(),
            'url' => $url,
            'log' => $smsResult['log'] ?? null,
            'sms_ok' => (bool) ($smsResult['ok'] ?? false),
        ];
    }

    public function findByPlainToken(string $token): ?CostApproval
    {
        if ($token === '' || strlen($token) < 20) {
            return null;
        }

        return CostApproval::query()
            ->with(['reception.customer', 'reception.parts', 'customer'])
            ->where('token_hash', hash('sha256', $token))
            ->first();
    }

    public function markViewed(CostApproval $approval, Request $request): CostApproval
    {
        if ($approval->isExpired() && $approval->status !== CostApproval::STATUS_EXPIRED) {
            $approval->forceFill(['status' => CostApproval::STATUS_EXPIRED])->save();
            $approval->reception?->forceFill(['cost_approval_status' => CostApproval::STATUS_EXPIRED])->save();

            return $approval->fresh(['reception', 'customer']) ?? $approval;
        }

        if (in_array($approval->status, [CostApproval::STATUS_SENT, CostApproval::STATUS_VIEWED], true)) {
            $payload = [
                'viewer_ip' => $request->ip(),
                'viewer_ua' => Str::limit((string) $request->userAgent(), 500, ''),
            ];
            if (! $approval->viewed_at) {
                $payload['viewed_at'] = now();
            }
            if ($approval->status === CostApproval::STATUS_SENT) {
                $payload['status'] = CostApproval::STATUS_VIEWED;
            }
            $approval->forceFill($payload)->save();
            $approval->reception?->forceFill(['cost_approval_status' => CostApproval::STATUS_VIEWED])->save();
        }

        return $approval->fresh(['reception.customer', 'reception.parts', 'customer']) ?? $approval;
    }

    /**
     * @return array{ok:bool,message:string,approval?:CostApproval}
     */
    public function approve(CostApproval $approval, Request $request): array
    {
        if ($approval->isExpired()) {
            $approval->forceFill(['status' => CostApproval::STATUS_EXPIRED])->save();

            return ['ok' => false, 'message' => 'این لینک منقضی شده است. از تعمیرگاه لینک جدید بخواهید.'];
        }

        if (! $approval->canDecide()) {
            return ['ok' => false, 'message' => 'این لینک دیگر قابل تأیید نیست (قبلاً استفاده یا باطل شده).'];
        }

        $approval->forceFill([
            'status' => CostApproval::STATUS_APPROVED,
            'decided_at' => now(),
            'decision_ip' => $request->ip(),
            'decision_ua' => Str::limit((string) $request->userAgent(), 500, ''),
            'viewed_at' => $approval->viewed_at ?: now(),
        ])->save();

        $reception = $approval->reception;
        if ($reception) {
            $reception->forceFill([
                'cost_approval_status' => CostApproval::STATUS_APPROVED,
                'customer_cost_approved_at' => now(),
                'customer_cost_approved_amount' => (int) $approval->amount,
                'cost_confirmed_at' => $reception->cost_confirmed_at ?: now(),
            ])->save();
        }

        $this->syncLinkedStage($approval, 'approved');

        try {
            app(ReceptionLifecycleService::class)->log(
                $reception ?? $approval->reception,
                $reception?->status ?? 'ready',
                'cost_stage',
                $reception?->status,
                'تأیید مشتری روی هزینه'.($approval->stage_key ? ' (مرحله)' : ''),
                number_format((int) $approval->amount).' تومان — کد '.$approval->approval_code
            );
        } catch (\Throwable) {
        }

        try {
            $this->notifier->notifyMany(
                $this->notifier->deskUsers(),
                'cost_approved',
                'تأیید هزینه مشتری',
                sprintf(
                    'قبض %s — مبلغ %s تومان تأیید شد (کد %s)',
                    $reception?->ticket_no ?? '—',
                    number_format((int) $approval->amount),
                    $approval->approval_code
                ),
                $reception ? route('receptions.show', $reception) : null,
                ['cost_approval_id' => $approval->id, 'reception_id' => $reception?->id]
            );
        } catch (\Throwable) {
        }

        return [
            'ok' => true,
            'message' => 'هزینه با موفقیت تأیید شد.',
            'approval' => $approval->fresh(['reception', 'customer']),
        ];
    }

    /**
     * @return array{ok:bool,message:string,approval?:CostApproval}
     */
    public function reject(CostApproval $approval, Request $request, ?string $reason = null): array
    {
        if ($approval->isExpired()) {
            $approval->forceFill(['status' => CostApproval::STATUS_EXPIRED])->save();

            return ['ok' => false, 'message' => 'این لینک منقضی شده است.'];
        }

        if (! $approval->canDecide()) {
            return ['ok' => false, 'message' => 'این لینک دیگر قابل رد نیست.'];
        }

        $approval->forceFill([
            'status' => CostApproval::STATUS_REJECTED,
            'decided_at' => now(),
            'reject_reason' => $reason ? Str::limit($reason, 500, '') : null,
            'decision_ip' => $request->ip(),
            'decision_ua' => Str::limit((string) $request->userAgent(), 500, ''),
            'viewed_at' => $approval->viewed_at ?: now(),
        ])->save();

        $reception = $approval->reception;
        $reception?->forceFill(['cost_approval_status' => CostApproval::STATUS_REJECTED])->save();

        $this->syncLinkedStage($approval, 'rejected');

        try {
            app(ReceptionLifecycleService::class)->log(
                $reception ?? $approval->reception,
                $reception?->status ?? 'repairing',
                'cost_stage',
                $reception?->status,
                'رد هزینه توسط مشتری',
                $reason
            );
        } catch (\Throwable) {
        }

        try {
            $this->notifier->notifyMany(
                $this->notifier->deskUsers(),
                'cost_rejected',
                'رد هزینه توسط مشتری',
                sprintf(
                    'قبض %s — مبلغ %s رد شد%s',
                    $reception?->ticket_no ?? '—',
                    number_format((int) $approval->amount),
                    $reason ? (' — '.$reason) : ''
                ),
                $reception ? route('receptions.show', $reception) : null,
                ['cost_approval_id' => $approval->id]
            );
        } catch (\Throwable) {
        }

        return [
            'ok' => true,
            'message' => 'رد هزینه ثبت شد.',
            'approval' => $approval->fresh(['reception', 'customer']),
        ];
    }

    private function syncLinkedStage(CostApproval $approval, string $stageStatus): void
    {
        $stage = null;
        if ($approval->reception_cost_stage_id) {
            $stage = \App\Models\ReceptionCostStage::query()->find($approval->reception_cost_stage_id);
        }

        if (! $stage) {
            return;
        }

        $stage->forceFill([
            'status' => $stageStatus,
            'approved_at' => $stageStatus === 'approved' ? now() : $stage->approved_at,
            'cost_approval_id' => $approval->id,
        ])->save();

        $approval->reception?->recalculateTotals();
    }

    private function sendApprovalSms(Reception $reception, CostApproval $approval, string $url): array
    {
        if (! $this->smsNotifications->masterEnabled()) {
            return ['ok' => false, 'message' => 'ارسال پیامک در سطح سیستم خاموش است.'];
        }

        $rule = SmsStatusRule::findOnPrice();
        $phone = $reception->customer?->phone;
        if (! $phone) {
            return ['ok' => false, 'message' => 'شماره موبایل مشتری موجود نیست.'];
        }

        $shop = AppSetting::getValue('invoice_shop_name', (string) config('app.name', 'تعمیرگاه'));
        $template = $rule?->message_template;
        if (! $template || ! str_contains($template, '{approval_url}')) {
            $template = "سلام {customer_name}\nقبض {ticket_no}\nسریال {serial}\nمبلغ پیشنهادی: {amount} تومان\nبرای تأیید هزینه:\n{approval_url}\n{$shop}";
        }

        $map = $this->smsNotifications->placeholderMap(
            $rule ?: new SmsStatusRule(['title' => 'تأیید هزینه', 'status_key' => 'cost_approval']),
            $reception
        );
        $map['{amount}'] = number_format((int) $approval->amount);
        $map['{price}'] = $map['{amount}'];
        $map['{approval_url}'] = $url;
        $map['{approval_code}'] = $approval->approval_code ?? '';
        $message = strtr($template, $map);

        $result = $this->sms->send($phone, $message);
        $log = SmsLog::create([
            'reception_id' => $reception->id,
            'customer_id' => $reception->customer_id,
            'sms_status_rule_id' => $rule?->id,
            'cost_approval_id' => $approval->id,
            'sent_by' => Auth::id(),
            'phone' => $phone,
            'status_key' => 'cost_approval',
            'audience' => 'customer',
            'message' => $message,
            'ok' => (bool) ($result['ok'] ?? false),
            'provider_message' => $result['message'] ?? null,
        ]);

        return [
            'ok' => (bool) ($result['ok'] ?? false),
            'message' => $result['message'] ?? '',
            'log' => $log,
        ];
    }
}
