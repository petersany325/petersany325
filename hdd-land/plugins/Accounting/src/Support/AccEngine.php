<?php

namespace Plugins\Accounting\src\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AccEngine
{
    public const TYPES = [
        'sale' => 'فاکتور فروش',
        'purchase' => 'فاکتور خرید',
        'proforma' => 'پیش‌فاکتور',
        'voucher' => 'سند دستی',
        'expense' => 'هزینه',
        'payroll' => 'حقوق و دستمزد',
        'stock_in' => 'رسید انبار',
        'stock_out' => 'حواله انبار',
        'transfer' => 'انتقال بین انبار',
    ];

    public const CHECK_DIRECTIONS = [
        'receivable' => 'دریافتی',
        'payable' => 'پرداختی',
    ];

    public const CHECK_STATUSES = [
        'pending' => 'در انتظار',
        'received' => 'وصول‌شده',
        'paid' => 'پرداخت‌شده',
        'returned' => 'برگشتی',
        'delivered' => 'تحویل‌شده',
        'bounced' => 'برگشت‌خورده',
        'cancelled' => 'ابطال',
    ];

    public const INSTALLMENT_STATUSES = [
        'pending' => 'در انتظار',
        'reviewing' => 'در حال بررسی',
        'approved' => 'تأیید شده',
        'rejected' => 'رد شده',
        'active' => 'فعال',
        'completed' => 'تسویه',
        'cancelled' => 'لغو',
    ];

    public static function nextNumber(string $type): string
    {
        $prefix = match ($type) {
            'sale' => 'SF',
            'purchase' => 'PF',
            'proforma' => 'PR',
            'voucher' => 'JV',
            'expense' => 'EX',
            'payroll' => 'PY',
            'stock_in' => 'IN',
            'stock_out' => 'OUT',
            'transfer' => 'TR',
            default => 'DOC',
        };
        $stamp = date('ym');
        $like = $prefix.'-'.$stamp.'-%';
        $last = 0;
        try {
            $row = DB::table('acc_documents')->where('number', 'like', $like)->orderByDesc('id')->value('number');
            if (is_string($row) && preg_match('/(\d+)$/', $row, $m)) {
                $last = (int) $m[1];
            }
        } catch (\Throwable) {
        }

        return sprintf('%s-%s-%04d', $prefix, $stamp, $last + 1);
    }

