<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Reception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Recycle bin: soft-delete → restore or permanent purge.
 * Matches common ERP practice (SoftDeletes + admin trash UI).
 */
class TrashService
{
    /**
     * @return array{receptions:Collection,journals:Collection,customers:Collection,counts:array}
     */
    public function inventory(int $limit = 80): array
    {
        $receptions = Reception::onlyTrashed()
            ->with(['customer' => fn ($q) => $q->withTrashed(), 'deleter'])
            ->latest('deleted_at')
            ->limit($limit)
            ->get();

        $journals = JournalEntry::onlyTrashed()
            ->with(['customer' => fn ($q) => $q->withTrashed(), 'reception' => fn ($q) => $q->withTrashed(), 'deleter'])
            ->latest('deleted_at')
            ->limit($limit)
            ->get();

        $customers = Customer::onlyTrashed()
            ->latest('deleted_at')
            ->limit($limit)
            ->get();

        return [
            'receptions' => $receptions,
            'journals' => $journals,
            'customers' => $customers,
            'counts' => [
                'receptions' => Reception::onlyTrashed()->count(),
                'journals' => JournalEntry::onlyTrashed()->count(),
                'customers' => Customer::onlyTrashed()->count(),
            ],
        ];
    }

    public function softDeleteReception(Reception $reception, ?string $reason = null): void
    {
        if ($reception->trashed()) {
            return;
        }

        DB::transaction(function () use ($reception, $reason) {
            $reason = trim((string) $reason) ?: 'حذف قبض توسط کاربر';
            $userId = Auth::id();

            JournalEntry::query()
                ->where('reception_id', $reception->id)
                ->get()
                ->each(function (JournalEntry $entry) use ($userId, $reason) {
                    $entry->forceFill([
                        'deleted_by' => $userId,
                        'delete_reason' => $reason,
                    ])->save();
                    $entry->delete();
                });

            app(ReceptionLifecycleService::class)->log(
                $reception,
                $reception->status,
                'trash',
                $reception->status,
                'قبض به سطل زباله منتقل شد',
                $reason,
                ['trashed' => true]
            );

            $reception->forceFill([
                'deleted_by' => $userId,
                'delete_reason' => $reason,
            ])->save();
            $reception->delete();
        });
    }

    public function restoreReception(int $id): Reception
    {
        $reception = Reception::onlyTrashed()->findOrFail($id);

        DB::transaction(function () use ($reception) {
            $reception->restore();
            $reception->forceFill([
                'deleted_by' => null,
                'delete_reason' => null,
            ])->save();

            JournalEntry::onlyTrashed()
                ->where('reception_id', $reception->id)
                ->get()
                ->each(function (JournalEntry $entry) {
                    $entry->restore();
                    $entry->forceFill([
                        'deleted_by' => null,
                        'delete_reason' => null,
                    ])->save();
                });

            app(ReceptionLifecycleService::class)->log(
                $reception->fresh(),
                $reception->status,
                'trash_restore',
                $reception->status,
                'قبض از سطل زباله بازیابی شد',
                null,
                ['restored' => true]
            );
        });

        return $reception->fresh();
    }

    public function forceDeleteReception(int $id): void
    {
        $reception = Reception::onlyTrashed()->findOrFail($id);

        DB::transaction(function () use ($reception) {
            JournalEntry::withTrashed()
                ->where('reception_id', $reception->id)
                ->get()
                ->each(function (JournalEntry $entry) {
                    $entry->lines()->delete();
                    $entry->forceDelete();
                });

            // Hard delete cascades payments/parts/logs per schema.
            $reception->forceDelete();
        });
    }

    public function softDeleteJournal(JournalEntry $entry, ?string $reason = null): void
    {
        if ($entry->trashed()) {
            return;
        }
        if (in_array($entry->source_type, ['reception_revenue', 'payment', 'reception_part'], true)) {
            throw ValidationException::withMessages([
                'journal' => 'اسناد خودکار قبض/پرداخت را از سطل قبض حذف کنید، یا از بازسازی اسناد استفاده کنید.',
            ]);
        }

        $entry->forceFill([
            'deleted_by' => Auth::id(),
            'delete_reason' => trim((string) $reason) ?: 'حذف سند به سطل زباله',
        ])->save();
        $entry->delete();
    }

    public function restoreJournal(int $id): JournalEntry
    {
        $entry = JournalEntry::onlyTrashed()->findOrFail($id);
        $entry->restore();
        $entry->forceFill([
            'deleted_by' => null,
            'delete_reason' => null,
        ])->save();

        return $entry->fresh();
    }

    public function forceDeleteJournal(int $id): void
    {
        $entry = JournalEntry::onlyTrashed()->findOrFail($id);
        DB::transaction(function () use ($entry) {
            JournalLine::where('journal_entry_id', $entry->id)->delete();
            $entry->forceDelete();
        });
    }

    public function restoreCustomer(int $id): Customer
    {
        $customer = Customer::onlyTrashed()->findOrFail($id);
        $customer->restore();

        return $customer->fresh();
    }

    public function forceDeleteCustomer(int $id): void
    {
        $customer = Customer::onlyTrashed()->findOrFail($id);
        // Prefer soft history: block hard delete if active receptions exist.
        if ($customer->receptions()->withTrashed()->exists()) {
            throw ValidationException::withMessages([
                'customer' => 'این مشتری قبض دارد. ابتدا قبض‌ها را از سطل حذف دائم کنید.',
            ]);
        }
        $customer->forceDelete();
    }
}
