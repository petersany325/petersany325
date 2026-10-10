<?php
declare(strict_types=1);

/**
 * Cheque Management Engine — Iranian treasury/accounting standard flows.
 * Posts double-entry vouchers per lifecycle event; never posts uncollected
 * cheques into bank cash balance.
 */
final class ChequeEngine
{
    /** Logical account key => default moein code (aligned with seeded coding). */
    public const ACCOUNT_MAP = [
        'RECV_ON_HAND' => '1304',       // چک/اسناد نزد صندوق
        'RECV_IN_COLLECTION' => '1303', // در جریان وصول
        'RECV_RETURNED' => '1306',      // برگشتی / واخواست
        'RECV_LEGAL' => '1308',         // پیگیری حقوقی
        'RECV_AGENCY' => '1309',        // در اختیار نمایندگی
        'AR' => '1302',                 // دریافتنی مشتری
        'AP' => '3103',                 // پرداختنی تأمین‌کننده
        'PAY_ISSUED' => '3109',         // چک صادره تحویل‌شده
        'PAY_RETURNED' => '3110',       // چک برگشتی پرداختنی
        'PAY_OVERDUE' => '3111',        // پرداختنی سررسیدگذشته
        'BANK' => '1101',               // موجودی بانک (کد ریشه؛ resolveMoein می‌یابد)
        'CASH' => '1102',
    ];

    public static function resolveMoein(string $key): int
    {
        $defaults = self::ACCOUNT_MAP;
        $code = SettingsStore::get('cheque_acct_' . $key, $defaults[$key] ?? '');
        if ($code === '' || $code === null) {
            $code = $defaults[$key] ?? '';
        }
        // Exact match first
        $row = Database::query('SELECT id FROM accounts_moein WHERE code=? AND is_active=1 LIMIT 1', [$code])->fetch();
        if ($row) {
            return (int) $row['id'];
        }
        // Prefix for bank/cash roots like 1101 → 110101…
        if (in_array($key, ['BANK', 'CASH', 'AR', 'AP'], true)) {
            $row = Database::query(
                'SELECT id FROM accounts_moein WHERE code LIKE ? AND is_active=1 ORDER BY LENGTH(code), code LIMIT 1',
                [$code . '%']
            )->fetch();
            if ($row) {
                return (int) $row['id'];
            }
        }
        // posting_rules fallback
        $ruleCode = match ($key) {
            'RECV_ON_HAND', 'RECV_IN_COLLECTION' => 'CHECK_RECV',
            'PAY_ISSUED' => 'CHECK_PAY',
            'AR' => 'AR_CUSTOMER',
            'AP' => 'AP_SUPPLIER',
            default => '',
        };
        if ($ruleCode !== '') {
            $rule = Database::query('SELECT moein_code FROM posting_rules WHERE code=? LIMIT 1', [$ruleCode])->fetch();
            if ($rule) {
                $row = Database::query('SELECT id FROM accounts_moein WHERE code=? LIMIT 1', [$rule['moein_code']])->fetch();
                if ($row) {
                    return (int) $row['id'];
                }
            }
        }
        throw new RuntimeException('حساب معین چک یافت نشد: ' . $key . ' (' . $code . ') — کدینگ را تکمیل کنید.');
    }

    public static function find(int $id): ?array
    {
        $row = Database::query('SELECT * FROM cheques WHERE id=?', [$id])->fetch();
        return $row ?: null;
    }

    public static function events(int $chequeId): array
    {
        return Database::query(
            'SELECT e.*, u.name user_name FROM cheque_events e
             LEFT JOIN users u ON u.id=e.user_id
             WHERE e.cheque_id=? ORDER BY e.id',
            [$chequeId]
        )->fetchAll();
    }

