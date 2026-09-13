<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\PortalInviteBatch;
use App\Models\PortalInviteSend;
use App\Models\SmsLog;
use App\Models\User;
use App\Support\PortalInviteTemplates;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class PortalInviteService
{
    public const CHUNK = 25;

    public function __construct(private NiazpardazSmsService $sms)
    {
    }

    /** @return array{total:int,with_phone:int,invited_ok:int,never_sent:int,last_failed:int} */
    public function stats(): array
    {
        $withPhone = Customer::query()
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->count();

        $invitedOkIds = PortalInviteSend::query()
            ->where('ok', true)
            ->distinct()
            ->pluck('customer_id');

        $lastFailIds = $this->lastFailedCustomerIds();

        return [
            'total' => Customer::query()->count(),
            'with_phone' => $withPhone,
            'invited_ok' => $invitedOkIds->count(),
            'never_sent' => max(0, $withPhone - $invitedOkIds->count()),
            'last_failed' => $lastFailIds->count(),
        ];
    }

    /** @return Collection<int, int> */
    public function recipientIds(string $filter): Collection
    {
        $base = Customer::query()
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->orderBy('id');

        if ($filter === PortalInviteBatch::FILTER_NEVER_SENT) {
            $okIds = PortalInviteSend::query()->where('ok', true)->distinct()->pluck('customer_id');
            if ($okIds->isNotEmpty()) {
                $base->whereNotIn('id', $okIds);
            }
        } elseif ($filter === PortalInviteBatch::FILTER_FAILED) {
            $failIds = $this->lastFailedCustomerIds();
            if ($failIds->isEmpty()) {
                return collect();
            }
            $base->whereIn('id', $failIds);
        }

        return $base->pluck('id');
    }

    /** @return Collection<int, int> customers whose latest invite send is failed */
    public function lastFailedCustomerIds(): Collection
    {
        $latestIds = PortalInviteSend::query()
            ->selectRaw('MAX(id) as id')
            ->groupBy('customer_id')
            ->pluck('id');

        if ($latestIds->isEmpty()) {
            return collect();
        }

        return PortalInviteSend::query()
            ->whereIn('id', $latestIds)
            ->where('ok', false)
            ->pluck('customer_id');
    }

    public function createBatch(string $filter, ?string $template = null): PortalInviteBatch
    {
        $ids = $this->recipientIds($filter)->values()->all();
        $tpl = trim((string) ($template ?? '')) ?: PortalInviteTemplates::template();

        return PortalInviteBatch::query()->create([
            'code' => PortalInviteBatch::nextCode(),
            'filter' => $filter,
            'status' => $ids === [] ? PortalInviteBatch::STATUS_DONE : PortalInviteBatch::STATUS_PENDING,
            'total' => count($ids),
            'sent_ok' => 0,
            'sent_fail' => 0,
            'cursor' => 0,
            'customer_ids' => $ids,
            'template_snapshot' => $tpl,
            'created_by' => Auth::id(),
            'finished_at' => $ids === [] ? now('Asia/Tehran') : null,
        ]);
    }

    /**
     * Process next chunk of a batch.
     *
     * @return array{batch:PortalInviteBatch,processed:int,ok:int,fail:int,done:bool}
     */
    public function processChunk(PortalInviteBatch $batch, int $chunk = self::CHUNK): array
    {
        if ($batch->isFinished()) {
            return ['batch' => $batch, 'processed' => 0, 'ok' => 0, 'fail' => 0, 'done' => true];
        }

        $ids = is_array($batch->customer_ids) ? $batch->customer_ids : [];
        $cursor = (int) $batch->cursor;
        $slice = array_slice($ids, $cursor, max(1, $chunk));

        $batch->forceFill(['status' => PortalInviteBatch::STATUS_RUNNING])->save();

        $ok = 0;
        $fail = 0;
        foreach ($slice as $customerId) {
            $customer = Customer::query()->find((int) $customerId);
            if (! $customer) {
                $fail++;
                continue;
            }
            $result = $this->sendToCustomer($customer, $batch->template_snapshot, $batch->id);
            if ($result['ok']) {
                $ok++;
            } else {
                $fail++;
            }
        }

        $newCursor = $cursor + count($slice);
        $done = $newCursor >= count($ids);

        $batch->forceFill([
            'cursor' => $newCursor,
            'sent_ok' => (int) $batch->sent_ok + $ok,
            'sent_fail' => (int) $batch->sent_fail + $fail,
            'status' => $done ? PortalInviteBatch::STATUS_DONE : PortalInviteBatch::STATUS_RUNNING,
            'finished_at' => $done ? now('Asia/Tehran') : null,
        ])->save();

        return [
            'batch' => $batch->fresh(),
            'processed' => count($slice),
            'ok' => $ok,
            'fail' => $fail,
            'done' => $done,
        ];
    }

    /** @return array{ok:bool,message:string,send:?PortalInviteSend} */
    public function sendToCustomer(Customer $customer, ?string $template = null, ?int $batchId = null): array
    {
        $phone = User::normalizePhone((string) $customer->phone);
        if (! $phone) {
            $send = PortalInviteSend::query()->create([
                'batch_id' => $batchId,
                'customer_id' => $customer->id,
                'phone' => (string) $customer->phone,
                'message' => '',
                'ok' => false,
                'provider_message' => 'شماره موبایل معتبر نیست',
                'sent_by' => Auth::id(),
            ]);

            return ['ok' => false, 'message' => 'شماره موبایل معتبر نیست', 'send' => $send];
        }

        $text = PortalInviteTemplates::render($customer, $template);
        $result = $this->sms->send($phone, $text);

        $log = null;
        try {
            $log = SmsLog::create([
                'customer_id' => $customer->id,
                'sent_by' => Auth::id(),
                'phone' => $phone,
                'status_key' => 'customer_portal_invite',
                'audience' => 'customer',
                'message' => $text,
                'ok' => (bool) ($result['ok'] ?? false),
                'provider_message' => $result['message'] ?? null,
            ]);
        } catch (\Throwable $e) {
        }

        $send = PortalInviteSend::query()->create([
            'batch_id' => $batchId,
            'customer_id' => $customer->id,
            'phone' => $phone,
            'message' => $text,
            'ok' => (bool) ($result['ok'] ?? false),
            'provider_message' => $result['message'] ?? null,
            'sent_by' => Auth::id(),
            'sms_log_id' => $log?->id,
        ]);

        return [
            'ok' => (bool) ($result['ok'] ?? false),
            'message' => (string) ($result['message'] ?? ''),
            'send' => $send,
        ];
    }

    /**
     * ارسال تکی لینک کارتابل با شماره دستی؛ مشتری جدید در صورت نیاز ساخته می‌شود.
     *
     * @return array{ok:bool,message:string,send:?PortalInviteSend,customer:?Customer,created:bool}
     */
    public function sendToManualPhone(string $rawPhone, ?string $name = null, ?string $template = null): array
    {
        $phone = User::normalizePhone($rawPhone);
        if (! $phone || strlen($phone) < 10) {
            return [
                'ok' => false,
                'message' => 'شماره موبایل معتبر نیست',
                'send' => null,
                'customer' => null,
                'created' => false,
            ];
        }

        $customer = Customer::findByPhone($phone);
        $created = false;

        if (! $customer) {
            $displayName = trim((string) $name);
            if ($displayName === '') {
                $displayName = 'مشتری '.$phone;
            }

            $base = $displayName;
            $suffix = 0;
            while (
                Customer::query()
                    ->where('name', $displayName)
                    ->whereNull('deleted_at')
                    ->exists()
            ) {
                $suffix++;
                $displayName = $base.' ('.$suffix.')';
            }

            $customer = Customer::query()->create([
                'name' => $displayName,
                'phone' => $phone,
            ]);
            $created = true;
        } elseif (trim((string) $name) !== '') {
            // اگر نام دستی داده شد و مشتری موجود نام پیش‌فرض دارد، به‌روز کن
            $current = trim((string) $customer->name);
            if ($current === '' || str_starts_with($current, 'مشتری ')) {
                $customer->forceFill(['name' => trim((string) $name)])->save();
            }
        }

        $result = $this->sendToCustomer($customer, $template);

        return [
            'ok' => (bool) ($result['ok'] ?? false),
            'message' => (string) ($result['message'] ?? ''),
            'send' => $result['send'] ?? null,
            'customer' => $customer,
            'created' => $created,
        ];
    }
}
