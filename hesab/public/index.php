<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/Router.php';

$router = new Router();

$router->get('/install', function () {
    if (Installer::isInstalled()) {
        redirect('/login');
    }
    view('install', ['title' => 'نصب سامانه']);
});

$router->post('/install', function () {
    if (Installer::isInstalled()) {
        redirect('/login');
    }
    verify_csrf();
    try {
        Installer::run([
            'host' => trim($_POST['db_host'] ?? 'localhost'),
            'name' => trim($_POST['db_name'] ?? ''),
            'user' => trim($_POST['db_user'] ?? ''),
            'pass' => (string) ($_POST['db_pass'] ?? ''),
            'base_url' => trim($_POST['base_url'] ?? 'https://hesab.hdd-land.ir'),
        ], trim($_POST['admin_name'] ?? 'مدیر'), trim($_POST['admin_email'] ?? ''), (string) ($_POST['admin_pass'] ?? ''));
        flash('ok', 'نصب با موفقیت انجام شد. وارد شوید.');
        redirect('/login');
    } catch (Throwable $e) {
        flash('err', 'خطا در نصب: ' . $e->getMessage());
        redirect('/install');
    }
});

$router->get('/login', function () {
    if (!Installer::isInstalled()) {
        redirect('/install');
    }
    if (current_user()) {
        redirect('/');
    }
    view('login', ['title' => 'ورود']);
});

$router->post('/login', function () {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    $pass = (string) ($_POST['password'] ?? '');
    if (attempt_login($email, $pass)) {
        redirect('/');
    }
    flash('err', 'ایمیل یا رمز عبور نادرست است.');
    redirect('/login');
});

$router->get('/logout', function () {
    logout_user();
    redirect('/login');
});

$router->get('/', function () {
    require_login();
    $fy = Database::query('SELECT * FROM fiscal_years WHERE is_active=1 LIMIT 1')->fetch();
    $counts = [
        'moein' => (int) Database::query('SELECT COUNT(*) c FROM accounts_moein')->fetch()['c'],
        'vouchers' => (int) Database::query('SELECT COUNT(*) c FROM vouchers')->fetch()['c'],
        'posted' => (int) Database::query("SELECT COUNT(*) c FROM vouchers WHERE status='posted'")->fetch()['c'],
        'parties' => (int) Database::query('SELECT COUNT(*) c FROM parties')->fetch()['c'],
        'invoices' => (int) Database::query('SELECT COUNT(*) c FROM invoices')->fetch()['c'],
    ];
    $recent = Database::query('SELECT v.*, u.name AS user_name FROM vouchers v LEFT JOIN users u ON u.id=v.created_by ORDER BY v.id DESC LIMIT 8')->fetchAll();
    view('dashboard', compact('fy', 'counts', 'recent') + ['title' => 'داشبورد', 'nav' => 'dashboard']);
});

$router->get('/accounts', function () {
    require_login();
    $rows = Database::query(
        'SELECT g.code gc, g.title gt, k.code kc, k.title kt, m.code mc, m.title mt, m.id mid
         FROM accounts_moein m
         JOIN accounts_kol k ON k.id=m.kol_id
         JOIN account_groups g ON g.id=k.group_id
         ORDER BY g.code+0, k.code+0, m.code+0'
    )->fetchAll();
    view('accounts', ['title' => 'کدینگ حساب‌ها', 'nav' => 'accounts', 'rows' => $rows]);
});

$router->get('/vouchers', function () {
    require_login();
    $rows = Database::query('SELECT * FROM vouchers ORDER BY id DESC LIMIT 200')->fetchAll();
    view('vouchers', ['title' => 'اسناد حسابداری', 'nav' => 'vouchers', 'rows' => $rows]);
});

$router->get('/vouchers/create', function () {
    require_login();
    $moeins = Database::query('SELECT id, code, title FROM accounts_moein WHERE is_active=1 ORDER BY code+0')->fetchAll();
    view('voucher_form', ['title' => 'سند جدید', 'nav' => 'vouchers', 'moeins' => $moeins]);
});