    /** @return array{ok:bool,message:string,id?:int,voucher_id?:int} */
    public static function receive(array $data): array
    {
        $amount = (float) str_replace(',', '', (string) ($data['amount'] ?? 0));
        $checkNo = trim((string) ($data['check_no'] ?? ''));
        if ($amount <= 0 || $checkNo === '') {
            return ['ok' => false, 'message' => 'شماره چک و مبلغ الزامی است'];
        }
        $due = trim((string) ($data['due_date'] ?? ''));
        $partyId = (int) ($data['party_tafsili_id'] ?? 0);
        Database::query(
            'INSERT INTO cheques
              (direction, check_no, sayad_id, bank_name, branch_name, account_no,
               amount, currency, issue_date, receive_date, due_date,
               party_tafsili_id, issuer_name, beneficiary, payee,
               physical_status, sayad_status, settlement_status,
               location, bank_account_id, checkbook_id, description, created_by, created_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())',
            [
                'receivable',
                $checkNo,
                trim((string) ($data['sayad_id'] ?? '')) ?: null,
                trim((string) ($data['bank_name'] ?? '')) ?: null,
                trim((string) ($data['branch_name'] ?? '')) ?: null,
                trim((string) ($data['account_no'] ?? '')) ?: null,
                $amount,
                'IRR',
                trim((string) ($data['issue_date'] ?? '')) ?: null,
                trim((string) ($data['receive_date'] ?? date('Y-m-d'))),
                $due !== '' ? $due : null,
                $partyId ?: null,
                trim((string) ($data['issuer_name'] ?? '')) ?: null,
                trim((string) ($data['beneficiary'] ?? '')) ?: null,
                trim((string) ($data['payee'] ?? '')) ?: null,
                'in_hand',
                trim((string) ($data['sayad_status'] ?? 'unknown')),
                'open',
                'cash',
                (int) ($data['bank_account_id'] ?? 0) ?: null,
                null,
                trim((string) ($data['description'] ?? '')) ?: null,
                current_user()['id'] ?? null,
            ]
        );
        $id = (int) Database::pdo()->lastInsertId();

        $onHand = self::resolveMoein('RECV_ON_HAND');
        $ar = self::resolveMoein('AR');
        $vid = Accounting::createVoucher([
            'voucher_date' => $data['receive_date'] ?? date('Y-m-d'),
            'description' => 'دریافت چک شماره ' . $checkNo,
            'voucher_type_id' => self::voucherTypeId(),
            'source_module' => 'cheque',
            'source_id' => $id,
        ], [
            ['moein_id' => $onHand, 'debit' => $amount, 'credit' => 0, 'description' => 'چک نزد صندوق', 'tafsili1_id' => $partyId],
            ['moein_id' => $ar, 'debit' => 0, 'credit' => $amount, 'description' => 'تسویه دریافتنی با چک', 'tafsili1_id' => $partyId],
        ], 'operational');

        self::logEvent($id, 'receive', 'in_hand', 'open', $amount, $vid, 'ثبت دریافت چک نزد صندوق');
        Database::query('UPDATE cheques SET last_voucher_id=? WHERE id=?', [$vid, $id]);
        return ['ok' => true, 'message' => 'چک دریافتی ثبت و سند صادر شد', 'id' => $id, 'voucher_id' => $vid];
    }

    /** @return array{ok:bool,message:string,voucher_id?:int} */
    public static function issuePayable(array $data): array
    {
        $amount = (float) str_replace(',', '', (string) ($data['amount'] ?? 0));
        $checkNo = trim((string) ($data['check_no'] ?? ''));
        $bankId = (int) ($data['bank_account_id'] ?? 0);
        $bookId = (int) ($data['checkbook_id'] ?? 0);
        if ($amount <= 0 || $checkNo === '' || $bankId <= 0) {
            return ['ok' => false, 'message' => 'شماره چک، مبلغ و حساب بانکی الزامی است'];
        }
        $partyId = (int) ($data['party_tafsili_id'] ?? 0);
        Database::query(
            'INSERT INTO cheques
              (direction, check_no, sayad_id, bank_name, branch_name, account_no,
               amount, currency, issue_date, due_date,
               party_tafsili_id, beneficiary, payee,
               physical_status, sayad_status, settlement_status,
               location, bank_account_id, checkbook_id, description, created_by, created_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())',
            [
                'payable',
                $checkNo,
                trim((string) ($data['sayad_id'] ?? '')) ?: null,
                trim((string) ($data['bank_name'] ?? '')) ?: null,
                trim((string) ($data['branch_name'] ?? '')) ?: null,
                trim((string) ($data['account_no'] ?? '')) ?: null,
                $amount,
                'IRR',
                trim((string) ($data['issue_date'] ?? date('Y-m-d'))),
                trim((string) ($data['due_date'] ?? '')) ?: null,
                $partyId ?: null,
                trim((string) ($data['beneficiary'] ?? '')) ?: null,
                trim((string) ($data['payee'] ?? '')) ?: null,
                'issued',
                trim((string) ($data['sayad_status'] ?? 'unknown')),
                'open',
                'third_party',
                $bankId,
                $bookId ?: null,
                trim((string) ($data['description'] ?? '')) ?: null,
                current_user()['id'] ?? null,
            ]
        );
        $id = (int) Database::pdo()->lastInsertId();
        if ($bookId > 0) {
            Database::query('UPDATE checkbooks SET next_no = GREATEST(next_no, ?) + 0 WHERE id=?', [(int) $checkNo + 1, $bookId]);
        }

        $ap = self::resolveMoein('AP');
        $pay = self::resolveMoein('PAY_ISSUED');
        $vid = Accounting::createVoucher([
            'voucher_date' => $data['issue_date'] ?? date('Y-m-d'),
            'description' => 'صدور و تحویل چک پرداختی ' . $checkNo,
            'voucher_type_id' => self::voucherTypeId(),
            'source_module' => 'cheque',
            'source_id' => $id,
        ], [
            ['moein_id' => $ap, 'debit' => $amount, 'credit' => 0, 'description' => 'تسویه پرداختنی با چک', 'tafsili1_id' => $partyId],
            ['moein_id' => $pay, 'debit' => 0, 'credit' => $amount, 'description' => 'چک صادره تحویل‌شده', 'tafsili1_id' => $partyId],
        ], 'operational');

        self::logEvent($id, 'issue_deliver', 'issued', 'open', $amount, $vid, 'صدور و تحویل به ذی‌نفع');
        Database::query('UPDATE cheques SET last_voucher_id=? WHERE id=?', [$vid, $id]);
        return ['ok' => true, 'message' => 'چک پرداختی صادر و سند ثبت شد', 'id' => $id, 'voucher_id' => $vid];
    }

