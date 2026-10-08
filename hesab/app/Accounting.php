<?php
declare(strict_types=1);

final class Accounting
{
    public static function activeYear(): array
    {
        $fy = Database::query('SELECT * FROM fiscal_years WHERE is_active=1 AND is_closed=0 LIMIT 1')->fetch();
        if (!$fy) {
            throw new RuntimeException('سال مالی فعال یافت نشد.');
        }
        return $fy;
    }

    public static function nextVoucherNumber(int $yearId): int
    {
        return (int) Database::query('SELECT COALESCE(MAX(number),0)+1 n FROM vouchers WHERE fiscal_year_id=?', [$yearId])->fetch()['n'];
    }

    public static function validateLines(array $lines): array
    {
        $sumD = 0.0;
        $sumC = 0.0;
        $clean = [];
        foreach ($lines as $ln) {
            $moeinId = (int) ($ln['moein_id'] ?? 0);
            $d = (float) ($ln['debit'] ?? 0);
            $c = (float) ($ln['credit'] ?? 0);
            if (!$moeinId || ($d <= 0 && $c <= 0)) {
                continue;
            }
            if ($d > 0 && $c > 0) {
                throw new InvalidArgumentException('هر ردیف فقط بدهکار یا بستانکار باشد.');
            }
            $moein = Database::query('SELECT * FROM accounts_moein WHERE id=?', [$moeinId])->fetch();
            if (!$moein || !(int) $moein['is_active']) {
                throw new InvalidArgumentException('حساب معین نامعتبر است.');
            }
            if ($d > 0 && !(int) $moein['allow_debit']) {
                throw new InvalidArgumentException('حساب ' . $moein['code'] . ' اجازه بدهکار ندارد.');
            }
            if ($c > 0 && !(int) $moein['allow_credit']) {
                throw new InvalidArgumentException('حساب ' . $moein['code'] . ' اجازه بستانکار ندارد.');
            }
            // tafsili map controls
            $maps = Database::query('SELECT * FROM moein_tafsili_map WHERE moein_id=? ORDER BY level', [$moeinId])->fetchAll();
            foreach ($maps as $m) {
                $key = 'tafsili' . $m['level'] . '_id';
                $val = (int) ($ln[$key] ?? 0);
                if ((int) $m['is_required'] && !$val) {
                    throw new InvalidArgumentException('تفصیلی سطح ' . $m['level'] . ' برای حساب ' . $moein['code'] . ' الزامی است.');
                }
                if ($val) {
                    $ok = Database::query('SELECT id FROM tafsili_items WHERE id=? AND type_id=? AND is_active=1', [$val, $m['tafsili_type_id']])->fetch();
                    if (!$ok) {
                        throw new InvalidArgumentException('تفصیلی سطح ' . $m['level'] . ' با نوع تعریف‌شده برای معین هم‌خوان نیست.');
                    }
                }
            }
            $clean[] = [
                'moein_id' => $moeinId,
                'debit' => $d,
                'credit' => $c,
                'description' => trim((string) ($ln['description'] ?? '')),
                'tafsili1_id' => (int) ($ln['tafsili1_id'] ?? 0) ?: null,
                'tafsili2_id' => (int) ($ln['tafsili2_id'] ?? 0) ?: null,
                'tafsili3_id' => (int) ($ln['tafsili3_id'] ?? 0) ?: null,
            ];
            $sumD += $d;
            $sumC += $c;
        }
        if (count($clean) < 2) {
            throw new InvalidArgumentException('حداقل دو ردیف لازم است.');
        }
        if (abs($sumD - $sumC) > 0.0001) {
            throw new InvalidArgumentException('سند تراز نیست. بدهکار: ' . money($sumD) . ' / بستانکار: ' . money($sumC));
        }
        return $clean;
    }

