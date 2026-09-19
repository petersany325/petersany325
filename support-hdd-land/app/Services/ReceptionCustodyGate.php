<?php

namespace App\Services;

use App\Models\DeviceHandoff;
use App\Models\Reception;
use App\Models\ReceptionWorkReport;
use Illuminate\Validation\ValidationException;

/**
 * Enforces Chain-of-Custody before cost announcement and delivery/exit.
 *
 * Required happy path:
 * reception → desk assigns to named tech → tech confirms → work report
 * → tech returns to desk → desk confirms receive → then cost/delivery.
 */
class ReceptionCustodyGate
{
    public function hasAcceptedBenchHandoff(Reception $reception): bool
    {
        return DeviceHandoff::query()
            ->where('reception_id', $reception->id)
            ->where('direction', DeviceHandoff::DIR_TO_BENCH)
            ->where('status', DeviceHandoff::STATUS_ACCEPTED)
            ->exists();
    }

    public function hasAcceptedReturnHandoff(Reception $reception): bool
    {
        return DeviceHandoff::query()
            ->where('reception_id', $reception->id)
            ->where('direction', DeviceHandoff::DIR_TO_FRONT)
            ->where('status', DeviceHandoff::STATUS_ACCEPTED)
            ->exists();
    }

    public function hasPendingHandoff(Reception $reception): bool
    {
        return DeviceHandoff::query()
            ->where('reception_id', $reception->id)
            ->where('status', DeviceHandoff::STATUS_PENDING)
            ->exists();
    }

    public function hasWorkReport(Reception $reception): bool
    {
        return ReceptionWorkReport::query()
            ->where('reception_id', $reception->id)
            ->exists();
    }

    public function wentToTechnician(Reception $reception): bool
    {
        return $this->hasAcceptedBenchHandoff($reception)
            || in_array($reception->custody ?? 'front_desk', ['with_technician', 'returning'], true)
            || (int) ($reception->custody_technician_id ?? 0) > 0;
    }

    /** Persian reason blocking delivery/exit, or null if allowed. */
    public function deliveryBlockReason(Reception $reception): ?string
    {
        if ($reception->status === 'delivered') {
            return null;
        }

        if ($this->hasPendingHandoff($reception)) {
            $pending = DeviceHandoff::query()
                ->where('reception_id', $reception->id)
                ->where('status', DeviceHandoff::STATUS_PENDING)
                ->latest('id')
                ->first();

            if ($pending?->direction === DeviceHandoff::DIR_TO_BENCH) {
                return 'هنوز تعمیرکار تأیید دریافت ارجاع را نزده است. تا تأیید کارتابل تعمیرکار، خروج/تحویل مجاز نیست.';
            }

            return 'ارجاع بازگشت دستگاه هنوز توسط منشی/حسابدار تأیید دریافت نشده است. تا تأیید، خروج مجاز نیست.';
        }

        $custody = $reception->custody ?? 'front_desk';
        if ($custody === 'with_technician') {
            $name = $reception->custodyTechnician?->name ?: 'تعمیرکار';

            return "دستگاه هنوز نزد {$name} است. تعمیرکار باید دستگاه را به پذیرش ارجاع دهد و منشی/حسابدار دریافت را تأیید کند.";
        }
        if ($custody === 'returning') {
            return 'دستگاه در حال بازگشت به پذیرش است؛ منتظر تأیید دریافت منشی/حسابدار بمانید.';
        }

        if ($this->wentToTechnician($reception)) {
            if (! $this->hasWorkReport($reception)) {
                return 'هنوز تعمیرکار گزارش کار این قبض را ثبت نکرده است. بدون گزارش کار، خروج/تحویل مجاز نیست.';
            }
            if (! $this->hasAcceptedReturnHandoff($reception)) {
                return 'بازگشت دستگاه از تعمیرکار به پذیرش هنوز تأیید نشده است. بدون تأیید دریافت، خروج مجاز نیست.';
            }
        }

        if ($custody !== 'front_desk') {
            return 'محل دستگاه نزد پذیرش نیست؛ تحویل/خروج قفل است.';
        }

        return null;
    }

    /** Persian reason blocking cost announcement, or null if allowed. */
    public function costBlockReason(Reception $reception): ?string
    {
        if (! $this->wentToTechnician($reception)) {
            // Cost can be set at desk for tickets that never left reception.
            return null;
        }

        if ($this->hasPendingHandoff($reception)) {
            $pending = DeviceHandoff::query()
                ->where('reception_id', $reception->id)
                ->where('status', DeviceHandoff::STATUS_PENDING)
                ->latest('id')
                ->first();
            if ($pending?->direction === DeviceHandoff::DIR_TO_BENCH) {
                return 'هنوز تعمیرکار تأیید دریافت ارجاع را نزده است؛ اعلام هزینه ممکن نیست.';
            }
        }

        if (! $this->hasWorkReport($reception)) {
            return 'هنوز تعمیرکار گزارش کار این قبض را در کارتابل ثبت نکرده است. حسابدار فقط بعد از گزارش کار می‌تواند هزینه را اعلام کند.';
        }

        return null;
    }

    public function assertCanDeliver(Reception $reception): void
    {
        $reason = $this->deliveryBlockReason($reception);
        if ($reason) {
            throw ValidationException::withMessages(['status' => $reason]);
        }
    }

    public function assertCanSetCost(Reception $reception): void
    {
        $reason = $this->costBlockReason($reception);
        if ($reason) {
            throw ValidationException::withMessages(['labor_cost' => $reason]);
        }
    }

    /** Checklist for UI. */
    public function checklist(Reception $reception): array
    {
        $bench = $this->hasAcceptedBenchHandoff($reception);
        $report = $this->hasWorkReport($reception);
        $ret = $this->hasAcceptedReturnHandoff($reception);
        $pending = $this->hasPendingHandoff($reception);
        $atDesk = ($reception->custody ?? 'front_desk') === 'front_desk' && ! $pending;

        return [
            'assigned_confirmed' => $bench,
            'work_report' => $report,
            'return_confirmed' => $ret,
            'at_front_desk' => $atDesk,
            'ready_for_cost' => $this->costBlockReason($reception) === null,
            'ready_for_delivery' => $this->deliveryBlockReason($reception) === null,
            'delivery_block' => $this->deliveryBlockReason($reception),
            'cost_block' => $this->costBlockReason($reception),
        ];
    }
}
