<?php

namespace App\Http\Controllers;

use App\Models\NetworkReferral;
use App\Models\ProductLicense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Seller hub API: licensed shops directory + cross-shop receipt referrals.
 */
class LicenseNetworkApiController extends Controller
{
    public function peers(Request $request): JsonResponse
    {
        $auth = $this->authorizeLicense($request);
        if ($auth instanceof JsonResponse) {
            return $auth;
        }
        /** @var ProductLicense $self */
        $self = $auth;

        $peers = ProductLicense::query()
            ->where('status', 'active')
            ->whereNotNull('domain')
            ->where('domain', '!=', '')
            ->where('id', '!=', $self->id)
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
            ->values()
            ->all();

        return response()->json([
            'ok' => true,
            'peers' => $peers,
            'count' => count($peers),
            'message' => count($peers).' همکار فعال در شبکه.',
        ]);
    }

    public function createReferral(Request $request): JsonResponse
    {
        $auth = $this->authorizeLicense($request);
        if ($auth instanceof JsonResponse) {
            return $auth;
        }
        /** @var ProductLicense $from */
        $from = $auth;

        $data = $request->validate([
            'to_license_key' => ['nullable', 'string', 'max:64'],
            'to_domain' => ['nullable', 'string', 'max:190'],
            'origin_receipt_no' => ['nullable', 'string', 'max:60'],
            'origin_ticket_no' => ['nullable', 'string', 'max:60'],
            'payload_json' => ['required', 'string'],
        ]);

        $payload = json_decode($data['payload_json'], true);
        if (! is_array($payload)) {
            return response()->json(['ok' => false, 'message' => 'payload نامعتبر است.'], 422);
        }

        $toKey = ProductLicense::normalizeKey((string) ($data['to_license_key'] ?? ''));
        $toDomain = ProductLicense::normalizeDomain((string) ($data['to_domain'] ?? ''));
        $to = ProductLicense::query()
            ->where('status', 'active')
            ->when($toKey !== '', fn ($q) => $q->where('license_key', $toKey))
            ->when($toKey === '' && $toDomain !== '', fn ($q) => $q->where('domain', $toDomain))
            ->first();
        if (! $to) {
            return response()->json(['ok' => false, 'message' => 'همکار مقصد فعال پیدا نشد.'], 404);
        }
        if ($to->id === $from->id) {
            return response()->json(['ok' => false, 'message' => 'نمی‌توانید به خودتان ارجاع دهید.'], 422);
        }

        $row = NetworkReferral::query()->create([
            'from_license_id' => $from->id,
            'to_license_id' => $to->id,
            'from_domain' => $from->domain,
            'to_domain' => $to->domain,
            'from_shop_name' => $from->customer_name,
            'to_shop_name' => $to->customer_name,
            'origin_receipt_no' => $data['origin_receipt_no'] ?? null,
            'origin_ticket_no' => $data['origin_ticket_no'] ?? null,
            'status' => NetworkReferral::STATUS_PENDING,
            'payload' => $payload,
        ]);

        return response()->json([
            'ok' => true,
            'uuid' => $row->uuid,
            'message' => 'ارجاع در شبکه ثبت شد.',
        ]);
    }

    public function inbox(Request $request): JsonResponse
    {
        $auth = $this->authorizeLicense($request);
        if ($auth instanceof JsonResponse) {
            return $auth;
        }
        /** @var ProductLicense $self */
        $self = $auth;

        $items = NetworkReferral::query()
            ->where('to_license_id', $self->id)
            ->whereIn('status', [NetworkReferral::STATUS_PENDING, NetworkReferral::STATUS_AWAITING])
            ->latest('id')
            ->limit(50)
            ->get()
            ->map(fn (NetworkReferral $r) => [
                'uuid' => $r->uuid,
                'origin_receipt_no' => $r->origin_receipt_no,
                'from_shop_name' => $r->from_shop_name,
                'from_domain' => $r->from_domain,
                'status' => $r->status,
                'payload' => $r->payload,
                'created_at' => optional($r->created_at)?->toIso8601String(),
            ])
            ->values()
            ->all();

        return response()->json(['ok' => true, 'items' => $items, 'count' => count($items)]);
    }

