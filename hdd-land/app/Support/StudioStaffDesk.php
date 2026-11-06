<?php

namespace App\Support;

/**
 * Representative desk: prices, sales, share, tickets, invoices.
 * Stored in settings so a missing table never 500s the site.
 */
class StudioStaffDesk
{
    public const KEY = 'studio_staff_desk';

    /** @return array<string, mixed> */
    public static function state(): array
    {
        $raw = [];
        try {
            $raw = SettingsStore::get(self::KEY, []);
        } catch (\Throwable) {
            $raw = [];
        }
        if (is_string($raw)) {
            $raw = json_decode($raw, true) ?: [];
        }
        if (! is_array($raw)) {
            $raw = [];
        }

        return [
            'sales' => is_array($raw['sales'] ?? null) ? $raw['sales'] : [],
            'invoices' => is_array($raw['invoices'] ?? null) ? $raw['invoices'] : [],
            'tickets' => is_array($raw['tickets'] ?? null) ? $raw['tickets'] : [],
            'balances' => is_array($raw['balances'] ?? null) ? $raw['balances'] : [],
            'messages' => is_array($raw['messages'] ?? null) ? $raw['messages'] : [],
        ];
    }

    /** @param  array<string, mixed>  $state */
    public static function save(array $state): void
    {
        try {
            SettingsStore::set(self::KEY, $state);
        } catch (\Throwable) {
        }
    }

    /** @return list<array{label:string,url:string,icon:string,match:string}> */
    public static function menu(): array
    {
        return [
            ['label' => 'میز کار', 'url' => '/staff/desk', 'icon' => '▣', 'match' => 'staff/desk'],
            ['label' => 'خرید', 'url' => '/staff/desk/buy', 'icon' => '⬇', 'match' => 'staff/desk/buy'],
            ['label' => 'فروش', 'url' => '/staff/desk/sell', 'icon' => '⬆', 'match' => 'staff/desk/sell'],
            ['label' => 'قیمت‌های فروش', 'url' => '/staff/desk/prices', 'icon' => '﷼', 'match' => 'staff/desk/prices'],
            ['label' => 'فروش‌های من', 'url' => '/staff/desk/sales', 'icon' => '☰', 'match' => 'staff/desk/sales'],
            ['label' => 'فاکتورها', 'url' => '/staff/desk/invoices', 'icon' => '▤', 'match' => 'staff/desk/invoices'],
            ['label' => 'لیست نمایندگان', 'url' => '/staff/desk/reps', 'icon' => '☺', 'match' => 'staff/desk/reps'],
            ['label' => 'تیکت‌ها', 'url' => '/staff/desk/tickets', 'icon' => '✉', 'match' => 'staff/desk/tickets'],
            ['label' => 'پیامک', 'url' => '/staff/desk/sms', 'icon' => '☎', 'match' => 'staff/desk/sms'],
            ['label' => 'پشتیبانی', 'url' => '/support', 'icon' => '?', 'match' => 'support'],
            ['label' => 'پنل ورود', 'url' => '/auth/staff', 'icon' => '→', 'match' => 'auth/staff'],
        ];
    }

    /** @return list<array<string, mixed>> */
    public static function reps(): array
    {
        $out = [];
        try {
            if (class_exists(\Illuminate\Support\Facades\Schema::class)
                && \Illuminate\Support\Facades\Schema::hasTable('staff_members')) {
                $rows = \Illuminate\Support\Facades\DB::table('staff_members')
                    ->where('is_active', 1)
                    ->orderBy('name')
                    ->get();
                foreach ($rows as $row) {
                    $uid = (int) ($row->user_id ?? 0);
                    $out[] = [
                        'id' => (int) ($row->id ?? 0),
                        'user_id' => $uid,
                        'name' => (string) ($row->name ?? 'نماینده'),
                        'phone' => (string) ($row->phone ?? ''),
                        'role' => (string) ($row->role ?? 'seller'),
                        'commission' => (float) ($row->commission_rate ?? 0),
                        'balance' => self::balance($uid),
                    ];
                }
            }
        } catch (\Throwable) {
        }

        return $out;
    }