$router->post('/vouchers/create', function () {
    require_login();
    verify_csrf();
    $fy = Database::query('SELECT * FROM fiscal_years WHERE is_active=1 LIMIT 1')->fetch();
    if (!$fy) {
        flash('err', 'سال مالی فعال تعریف نشده است.');
        redirect('/vouchers');
    }
    $date = $_POST['voucher_date'] ?? date('Y-m-d');
    $desc = trim($_POST['description'] ?? '');
    $debits = $_POST['debit'] ?? [];
    $credits = $_POST['credit'] ?? [];
    $moeins = $_POST['moein_id'] ?? [];
    $lineDesc = $_POST['line_desc'] ?? [];

    $lines = [];
    $sumD = 0;
    $sumC = 0;
    foreach ($moeins as $i => $mid) {
        $d = (float) str_replace(',', '', (string) ($debits[$i] ?? 0));
        $c = (float) str_replace(',', '', (string) ($credits[$i] ?? 0));
        if (!$mid || ($d <= 0 && $c <= 0)) {
            continue;
        }
        if ($d > 0 && $c > 0) {
            flash('err', 'هر ردیف فقط بدهکار یا بستانکار باشد.');
            redirect('/vouchers/create');
        }
        $lines[] = [
            'moein_id' => (int) $mid,
            'debit' => $d,
            'credit' => $c,
            'description' => trim((string) ($lineDesc[$i] ?? '')),
        ];
        $sumD += $d;
        $sumC += $c;
    }
    if (count($lines) < 2) {
        flash('err', 'حداقل دو ردیف لازم است.');
        redirect('/vouchers/create');
    }
    if (abs($sumD - $sumC) > 0.0001) {
        flash('err', 'سند تراز نیست. بدهکار: ' . money($sumD) . ' / بستانکار: ' . money($sumC));
        redirect('/vouchers/create');
    }

    $pdo = Database::pdo();
    $pdo->beginTransaction();
    try {
        $num = (int) Database::query('SELECT COALESCE(MAX(number),0)+1 n FROM vouchers WHERE fiscal_year_id=?', [$fy['id']])->fetch()['n'];
        Database::query(
            'INSERT INTO vouchers (fiscal_year_id, number, voucher_date, description, status, created_by) VALUES (?,?,?,?,?,?)',
            [$fy['id'], $num, $date, $desc, 'draft', current_user()['id']]
        );
        $vid = (int) $pdo->lastInsertId();
        $n = 1;
        foreach ($lines as $ln) {
            Database::query(
                'INSERT INTO voucher_lines (voucher_id, line_no, moein_id, description, debit, credit) VALUES (?,?,?,?,?,?)',
                [$vid, $n++, $ln['moein_id'], $ln['description'], $ln['debit'], $ln['credit']]
            );
        }
        $pdo->commit();
        flash('ok', 'سند پیش‌نویس شماره ' . $num . ' ذخیره شد.');
        redirect('/vouchers/view?id=' . $vid);
    } catch (Throwable $e) {
        $pdo->rollBack();
        flash('err', $e->getMessage());
        redirect('/vouchers/create');
    }
});

$router->get('/vouchers/view', function () {
    require_login();
    $id = (int) ($_GET['id'] ?? 0);
    $v = Database::query('SELECT * FROM vouchers WHERE id=?', [$id])->fetch();
    if (!$v) {
        flash('err', 'سند یافت نشد.');
        redirect('/vouchers');
    }
    $lines = Database::query(
        'SELECT l.*, m.code, m.title FROM voucher_lines l JOIN accounts_moein m ON m.id=l.moein_id WHERE voucher_id=? ORDER BY line_no',
        [$id]
    )->fetchAll();
    view('voucher_view', ['title' => 'سند ' . $v['number'], 'nav' => 'vouchers', 'v' => $v, 'lines' => $lines]);
});

$router->post('/vouchers/post', function () {
    require_login();
    verify_csrf();
    $id = (int) ($_POST['id'] ?? 0);
    $v = Database::query('SELECT * FROM vouchers WHERE id=?', [$id])->fetch();
    if (!$v || $v['status'] !== 'draft') {
        flash('err', 'فقط سند پیش‌نویس قابل ثبت قطعی است.');
        redirect('/vouchers');
    }
    $sum = Database::query('SELECT SUM(debit) d, SUM(credit) c FROM voucher_lines WHERE voucher_id=?', [$id])->fetch();
    if ((float) $sum['d'] !== (float) $sum['c']) {
        flash('err', 'سند تراز نیست.');
        redirect('/vouchers/view?id=' . $id);
    }
    Database::query("UPDATE vouchers SET status='posted', posted_at=NOW() WHERE id=?", [$id]);
    flash('ok', 'سند قطعی شد.');
    redirect('/vouchers/view?id=' . $id);
});

$router->get('/ledger', function () {
    require_login();
    $moeinId = (int) ($_GET['moein_id'] ?? 0);
    $moeins = Database::query('SELECT id, code, title FROM accounts_moein ORDER BY code+0')->fetchAll();
    $rows = [];
    $account = null;
    if ($moeinId) {
        $account = Database::query('SELECT * FROM accounts_moein WHERE id=?', [$moeinId])->fetch();
        $rows = Database::query(
            "SELECT v.number, v.voucher_date, v.status, l.description, l.debit, l.credit
             FROM voucher_lines l
             JOIN vouchers v ON v.id=l.voucher_id
             WHERE l.moein_id=? AND v.status='posted'
             ORDER BY v.voucher_date, v.number, l.line_no",
            [$moeinId]
        )->fetchAll();
    }
    view('ledger', compact('moeins', 'moeinId', 'rows', 'account') + ['title' => 'دفتر حساب', 'nav' => 'ledger']);
});

$router->get('/trial-balance', function () {
    require_login();
    $rows = Database::query(
        "SELECT m.code, m.title,
                COALESCE(SUM(l.debit),0) debit,
                COALESCE(SUM(l.credit),0) credit
         FROM accounts_moein m
         LEFT JOIN voucher_lines l ON l.moein_id=m.id
         LEFT JOIN vouchers v ON v.id=l.voucher_id AND v.status='posted'
         GROUP BY m.id
         HAVING debit<>0 OR credit<>0
         ORDER BY m.code+0"
    )->fetchAll();
    view('trial_balance', ['title' => 'تراز آزمایشی', 'nav' => 'trial', 'rows' => $rows]);
});