    /** Apply lifecycle action with GL posting and idempotency. */
    public static function apply(int $id, string $action, array $extra = []): array
    {
        $ch = self::find($id);
        if (!$ch) {
            return ['ok' => false, 'message' => 'چک یافت نشد'];
        }
        if (self::eventExists($id, $action) && !in_array($action, ['note', 'sayad_update'], true)) {
            return ['ok' => false, 'message' => 'این رویداد قبلاً برای چک ثبت شده است'];
        }

        return match ($action) {
            'deposit' => self::actionDeposit($ch, $extra),
            'collect' => self::actionCollect($ch, $extra),
            'return_recv' => self::actionReturnRecv($ch, $extra),
            'endorse' => self::actionEndorse($ch, $extra),
            'legal' => self::actionLegal($ch, $extra),
            'restore_hand' => self::actionRestoreHand($ch, $extra),
            'clear_pay' => self::actionClearPay($ch, $extra),
            'return_pay' => self::actionReturnPay($ch, $extra),
            'cancel' => self::actionCancel($ch, $extra),
            'sayad_update' => self::actionSayad($ch, $extra),
            'partial_collect' => self::actionPartialCollect($ch, $extra),
            default => ['ok' => false, 'message' => 'عملیات ناشناخته: ' . $action],
        };
    }

    private static function actionDeposit(array $ch, array $extra): array
    {
        if ($ch['direction'] !== 'receivable' || $ch['physical_status'] !== 'in_hand') {
            return ['ok' => false, 'message' => 'فقط چک دریافتی نزد صندوق قابل واگذاری است'];
        }
        $amount = (float) $ch['amount'] - (float) ($ch['amount_settled'] ?? 0);
        $inCol = self::resolveMoein('RECV_IN_COLLECTION');
        $onHand = self::resolveMoein('RECV_ON_HAND');
        $vid = Accounting::createVoucher([
            'voucher_date' => $extra['event_date'] ?? date('Y-m-d'),
            'description' => 'واگذاری چک ' . $ch['check_no'] . ' به بانک',
            'voucher_type_id' => self::voucherTypeId(),
            'source_module' => 'cheque',
            'source_id' => (int) $ch['id'],
        ], [
            ['moein_id' => $inCol, 'debit' => $amount, 'credit' => 0, 'description' => 'در جریان وصول'],
            ['moein_id' => $onHand, 'debit' => 0, 'credit' => $amount, 'description' => 'خروج از نزد صندوق'],
        ], 'operational');

        $bankId = (int) ($extra['bank_account_id'] ?? $ch['bank_account_id'] ?? 0);
        Database::query(
            'UPDATE cheques SET physical_status=?, location=?, bank_account_id=COALESCE(?, bank_account_id), last_voucher_id=? WHERE id=?',
            ['deposited', 'bank', $bankId ?: null, $vid, $ch['id']]
        );
        Database::query(
            'INSERT INTO cheque_deposits (cheque_id, bank_account_id, deposit_date, slip_no, voucher_id, created_at)
             VALUES (?,?,?,?,?,NOW())',
            [$ch['id'], $bankId ?: null, $extra['event_date'] ?? date('Y-m-d'), trim((string) ($extra['slip_no'] ?? '')) ?: null, $vid]
        );
        self::logEvent((int) $ch['id'], 'deposit', 'deposited', 'open', $amount, $vid, 'واگذاری به بانک برای وصول');
        return ['ok' => true, 'message' => 'واگذاری به بانک ثبت شد', 'voucher_id' => $vid];
    }

