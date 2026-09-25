<?php

namespace Plugins\Accounting\src\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AccJournal
{
    /**
     * @param  list<array{account:string,debit?:int,credit?:int,memo?:string}>  $lines
     */
    public static function post(string $source, int $sourceId, string $date, string $description, array $lines, ?int $documentId = null): ?int
    {
        if (! Schema::hasTable('acc_journal_entries') || ! Schema::hasTable('acc_journal_lines')) {
            return null;
        }
        if (self::findPosted($source, $sourceId)) {
            return null;
        }
        $norm = self::normalizeLines($lines);
        if ($norm === [] || ! self::isBalanced($norm)) {
            return null;
        }
        $number = self::nextNumber();
        $entryId = (int) DB::table('acc_journal_entries')->insertGetId([
            'number' => $number,
            'entry_date' => $date ?: now()->toDateString(),
            'source' => $source,
            'source_id' => $sourceId,
            'document_id' => $documentId,
            'description' => $description,
            'status' => 'posted',
            'created_by' => Auth::id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        foreach ($norm as $line) {
            $accountId = AccChart::idByCode($line['account']);
            if (! $accountId) {
                continue;
            }
            DB::table('acc_journal_lines')->insert([
                'entry_id' => $entryId,
                'account_id' => $accountId,
                'account_code' => $line['account'],
                'debit' => $line['debit'],
                'credit' => $line['credit'],
                'memo' => $line['memo'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $entryId;
    }

    public static function reverse(string $source, int $sourceId, string $reason = 'برگشت سند'): bool
    {
        $row = self::findPosted($source, $sourceId);
        if (! $row) {
            return false;
        }
        $lines = DB::table('acc_journal_lines')->where('entry_id', $row->id)->get();
        $rev = [];
        foreach ($lines as $line) {
            $rev[] = [
                'account' => (string) ($line->account_code ?? ''),
                'debit' => (int) $line->credit,
                'credit' => (int) $line->debit,
                'memo' => $reason,
            ];
        }
        $ok = self::post($source.':rev', (int) $row->id, now()->toDateString(), $reason.' '.$row->number, $rev, $row->document_id ? (int) $row->document_id : null);
        if ($ok) {
            DB::table('acc_journal_entries')->where('id', $row->id)->update([
                'status' => 'reversed',
                'updated_at' => now(),
            ]);
        }

        return (bool) $ok;
    }

    public static function findPosted(string $source, int $sourceId): ?object
    {
        if (! Schema::hasTable('acc_journal_entries')) {
            return null;
        }
        $row = DB::table('acc_journal_entries')
            ->where('source', $source)
            ->where('source_id', $sourceId)
            ->where('status', 'posted')
            ->orderByDesc('id')
            ->first();

        return $row ?: null;
    }

    public static function postDocument(object $doc): ?int
    {
        $doc = self::hydrateDocument($doc);
        $lines = self::linesForDocument($doc);
        if ($lines === []) {
            return null;
        }

        return self::post(
            'document',
            (int) $doc->id,
            (string) ($doc->doc_date ?? now()->toDateString()),
            (AccEngine::TYPES[$doc->type] ?? $doc->type).' '.$doc->number,
            $lines,
            (int) $doc->id
        );
    }

    public static function reverseDocument(int $documentId): bool
    {
        return self::reverse('document', $documentId, 'ابطال سند');
    }

    /**
     * @return list<array{account:string,debit:int,credit:int,memo?:string}>
     */
    public static function linesForDocument(object $doc): array
    {
        $type = (string) ($doc->type ?? '');
        $subtotal = (int) ($doc->subtotal ?? 0);
        $discount = (int) ($doc->discount ?? 0);
        $tax = (int) ($doc->tax ?? 0);
        $total = (int) ($doc->total ?? max(0, $subtotal - $discount + $tax));
        $cost = (int) ($doc->cogs ?? 0);
        $commission = (int) ($doc->commission_amount ?? 0);
        $paid = ! in_array(strtolower((string) ($doc->payment_method ?? '')), ['unpaid', 'credit', 'نسیه'], true);
        $pay = AccChart::paymentAccountCode($doc->payment_method ?? null, $paid);
        $lines = [];

        if ($type === 'sale') {
            if ($total > 0) {
                $lines[] = ['account' => $pay, 'debit' => $total, 'credit' => 0, 'memo' => 'وصول فروش'];
            }
            if ($discount > 0) {
                $lines[] = ['account' => AccChart::setting('discount'), 'debit' => $discount, 'credit' => 0, 'memo' => 'تخفیف'];
            }
            if ($subtotal > 0) {
                $lines[] = ['account' => self::saleAccountFor($doc), 'debit' => 0, 'credit' => $subtotal, 'memo' => 'درآمد'];
            }
            if ($tax > 0) {
                $lines[] = ['account' => AccChart::setting('vat_out'), 'debit' => 0, 'credit' => $tax, 'memo' => 'ارزش افزوده'];
            }
            if ($cost > 0) {
                $lines[] = ['account' => AccChart::setting('cogs'), 'debit' => $cost, 'credit' => 0, 'memo' => 'بهای تمام‌شده'];
                $lines[] = ['account' => AccChart::setting('inventory'), 'debit' => 0, 'credit' => $cost, 'memo' => 'خروج کالا'];
            }
            if ($commission > 0) {
                $lines[] = ['account' => AccChart::setting('exp_comm'), 'debit' => $commission, 'credit' => 0];
                $lines[] = ['account' => AccChart::setting('comm_pay'), 'debit' => 0, 'credit' => $commission];
            }
        } elseif ($type === 'purchase') {
            if ($subtotal > 0) {
                $lines[] = ['account' => AccChart::setting('inventory'), 'debit' => $subtotal, 'credit' => 0];
            }
            if ($tax > 0) {
                $lines[] = ['account' => AccChart::setting('vat_in'), 'debit' => $tax, 'credit' => 0];
            }
            if ($total > 0) {
                $lines[] = ['account' => $paid ? $pay : AccChart::setting('ap'), 'debit' => 0, 'credit' => $total];
            }
        } elseif ($type === 'expense') {
            $exp = AccChart::setting('exp_ops');
            if ($total > 0) {
                $lines[] = ['account' => $exp, 'debit' => $total, 'credit' => 0];
                $lines[] = ['account' => $pay, 'debit' => 0, 'credit' => $total];
            }
        } elseif ($type === 'payroll') {
            $gross = max($total, $subtotal);
            if ($gross > 0) {
                $lines[] = ['account' => AccChart::setting('exp_pay'), 'debit' => $gross, 'credit' => 0];
                $lines[] = ['account' => AccChart::setting('wages'), 'debit' => 0, 'credit' => $gross];
            }
        } elseif ($type === 'stock_in') {
            if ($total > 0) {
                $lines[] = ['account' => AccChart::setting('inventory'), 'debit' => $total, 'credit' => 0];
                $lines[] = ['account' => AccChart::setting('ap'), 'debit' => 0, 'credit' => $total];
            }
        } elseif ($type === 'stock_out') {
            if ($total > 0) {
                $lines[] = ['account' => AccChart::setting('cogs'), 'debit' => $total, 'credit' => 0];
                $lines[] = ['account' => AccChart::setting('inventory'), 'debit' => 0, 'credit' => $total];
            }
        } elseif ($type === 'voucher') {
            foreach ($doc->voucher_lines ?? [] as $vl) {
                $code = (string) ($vl['account'] ?? '');
                if ($code === '') {
                    continue;
                }
                $debit = (int) ($vl['debit'] ?? 0);
                $credit = (int) ($vl['credit'] ?? 0);
                if ($debit <= 0 && $credit <= 0) {
                    $side = (string) ($vl['side'] ?? 'debit');
                    $amt = (int) ($vl['amount'] ?? $vl['line_total'] ?? 0);
                    if ($amt <= 0) {
                        continue;
                    }
                    $debit = $side === 'credit' ? 0 : $amt;
                    $credit = $side === 'credit' ? $amt : 0;
                }
                $lines[] = [
                    'account' => $code,
                    'debit' => $debit,
                    'credit' => $credit,
                    'memo' => trim((string) ($vl['title'] ?? '').' '.($vl['tafsil'] ?? '')),
                ];
            }
        }

        return self::normalizeLines($lines);
    }

    public static function postInstallmentPayment(object $schedule, object $request, int $amount): ?int
    {
        if ($amount <= 0) {
            return null;
        }

        return self::post(
            'installment_pay',
            (int) $schedule->id,
            now()->toDateString(),
            'وصول قسط '.$request->number.' #'.$schedule->installment_no,
            [
                ['account' => AccChart::setting('cash'), 'debit' => $amount, 'credit' => 0],
                ['account' => AccChart::setting('ar_install'), 'debit' => 0, 'credit' => $amount],
            ]
        );
    }

    public static function postCheckStatus(object $check, string $from, string $to): ?int
    {
        $amt = (int) ($check->amount ?? 0);
        if ($amt <= 0) {
            return null;
        }
        $dir = (string) ($check->direction ?? 'receivable');
        $lines = [];
        if ($dir === 'receivable' && in_array($to, ['received', 'paid'], true) && ! in_array($from, ['received', 'paid'], true)) {
            $lines = [
                ['account' => AccChart::setting('bank'), 'debit' => $amt, 'credit' => 0],
                ['account' => AccChart::setting('notes'), 'debit' => 0, 'credit' => $amt],
            ];
        } elseif ($dir === 'receivable' && in_array($to, ['bounced', 'returned'], true)) {
            $lines = [
                ['account' => AccChart::setting('ar'), 'debit' => $amt, 'credit' => 0],
                ['account' => AccChart::setting('notes'), 'debit' => 0, 'credit' => $amt],
            ];
        } elseif ($dir === 'payable' && $to === 'paid') {
            $lines = [
                ['account' => AccChart::setting('notes_pay'), 'debit' => $amt, 'credit' => 0],
                ['account' => AccChart::setting('bank'), 'debit' => 0, 'credit' => $amt],
            ];
        } elseif (in_array($dir, ['receivable', 'spent'], true) && $to === 'spent') {
            $lines = [
                ['account' => AccChart::setting('ap'), 'debit' => $amt, 'credit' => 0],
                ['account' => AccChart::setting('notes'), 'debit' => 0, 'credit' => $amt],
            ];
        } elseif ($dir === 'receivable' && $to === 'in_collection') {
            $lines = [
                ['account' => AccChart::setting('notes'), 'debit' => $amt, 'credit' => 0],
                ['account' => AccChart::setting('ar'), 'debit' => 0, 'credit' => $amt],
            ];
        }
        if ($lines === []) {
            return null;
        }

        return self::post('check:'.$to, (int) $check->id, now()->toDateString(), 'چک '.$check->number, $lines);
    }

    /**
     * @param  list<array{account:string,debit?:int,credit?:int,memo?:string}>  $lines
     * @return list<array{account:string,debit:int,credit:int,memo?:string}>
     */
    public static function normalizeLines(array $lines): array
    {
        $out = [];
        foreach ($lines as $line) {
            $acc = trim((string) ($line['account'] ?? ''));
            $d = max(0, (int) ($line['debit'] ?? 0));
            $c = max(0, (int) ($line['credit'] ?? 0));
            if ($acc === '' || ($d === 0 && $c === 0)) {
                continue;
            }
            $out[] = ['account' => $acc, 'debit' => $d, 'credit' => $c, 'memo' => $line['memo'] ?? null];
        }

        return $out;
    }

    /** @param  list<array{debit:int,credit:int}>  $lines */
    public static function isBalanced(array $lines): bool
    {
        $d = 0;
        $c = 0;
        foreach ($lines as $line) {
            $d += (int) ($line['debit'] ?? 0);
            $c += (int) ($line['credit'] ?? 0);
        }

        return $d > 0 && $d === $c;
    }

    public static function nextNumber(): string
    {
        $stamp = date('ym');
        $like = 'JE-'.$stamp.'-%';
        $last = 0;
        try {
            $row = DB::table('acc_journal_entries')->where('number', 'like', $like)->orderByDesc('id')->value('number');
            if (is_string($row) && preg_match('/(\d+)$/', $row, $m)) {
                $last = (int) $m[1];
            }
        } catch (\Throwable) {
        }

        return sprintf('JE-%s-%04d', $stamp, $last + 1);
    }

    protected static function hydrateDocument(object $doc): object
    {
        $cost = 0;
        $voucher = [];
        if (Schema::hasTable('acc_document_lines')) {
            $rows = DB::table('acc_document_lines')->where('document_id', $doc->id)->get();
            foreach ($rows as $line) {
                $cost += (int) round(((float) ($line->qty ?? 1)) * ((int) ($line->unit_cost ?? 0)));
                $code = '';
                if (! empty($line->account_id) && Schema::hasTable('acc_accounts')) {
                    $code = (string) (DB::table('acc_accounts')->where('id', $line->account_id)->value('code') ?? '');
                }
                $debit = (int) ($line->unit_price ?? 0);
                $credit = (int) ($line->unit_cost ?? 0);
                $side = (string) ($line->side ?? '');
                $amount = (int) ($line->line_total ?? 0);
                if ($debit <= 0 && $credit <= 0 && $amount > 0) {
                    if ($side === 'credit') {
                        $credit = $amount;
                    } else {
                        $debit = $amount;
                    }
                }
                $voucher[] = [
                    'account' => $code,
                    'side' => $credit > 0 && $debit <= 0 ? 'credit' : 'debit',
                    'amount' => $debit > 0 ? $debit : $credit,
                    'debit' => $debit,
                    'credit' => $credit,
                    'title' => $line->title ?? '',
                    'tafsil' => $line->tafsil ?? '',
                ];
            }
        }
        $doc->cogs = (int) ($doc->cogs ?? $cost);
        $doc->voucher_lines = $voucher;

        return $doc;
    }

    protected static function saleAccountFor(object $doc): string
    {
        $note = strtolower((string) ($doc->notes ?? '').' '.($doc->source ?? ''));
        if (str_contains($note, 'ریکاوری') || str_contains($note, 'recovery')) {
            return AccChart::setting('recovery');
        }
        if (str_contains($note, 'تعمیر') || str_contains($note, 'repair')) {
            return AccChart::setting('repair');
        }
        if (str_contains($note, 'آموزش') || str_contains($note, 'train')) {
            return AccChart::setting('training');
        }

        return AccChart::setting('sales');
    }

    /** @return list<object> */
    public static function trialBalance(?string $from = null, ?string $to = null): array
    {
        if (! Schema::hasTable('acc_journal_lines')) {
            return [];
        }
        $q = DB::table('acc_journal_lines as l')
            ->join('acc_journal_entries as e', 'e.id', '=', 'l.entry_id')
            ->leftJoin('acc_accounts as a', 'a.id', '=', 'l.account_id')
            ->where('e.status', 'posted')
            ->select('a.code', 'a.name', 'a.type', DB::raw('SUM(l.debit) as debit'), DB::raw('SUM(l.credit) as credit'))
            ->groupBy('a.code', 'a.name', 'a.type')
            ->orderBy('a.code');
        if ($from) {
            $q->whereDate('e.entry_date', '>=', $from);
        }
        if ($to) {
            $q->whereDate('e.entry_date', '<=', $to);
        }

        return $q->get()->all();
    }
}