$router->get('/parties', function () {
    require_login();
    $rows = Database::query('SELECT * FROM parties ORDER BY id DESC')->fetchAll();
    view('parties', ['title' => 'طرف‌حساب‌ها', 'nav' => 'parties', 'rows' => $rows]);
});

$router->post('/parties', function () {
    require_login();
    verify_csrf();
    $code = trim($_POST['code'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $type = $_POST['type'] ?? 'customer';
    $phone = trim($_POST['phone'] ?? '');
    if ($code === '' || $name === '') {
        flash('err', 'کد و نام الزامی است.');
        redirect('/parties');
    }
    try {
        Database::query('INSERT INTO parties (code, name, type, phone) VALUES (?,?,?,?)', [$code, $name, $type, $phone]);
        flash('ok', 'طرف‌حساب ذخیره شد.');
    } catch (Throwable $e) {
        flash('err', 'خطا: احتمالاً کد تکراری است.');
    }
    redirect('/parties');
});

$router->get('/invoices', function () {
    require_login();
    $rows = Database::query(
        'SELECT i.*, p.name party_name FROM invoices i JOIN parties p ON p.id=i.party_id ORDER BY i.id DESC'
    )->fetchAll();
    $parties = Database::query('SELECT id, name FROM parties ORDER BY name')->fetchAll();
    view('invoices', compact('rows', 'parties') + ['title' => 'فاکتور فروش', 'nav' => 'invoices']);
});

$router->post('/invoices', function () {
    require_login();
    verify_csrf();
    $partyId = (int) ($_POST['party_id'] ?? 0);
    $date = $_POST['invoice_date'] ?? date('Y-m-d');
    $title = trim($_POST['item_title'] ?? 'فروش');
    $qty = (float) ($_POST['qty'] ?? 1);
    $price = (float) str_replace(',', '', (string) ($_POST['unit_price'] ?? 0));
    $amount = $qty * $price;
    if (!$partyId || $amount <= 0) {
        flash('err', 'طرف‌حساب و مبلغ معتبر لازم است.');
        redirect('/invoices');
    }

    $sale = Database::query("SELECT id FROM accounts_moein WHERE code='6101' LIMIT 1")->fetch();
    $recv = Database::query("SELECT id FROM accounts_moein WHERE code='1302' LIMIT 1")->fetch()
        ?: Database::query("SELECT id FROM accounts_moein WHERE code='1301' LIMIT 1")->fetch();
    if (!$sale || !$recv) {
        flash('err', 'حساب فروش یا دریافتنی در کدینگ پیدا نشد.');
        redirect('/invoices');
    }
    $fy = Database::query('SELECT * FROM fiscal_years WHERE is_active=1 LIMIT 1')->fetch();

    $pdo = Database::pdo();
    $pdo->beginTransaction();
    try {
        $num = (int) Database::query('SELECT COALESCE(MAX(number),0)+1 n FROM invoices')->fetch()['n'];
        Database::query(
            'INSERT INTO invoices (number, invoice_date, party_id, total, description, status) VALUES (?,?,?,?,?,?)',
            [$num, $date, $partyId, $amount, $title, 'confirmed']
        );
        $iid = (int) $pdo->lastInsertId();
        Database::query(
            'INSERT INTO invoice_items (invoice_id, title, qty, unit_price, amount) VALUES (?,?,?,?,?)',
            [$iid, $title, $qty, $price, $amount]
        );

        $vnum = (int) Database::query('SELECT COALESCE(MAX(number),0)+1 n FROM vouchers WHERE fiscal_year_id=?', [$fy['id']])->fetch()['n'];
        Database::query(
            'INSERT INTO vouchers (fiscal_year_id, number, voucher_date, description, status, created_by, posted_at) VALUES (?,?,?,?,?,?,NOW())',
            [$fy['id'], $vnum, $date, 'سند خودکار فاکتور فروش ' . $num, 'posted', current_user()['id']]
        );
        $vid = (int) $pdo->lastInsertId();
        Database::query(
            'INSERT INTO voucher_lines (voucher_id, line_no, moein_id, party_id, description, debit, credit) VALUES (?,?,?,?,?,?,?)',
            [$vid, 1, $recv['id'], $partyId, 'بدهکار مشتری', $amount, 0]
        );
        Database::query(
            'INSERT INTO voucher_lines (voucher_id, line_no, moein_id, party_id, description, debit, credit) VALUES (?,?,?,?,?,?,?)',
            [$vid, 2, $sale['id'], $partyId, 'فروش', 0, $amount]
        );
        Database::query('UPDATE invoices SET voucher_id=? WHERE id=?', [$vid, $iid]);
        $pdo->commit();
        flash('ok', 'فاکتور و سند حسابداری صادر شد.');
    } catch (Throwable $e) {
        $pdo->rollBack();
        flash('err', $e->getMessage());
    }
    redirect('/invoices');
});

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', request_path());