    public function ack(Request $request): JsonResponse
    {
        $auth = $this->authorizeLicense($request);
        if ($auth instanceof JsonResponse) {
            return $auth;
        }
        /** @var ProductLicense $self */
        $self = $auth;
        $data = $request->validate(['uuid' => ['required', 'string', 'max:64']]);
        $row = NetworkReferral::query()->where('uuid', $data['uuid'])->where('to_license_id', $self->id)->first();
        if (! $row) {
            return response()->json(['ok' => false, 'message' => 'ارجاع پیدا نشد.'], 404);
        }
        if ($row->status === NetworkReferral::STATUS_PENDING) {
            $row->update(['status' => NetworkReferral::STATUS_AWAITING, 'pulled_at' => now()]);
        }

        return response()->json(['ok' => true]);
    }

    public function decide(Request $request): JsonResponse
    {
        $auth = $this->authorizeLicense($request);
        if ($auth instanceof JsonResponse) {
            return $auth;
        }
        /** @var ProductLicense $self */
        $self = $auth;
        $data = $request->validate([
            'uuid' => ['required', 'string', 'max:64'],
            'decision' => ['required', 'in:accepted,rejected'],
            'reject_reason' => ['nullable', 'string', 'max:500'],
            'dest_receipt_no' => ['nullable', 'string', 'max:60'],
        ]);
        $row = NetworkReferral::query()->where('uuid', $data['uuid'])->where('to_license_id', $self->id)->first();
        if (! $row) {
            return response()->json(['ok' => false, 'message' => 'ارجاع پیدا نشد.'], 404);
        }
        $row->update([
            'status' => $data['decision'] === 'accepted' ? NetworkReferral::STATUS_ACCEPTED : NetworkReferral::STATUS_REJECTED,
            'reject_reason' => $data['decision'] === 'rejected' ? ($data['reject_reason'] ?? 'رد شد') : null,
            'dest_receipt_no' => $data['dest_receipt_no'] ?? $row->dest_receipt_no,
            'decided_at' => now(),
        ]);

        return response()->json(['ok' => true, 'status' => $row->status]);
    }

    public function status(Request $request): JsonResponse
    {
        $auth = $this->authorizeLicense($request);
        if ($auth instanceof JsonResponse) {
            return $auth;
        }
        /** @var ProductLicense $self */
        $self = $auth;
        $data = $request->validate(['uuid' => ['required', 'string', 'max:64']]);
        $row = NetworkReferral::query()
            ->where('uuid', $data['uuid'])
            ->where(function ($q) use ($self) {
                $q->where('from_license_id', $self->id)->orWhere('to_license_id', $self->id);
            })
            ->first();
        if (! $row) {
            return response()->json(['ok' => false, 'message' => 'ارجاع پیدا نشد.'], 404);
        }

        return response()->json([
            'ok' => true,
            'status' => $row->status,
            'reject_reason' => $row->reject_reason,
            'dest_receipt_no' => $row->dest_receipt_no,
        ]);
    }

    /** @return ProductLicense|JsonResponse */
    private function authorizeLicense(Request $request)
    {
        $data = $request->validate([
            'license_key' => ['required', 'string', 'max:64'],
            'domain' => ['required', 'string', 'max:190'],
            'token' => ['required', 'string', 'max:128'],
            'product' => ['nullable', 'string', 'max:60'],
            'version' => ['nullable', 'string', 'max:30'],
        ]);

        $key = ProductLicense::normalizeKey($data['license_key']);
        $domain = ProductLicense::normalizeDomain($data['domain']);
        $product = $data['product'] ?? 'hddland-repair';

        $license = ProductLicense::query()->where('license_key', $key)->first();
        if (! $license || $license->status !== 'active') {
            return response()->json(['ok' => false, 'message' => 'لایسنس فعال نیست.'], 403);
        }
        if ($license->product && $license->product !== $product) {
            return response()->json(['ok' => false, 'message' => 'محصول لایسنس مطابقت ندارد.'], 422);
        }
        if ($license->domain !== $domain || ! hash_equals((string) $license->token, $data['token'])) {
            return response()->json(['ok' => false, 'message' => 'توکن یا دامنه نامعتبر است.'], 403);
        }
        if ($license->expires_at && $license->expires_at->isPast()) {
            $license->update(['status' => 'expired']);

            return response()->json(['ok' => false, 'message' => 'اعتبار لایسنس گذشته است.'], 423);
        }

        $license->forceFill([
            'last_check_at' => now(),
            'check_count' => (int) $license->check_count + 1,
            'last_check_ip' => $request->ip(),
            'last_check_version' => $data['version'] ?? $license->last_check_version,
        ])->save();

        return $license;
    }
}
