<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Plugins\Studio\src\Support\StudioSms;

/**
 * Soft-wires live plugins (tickets, cart, orders, wallet, serials)
 * without fataling if a table or class is missing.
 */
class StudioPlugins
{
    /** @return list<string> */
    public static function ticketTables(): array
    {
        $out = [];
        foreach (['tickets', 'support_tickets', 'customer_tickets'] as $table) {
            try {
                if (Schema::hasTable($table)) {
                    $out[] = $table;
                }
            } catch (\Throwable) {
            }
        }

        return $out;
    }

    public static function ticketsReady(): bool
    {
        return self::ticketTables() !== [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function tickets(int $userId, bool $all = false): array
    {
        $rows = [];
        foreach (self::ticketTables() as $table) {
            try {
                $q = DB::table($table);
                if (! $all && $userId > 0 && Schema::hasColumn($table, 'user_id')) {
                    $q->where('user_id', $userId);
                }
                if (Schema::hasColumn($table, 'id')) {
                    $q->orderByDesc('id');
                }
                foreach ($q->limit(80)->get() as $row) {
                    $rows[] = [
                        'id' => (string) ($row->id ?? ''),
                        'subject' => (string) ($row->subject ?? $row->title ?? 'تیکت'),
                        'body' => (string) ($row->body ?? $row->message ?? $row->content ?? ''),
                        'status' => (string) ($row->status ?? 'open'),
                        'user_id' => (int) ($row->user_id ?? 0),
                        'phone' => (string) ($row->phone ?? ''),
                        'created_at' => (string) ($row->created_at ?? ''),
                        'source' => $table,
                    ];
                }
            } catch (\Throwable) {
            }
        }
        if (class_exists(StudioStaffDesk::class)) {
            foreach (StudioStaffDesk::ticketsFor($userId, $all) as $row) {
                $row['source'] = $row['source'] ?? 'desk';
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    public static function openTicket(int $userId, string $name, string $phone, string $subject, string $body): array
    {
        $phone = self::phone($phone);
        $ticket = [
            'id' => '',
            'user_id' => $userId,
            'name' => $name,
            'phone' => $phone,
            'subject' => $subject,
            'body' => $body,
            'status' => 'open',
            'created_at' => date('c'),
        ];
        foreach (self::ticketTables() as $table) {
            try {
                $data = [];
                foreach ([
                    'user_id' => $userId,
                    'name' => $name,
                    'phone' => $phone,
                    'subject' => $subject,
                    'title' => $subject,
                    'body' => $body,
                    'message' => $body,
                    'content' => $body,
                    'status' => 'open',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ] as $col => $val) {
                    if (Schema::hasColumn($table, $col)) {
                        $data[$col] = $val;
                    }
                }
                if ($data === []) {
                    continue;
                }
                $id = DB::table($table)->insertGetId($data);
                $ticket['id'] = (string) $id;
                $ticket['source'] = $table;
                break;
            } catch (\Throwable) {
            }
        }
        if ($ticket['id'] === '' && class_exists(StudioStaffDesk::class)) {
            $ticket = StudioStaffDesk::openTicket($userId, $name, $phone, $subject, $body);
        }
        if (class_exists(StudioNotify::class)) {
            StudioNotify::ticketOpened($ticket);
        }

        return $ticket;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function orders(int $userId): array
    {
        $rows = [];
        try {
            if ($userId > 0 && Schema::hasTable('orders')) {
                $q = DB::table('orders');
                if (Schema::hasColumn('orders', 'user_id')) {
                    $q->where('user_id', $userId);
                }
                foreach ($q->orderByDesc('id')->limit(80)->get() as $row) {
                    $rows[] = [
                        'id' => (string) ($row->id ?? ''),
                        'number' => (string) ($row->order_number ?? $row->id ?? ''),
                        'title' => (string) ($row->title ?? 'سفارش'),
                        'total' => (int) ($row->total ?? 0),
                        'status' => (string) ($row->status ?? ''),
                        'created_at' => (string) ($row->created_at ?? ''),
                        'url' => '/account/orders',
                    ];
                }
            }
        } catch (\Throwable) {
        }
        if (class_exists(StudioStaffDesk::class)) {
            foreach (StudioStaffDesk::salesForBuyer($userId) as $row) {
                $rows[] = [
                    'id' => (string) ($row['id'] ?? ''),
                    'number' => (string) ($row['id'] ?? ''),
                    'title' => (string) ($row['title'] ?? 'فروش'),
                    'total' => (int) ($row['price'] ?? 0),
                    'status' => (string) ($row['status'] ?? 'recorded'),
                    'created_at' => (string) ($row['created_at'] ?? ''),
                    'url' => (string) ($row['url'] ?? '/account/invoices'),
                ];
            }
        }

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function invoices(int $userId): array
    {
        $rows = [];
        foreach (['invoices', 'order_invoices'] as $table) {
            try {
                if (! Schema::hasTable($table)) {
                    continue;
                }
                $q = DB::table($table);
                if ($userId > 0) {
                    if (Schema::hasColumn($table, 'user_id')) {
                        $q->where('user_id', $userId);
                    } elseif (Schema::hasColumn($table, 'customer_id')) {
                        $q->where('customer_id', $userId);
                    }
                }
                foreach ($q->orderByDesc('id')->limit(80)->get() as $row) {
                    $rows[] = [
                        'id' => (string) ($row->id ?? ''),
                        'title' => (string) ($row->title ?? $row->number ?? 'فاکتور'),
                        'price_label' => isset($row->total) ? number_format((int) $row->total).' تومان' : StudioOffers::PRICE_LABEL,
                        'status' => (string) ($row->status ?? ''),
                        'gateway' => (string) ($row->gateway ?? ''),
                    ];
                }
            } catch (\Throwable) {
            }
        }
        if (class_exists(StudioStaffDesk::class)) {
            foreach (StudioStaffDesk::salesForBuyer($userId) as $row) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function shopProducts(int $limit = 24): array
    {
        $rows = [];
        try {
            if (Schema::hasTable('products')) {
                $q = DB::table('products');
                if (Schema::hasColumn('products', 'is_active')) {
                    $q->where('is_active', 1);
                } elseif (Schema::hasColumn('products', 'status')) {
                    $q->where('status', 'publish');
                }
                foreach ($q->orderByDesc('id')->limit($limit)->get() as $row) {
                    $id = (int) ($row->id ?? 0);
                    $slug = (string) ($row->slug ?? $id);
                    $price = (int) ($row->price ?? 0);
                    $rows[] = [
                        'sku' => 'shop-'.$id,
                        'kind' => 'shop',
                        'title' => (string) ($row->name ?? 'محصول'),
                        'short' => (string) ($row->short_description ?? ''),
                        'url' => '/product/'.$slug,
                        'sell_url' => '/pay/start?sku=shop-'.$id,
                        'price' => $price,
                        'price_label' => $price > 0 ? number_format($price).' تومان' : StudioOffers::PRICE_LABEL,
                        'commission' => 0,
                    ];
                }
            }
        } catch (\Throwable) {
        }

        return $rows;
    }

    /** @return array<string, mixed>|null */
    public static function shopOffer(string $sku): ?array
    {
        if (! str_starts_with($sku, 'shop-')) {
            return null;
        }
        $id = (int) substr($sku, 5);
        foreach (self::shopProducts(200) as $row) {
            if ((string) ($row['sku'] ?? '') === $sku || str_ends_with((string) ($row['sku'] ?? ''), (string) $id)) {
                return $row;
            }
        }

        return null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function serials(int $userId): array
    {
        $rows = [];
        try {
            if ($userId > 0 && Schema::hasTable('product_serials')) {
                $q = DB::table('product_serials');
                foreach (['user_id', 'sold_to_user_id', 'customer_id'] as $col) {
                    if (Schema::hasColumn('product_serials', $col)) {
                        $q->where($col, $userId);
                        break;
                    }
                }
                foreach ($q->orderByDesc('id')->limit(80)->get() as $row) {
                    $rows[] = [
                        'serial' => (string) ($row->serial ?? ''),
                        'title' => (string) ($row->warranty_company ?? 'سریال'),
                        'status' => (string) ($row->status ?? ''),
                    ];
                }
            }
        } catch (\Throwable) {
        }

        return $rows;
    }

    /** @return array{balance:int,ready:bool} */
    public static function wallet(int $userId): array
    {
        try {
            if ($userId > 0 && Schema::hasTable('wallets') && Schema::hasColumn('wallets', 'user_id')) {
                $row = DB::table('wallets')->where('user_id', $userId)->first();
                if ($row) {
                    return ['balance' => (int) ($row->balance ?? $row->amount ?? 0), 'ready' => true];
                }
            }
        } catch (\Throwable) {
        }

        return ['balance' => 0, 'ready' => false];
    }

    public static function cartCount(): int
    {
        try {
            if (class_exists(\Plugins\CartCheckout\src\Cart::class)) {
                return (int) \Plugins\CartCheckout\src\Cart::count();
            }
        } catch (\Throwable) {
        }

        return 0;
    }

    public static function cartUrl(): string
    {
        return class_exists(\Plugins\CartCheckout\src\Cart::class) ? '/cart' : '/account/buy';
    }

    public static function checkoutUrl(): string
    {
        return class_exists(\Plugins\CartCheckout\src\Cart::class) ? '/checkout' : '/account/buy';
    }

    public static function phone(string $raw): string
    {
        try {
            if (class_exists(StudioSms::class)) {
                return StudioSms::normalizePhone($raw);
            }
        } catch (\Throwable) {
        }

        return trim($raw);
    }

    public static function currentUserId(): int
    {
        try {
            return (int) (Auth::id() ?? 0);
        } catch (\Throwable) {
            return 0;
        }
    }
}
