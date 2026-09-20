<?php

namespace Plugins\Accounting\src\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;

/**
 * One sale = one ledger row.
 * Shop orders, manual invoices, and approved installments share acc_documents
 * and the same products.stock / acc_stock_balances / product_serials.
 */
class AccCommerce
{
    public static function boot(): void
    {
        try {
            Event::listen('order.paid', static function ($orderId) {
                self::syncOrder((int) $orderId, true);
            });
            Event::listen('order.created', static function ($orderId) {
                self::syncOrder((int) $orderId, null);
            });
            Event::listen('order.cancelled', static function ($orderId) {
                self::cancelLinkedSale((int) $orderId);
            });
        } catch (\Throwable) {
        }
    }

    public static function defaultWarehouseId(): ?int
    {
        if (! Schema::hasTable('acc_warehouses')) {
            return null;
        }
        $id = DB::table('acc_warehouses')->where('is_default', 1)->where('is_active', 1)->value('id');
        if ($id) {
            return (int) $id;
        }
        $id = DB::table('acc_warehouses')->where('is_active', 1)->orderBy('id')->value('id');

        return $id ? (int) $id : null;
    }

    public static function staffMemberId(?int $userId): ?int
    {
        if (! $userId || ! Schema::hasTable('staff_members')) {
            return null;
        }
        $id = DB::table('staff_members')->where('user_id', $userId)->where('is_active', 1)->value('id');

        return $id ? (int) $id : null;
    }

    public static function userName(?int $userId): ?string
    {
        if (! $userId || ! Schema::hasTable('users')) {
            return null;
        }
        $name = DB::table('users')->where('id', $userId)->value('name');

        return $name !== null && $name !== '' ? (string) $name : null;
    }

