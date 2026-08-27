<?php

namespace App\Services;

use App\Models\Reception;
use App\Models\ReceptionStatusLog;
use Illuminate\Support\Facades\Auth;

class ReceptionLifecycleService
{
    public function log(
        Reception $reception,
        string $toStatus,
        string $eventType = 'status_change',
        ?string $fromStatus = null,
        ?string $title = null,
        ?string $note = null,
        array $meta = []
    ): ReceptionStatusLog {
        return ReceptionStatusLog::create([
            'reception_id' => $reception->id,
            'from_status' => $fromStatus ?? $reception->getOriginal('status'),
            'to_status' => $toStatus,
            'event_type' => $eventType,
            'title' => $title,
            'note' => $note,
            'changed_by' => Auth::id(),
            'meta' => $meta ?: null,
        ]);
    }

    /**
     * Cancel customer delivery and return device to repair cycle on same ticket/serial.
     *
     * @return array{ok:bool,message:string}
     */
    public function cancelDelivery(Reception $reception, string $restoreTo = 'repairing', ?string $reason = null): array
    {
        if ($reception->status !== 'delivered') {
            return ['ok' => false, 'message' => 'این قبض در وضعیت تحویل‌شده نیست.'];
        }

        if (! in_array($restoreTo, ['repairing', 'ready', 'waiting_part', 'received'], true)) {
            $restoreTo = 'repairing';
        }

        $from = $reception->status;
        $meta = [
            'previous_delivered_at' => optional($reception->delivered_at)?->toDateTimeString(),
            'previous_delivery_batch_id' => $reception->delivery_batch_id,
            'previous_pickup_name' => $reception->pickup_name,
            'previous_pickup_phone' => $reception->pickup_phone,
            'previous_settlement_mode' => $reception->settlement_mode,
            'previous_settled_at' => optional($reception->settled_at)?->toDateTimeString(),
        ];

        $reception->forceFill([
            'status' => $restoreTo,
            'delivered_at' => null,
            'delivery_batch_id' => null,
            'pickup_name' => null,
            'pickup_phone' => null,
            'settlement_mode' => null,
            'settled_at' => null,
            'settlement_note' => null,
            'custody' => 'front_desk',
            'delivery_cancelled_at' => now(),
            'delivery_cancel_count' => (int) $reception->delivery_cancel_count + 1,
        ])->save();

        $this->log(
            $reception,
            $restoreTo,
            'delivery_cancel',
            $from,
            'لغو تحویل — بازگشت به چرخه تعمیر',
            $reason,
            $meta
        );

        return [
            'ok' => true,
            'message' => 'تحویل لغو شد. دستگاه روی همین قبض و سریال به چرخه تعمیر برگشت.',
        ];
    }
}