    private static function actionCollect(array $ch, array $extra): array
    {
        if ($ch['direction'] !== 'receivable' || !in_array($ch['physical_status'], ['deposited', 'in_hand'], true)) {
            return ['ok' => false, 'message' => 'وضعیت چک برای وصول کامل مناسب نیست'];
        }
        if ($ch['settlement_status'] === 'settled') {
            return ['ok' => false, 'message' => 'چک قبلاً تسویه شده است'];
        }
        $remain = (float) $ch['amount'] - (float) ($ch['amount_settled'] ?? 0);
        if ($remain <= 0) {
            return ['ok' => false, 'message' => 'مانده‌ای برای وصول نیست'];
        }
        $fromKey = $ch['physical_status'] === 'deposited' ? 'RECV_IN_COLLECTION' : 'RECV_ON_HAND';
        $bank = self::resolveMoein('BANK');
        $from = self::resolveMoein($fromKey);
        $vid = Accounting::createVoucher([
            'voucher_date' => $extra['event_date'] ?? date('Y-m-d'),
            'description' => 'وصول چک ' . $ch['check_no'],
            'voucher_type_id' => self::voucherTypeId(),
            'source_module' => 'cheque',
            'source_id' => (int) $ch['id'],
        ], [
            ['moein_id' => $bank, 'debit' => $remain, 'credit' => 0, 'description' => 'واریز به بانک'],
            ['moein_id' => $from, 'debit' => 0, 'credit' => $remain, 'description' => 'بستن چک'],
        ], 'operational');

        Database::query(
            'UPDATE cheques SET physical_status=?, settlement_status=?, amount_settled=amount, settle_date=?, last_voucher_id=? WHERE id=?',
            ['collected', 'settled', $extra['event_date'] ?? date('Y-m-d'), $vid, $ch['id']]
        );
        Database::query(
            'INSERT INTO cheque_settlements (cheque_id, settle_date, amount, kind, voucher_id, created_at) VALUES (?,?,?,?,?,NOW())',
            [$ch['id'], $extra['event_date'] ?? date('Y-m-d'), $remain, 'full', $vid]
        );
        self::logEvent((int) $ch['id'], 'collect', 'collected', 'settled', $remain, $vid, 'وصول کامل بانکی');
        return ['ok' => true, 'message' => 'وصول ثبت و موجودی بانک افزایش یافت', 'voucher_id' => $vid];
    }

    private static function actionPartialCollect(array $ch, array $extra): array
    {
        if ($ch['direction'] !== 'receivable') {
            return ['ok' => false, 'message' => 'فقط برای چک دریافتی'];
        }
        $part = (float) str_replace(',', '', (string) ($extra['amount'] ?? 0));
        $remain = (float) $ch['amount'] - (float) ($ch['amount_settled'] ?? 0);
        if ($part <= 0 || $part > $remain) {
            return ['ok' => false, 'message' => 'مبلغ وصول جزئی نامعتبر است'];
        }
        $fromKey = $ch['physical_status'] === 'deposited' ? 'RECV_IN_COLLECTION' : 'RECV_ON_HAND';
        $bank = self::resolveMoein('BANK');
        $from = self::resolveMoein($fromKey);
        $vid = Accounting::createVoucher([
            'voucher_date' => $extra['event_date'] ?? date('Y-m-d'),
            'description' => 'وصول جزئی چک ' . $ch['check_no'],
            'voucher_type_id' => self::voucherTypeId(),
            'source_module' => 'cheque',
            'source_id' => (int) $ch['id'],
        ], [
            ['moein_id' => $bank, 'debit' => $part, 'credit' => 0, 'description' => 'وصول جزئی'],
            ['moein_id' => $from, 'debit' => 0, 'credit' => $part, 'description' => 'کاهش مانده چک'],
        ], 'operational');
        $newSettled = (float) ($ch['amount_settled'] ?? 0) + $part;
        $done = $newSettled + 0.0001 >= (float) $ch['amount'];
        Database::query(
            'UPDATE cheques SET amount_settled=?, settlement_status=?, physical_status=IF(?, ?, physical_status), settle_date=IF(?, ?, settle_date), last_voucher_id=? WHERE id=?',
            [
                $newSettled,
                $done ? 'settled' : 'partial',
                $done ? 1 : 0,
                'collected',
                $done ? 1 : 0,
                $extra['event_date'] ?? date('Y-m-d'),
                $vid,
                $ch['id'],
            ]
        );
        Database::query(
            'INSERT INTO cheque_settlements (cheque_id, settle_date, amount, kind, voucher_id, created_at) VALUES (?,?,?,?,?,NOW())',
            [$ch['id'], $extra['event_date'] ?? date('Y-m-d'), $part, 'partial', $vid]
        );
        self::logEvent((int) $ch['id'], 'partial_collect', $ch['physical_status'], $done ? 'settled' : 'partial', $part, $vid, 'وصول جزئی');
        return ['ok' => true, 'message' => 'وصول جزئی ثبت شد', 'voucher_id' => $vid];
    }