    /** @return \Illuminate\Support\Collection<int,object> */
    public static function catalogProducts(string $q = '', int $limit = 80)
    {
        if (! Schema::hasTable('products')) {
            return collect();
        }
        $query = DB::table('products')->orderByDesc('id')->limit($limit);
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', '%'.$q.'%');
                if (Schema::hasColumn('products', 'sku')) {
                    $w->orWhere('sku', 'like', '%'.$q.'%');
                }
            });
        }
        $cols = ['id', 'name'];
        foreach (['sku', 'price', 'cost_price', 'stock', 'stock_status'] as $col) {
            if (Schema::hasColumn('products', $col)) {
                $cols[] = $col;
            }
        }

        return $query->get($cols);
    }

    /** @return \Illuminate\Support\Collection<int,object> */
    public static function customers(string $q = '', int $limit = 80)
    {
        if (! Schema::hasTable('users')) {
            return collect();
        }
        $query = DB::table('users')->orderBy('name')->limit($limit);
        if (Schema::hasColumn('users', 'role')) {
            $query->where(function ($w) {
                $w->whereNull('role')->orWhereNotIn('role', ['admin', 'staff']);
            });
        }
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', '%'.$q.'%');
                if (Schema::hasColumn('users', 'mobile')) {
                    $w->orWhere('mobile', 'like', '%'.$q.'%');
                }
                if (Schema::hasColumn('users', 'phone')) {
                    $w->orWhere('phone', 'like', '%'.$q.'%');
                }
                if (Schema::hasColumn('users', 'email')) {
                    $w->orWhere('email', 'like', '%'.$q.'%');
                }
            });
        }
        $cols = ['id', 'name'];
        foreach (['email', 'mobile', 'phone'] as $col) {
            if (Schema::hasColumn('users', $col)) {
                $cols[] = $col;
            }
        }

        return $query->get($cols);
    }

    public static function commissionBaseAmount(int $orderId): int
    {
        if (! Schema::hasTable('orders')) {
            return 0;
        }
        $order = DB::table('orders')->where('id', $orderId)->first();
        if (! $order) {
            return 0;
        }
        $subtotal = (int) ($order->subtotal ?? 0);
        if (Schema::hasTable('order_items') && Schema::hasColumn('order_items', 'unit_cost')) {
            $qtyExpr = Schema::hasColumn('order_items', 'quantity')
                ? 'quantity'
                : (Schema::hasColumn('order_items', 'qty') ? 'qty' : '1');
            $cost = (int) DB::table('order_items')
                ->where('order_id', $orderId)
                ->selectRaw('COALESCE(SUM(COALESCE(unit_cost,0) * COALESCE('.$qtyExpr.',1)),0) as c')
                ->value('c');

            return max(0, $subtotal - $cost);
        }

        return max(0, $subtotal);
    }

    public static function findSaleForOrder(int $orderId): ?object
    {
        if (! Schema::hasTable('acc_documents') || $orderId < 1) {
            return null;
        }
        if (Schema::hasColumn('acc_documents', 'order_id')) {
            $row = DB::table('acc_documents')->where('type', 'sale')->where('order_id', $orderId)->orderByDesc('id')->first();
            if ($row) {
                return $row;
            }
        }

        return DB::table('acc_documents')
            ->where('type', 'sale')
            ->where('related_id', $orderId)
            ->where(function ($q) use ($orderId) {
                $q->where('notes', 'like', '%سفارش #'.$orderId.'%')
                    ->orWhere('notes', 'like', '%order #'.$orderId.'%');
            })
            ->orderByDesc('id')
            ->first();
    }

    public static function cancelLinkedSale(int $orderId): ?int
    {
        $doc = self::findSaleForOrder($orderId);
        if (! $doc) {
            return null;
        }
        AccEngine::cancelDocument((int) $doc->id);

        return (int) $doc->id;
    }

    /**
     * Create or refresh the sale document for a shop order.
     * $forceIssue: true=issue, false=draft, null=decide from order status.
     */
    public static function syncOrder(int $orderId, ?bool $forceIssue = null): ?int
    {
        if ($orderId < 1 || ! Schema::hasTable('orders') || ! Schema::hasTable('acc_documents')) {
            return null;
        }
        $order = DB::table('orders')->where('id', $orderId)->first();
        if (! $order) {
            return null;
        }
        $status = (string) ($order->status ?? '');
        if (AccMath::orderLooksCancelled($status)) {
            return self::cancelLinkedSale($orderId);
        }

        $existing = self::findSaleForOrder($orderId);
        if ($existing && (string) $existing->status === 'issued') {
            return (int) $existing->id;
        }

        $userId = (int) ($order->user_id ?? $order->customer_id ?? 0) ?: null;
        $partyName = trim((string) ($order->customer_name ?? $order->name ?? ''));
        if ($partyName === '') {
            $partyName = self::userName($userId) ?: ('سفارش #'.$orderId);
        }

        $lines = self::linesFromOrder($orderId, $order);
        $subtotal = 0;
        foreach ($lines as $line) {
            $subtotal += (int) ($line['line_total'] ?? 0);
        }
        $discount = (int) ($order->discount ?? $order->discount_amount ?? 0);
        $tax = (int) ($order->tax ?? $order->tax_amount ?? 0);
        $total = (int) ($order->total ?? $order->grand_total ?? max(0, $subtotal - $discount + $tax));
        $soldBy = (int) ($order->sold_by_user_id ?? 0) ?: null;
        $shouldIssue = $forceIssue ?? AccMath::orderLooksPaid($status);

        if ($existing && (string) $existing->status === 'draft') {
            DB::table('acc_document_serials')->where('document_id', $existing->id)->delete();
            DB::table('acc_document_lines')->where('document_id', $existing->id)->delete();
            DB::table('acc_documents')->where('id', $existing->id)->delete();
            $existing = null;
        }

        $id = AccEngine::createDocument([
            'type' => 'sale',
            'status' => 'draft',
            'doc_date' => isset($order->created_at) ? substr((string) $order->created_at, 0, 10) : now()->toDateString(),
            'party_user_id' => $userId,
            'party_name' => $partyName,
            'warehouse_id' => self::defaultWarehouseId(),
            'staff_id' => self::staffMemberId($soldBy),
            'order_id' => $orderId,
            'related_id' => $orderId,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax' => $tax,
            'total' => $total > 0 ? $total : max(0, $subtotal - $discount + $tax),
            'commission_rate' => (float) ($order->commission_rate ?? 0),
            'commission_amount' => (int) ($order->commission_amount ?? 0),
            'payment_method' => $order->payment_method ?? $order->gateway ?? 'shop',
            'notes' => 'همگام با سفارش #'.$orderId,
            'source' => 'shop',
        ], $lines);

        if ($shouldIssue) {
            AccEngine::issueDocument($id);
        }

        return $id;
    }

    public static function syncPendingShopOrders(int $limit = 40): int
    {
        if (! Schema::hasTable('orders') || ! Schema::hasTable('acc_documents')) {
            return 0;
        }
        $q = DB::table('orders')->orderByDesc('id')->limit($limit * 3);
        $count = 0;
        foreach ($q->get() as $order) {
            if ($count >= $limit) {
                break;
            }
            $status = (string) ($order->status ?? '');
            if (! AccMath::orderLooksPaid($status) && ! AccMath::orderLooksCancelled($status)) {
                continue;
            }
            $existing = self::findSaleForOrder((int) $order->id);
            if ($existing && (string) $existing->status === 'issued' && AccMath::orderLooksPaid($status)) {
                continue;
            }
            if (self::syncOrder((int) $order->id) ) {
                $count++;
            }
        }

        return $count;
    }

    public static function saleFromInstallment(object $row, bool $issue = true): ?int
    {
        if (! Schema::hasTable('acc_documents')) {
            return null;
        }
        $existingId = (int) ($row->document_id ?? 0);
        if ($existingId > 0) {
            $doc = DB::table('acc_documents')->where('id', $existingId)->first();
            if ($doc) {
                if ($issue && (string) $doc->status === 'draft') {
                    AccEngine::issueDocument($existingId);
                }

                return $existingId;
            }
        }

        $userId = (int) ($row->user_id ?? 0) ?: null;
        $productId = (int) ($row->product_id ?? 0) ?: null;
        $title = (string) ($row->product_title ?? 'فروش اقساطی');
        $price = (int) ($row->product_price ?? 0);
        $sku = null;
        $cost = 0;
        if ($productId && Schema::hasTable('products')) {
            $p = DB::table('products')->where('id', $productId)->first();
            if ($p) {
                $title = $title !== '' ? $title : (string) ($p->name ?? $title);
                $sku = $p->sku ?? null;
                $cost = (int) ($p->cost_price ?? 0);
                if ($price <= 0) {
                    $price = (int) ($p->price ?? 0);
                }
            }
        }

        $id = AccEngine::createDocument([
            'type' => 'sale',
            'status' => 'draft',
            'doc_date' => now()->toDateString(),
            'party_user_id' => $userId,
            'party_name' => (string) ($row->customer_name ?? self::userName($userId) ?? 'مشتری اقساط'),
            'warehouse_id' => self::defaultWarehouseId(),
            'related_id' => (int) $row->id,
            'subtotal' => $price,
            'discount' => 0,
            'tax' => 0,
            'total' => $price,
            'payment_method' => 'installment',
            'notes' => 'فروش اقساطی '.$row->number,
            'source' => 'installment',
        ], [[
            'product_id' => $productId,
            'title' => $title,
            'sku' => $sku,
            'qty' => 1,
            'unit_price' => $price,
            'unit_cost' => $cost,
            'line_total' => $price,
            'serials' => [],
        ]]);

        if ($issue) {
            AccEngine::issueDocument($id);
        }

        if (Schema::hasTable('acc_installment_requests') && Schema::hasColumn('acc_installment_requests', 'document_id')) {
            DB::table('acc_installment_requests')->where('id', $row->id)->update([
                'document_id' => $id,
                'updated_at' => now(),
            ]);
        }

        return $id;
    }

    /** Apply or reverse products.stock + product_serials for an issued/cancelled document. */
    public static function applyCatalogStock(object $doc, $lines, int $direction): void
    {
        // Shop checkout / staff-sell already moved products.stock for source=shop.
        if ((string) ($doc->source ?? '') === 'shop') {
            return;
        }
        $sign = AccMath::catalogStockSign((string) $doc->type) * $direction;
        if ($sign === 0) {
            return;
        }
        foreach ($lines as $line) {
            $productId = (int) ($line->product_id ?? 0);
            $qty = (float) ($line->qty ?? 0);
            if ($productId < 1 || $qty == 0.0) {
                continue;
            }
            self::bumpProductStock($productId, $sign * $qty);
        }
        self::syncDocumentSerials($doc, $lines, $direction > 0);
    }

    public static function bumpProductStock(int $productId, float $delta): void
    {
        if (! Schema::hasTable('products') || ! Schema::hasColumn('products', 'stock') || $productId < 1 || $delta == 0.0) {
            return;
        }
        $row = DB::table('products')->where('id', $productId)->first();
        if (! $row) {
            return;
        }
        $new = (float) ($row->stock ?? 0) + $delta;
        $data = ['stock' => $new];
        if (Schema::hasColumn('products', 'updated_at')) {
            $data['updated_at'] = now();
        }
        if (Schema::hasColumn('products', 'stock_status') && ! empty($row->manage_stock)) {
            if ($new <= 0 && ($row->stock_status ?? '') !== 'onbackorder') {
                $data['stock_status'] = 'outofstock';
            } elseif ($new > 0 && ($row->stock_status ?? '') === 'outofstock') {
                $data['stock_status'] = 'instock';
            }
        }
        DB::table('products')->where('id', $productId)->update($data);
    }

    protected static function syncDocumentSerials(object $doc, $lines, bool $issuing): void
    {
        if (! Schema::hasTable('acc_document_serials') || ! Schema::hasTable('product_serials')) {
            return;
        }
        $serials = DB::table('acc_document_serials')->where('document_id', $doc->id)->get();
        $cols = Schema::getColumnListing('product_serials');
        foreach ($serials as $sn) {
            $serial = trim((string) $sn->serial);
            if ($serial === '') {
                continue;
            }
            $q = DB::table('product_serials')->where('serial', $serial);
            if (! empty($sn->product_id)) {
                $q->where('product_id', $sn->product_id);
            }
            $row = $q->first();
            $payload = [];
            if (in_array('status', $cols, true)) {
                $payload['status'] = $issuing && AccMath::catalogStockSign((string) $doc->type) < 0
                    ? 'sold'
                    : 'available';
            }
            if (in_array('updated_at', $cols, true)) {
                $payload['updated_at'] = now();
            }
            if ($issuing && in_array('sold_at', $cols, true)) {
                $payload['sold_at'] = now();
            }
            if (! $issuing && in_array('sold_at', $cols, true)) {
                $payload['sold_at'] = null;
            }
            if ($issuing && ! empty($doc->order_id) && in_array('order_id', $cols, true)) {
                $payload['order_id'] = $doc->order_id;
            }
            if ($payload === []) {
                continue;
            }
            if ($row) {
                DB::table('product_serials')->where('id', $row->id)->update($payload);
            }
        }
    }

    /**
     * @return list<array<string,mixed>>
     */
    protected static function linesFromOrder(int $orderId, object $order): array
    {
        $lines = [];
        if (Schema::hasTable('order_items')) {
            $items = DB::table('order_items')->where('order_id', $orderId)->get();
            foreach ($items as $it) {
                $qty = (float) ($it->quantity ?? $it->qty ?? 1);
                $price = (int) ($it->unit_price ?? $it->price ?? 0);
                $cost = (int) ($it->unit_cost ?? $it->cost ?? 0);
                $title = trim((string) ($it->title ?? $it->name ?? $it->product_name ?? ''));
                $productId = (int) ($it->product_id ?? 0) ?: null;
                if ($title === '' && $productId && Schema::hasTable('products')) {
                    $title = (string) (DB::table('products')->where('id', $productId)->value('name') ?? '');
                }
                if ($title === '') {
                    $title = 'قلم سفارش';
                }
                $lineTotal = (int) ($it->line_total ?? $it->total ?? (int) round($qty * $price));
                $serials = [];
                foreach (['serial', 'serial_number'] as $col) {
                    if (! empty($it->{$col})) {
                        $serials[] = trim((string) $it->{$col});
                    }
                }
                if (! empty($it->serials)) {
                    $serials = array_merge($serials, preg_split('/[\s,;]+/u', (string) $it->serials, -1, PREG_SPLIT_NO_EMPTY) ?: []);
                }
                $lines[] = [
                    'product_id' => $productId,
                    'title' => $title,
                    'sku' => $it->sku ?? null,
                    'qty' => $qty,
                    'unit_price' => $price,
                    'unit_cost' => $cost,
                    'line_total' => $lineTotal,
                    'serials' => array_values(array_unique(array_filter($serials))),
                ];
            }
        }
        if ($lines === []) {
            $total = (int) ($order->total ?? $order->grand_total ?? $order->subtotal ?? 0);
            $lines[] = [
                'title' => 'سفارش #'.$orderId,
                'qty' => 1,
                'unit_price' => $total,
                'unit_cost' => 0,
                'line_total' => $total,
                'serials' => [],
            ];
        }

        return $lines;
    }
}
