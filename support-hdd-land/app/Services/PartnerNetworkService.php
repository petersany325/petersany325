<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\NetworkReferral;
use App\Models\Partner;
use App\Models\ProductLicense;
use App\Models\Reception;
use App\Support\LicenseStatus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Licensed-shop partner network: auto peers from active licenses + cross-shop receipt referral.
 * Seller hub stores network_referrals; customer shops talk via LICENSE_SERVER API.
 */
class PartnerNetworkService
{
    /** @return array{ok:bool,message:string,count?:int} */
    public function syncPeers(): array
    {
        try {
            $peers = $this->fetchPeers();
        } catch (Throwable $e) {
            Log::warning('partner_peers_sync_failed', ['error' => $e->getMessage()]);

            return ['ok' => false, 'message' => 'همگام‌سازی همکاران ناموفق: '.$e->getMessage()];
        }

        $seenKeys = [];
        $n = 0;
        foreach ($peers as $peer) {
            $key = ProductLicense::normalizeKey((string) ($peer['license_key'] ?? ''));
            $domain = ProductLicense::normalizeDomain((string) ($peer['domain'] ?? ''));
            if ($key === '' && $domain === '') {
                continue;
            }
            if ($key !== '') {
                $seenKeys[] = $key;
            }
            $partner = null;
            if ($key !== '') {
                $partner = Partner::query()->where('license_key', $key)->first();
            }
            if (! $partner && $domain !== '') {
                $partner = Partner::query()->where('domain', $domain)->first();
            }
            if (! $partner) {
                $partner = new Partner();
            }
            $name = trim((string) ($peer['customer_name'] ?? $peer['name'] ?? '')) ?: ($domain !== '' ? $domain : $key);
            $partner->fill([
                'name' => $name,
                'shop_name' => trim((string) ($peer['shop_name'] ?? $name)) ?: $name,
                'phone' => trim((string) ($peer['customer_phone'] ?? $peer['phone'] ?? '')) ?: null,
                'domain' => $domain !== '' ? $domain : $partner->domain,
                'license_key' => $key !== '' ? $key : $partner->license_key,
                'code' => $key !== '' ? $key : ($domain ?: $partner->code),
                'source' => 'license',
                'is_active' => true,
                'last_synced_at' => now(),
                'notes' => 'همکار لایسنس‌دار — همگام خودکار از شبکه',
            ]);
            $partner->save();
            $partner->ensureCustomer();
            $n++;
        }

        // Deactivate license-sourced partners no longer active (keep rows for history).
        $q = Partner::query()->where('source', 'license')->where('is_active', true);
        if ($seenKeys !== []) {
            $q->where(function ($inner) use ($seenKeys) {
                $inner->whereNotNull('license_key')->whereNotIn('license_key', $seenKeys);
            });
            // Also deactivate license partners without key that weren't refreshed in this sync.
            Partner::query()
                ->where('source', 'license')
                ->where('is_active', true)
                ->whereNull('license_key')
                ->where(function ($inner) {
                    $inner->whereNull('last_synced_at')->orWhere('last_synced_at', '<', now()->subMinutes(5));
                })
                ->update(['is_active' => false]);
        } elseif ($peers === []) {
            Partner::query()->where('source', 'license')->where('is_active', true)->update(['is_active' => false]);
        }
        if ($seenKeys !== []) {
            Partner::query()
                ->where('source', 'license')
                ->whereNotNull('license_key')
                ->whereNotIn('license_key', $seenKeys)
                ->update(['is_active' => false]);
        }

        return ['ok' => true, 'message' => $n.' همکار لایسنس‌دار همگام شد.', 'count' => $n];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchPeers(): array
    {
        if ($this->isSellerHub()) {
            $selfDomain = $this->selfDomain();

            return ProductLicense::query()
                ->where('status', 'active')
                ->whereNotNull('domain')
                ->where('domain', '!=', '')
                ->when($selfDomain !== '', fn ($q) => $q->where('domain', '!=', $selfDomain))
                ->orderBy('customer_name')
                ->get()
                ->map(fn (ProductLicense $l) => [
                    'license_key' => $l->license_key,
                    'domain' => $l->domain,
                    'customer_name' => $l->customer_name,
                    'shop_name' => $l->customer_name,
                    'customer_phone' => $l->customer_phone,
                    'plan_label' => $l->plan_label,
                    'last_check_at' => optional($l->last_check_at)?->toIso8601String(),
                ])
                ->all();
        }

        $auth = $this->licenseAuthPayload();
        if ($auth === null) {
            return [];
        }
        $server = rtrim((string) config('license.server'), '/');
        $response = Http::timeout(25)->asForm()->acceptJson()->post($server.'/license/network/peers', $auth);
        $json = $response->json();
        if (! is_array($json) || ($json['ok'] ?? false) !== true) {
            throw new \RuntimeException(is_array($json) ? (string) ($json['message'] ?? 'پاسخ نامعتبر') : 'HTTP '.$response->status());
        }

        return array_values(is_array($json['peers'] ?? null) ? $json['peers'] : []);
    }

    /**
     * Send full receipt snapshot to peer shop via seller hub.
     *
     * @return array{ok:bool,message:string,uuid?:string}
     */
    public function sendReferral(Reception $reception, Partner $toPartner, ?string $note = null): array
    {
        $reception->loadMissing('customer');
        $customer = $reception->customer;
        if (! $toPartner->domain && ! $toPartner->license_key) {
            return ['ok' => false, 'message' => 'همکار مقصد دامنه/لایسنس شبکه ندارد.'];
        }

        $payload = [
            'origin' => [
                'receipt_no' => $reception->receipt_no,
                'ticket_no' => $reception->ticket_no,
                'note' => $note,
            ],
            'customer' => [
                'name' => $customer?->name,
                'phone' => $customer?->phone,
                'alias' => $customer?->alias,
                'gender' => $customer?->gender,
                'national_code' => $customer?->national_code,
                'job' => $customer?->job,
                'address' => $customer?->address,
                'notes' => $customer?->notes,
            ],
            'device' => [
                'product_name' => $reception->product_name,
                'brand' => $reception->brand,
                'model' => $reception->model,
                'serial_number' => $reception->serial_number,
                'lock_code' => $reception->lock_code,
                'accessories' => $reception->accessories,
                'appearance_notes' => $reception->appearance_notes,
                'reported_fault' => $reception->reported_fault,
                'hdd_capacity' => $reception->hdd_capacity,
                'estimated_cost' => $reception->estimated_cost,
                'admission_type' => $reception->admission_type,
                'service_type' => $reception->service_type,
                'repair_type' => $reception->repair_type,
                'delivered_by' => $reception->delivered_by,
                'referrer' => $reception->referrer,
                'technician_notes' => $reception->technician_notes,
            ],
            'from_shop' => [
                'name' => shop_name(),
                'domain' => $this->selfDomain(),
                'license_key' => $this->selfLicenseKey(),
            ],
        ];

        if ($this->isSellerHub()) {
            $to = ProductLicense::query()
                ->where('status', 'active')
                ->when($toPartner->license_key, fn ($q) => $q->where('license_key', ProductLicense::normalizeKey((string) $toPartner->license_key)))
                ->when(! $toPartner->license_key && $toPartner->domain, fn ($q) => $q->where('domain', ProductLicense::normalizeDomain((string) $toPartner->domain)))
                ->first();
            if (! $to) {
                return ['ok' => false, 'message' => 'لایسنس فعال مقصد در هاب پیدا نشد.'];
            }
            $row = NetworkReferral::query()->create([
                'from_license_id' => null,
                'to_license_id' => $to->id,
                'from_domain' => $this->selfDomain(),
                'to_domain' => $to->domain,
                'from_shop_name' => shop_name(),
                'to_shop_name' => $to->customer_name,
                'origin_receipt_no' => $reception->receipt_no,
                'origin_ticket_no' => $reception->ticket_no,
                'status' => NetworkReferral::STATUS_PENDING,
                'payload' => $payload,
            ]);
            $uuid = $row->uuid;
        } else {
            $auth = $this->licenseAuthPayload();
            if ($auth === null) {
                return ['ok' => false, 'message' => 'لایسنس این نصب برای شبکه تنظیم نشده است.'];
            }
            $server = rtrim((string) config('license.server'), '/');
            $response = Http::timeout(30)->asForm()->acceptJson()->post($server.'/license/network/referrals', array_merge($auth, [
                'to_license_key' => (string) ($toPartner->license_key ?? ''),
                'to_domain' => (string) ($toPartner->domain ?? ''),
                'origin_receipt_no' => (string) $reception->receipt_no,
                'origin_ticket_no' => (string) $reception->ticket_no,
                'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            ]));
            $json = $response->json();
            if (! is_array($json) || ($json['ok'] ?? false) !== true) {
                return ['ok' => false, 'message' => is_array($json) ? (string) ($json['message'] ?? 'ارسال ارجاع ناموفق') : 'HTTP '.$response->status()];
            }
            $uuid = (string) ($json['uuid'] ?? '');
        }

        $tech = trim((string) $reception->technician_notes);
        if ($note) {
            $tech = trim($tech."\n".'ارجاع شبکه به '.$toPartner->displayName().': '.$note);
        }
        $reception->update([
            'partner_flow' => Reception::PARTNER_FLOW_OUTBOUND,
            'partner_referred_to_id' => $toPartner->id,
            'partner_referred_at' => now(),
            'partner_network_ref' => $uuid !== '' ? $uuid : $reception->partner_network_ref,
            'partner_approval_status' => 'sent',
            'status' => $reception->status === 'delivered' ? $reception->status : 'waiting_part',
            'technician_notes' => $tech !== '' ? $tech : $reception->technician_notes,
        ]);

        return ['ok' => true, 'message' => 'ارجاع به «'.$toPartner->displayName().'» ارسال شد. منتظر تأیید منشی مقصد.', 'uuid' => $uuid];
    }

    /**
     * Pull pending inbound referrals from hub and create local draft receptions for secretary approval.
     *
     * @return array{ok:bool,message:string,created?:int}
     */
    public function pullInbox(): array
    {
        try {
            $items = $this->fetchInbox();
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => 'دریافت ارجاع‌ها ناموفق: '.$e->getMessage()];
        }

        $created = 0;
        foreach ($items as $item) {
            $uuid = (string) ($item['uuid'] ?? '');
            if ($uuid === '') {
                continue;
            }
            if (Reception::query()->where('partner_network_ref', $uuid)->exists()) {
                continue;
            }
            $payload = is_array($item['payload'] ?? null) ? $item['payload'] : [];
            $from = is_array($payload['from_shop'] ?? null) ? $payload['from_shop'] : [];
            $partner = $this->upsertPartnerFromShop($from, $item);
            $custData = is_array($payload['customer'] ?? null) ? $payload['customer'] : [];
            $device = is_array($payload['device'] ?? null) ? $payload['device'] : [];
            $origin = is_array($payload['origin'] ?? null) ? $payload['origin'] : [];

            $phone = trim((string) ($custData['phone'] ?? ''));
            $customer = $phone !== '' ? Customer::findByPhone($phone) : null;
            if (! $customer) {
                $customer = Customer::query()->create([
                    'name' => trim((string) ($custData['name'] ?? '')) ?: 'مشتری ارجاع شبکه',
                    'phone' => $phone !== '' ? $phone : ('net-'.$uuid),
                    'alias' => $custData['alias'] ?? null,
                    'gender' => $custData['gender'] ?? null,
                    'national_code' => $custData['national_code'] ?? null,
                    'job' => $custData['job'] ?? null,
                    'address' => $custData['address'] ?? null,
                    'notes' => trim((string) ($custData['notes'] ?? '')."\n".'ارجاع شبکه از '.$partner->displayName()),
                ]);
            }

            $peerReceipt = trim((string) ($origin['receipt_no'] ?? ($item['origin_receipt_no'] ?? '')));
            $localReceipt = Reception::nextReceiptNo();
            // Avoid issuing a local draft that already equals the peer receipt number.
            $guard = 0;
            while ($peerReceipt !== '' && $this->sameReceiptNo($localReceipt, $peerReceipt) && $guard < 30) {
                $localReceipt = $this->bumpReceiptCandidate($localReceipt);
                $guard++;
            }

            Reception::query()->create([
                'ticket_no' => Reception::nextTicketNo(),
                'receipt_no' => $localReceipt,
                'customer_id' => $customer->id,
                'partner_id' => $partner->id,
                'partner_flow' => Reception::PARTNER_FLOW_INBOUND,
                'partner_peer_receipt_no' => $peerReceipt !== '' ? $peerReceipt : null,
                'partner_end_customer_note' => $origin['note'] ?? null,
                'partner_approval_status' => 'pending',
                'partner_network_ref' => $uuid,
                'partner_payload' => $payload,
                'product_name' => $device['product_name'] ?? null,
                'brand' => $device['brand'] ?? null,
                'model' => $device['model'] ?? null,
                'serial_number' => $device['serial_number'] ?? null,
                'lock_code' => $device['lock_code'] ?? null,
                'accessories' => $device['accessories'] ?? null,
                'appearance_notes' => $device['appearance_notes'] ?? null,
                'reported_fault' => $device['reported_fault'] ?? null,
                'hdd_capacity' => $device['hdd_capacity'] ?? null,
                'estimated_cost' => $device['estimated_cost'] ?? 0,
                'admission_type' => $device['admission_type'] ?? null,
                'service_type' => $device['service_type'] ?? null,
                'repair_type' => $device['repair_type'] ?? null,
                'delivered_by' => $device['delivered_by'] ?? null,
                'technician_notes' => $device['technician_notes'] ?? null,
                'referrer' => 'ارجاع شبکه: '.$partner->displayName(),
                // منتظر ورود قطعه / تأیید منشی
                'status' => 'waiting_part',
                'custody' => 'front_desk',
                'created_by' => auth()->id(),
                'received_at' => now(),
            ]);
            $created++;
            $this->ackPulled($uuid);
        }

        return ['ok' => true, 'message' => $created > 0 ? ($created.' ارجاع جدید — منتظر قطعه و تأیید منشی.') : 'ارجاع معلقی نبود.', 'created' => $created];
    }

    /**
     * Secretary confirms part arrived + details match.
     * Peer receipt stays; this shop keeps/issues its own distinct receipt number.
     *
     * @return array{ok:bool,message:string}
     */
    public function approveInbound(Reception $reception): array
    {
        if ($reception->partner_approval_status !== 'pending') {
            return ['ok' => false, 'message' => 'این قبض در انتظار تأیید شبکه نیست.'];
        }

        $peer = trim((string) $reception->partner_peer_receipt_no);
        if ($peer === '') {
            return ['ok' => false, 'message' => 'خطای قبض: شماره قبض نماینده مبدأ ثبت نشده است.'];
        }

        // Peer receipt must not already exist as a local receipt in this shop.
        $peerClash = Reception::withTrashed()
            ->where('id', '!=', $reception->id)
            ->where('receipt_no', $peer)
            ->exists();
        if ($peerClash) {
            return ['ok' => false, 'message' => 'خطای قبض: شماره قبض نماینده مبدأ («'.$peer.'») با قبض این مجموعه یکسان/تکراری است.'];
        }

        // Local shop receipt must differ from peer receipt.
        $local = trim((string) $reception->receipt_no);
        if ($local === '' || $this->sameReceiptNo($local, $peer)) {
            $local = Reception::nextReceiptNo();
            $guard = 0;
            while ($this->sameReceiptNo($local, $peer) && $guard < 30) {
                $local = $this->bumpReceiptCandidate($local);
                $guard++;
            }
            if ($this->sameReceiptNo($local, $peer)) {
                return ['ok' => false, 'message' => 'خطای قبض: نتوانستیم قبض جدید متمایز از قبض نماینده بسازیم. پیشوند قبض را در تنظیمات عوض کنید.'];
            }
        }

        // On confirm: keep peer receipt, finalize this shop's own receipt, start internal flow.
        $reception->update([
            'receipt_no' => $local,
            'partner_peer_receipt_no' => $peer,
            'partner_approval_status' => 'approved',
            'status' => 'received',
            'received_at' => $reception->received_at ?: now(),
        ]);
        $this->decideRemote($reception, true);

        return ['ok' => true, 'message' => 'تأیید شد. قبض نماینده «'.$peer.'» نگه داشته شد و قبض این مجموعه «'.$local.'» ثبت شد. ادامه تعمیر طبق روند داخلی.'];
    }

    /** @return array{ok:bool,message:string} */
    public function rejectInbound(Reception $reception, string $reason = ''): array
    {
        if ($reception->partner_approval_status !== 'pending') {
            return ['ok' => false, 'message' => 'این قبض در انتظار تأیید شبکه نیست.'];
        }
        $reception->update([
            'partner_approval_status' => 'rejected',
            'partner_reject_reason' => $reason !== '' ? $reason : 'رد توسط منشی مقصد — ناهماهنگی مشخصات/قطعه',
            'partner_returned_at' => now(),
            'status' => 'cancelled',
        ]);
        $this->decideRemote($reception, false, $reason);

        return ['ok' => true, 'message' => 'قبض رد شد و به همکار مبدأ برگشت.'];
    }

    /**
     * After repair/pricing/SMS: return device/receipt workflow to originating colleague for exit to customer.
     *
     * @return array{ok:bool,message:string}
     */
    public function returnToOrigin(Reception $reception): array
    {
        if (! $reception->isPartnerInbound() || $reception->partner_approval_status !== 'approved') {
            return ['ok' => false, 'message' => 'فقط قبض ورودی تأییدشده را می‌توان به همکار مبدأ برگرداند.'];
        }
        if (in_array($reception->status, ['cancelled', 'delivered'], true)) {
            return ['ok' => false, 'message' => 'این قبض قابل برگشت شبکه نیست.'];
        }

        $reception->update([
            'partner_approval_status' => 'returned_to_origin',
            'partner_returned_at' => now(),
            'status' => 'ready',
        ]);
        $this->notifyReturned($reception);

        return ['ok' => true, 'message' => 'ارجاع برگشت به همکار مبدأ ثبت شد. مبدأ می‌تواند خروج/تحویل به مشتری را انجام دهد.'];
    }

    /**
     * Refresh outbound referral statuses from hub (accepted/rejected/returned).
     *
     * @return array{ok:bool,message:string,updated?:int}
     */
    public function refreshOutboundStatuses(): array
    {
        $outbound = Reception::query()
            ->where('partner_flow', Reception::PARTNER_FLOW_OUTBOUND)
            ->whereNotNull('partner_network_ref')
            ->whereNotIn('partner_approval_status', ['rejected', 'returned_to_origin', 'returned'])
            ->latest('id')
            ->limit(80)
            ->get();
        if ($outbound->isEmpty()) {
            return ['ok' => true, 'message' => 'ارسالی برای پیگیری نبود.', 'updated' => 0];
        }

        $updated = 0;
        foreach ($outbound as $r) {
            $info = $this->fetchReferralStatusInfo((string) $r->partner_network_ref);
            if (! $info) {
                continue;
            }
            $status = (string) ($info['status'] ?? '');
            if ($status === NetworkReferral::STATUS_ACCEPTED && $r->partner_approval_status !== 'accepted') {
                $r->update(['partner_approval_status' => 'accepted']);
                $updated++;
            } elseif ($status === NetworkReferral::STATUS_REJECTED && $r->partner_approval_status !== 'rejected') {
                $r->update([
                    'partner_approval_status' => 'rejected',
                    'partner_flow' => Reception::PARTNER_FLOW_RETURNED,
                    'partner_returned_at' => now(),
                    'partner_reject_reason' => $info['reject_reason'] ?? $r->partner_reject_reason,
                    'status' => in_array($r->status, ['delivered', 'cancelled'], true) ? $r->status : 'ready',
                ]);
                $updated++;
            } elseif ($status === NetworkReferral::STATUS_RETURNED && $r->partner_approval_status !== 'returned') {
                $r->update([
                    'partner_approval_status' => 'returned',
                    'partner_flow' => Reception::PARTNER_FLOW_RETURNED,
                    'partner_returned_at' => now(),
                    'status' => in_array($r->status, ['delivered', 'cancelled'], true) ? $r->status : 'ready',
                ]);
                $updated++;
            }
        }

        return ['ok' => true, 'message' => $updated.' وضعیت ارجاع به‌روز شد.', 'updated' => $updated];
    }

    private function sameReceiptNo(string $a, string $b): bool
    {
        return mb_strtoupper(trim($a)) === mb_strtoupper(trim($b));
    }

    private function bumpReceiptCandidate(string $current): string
    {
        if (preg_match('/^(.*?)(\d+)$/', $current, $m)) {
            $prefix = $m[1];
            $num = (int) $m[2];
            $width = strlen($m[2]);
            for ($i = 1; $i <= 50; $i++) {
                $candidate = $prefix.str_pad((string) ($num + $i), $width, '0', STR_PAD_LEFT);
                if (! Reception::withTrashed()->where('receipt_no', $candidate)->exists()) {
                    return $candidate;
                }
            }
        }

        return Reception::nextReceiptNo();
    }

    public function isSellerHub(): bool
    {
        return LicenseStatus::isSellerSite() || trim((string) config('license.key')) === '';
    }

    private function selfDomain(): string
    {
        $d = trim((string) config('license.domain'));
        if ($d === '') {
            $d = (string) (request()?->getHost() ?: '');
        }

        return ProductLicense::normalizeDomain($d);
    }

    private function selfLicenseKey(): string
    {
        return ProductLicense::normalizeKey((string) config('license.key'));
    }

    /** @return array<string,string>|null */
    private function licenseAuthPayload(): ?array
    {
        $key = trim((string) config('license.key'));
        $token = trim((string) config('license.token'));
        $domain = $this->selfDomain();
        if ($key === '' || $token === '' || $domain === '') {
            return null;
        }

        return [
            'license_key' => ProductLicense::normalizeKey($key),
            'domain' => $domain,
            'token' => $token,
            'product' => 'hddland-repair',
            'version' => (string) config('updates.version', '1.0.0'),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchInbox(): array
    {
        if ($this->isSellerHub()) {
            // Seller shop itself is not a licensed peer by default; inbox empty unless matching domain licenses.
            $domain = $this->selfDomain();
            $license = ProductLicense::query()->where('status', 'active')->where('domain', $domain)->first();
            if (! $license) {
                return [];
            }

            return NetworkReferral::query()
                ->where('to_license_id', $license->id)
                ->whereIn('status', [NetworkReferral::STATUS_PENDING, NetworkReferral::STATUS_AWAITING])
                ->latest('id')
                ->limit(40)
                ->get()
                ->map(fn (NetworkReferral $r) => [
                    'uuid' => $r->uuid,
                    'origin_receipt_no' => $r->origin_receipt_no,
                    'from_shop_name' => $r->from_shop_name,
                    'from_domain' => $r->from_domain,
                    'payload' => $r->payload,
                ])
                ->all();
        }

        $auth = $this->licenseAuthPayload();
        if ($auth === null) {
            return [];
        }
        $server = rtrim((string) config('license.server'), '/');
        $response = Http::timeout(30)->asForm()->acceptJson()->post($server.'/license/network/referrals/inbox', $auth);
        $json = $response->json();
        if (! is_array($json) || ($json['ok'] ?? false) !== true) {
            throw new \RuntimeException(is_array($json) ? (string) ($json['message'] ?? 'پاسخ نامعتبر') : 'HTTP '.$response->status());
        }

        return array_values(is_array($json['items'] ?? null) ? $json['items'] : []);
    }

    private function ackPulled(string $uuid): void
    {
        if ($this->isSellerHub()) {
            NetworkReferral::query()->where('uuid', $uuid)->where('status', NetworkReferral::STATUS_PENDING)
                ->update(['status' => NetworkReferral::STATUS_AWAITING, 'pulled_at' => now()]);

            return;
        }
        $auth = $this->licenseAuthPayload();
        if ($auth === null) {
            return;
        }
        $server = rtrim((string) config('license.server'), '/');
        try {
            Http::timeout(20)->asForm()->acceptJson()->post($server.'/license/network/referrals/ack', array_merge($auth, [
                'uuid' => $uuid,
            ]));
        } catch (Throwable $e) {
            Log::warning('partner_ack_failed', ['uuid' => $uuid, 'error' => $e->getMessage()]);
        }
    }

    private function decideRemote(Reception $reception, bool $accept, string $reason = ''): void
    {
        $uuid = (string) $reception->partner_network_ref;
        if ($uuid === '') {
            return;
        }
        if ($this->isSellerHub()) {
            NetworkReferral::query()->where('uuid', $uuid)->update([
                'status' => $accept ? NetworkReferral::STATUS_ACCEPTED : NetworkReferral::STATUS_REJECTED,
                'reject_reason' => $accept ? null : ($reason !== '' ? $reason : 'رد شد'),
                'dest_receipt_no' => $accept ? $reception->receipt_no : null,
                'decided_at' => now(),
            ]);

            return;
        }
        $auth = $this->licenseAuthPayload();
        if ($auth === null) {
            return;
        }
        $server = rtrim((string) config('license.server'), '/');
        try {
            Http::timeout(25)->asForm()->acceptJson()->post($server.'/license/network/referrals/decide', array_merge($auth, [
                'uuid' => $uuid,
                'decision' => $accept ? 'accepted' : 'rejected',
                'reject_reason' => $reason,
                'dest_receipt_no' => (string) $reception->receipt_no,
            ]));
        } catch (Throwable $e) {
            Log::warning('partner_decide_failed', ['uuid' => $uuid, 'error' => $e->getMessage()]);
        }
    }

    /** @return array{status?:string,reject_reason?:string,dest_receipt_no?:string}|null */
    private function fetchReferralStatusInfo(string $uuid): ?array
    {
        if ($uuid === '') {
            return null;
        }
        if ($this->isSellerHub()) {
            $row = NetworkReferral::query()->where('uuid', $uuid)->first();
            if (! $row) {
                return null;
            }

            return [
                'status' => $row->status,
                'reject_reason' => (string) ($row->reject_reason ?? ''),
                'dest_receipt_no' => (string) ($row->dest_receipt_no ?? ''),
            ];
        }
        $auth = $this->licenseAuthPayload();
        if ($auth === null) {
            return null;
        }
        $server = rtrim((string) config('license.server'), '/');
        try {
            $response = Http::timeout(20)->asForm()->acceptJson()->post($server.'/license/network/referrals/status', array_merge($auth, [
                'uuid' => $uuid,
            ]));
            $json = $response->json();
            if (is_array($json) && ($json['ok'] ?? false) === true) {
                return [
                    'status' => (string) ($json['status'] ?? ''),
                    'reject_reason' => (string) ($json['reject_reason'] ?? ''),
                    'dest_receipt_no' => (string) ($json['dest_receipt_no'] ?? ''),
                ];
            }
        } catch (Throwable $e) {
            Log::warning('partner_status_failed', ['uuid' => $uuid, 'error' => $e->getMessage()]);
        }

        return null;
    }

    private function notifyReturned(Reception $reception): void
    {
        $uuid = (string) $reception->partner_network_ref;
        if ($uuid === '') {
            return;
        }
        if ($this->isSellerHub()) {
            NetworkReferral::query()->where('uuid', $uuid)->update([
                'status' => NetworkReferral::STATUS_RETURNED,
                'dest_receipt_no' => $reception->receipt_no,
                'decided_at' => now(),
            ]);

            return;
        }
        $auth = $this->licenseAuthPayload();
        if ($auth === null) {
            return;
        }
        $server = rtrim((string) config('license.server'), '/');
        try {
            Http::timeout(25)->asForm()->acceptJson()->post($server.'/license/network/referrals/return', array_merge($auth, [
                'uuid' => $uuid,
                'dest_receipt_no' => (string) $reception->receipt_no,
            ]));
        } catch (Throwable $e) {
            Log::warning('partner_return_failed', ['uuid' => $uuid, 'error' => $e->getMessage()]);
        }
    }

    /**
     * @param  array<string, mixed>  $from
     * @param  array<string, mixed>  $item
     */
    private function upsertPartnerFromShop(array $from, array $item): Partner
    {
        $key = ProductLicense::normalizeKey((string) ($from['license_key'] ?? ''));
        $domain = ProductLicense::normalizeDomain((string) ($from['domain'] ?? $item['from_domain'] ?? ''));
        $name = trim((string) ($from['name'] ?? $item['from_shop_name'] ?? '')) ?: ($domain !== '' ? $domain : 'همکار شبکه');

        $partner = null;
        if ($key !== '') {
            $partner = Partner::query()->where('license_key', $key)->first();
        }
        if (! $partner && $domain !== '') {
            $partner = Partner::query()->where('domain', $domain)->first();
        }
        if (! $partner) {
            $partner = new Partner();
        }
        $partner->fill([
            'name' => $name,
            'shop_name' => $name,
            'domain' => $domain !== '' ? $domain : null,
            'license_key' => $key !== '' ? $key : null,
            'code' => $key !== '' ? $key : $domain,
            'source' => 'license',
            'is_active' => true,
            'last_synced_at' => now(),
            'notes' => 'همکار شبکه لایسنس‌دار',
        ]);
        $partner->save();
        $partner->ensureCustomer();

        return $partner;
    }
}