    public static function balance(int $userId): int
    {
        $state = self::state();

        return (int) ($state['balances'][(string) $userId] ?? 0);
    }

    /**
     * @param  array<string, mixed>  $offer
     * @return array<string, mixed>
     */
    public static function recordSale(array $offer, int $userId, string $buyer, string $gateway): array
    {
        $state = self::state();
        $id = 'INV-'.date('ymdHis').'-'.substr(bin2hex(random_bytes(3)), 0, 6);
        $price = (int) preg_replace('/\D+/', '', (string) ($offer['price'] ?? '')) ?: 0;
        $rate = (float) ($offer['commission'] ?? 0);
        $share = (int) round($price * $rate / 100);
        $invoice = [
            'id' => $id,
            'sku' => (string) ($offer['sku'] ?? ''),
            'title' => (string) ($offer['title'] ?? ''),
            'url' => (string) ($offer['url'] ?? '/'),
            'buyer' => $buyer,
            'buyer_id' => (int) ($offer['buyer_id'] ?? 0),
            'seller_id' => $userId,
            'price' => $price,
            'price_label' => $price > 0 ? number_format($price).' تومان' : StudioOffers::PRICE_LABEL,
            'commission' => $rate,
            'share' => $share,
            'gateway' => $gateway,
            'status' => 'recorded',
            'created_at' => date('c'),
        ];
        $state['invoices'][] = $invoice;
        $state['sales'][] = $invoice;
        if ($userId > 0) {
            $key = (string) $userId;
            $state['balances'][$key] = (int) ($state['balances'][$key] ?? 0) + $share;
        }
        self::save($state);
        if (class_exists(StudioNotify::class)) {
            StudioNotify::invoice($invoice);
        }

        return $invoice;
    }

    /** @return list<array<string, mixed>> */
    public static function salesFor(int $userId): array
    {
        $rows = [];
        foreach (self::state()['sales'] as $row) {
            if ((int) ($row['seller_id'] ?? 0) === $userId || $userId === 0) {
                $rows[] = $row;
            }
        }

        return array_reverse($rows);
    }

    /** @return list<array<string, mixed>> */
    public static function salesForBuyer(int $userId): array
    {
        $rows = [];
        foreach (self::state()['sales'] as $row) {
            $buyerId = (int) ($row['buyer_id'] ?? 0);
            $sellerId = (int) ($row['seller_id'] ?? 0);
            if ($userId > 0 && ($buyerId === $userId || ($buyerId === 0 && $sellerId === $userId))) {
                $rows[] = $row;
            }
        }

        return array_reverse($rows);
    }

    /**
     * @return array<string, mixed>
     */
    public static function openTicket(int $userId, string $name, string $phone, string $subject, string $body): array
    {
        $state = self::state();
        $ticket = [
            'id' => 'T-'.date('ymdHis').'-'.substr(bin2hex(random_bytes(2)), 0, 4),
            'user_id' => $userId,
            'name' => $name,
            'phone' => $phone,
            'subject' => $subject,
            'body' => $body,
            'status' => 'open',
            'created_at' => date('c'),
            'replies' => [],
        ];
        $state['tickets'][] = $ticket;
        self::save($state);
        if (class_exists(StudioNotify::class)) {
            StudioNotify::ticketOpened($ticket);
        }

        return $ticket;
    }

    /** @return list<array<string, mixed>> */
    public static function ticketsFor(int $userId, bool $all = false): array
    {
        $rows = [];
        foreach (self::state()['tickets'] as $row) {
            if ($all || (int) ($row['user_id'] ?? 0) === $userId) {
                $rows[] = $row;
            }
        }

        return array_reverse($rows);
    }
}