    /**
     * @param  array<string,mixed>  $data
     * @param  list<array<string,mixed>>  $lines
     */
    public static function createDocument(array $data, array $lines = []): int
    {
        $type = (string) ($data['type'] ?? 'sale');
        $id = (int) DB::table('acc_documents')->insertGetId([
            'number' => $data['number'] ?? self::nextNumber($type),
            'type' => $type,
            'status' => $data['status'] ?? 'draft',
            'doc_date' => $data['doc_date'] ?? now()->toDateString(),
            'party_user_id' => $data['party_user_id'] ?? null,
            'party_name' => $data['party_name'] ?? null,
            'warehouse_id' => $data['warehouse_id'] ?? null,
            'warehouse_to_id' => $data['warehouse_to_id'] ?? null,
            'bank_id' => $data['bank_id'] ?? null,
            'staff_id' => $data['staff_id'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'related_id' => $data['related_id'] ?? null,
            'subtotal' => (int) ($data['subtotal'] ?? 0),
            'discount' => (int) ($data['discount'] ?? 0),
            'tax' => (int) ($data['tax'] ?? 0),
            'total' => (int) ($data['total'] ?? 0),
            'commission_rate' => (float) ($data['commission_rate'] ?? 0),
            'commission_amount' => (int) ($data['commission_amount'] ?? 0),
            'payment_method' => $data['payment_method'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => $data['created_by'] ?? Auth::id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($lines as $line) {
            $qty = (float) ($line['qty'] ?? 1);
            $price = (int) ($line['unit_price'] ?? 0);
            $lineTotal = (int) ($line['line_total'] ?? (int) round($qty * $price));
            $lineId = (int) DB::table('acc_document_lines')->insertGetId([
                'document_id' => $id,
                'product_id' => $line['product_id'] ?? null,
                'title' => (string) ($line['title'] ?? 'قلم'),
                'sku' => $line['sku'] ?? null,
                'qty' => $qty,
                'unit_price' => $price,
                'unit_cost' => (int) ($line['unit_cost'] ?? 0),
                'line_total' => $lineTotal,
                'account_id' => $line['account_id'] ?? null,
                'side' => $line['side'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            foreach ($line['serials'] ?? [] as $sn) {
                $sn = trim((string) $sn);
                if ($sn === '') {
                    continue;
                }
                DB::table('acc_document_serials')->insert([
                    'document_id' => $id,
                    'line_id' => $lineId,
                    'product_id' => $line['product_id'] ?? null,
                    'serial' => $sn,
                    'status' => 'assigned',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return $id;
    }

    public static function issueDocument(int $id): void
    {
        $doc = DB::table('acc_documents')->where('id', $id)->first();
        if (! $doc || in_array((string) $doc->status, ['issued', 'cancelled', 'converted'], true)) {
            return;
        }

        DB::table('acc_documents')->where('id', $id)->update([
            'status' => 'issued',
            'updated_at' => now(),
        ]);

        $lines = DB::table('acc_document_lines')->where('document_id', $id)->get();
        if (in_array($doc->type, ['purchase', 'stock_in'], true) && $doc->warehouse_id) {
            foreach ($lines as $line) {
                if ($line->product_id) {
                    self::adjustStock((int) $doc->warehouse_id, (int) $line->product_id, (float) $line->qty, (int) $line->unit_cost);
                }
            }
        }
        if (in_array($doc->type, ['sale', 'stock_out'], true) && $doc->warehouse_id) {
            foreach ($lines as $line) {
                if ($line->product_id) {
                    self::adjustStock((int) $doc->warehouse_id, (int) $line->product_id, -1 * (float) $line->qty, (int) $line->unit_cost);
                }
            }
        }
        if ($doc->type === 'transfer' && $doc->warehouse_id && $doc->warehouse_to_id) {
            foreach ($lines as $line) {
                if ($line->product_id) {
                    self::adjustStock((int) $doc->warehouse_id, (int) $line->product_id, -1 * (float) $line->qty, (int) $line->unit_cost);
                    self::adjustStock((int) $doc->warehouse_to_id, (int) $line->product_id, (float) $line->qty, (int) $line->unit_cost);
                }
            }
        }
    }

    public static function adjustStock(int $warehouseId, int $productId, float $qtyDelta, int $unitCost = 0): void
    {
        if (! Schema::hasTable('acc_stock_balances')) {
            return;
        }
        $row = DB::table('acc_stock_balances')->where([
            'warehouse_id' => $warehouseId,
            'product_id' => $productId,
        ])->first();
        if (! $row) {
            DB::table('acc_stock_balances')->insert([
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'qty' => $qtyDelta,
                'avg_cost' => $unitCost,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return;
        }
        $oldQty = (float) $row->qty;
        $newQty = $oldQty + $qtyDelta;
        $avg = (int) $row->avg_cost;
        if ($qtyDelta > 0 && $unitCost > 0) {
            $totalCost = ($oldQty * $avg) + ($qtyDelta * $unitCost);
            $avg = $newQty > 0 ? (int) round($totalCost / $newQty) : $unitCost;
        }
        DB::table('acc_stock_balances')->where('id', $row->id)->update([
            'qty' => $newQty,
            'avg_cost' => $avg,
            'updated_at' => now(),
        ]);
    }

    public static function convertProforma(int $proformaId): ?int
    {
        $doc = DB::table('acc_documents')->where('id', $proformaId)->where('type', 'proforma')->first();
        if (! $doc) {
            return null;
        }
        $lines = DB::table('acc_document_lines')->where('document_id', $proformaId)->get();
        $serials = DB::table('acc_document_serials')->where('document_id', $proformaId)->get()->groupBy('line_id');
        $lineArr = [];
        foreach ($lines as $line) {
            $lineArr[] = [
                'product_id' => $line->product_id,
                'title' => $line->title,
                'sku' => $line->sku,
                'qty' => $line->qty,
                'unit_price' => $line->unit_price,
                'unit_cost' => $line->unit_cost,
                'line_total' => $line->line_total,
                'serials' => $serials->get($line->id)?->pluck('serial')->all() ?? [],
            ];
        }
        $newId = self::createDocument([
            'type' => 'sale',
            'status' => 'draft',
            'doc_date' => now()->toDateString(),
            'party_user_id' => $doc->party_user_id,
            'party_name' => $doc->party_name,
            'warehouse_id' => $doc->warehouse_id,
            'bank_id' => $doc->bank_id,
            'staff_id' => $doc->staff_id,
            'subtotal' => $doc->subtotal,
            'discount' => $doc->discount,
            'tax' => $doc->tax,
            'total' => $doc->total,
            'commission_rate' => $doc->commission_rate,
            'commission_amount' => $doc->commission_amount,
            'notes' => 'تبدیل از پیش‌فاکتور '.$doc->number,
            'related_id' => $proformaId,
        ], $lineArr);

        DB::table('acc_documents')->where('id', $proformaId)->update([
            'status' => 'converted',
            'related_id' => $newId,
            'updated_at' => now(),
        ]);

        return $newId;
    }

    /** @return array<string,mixed> */
    public static function dashboardStats(): array
    {
        $sum = function (string $type): int {
            try {
                return (int) DB::table('acc_documents')->where('type', $type)->where('status', 'issued')->sum('total');
            } catch (\Throwable) {
                return 0;
            }
        };
        $count = function (string $type): int {
            try {
                return (int) DB::table('acc_documents')->where('type', $type)->count();
            } catch (\Throwable) {
                return 0;
            }
        };

        return [
            'sales_total' => $sum('sale'),
            'purchase_total' => $sum('purchase'),
            'expense_total' => $sum('expense'),
            'proforma_open' => (int) (DB::table('acc_documents')->where('type', 'proforma')->whereIn('status', ['draft', 'issued'])->count() ?? 0),
            'warehouses' => (int) (DB::table('acc_warehouses')->where('is_active', 1)->count() ?? 0),
            'banks' => (int) (DB::table('acc_banks')->where('is_active', 1)->count() ?? 0),
            'docs' => [
                'sale' => $count('sale'),
                'purchase' => $count('purchase'),
                'proforma' => $count('proforma'),
                'voucher' => $count('voucher'),
                'expense' => $count('expense'),
                'stock_in' => $count('stock_in'),
                'stock_out' => $count('stock_out'),
                'transfer' => $count('transfer'),
                'payroll' => $count('payroll'),
            ],
        ];
    }

    public static function money(int|float $n): string
    {
        return number_format((int) $n).' تومان';
    }

    public static function nextCheckNumber(): string
    {
        $stamp = date('ym');
        $like = 'CHK-'.$stamp.'-%';
        $last = 0;
        try {
            $row = DB::table('acc_checks')->where('number', 'like', $like)->orderByDesc('id')->value('number');
            if (is_string($row) && preg_match('/(\d+)$/', $row, $m)) {
                $last = (int) $m[1];
            }
        } catch (\Throwable) {
        }

        return sprintf('CHK-%s-%04d', $stamp, $last + 1);
    }

    public static function nextInstallmentNumber(): string
    {
        $stamp = date('ym');
        $like = 'INS-'.$stamp.'-%';
        $last = 0;
        try {
            $row = DB::table('acc_installment_requests')->where('number', 'like', $like)->orderByDesc('id')->value('number');
            if (is_string($row) && preg_match('/(\d+)$/', $row, $m)) {
                $last = (int) $m[1];
            }
        } catch (\Throwable) {
        }

        return sprintf('INS-%s-%04d', $stamp, $last + 1);
    }

    /** Build monthly schedule rows after approval. */
    public static function buildInstallmentSchedule(int $requestId, int $months, int $monthlyAmount, ?string $startDate = null): void
    {
        if (! Schema::hasTable('acc_installment_schedules') || $months < 1) {
            return;
        }
        DB::table('acc_installment_schedules')->where('request_id', $requestId)->delete();
        $start = $startDate ? \Carbon\Carbon::parse($startDate) : now()->addMonth()->startOfMonth();
        for ($i = 1; $i <= $months; $i++) {
            DB::table('acc_installment_schedules')->insert([
                'request_id' => $requestId,
                'installment_no' => $i,
                'due_date' => $start->copy()->addMonths($i - 1)->toDateString(),
                'amount' => $monthlyAmount,
                'status' => 'pending',
                'paid_at' => null,
                'paid_amount' => 0,
                'notes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Best-effort ticket creation for installment requests.
     * Returns ticket id when a known tickets table exists.
     */
    public static function createInstallmentTicket(int $userId, string $subject, string $body, array $meta = []): ?int
    {
        $tables = ['tickets', 'support_tickets', 'helpdesk_tickets'];
        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            try {
                $cols = Schema::getColumnListing($table);
                $row = ['created_at' => now(), 'updated_at' => now()];
                foreach ([
                    'user_id' => $userId,
                    'customer_id' => $userId,
                    'created_by' => $userId,
                    'subject' => $subject,
                    'title' => $subject,
                    'message' => $body,
                    'body' => $body,
                    'content' => $body,
                    'description' => $body,
                    'status' => in_array('status', $cols, true) ? 'open' : null,
                    'priority' => in_array('priority', $cols, true) ? 'normal' : null,
                    'category' => in_array('category', $cols, true) ? 'installment' : null,
                    'type' => in_array('type', $cols, true) ? 'installment' : null,
                    'department' => in_array('department', $cols, true) ? 'sales' : null,
                ] as $col => $val) {
                    if ($val !== null && in_array($col, $cols, true)) {
                        $row[$col] = $val;
                    }
                }
                if (isset($meta['number']) && in_array('reference', $cols, true)) {
                    $row['reference'] = $meta['number'];
                }
                $id = (int) DB::table($table)->insertGetId($row);
                if ($id > 0) {
                    // optional first message table
                    foreach (['ticket_messages', 'support_ticket_messages', 'ticket_replies'] as $msgTable) {
                        if (! Schema::hasTable($msgTable)) {
                            continue;
                        }
                        $mcols = Schema::getColumnListing($msgTable);
                        $msg = ['created_at' => now(), 'updated_at' => now()];
                        foreach ([
                            'ticket_id' => $id,
                            'user_id' => $userId,
                            'message' => $body,
                            'body' => $body,
                            'content' => $body,
                            'is_staff' => false,
                        ] as $col => $val) {
                            if (in_array($col, $mcols, true)) {
                                $msg[$col] = $val;
                            }
                        }
                        try {
                            DB::table($msgTable)->insert($msg);
                        } catch (\Throwable) {
                        }
                        break;
                    }

                    return $id;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    /** Cancel a draft (or issued without stock lines) document. */
    public static function cancelDocument(int $id): bool
    {
        $doc = DB::table('acc_documents')->where('id', $id)->first();
        if (! $doc || in_array((string) $doc->status, ['cancelled', 'converted'], true)) {
            return false;
        }
        if ((string) $doc->status === 'issued') {
            // reverse stock if applicable
            $lines = DB::table('acc_document_lines')->where('document_id', $id)->get();
            if (in_array($doc->type, ['purchase', 'stock_in'], true) && $doc->warehouse_id) {
                foreach ($lines as $line) {
                    if ($line->product_id) {
                        self::adjustStock((int) $doc->warehouse_id, (int) $line->product_id, -1 * (float) $line->qty, (int) $line->unit_cost);
                    }
                }
            }
            if (in_array($doc->type, ['sale', 'stock_out'], true) && $doc->warehouse_id) {
                foreach ($lines as $line) {
                    if ($line->product_id) {
                        self::adjustStock((int) $doc->warehouse_id, (int) $line->product_id, (float) $line->qty, (int) $line->unit_cost);
                    }
                }
            }
            if ($doc->type === 'transfer' && $doc->warehouse_id && $doc->warehouse_to_id) {
                foreach ($lines as $line) {
                    if ($line->product_id) {
                        self::adjustStock((int) $doc->warehouse_id, (int) $line->product_id, (float) $line->qty, (int) $line->unit_cost);
                        self::adjustStock((int) $doc->warehouse_to_id, (int) $line->product_id, -1 * (float) $line->qty, (int) $line->unit_cost);
                    }
                }
            }
        }
        DB::table('acc_documents')->where('id', $id)->update([
            'status' => 'cancelled',
            'updated_at' => now(),
        ]);

        return true;
    }

    public static function deleteDocument(int $id): bool
    {
        $doc = DB::table('acc_documents')->where('id', $id)->first();
        if (! $doc || ! in_array((string) $doc->status, ['draft', 'cancelled'], true)) {
            return false;
        }
        DB::table('acc_document_serials')->where('document_id', $id)->delete();
        DB::table('acc_document_lines')->where('document_id', $id)->delete();
        DB::table('acc_documents')->where('id', $id)->delete();

        return true;
    }
}