    private static function actionReturnRecv(array $ch, array $extra): array
    {
        if ($ch['direction'] !== 'receivable') {
            return ['ok' => false, 'message' => 'فقط چک دریافتی'];
        }
        if (!in_array($ch['physical_status'], ['deposited', 'in_hand'], true)) {
            return ['ok' => false, 'message' => 'وضعیت برای برگشت مناسب نیست'];
        }
        $remain = (float) $ch['amount'] - (float) ($ch['amount_settled'] ?? 0);
        $fromKey = $ch['physical_status'] === 'deposited' ? 'RECV_IN_COLLECTION' : 'RECV_ON_HAND';
        $ret = self::resolveMoein('RECV_RETURNED');
        $from = self::resolveMoein($fromKey);
        $vid = Accounting::createVoucher([
            'voucher_date' => $extra['event_date'] ?? date('Y-m-d'),
            'description' => 'برگشت چک ' . $ch['check_no'],
            'voucher_type_id' => self::voucherTypeId(),
            'source_module' => 'cheque',
            'source_id' => (int) $ch['id'],
        ], [
            ['moein_id' => $ret, 'debit' => $remain, 'credit' => 0, 'description' => 'چک برگشتی دریافتنی'],
            ['moein_id' => $from, 'debit' => 0, 'credit' => $remain, 'description' => 'خروج از وضعیت قبلی'],
        ], 'operational');
        Database::query(
            'UPDATE cheques SET physical_status=?, settlement_status=?, last_voucher_id=? WHERE id=?',
            ['returned', 'open', $vid, $ch['id']]
        );
        Database::query(
            'INSERT INTO cheque_returns (cheque_id, return_date, reason, voucher_id, created_at) VALUES (?,?,?,?,NOW())',
            [$ch['id'], $extra['event_date'] ?? date('Y-m-d'), trim((string) ($extra['reason'] ?? '')) ?: null, $vid]
        );
        self::logEvent((int) $ch['id'], 'return_recv', 'returned', 'open', $remain, $vid, $extra['reason'] ?? 'برگشت چک');
        return ['ok' => true, 'message' => 'برگشت ثبت شد (مطالبه همچنان برقرار است)', 'voucher_id' => $vid];
    }