    public static function createVoucher(array $header, array $lines, string $status = 'draft'): int
    {
        $fy = self::activeYear();
        $clean = self::validateLines($lines);
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $num = isset($header['number']) ? (int) $header['number'] : self::nextVoucherNumber((int) $fy['id']);
            Database::query(
                'INSERT INTO vouchers (fiscal_year_id, number, voucher_date, description, status, voucher_type_id, created_by, source_module, source_id)
                 VALUES (?,?,?,?,?,?,?,?,?)',
                [
                    $fy['id'],
                    $num,
                    $header['voucher_date'] ?? date('Y-m-d'),
                    $header['description'] ?? null,
                    $status,
                    $header['voucher_type_id'] ?? null,
                    current_user()['id'] ?? null,
                    $header['source_module'] ?? null,
                    $header['source_id'] ?? null,
                ]
            );
            $vid = (int) $pdo->lastInsertId();
            $n = 1;
            foreach ($clean as $ln) {
                Database::query(
                    'INSERT INTO voucher_lines (voucher_id, line_no, moein_id, description, debit, credit, tafsili1_id, tafsili2_id, tafsili3_id)
                     VALUES (?,?,?,?,?,?,?,?,?)',
                    [$vid, $n++, $ln['moein_id'], $ln['description'], $ln['debit'], $ln['credit'], $ln['tafsili1_id'], $ln['tafsili2_id'], $ln['tafsili3_id']]
                );
            }
            $pdo->commit();
            Audit::log('voucher.create', 'voucher', $vid, 'status=' . $status);
            return $vid;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function setStatus(int $id, string $status): void
    {
        $v = Database::query('SELECT * FROM vouchers WHERE id=?', [$id])->fetch();
        if (!$v) {
            throw new RuntimeException('سند یافت نشد.');
        }
        if ($v['status'] === 'locked' && $status !== 'locked') {
            throw new RuntimeException('سند قطعی قابل تغییر نیست.');
        }
        $allowed = [
            'draft' => ['operational', 'void'],
            'operational' => ['reviewed', 'draft', 'void'],
            'reviewed' => ['locked', 'operational', 'void'],
            'posted' => ['locked'],
            'locked' => [],
            'void' => [],
        ];
        if (!in_array($status, $allowed[$v['status']] ?? [], true) && $status !== $v['status']) {
            throw new RuntimeException('تغییر وضعیت از ' . $v['status'] . ' به ' . $status . ' مجاز نیست.');
        }
        $fields = ['status' => $status];
        $sql = 'UPDATE vouchers SET status=?';
        $params = [$status];
        if ($status === 'reviewed') {
            $sql .= ', reviewed_by=?, reviewed_at=NOW()';
            $params[] = current_user()['id'];
        }
        if ($status === 'locked') {
            // re-validate balance
            $sum = Database::query('SELECT SUM(debit) d, SUM(credit) c FROM voucher_lines WHERE voucher_id=?', [$id])->fetch();
            if ((float) $sum['d'] !== (float) $sum['c']) {
                throw new RuntimeException('سند تراز نیست.');
            }
            $sql .= ', locked_by=?, locked_at=NOW(), posted_at=NOW()';
            $params[] = current_user()['id'];
        }
        $sql .= ' WHERE id=?';
        $params[] = $id;
        Database::query($sql, $params);
        Audit::log('voucher.status', 'voucher', $id, $v['status'] . '=>' . $status);
    }

    public static function renumber(int $yearId): int
    {
        $rows = Database::query(
            "SELECT id FROM vouchers WHERE fiscal_year_id=? AND status<>'void' ORDER BY voucher_date, id",
            [$yearId]
        )->fetchAll();
        $n = 1;
        foreach ($rows as $r) {
            Database::query('UPDATE vouchers SET number=? WHERE id=?', [$n++, $r['id']]);
        }
        Audit::log('voucher.renumber', 'fiscal_year', $yearId, 'count=' . count($rows));
        return count($rows);
    }

    public static function createOpeningClosing(string $kind): int
    {
        $fy = self::activeYear();
        $type = Database::query('SELECT id FROM voucher_types WHERE code=? LIMIT 1', [$kind === 'opening' ? 'OPENING' : 'CLOSING'])->fetch();
        // Build balances from locked vouchers
        $rows = Database::query(
            "SELECT l.moein_id, SUM(l.debit) d, SUM(l.credit) c
             FROM voucher_lines l JOIN vouchers v ON v.id=l.voucher_id
             WHERE v.fiscal_year_id=? AND v.status IN ('locked','posted')
             GROUP BY l.moein_id",
            [$fy['id']]
        )->fetchAll();
        $lines = [];
        foreach ($rows as $r) {
            $bal = (float) $r['d'] - (float) $r['c'];
            if (abs($bal) < 0.0001) {
                continue;
            }
            if ($kind === 'closing') {
                // reverse
                $lines[] = ['moein_id' => $r['moein_id'], 'debit' => $bal < 0 ? abs($bal) : 0, 'credit' => $bal > 0 ? $bal : 0, 'description' => 'اختتامیه'];
            } else {
                $lines[] = ['moein_id' => $r['moein_id'], 'debit' => $bal > 0 ? $bal : 0, 'credit' => $bal < 0 ? abs($bal) : 0, 'description' => 'افتتاحیه'];
            }
        }
        if (count($lines) < 2) {
            throw new RuntimeException('مانده‌ای برای صدور سند ' . $kind . ' وجود ندارد.');
        }
        // force balance with contra if needed
        $sd = array_sum(array_column($lines, 'debit'));
        $sc = array_sum(array_column($lines, 'credit'));
        if (abs($sd - $sc) > 0.0001) {
            $equity = Database::query("SELECT id FROM accounts_moein WHERE code LIKE '58%' OR title LIKE '%سود و زیان%' LIMIT 1")->fetch();
            if (!$equity) {
                throw new RuntimeException('حساب سود و زیان برای تراز سند یافت نشد.');
            }
            $diff = $sd - $sc;
            $lines[] = ['moein_id' => $equity['id'], 'debit' => $diff < 0 ? abs($diff) : 0, 'credit' => $diff > 0 ? $diff : 0, 'description' => 'مقابل افتتاحیه/اختتامیه'];
        }
        return self::createVoucher([
            'voucher_date' => $kind === 'opening' ? $fy['start_date'] : $fy['end_date'],
            'description' => $kind === 'opening' ? 'سند افتتاحیه خودکار' : 'سند اختتامیه خودکار',
            'voucher_type_id' => $type['id'] ?? null,
            'source_module' => 'fiscal',
        ], $lines, 'operational');
    }

    public static function trialBalance(?string $from = null, ?string $to = null, string $level = 'moein'): array
    {
        $sql = "SELECT m.code, m.title,
                       COALESCE(SUM(l.debit),0) debit,
                       COALESCE(SUM(l.credit),0) credit
                FROM accounts_moein m
                LEFT JOIN voucher_lines l ON l.moein_id=m.id
                LEFT JOIN vouchers v ON v.id=l.voucher_id AND v.status IN ('locked','posted','reviewed','operational')
                WHERE 1=1";
        $params = [];
        if ($from) {
            $sql .= ' AND (v.voucher_date IS NULL OR v.voucher_date>=?)';
            $params[] = $from;
        }
        if ($to) {
            $sql .= ' AND (v.voucher_date IS NULL OR v.voucher_date<=?)';
            $params[] = $to;
        }
        $sql .= ' GROUP BY m.id HAVING debit<>0 OR credit<>0 ORDER BY m.code+0';
        return Database::query($sql, $params)->fetchAll();
    }
}
