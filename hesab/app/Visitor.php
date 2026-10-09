<?php
declare(strict_types=1);

/**
 * Sales visitor (ویزیتور) domain: master data, commission, visits, cartable, SMS.
 */
final class Visitor
{
    public static function listActive(): array
    {
        return Database::query(
            'SELECT * FROM visitors WHERE is_active=1 ORDER BY name'
        )->fetchAll();
    }

    public static function find(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $row = Database::query('SELECT * FROM visitors WHERE id=?', [$id])->fetch();
        return $row ?: null;
    }

    public static function commissionPercent(array $visitor, ?float $override = null): float
    {
        if ($override !== null) {
            return max(0, min(100, $override));
        }
        return max(0, min(100, (float) ($visitor['commission_percent'] ?? 0)));
    }

    /** Create cartable inbox item. */
    public static function cartablePush(array $data): int
    {
        Database::query(
            'INSERT INTO visitor_cartable
              (visitor_id, title, body, kind, status, ref_type, ref_id, due_date, created_by, created_at)
             VALUES (?,?,?,?,?,?,?,?,?,NOW())',
            [
                (int) ($data['visitor_id'] ?? 0) ?: null,
                trim((string) ($data['title'] ?? '')),
                trim((string) ($data['body'] ?? '')),
                $data['kind'] ?? 'task',
                $data['status'] ?? 'open',
                $data['ref_type'] ?? null,
                isset($data['ref_id']) ? (int) $data['ref_id'] : null,
                $data['due_date'] ?? null,
                current_user()['id'] ?? null,
            ]
        );
        return (int) Database::pdo()->lastInsertId();
    }

    /** Notify visitor via NiazPardaz SMS using visitor templates. */
    public static function smsNotify(array $visitor, string $templateKey, array $vars = []): array
    {
        $phone = Sms::normalizeMobile((string) ($visitor['phone'] ?? ''));
        if ($phone === '') {
            return ['ok' => false, 'message' => 'موبایل ویزیتور ثبت نشده'];
        }
        $defaults = [
            'sms_visitor_task' => '{app}: کارتابل — {title}',
            'sms_visitor_visit' => '{app}: بازدید {date} مشتری {customer} ثبت شد.',
            'sms_visitor_commission' => '{app}: پورسانت فاکتور {invoice} مبلغ {amount} ریال.',
        ];
        $tpl = SettingsStore::get($templateKey, $defaults[$templateKey] ?? '{title}')
            ?: ($defaults[$templateKey] ?? '{title}');
        $vars = array_merge([
            'app' => (string) cfg('app_name', 'حساب'),
            'visitor' => (string) ($visitor['name'] ?? ''),
            'title' => '',
            'date' => date('Y-m-d'),
            'customer' => '',
            'invoice' => '',
            'amount' => '',
            'percent' => (string) ($visitor['commission_percent'] ?? ''),
        ], $vars);
        $map = [];
        foreach ($vars as $k => $v) {
            $map['{' . $k . '}'] = (string) $v;
        }
        $text = strtr($tpl, $map);
        return Sms::send($phone, $text);
    }

    /**
     * Accrue commission when invoice is tied to a visitor.
     * @return array{ok:bool,message:string,id?:int}
     */
    public static function accrueFromInvoice(int $invoiceId): array
    {
        $inv = Database::query(
            'SELECT i.*, p.name party_name FROM invoices i
             LEFT JOIN parties p ON p.id=i.party_id WHERE i.id=?',
            [$invoiceId]
        )->fetch();
        if (!$inv) {
            return ['ok' => false, 'message' => 'فاکتور یافت نشد'];
        }
        $vid = (int) ($inv['visitor_id'] ?? 0);
        if ($vid <= 0) {
            return ['ok' => false, 'message' => 'ویزیتور روی فاکتور انتخاب نشده'];
        }
        $visitor = self::find($vid);
        if (!$visitor) {
            return ['ok' => false, 'message' => 'ویزیتور نامعتبر است'];
        }
        $exists = Database::query(
            'SELECT id FROM visitor_commissions WHERE invoice_id=? LIMIT 1',
            [$invoiceId]
        )->fetch();
        if ($exists) {
            return ['ok' => true, 'message' => 'پورسانت قبلاً ثبت شده', 'id' => (int) $exists['id']];
        }
        $base = (float) $inv['total'];
        $pct = self::commissionPercent($visitor);
        $amount = (int) round($base * $pct / 100);
        Database::query(
            'INSERT INTO visitor_commissions
              (visitor_id, invoice_id, base_amount, percent, commission_amount, status, created_at)
             VALUES (?,?,?,?,?,?,NOW())',
            [$vid, $invoiceId, $base, $pct, $amount, 'accrued']
        );
        $cid = (int) Database::pdo()->lastInsertId();
        self::cartablePush([
            'visitor_id' => $vid,
            'title' => 'پورسانت فاکتور ' . $inv['number'],
            'body' => 'مبلغ پایه ' . number_format($base) . ' — درصد ' . $pct . ' — پورسانت ' . number_format($amount),
            'kind' => 'commission',
            'ref_type' => 'commission',
            'ref_id' => $cid,
        ]);
        self::smsNotify($visitor, 'sms_visitor_commission', [
            'invoice' => (string) $inv['number'],
            'amount' => number_format($amount),
            'customer' => (string) ($inv['party_name'] ?? ''),
            'title' => 'پورسانت جدید',
        ]);
        return ['ok' => true, 'message' => 'پورسانت ثبت شد', 'id' => $cid];
    }
}