    private static function actionEndorse(array $ch, array $extra): array
    {
        if ($ch['direction'] !== 'receivable' || $ch['physical_status'] !== 'in_hand') {
            return ['ok' => false, 'message' => 'فقط چک نزد صندوق قابل خرج/انتقال است'];
        }
        $remain = (float) $ch['amount'] - (float) ($ch['amount_settled'] ?? 0);
        $recourse = !empty($extra['with_recourse']);
        $partyId = (int) ($extra['party_tafsili_id'] ?? 0);
        $onHand = self::resolveMoein('RECV_ON_HAND');
        $ap = self::resolveMoein('AP');
        $vid = Accounting::createVoucher([
            'voucher_date' => $extra['event_date'] ?? date('Y-m-d'),
            'description' => 'خرج/انتقال چک ' . $ch['check_no'] . ($recourse ? ' (با مسئولیت)' : ''),
            'voucher_type_id' => self::voucherTypeId(),
            'source_module' => 'cheque',
            'source_id' => (int) $ch['id'],
        ], [
            ['moein_id' => $ap, 'debit' => $remain, 'credit' => 0, 'description' => 'تسویه پرداختنی با انتقال چک', 'tafsili1_id' => $partyId],
            ['moein_id' => $onHand, 'debit' => 0, 'credit' => $remain, 'description' => 'خروج چک از صندوق'],
        ], 'operational');

        Database::query(
            'UPDATE cheques SET physical_status=?, location=?, with_recourse=?, settlement_status=?, last_voucher_id=? WHERE id=?',
            ['endorsed', 'third_party', $recourse ? 1 : 0, $recourse ? 'open' : 'settled', $vid, $ch['id']]
        );
        Database::query(
            'INSERT INTO cheque_transfers (cheque_id, transfer_date, to_party_tafsili_id, to_name, with_recourse, voucher_id, note, created_at)
             VALUES (?,?,?,?,?,?,?,NOW())',
            [
                $ch['id'],
                $extra['event_date'] ?? date('Y-m-d'),
                $partyId ?: null,
                trim((string) ($extra['to_name'] ?? '')) ?: null,
                $recourse ? 1 : 0,
                $vid,
                trim((string) ($extra['note'] ?? '')) ?: null,
            ]
        );
        self::logEvent((int) $ch['id'], 'endorse', 'endorsed', $recourse ? 'open' : 'settled', $remain, $vid, 'انتقال به شخص ثالث');
        return ['ok' => true, 'message' => 'خرج چک ثبت شد' . ($recourse ? ' (مسئولیت پیگیری حفظ شد)' : ''), 'voucher_id' => $vid];
    }

    private static function actionLegal(array $ch, array $extra): array
    {
        if ($ch['physical_status'] !== 'returned') {
            return ['ok' => false, 'message' => 'معمولاً پس از برگشت به پیگیری حقوقی می‌رود'];
        }
        $remain = (float) $ch['amount'] - (float) ($ch['amount_settled'] ?? 0);
        $legal = self::resolveMoein('RECV_LEGAL');
        $ret = self::resolveMoein('RECV_RETURNED');
        $vid = Accounting::createVoucher([
            'voucher_date' => $extra['event_date'] ?? date('Y-m-d'),
            'description' => 'انتقال چک برگشتی به پیگیری حقوقی ' . $ch['check_no'],
            'voucher_type_id' => self::voucherTypeId(),
            'source_module' => 'cheque',
            'source_id' => (int) $ch['id'],
        ], [
            ['moein_id' => $legal, 'debit' => $remain, 'credit' => 0, 'description' => 'پیگیری حقوقی'],
            ['moein_id' => $ret, 'debit' => 0, 'credit' => $remain, 'description' => 'خروج از برگشتی'],
        ], 'operational');
        Database::query('UPDATE cheques SET physical_status=?, last_voucher_id=? WHERE id=?', ['legal', $vid, $ch['id']]);
        self::logEvent((int) $ch['id'], 'legal', 'legal', 'open', $remain, $vid, 'پیگیری حقوقی');
        return ['ok' => true, 'message' => 'به پیگیری حقوقی منتقل شد', 'voucher_id' => $vid];
    }

    private static function actionRestoreHand(array $ch, array $extra): array
    {
        // استرداد از بانک به صندوق قبل از وصول
        if ($ch['direction'] !== 'receivable' || $ch['physical_status'] !== 'deposited') {
            return ['ok' => false, 'message' => 'فقط چک در جریان وصول قابل استرداد به صندوق است'];
        }
        $remain = (float) $ch['amount'] - (float) ($ch['amount_settled'] ?? 0);
        $onHand = self::resolveMoein('RECV_ON_HAND');
        $inCol = self::resolveMoein('RECV_IN_COLLECTION');
        $vid = Accounting::createVoucher([
            'voucher_date' => $extra['event_date'] ?? date('Y-m-d'),
            'description' => 'استرداد چک از بانک به صندوق ' . $ch['check_no'],
            'voucher_type_id' => self::voucherTypeId(),
            'source_module' => 'cheque',
            'source_id' => (int) $ch['id'],
        ], [
            ['moein_id' => $onHand, 'debit' => $remain, 'credit' => 0, 'description' => 'بازگشت نزد صندوق'],
            ['moein_id' => $inCol, 'debit' => 0, 'credit' => $remain, 'description' => 'خروج از جریان وصول'],
        ], 'operational');
        Database::query('UPDATE cheques SET physical_status=?, location=?, last_voucher_id=? WHERE id=?', ['in_hand', 'cash', $vid, $ch['id']]);
        self::logEvent((int) $ch['id'], 'restore_hand', 'in_hand', 'open', $remain, $vid, 'استرداد به صندوق');
        return ['ok' => true, 'message' => 'به نزد صندوق برگشت', 'voucher_id' => $vid];
    }

    private static function actionClearPay(array $ch, array $extra): array
    {
        if ($ch['direction'] !== 'payable' || $ch['physical_status'] !== 'issued') {
            return ['ok' => false, 'message' => 'فقط چک پرداختی صادره قابل برداشت بانکی است'];
        }
        $amount = (float) $ch['amount'];
        $pay = self::resolveMoein('PAY_ISSUED');
        $bank = self::resolveMoein('BANK');
        $vid = Accounting::createVoucher([
            'voucher_date' => $extra['event_date'] ?? date('Y-m-d'),
            'description' => 'برداشت بانکی چک پرداختی ' . $ch['check_no'],
            'voucher_type_id' => self::voucherTypeId(),
            'source_module' => 'cheque',
            'source_id' => (int) $ch['id'],
        ], [
            ['moein_id' => $pay, 'debit' => $amount, 'credit' => 0, 'description' => 'بستن چک پرداختنی'],
            ['moein_id' => $bank, 'debit' => 0, 'credit' => $amount, 'description' => 'کسر از بانک'],
        ], 'operational');
        Database::query(
            'UPDATE cheques SET physical_status=?, settlement_status=?, amount_settled=amount, settle_date=?, last_voucher_id=? WHERE id=?',
            ['cleared', 'settled', $extra['event_date'] ?? date('Y-m-d'), $vid, $ch['id']]
        );
        Database::query(
            'INSERT INTO cheque_settlements (cheque_id, settle_date, amount, kind, voucher_id, created_at) VALUES (?,?,?,?,?,NOW())',
            [$ch['id'], $extra['event_date'] ?? date('Y-m-d'), $amount, 'full', $vid]
        );
        self::logEvent((int) $ch['id'], 'clear_pay', 'cleared', 'settled', $amount, $vid, 'وصول بانکی چک پرداختی');
        return ['ok' => true, 'message' => 'برداشت بانک و تسویه ثبت شد', 'voucher_id' => $vid];
    }

    private static function actionReturnPay(array $ch, array $extra): array
    {
        if ($ch['direction'] !== 'payable' || $ch['physical_status'] !== 'issued') {
            return ['ok' => false, 'message' => 'فقط چک پرداختی صادره'];
        }
        $amount = (float) $ch['amount'];
        $pay = self::resolveMoein('PAY_ISSUED');
        $ret = self::resolveMoein('PAY_RETURNED');
        $vid = Accounting::createVoucher([
            'voucher_date' => $extra['event_date'] ?? date('Y-m-d'),
            'description' => 'برگشت چک پرداختی ' . $ch['check_no'],
            'voucher_type_id' => self::voucherTypeId(),
            'source_module' => 'cheque',
            'source_id' => (int) $ch['id'],
        ], [
            ['moein_id' => $pay, 'debit' => $amount, 'credit' => 0, 'description' => 'بستن چک صادره'],
            ['moein_id' => $ret, 'debit' => 0, 'credit' => $amount, 'description' => 'چک برگشتی پرداختنی'],
        ], 'operational');
        Database::query('UPDATE cheques SET physical_status=?, last_voucher_id=? WHERE id=?', ['returned', $vid, $ch['id']]);
        Database::query(
            'INSERT INTO cheque_returns (cheque_id, return_date, reason, voucher_id, created_at) VALUES (?,?,?,?,NOW())',
            [$ch['id'], $extra['event_date'] ?? date('Y-m-d'), trim((string) ($extra['reason'] ?? '')) ?: null, $vid]
        );
        self::logEvent((int) $ch['id'], 'return_pay', 'returned', 'open', $amount, $vid, 'برگشت چک پرداختی');
        return ['ok' => true, 'message' => 'برگشت چک پرداختی ثبت شد', 'voucher_id' => $vid];
    }

    private static function actionCancel(array $ch, array $extra): array
    {
        if ($ch['settlement_status'] === 'settled') {
            return ['ok' => false, 'message' => 'چک تسویه‌شده قابل ابطال نیست'];
        }
        // Soft cancel without reversing GL automatically — operator must reverse voucher if needed
        Database::query(
            'UPDATE cheques SET physical_status=?, settlement_status=? WHERE id=?',
            ['cancelled', 'void', $ch['id']]
        );
        self::logEvent((int) $ch['id'], 'cancel', 'cancelled', 'void', 0, null, $extra['reason'] ?? 'ابطال');
        return ['ok' => true, 'message' => 'چک ابطال شد (در صورت نیاز سند معکوس دستی بزنید)'];
    }

    private static function actionSayad(array $ch, array $extra): array
    {
        $st = trim((string) ($extra['sayad_status'] ?? 'confirmed'));
        if (!in_array($st, ['unknown', 'registered', 'confirmed', 'transferred', 'rejected'], true)) {
            $st = 'confirmed';
        }
        Database::query('UPDATE cheques SET sayad_status=?, sayad_id=COALESCE(?, sayad_id) WHERE id=?', [
            $st,
            trim((string) ($extra['sayad_id'] ?? '')) ?: null,
            $ch['id'],
        ]);
        self::logEvent((int) $ch['id'], 'sayad_update', $ch['physical_status'], $ch['settlement_status'], 0, null, 'وضعیت صیادی: ' . $st);
        return ['ok' => true, 'message' => 'وضعیت صیادی به‌روز شد'];
    }

    private static function eventExists(int $chequeId, string $action): bool
    {
        $row = Database::query(
            'SELECT id FROM cheque_events WHERE cheque_id=? AND event_type=? LIMIT 1',
            [$chequeId, $action]
        )->fetch();
        return (bool) $row;
    }

    private static function logEvent(
        int $chequeId,
        string $type,
        string $physical,
        string $settlement,
        float $amount,
        ?int $voucherId,
        string $detail
    ): void {
        Database::query(
            'INSERT INTO cheque_events
              (cheque_id, event_type, event_date, physical_status, settlement_status, amount, voucher_id, user_id, detail, created_at)
             VALUES (?,?,?,?,?,?,?,?,?,NOW())',
            [
                $chequeId,
                $type,
                date('Y-m-d'),
                $physical,
                $settlement,
                $amount,
                $voucherId,
                current_user()['id'] ?? null,
                $detail,
            ]
        );
    }

    private static function voucherTypeId(): ?int
    {
        $row = Database::query("SELECT id FROM voucher_types WHERE code IN ('BANK','AUTO','CHK') ORDER BY FIELD(code,'CHK','BANK','AUTO') LIMIT 1")->fetch();
        return $row ? (int) $row['id'] : null;
    }

    public static function physicalLabel(string $s): string
    {
        return [
            'blank' => 'برگه سفید',
            'in_hand' => 'نزد صندوق',
            'deposited' => 'در جریان وصول',
            'collected' => 'وصول‌شده',
            'returned' => 'برگشتی',
            'endorsed' => 'خرج/انتقال‌شده',
            'legal' => 'پیگیری حقوقی',
            'issued' => 'صادر و تحویل‌شده',
            'cleared' => 'برداشت بانکی',
            'cancelled' => 'ابطال',
            'overdue' => 'سررسیدگذشته',
        ][$s] ?? $s;
    }

    public static function dashboardStats(): array
    {
        $recv = Database::query(
            "SELECT
               COUNT(*) cnt,
               COALESCE(SUM(amount - amount_settled),0) open_amt,
               COALESCE(SUM(CASE WHEN physical_status='in_hand' THEN amount-amount_settled ELSE 0 END),0) in_hand,
               COALESCE(SUM(CASE WHEN physical_status='deposited' THEN amount-amount_settled ELSE 0 END),0) deposited,
               COALESCE(SUM(CASE WHEN physical_status='returned' THEN amount-amount_settled ELSE 0 END),0) returned
             FROM cheques WHERE direction='receivable' AND settlement_status NOT IN ('void')"
        )->fetch() ?: [];
        $pay = Database::query(
            "SELECT
               COUNT(*) cnt,
               COALESCE(SUM(CASE WHEN physical_status='issued' THEN amount ELSE 0 END),0) issued_amt,
               COALESCE(SUM(CASE WHEN settlement_status='settled' THEN amount ELSE 0 END),0) cleared_amt
             FROM cheques WHERE direction='payable' AND settlement_status NOT IN ('void')"
        )->fetch() ?: [];
        return ['receivable' => $recv, 'payable' => $pay];
    }
}
