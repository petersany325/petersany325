<?php
declare(strict_types=1);

/** @var Router $router */

$mview = static function (string $name, array $data = []): void {
    $data['force_mobile'] = true;
    view($name, $data);
};

$router->get('/m', function () use ($mview) {
    require_login();
    $_SESSION['ui_mode'] = 'mobile';
    $fy = Database::query('SELECT * FROM fiscal_years WHERE is_active=1 LIMIT 1')->fetch();
    $counts = [
        'moein' => (int) Database::query('SELECT COUNT(*) c FROM accounts_moein')->fetch()['c'],
        'tafsili' => (int) Database::query('SELECT COUNT(*) c FROM tafsili_items')->fetch()['c'],
        'vouchers' => (int) Database::query('SELECT COUNT(*) c FROM vouchers')->fetch()['c'],
        'locked' => (int) Database::query("SELECT COUNT(*) c FROM vouchers WHERE status IN ('locked','posted')")->fetch()['c'],
        'checks' => (int) Database::query('SELECT COUNT(*) c FROM checks')->fetch()['c'],
    ];
    $recent = Database::query(
        'SELECT v.*, u.name AS user_name, t.title type_title
         FROM vouchers v
         LEFT JOIN users u ON u.id=v.created_by
         LEFT JOIN voucher_types t ON t.id=v.voucher_type_id
         ORDER BY v.id DESC LIMIT 12'
    )->fetchAll();
    $mview('dashboard', compact('fy', 'counts', 'recent') + ['title' => 'خانه', 'nav' => 'dashboard']);
});

$router->get('/m/login', function () use ($mview) {
    if (!Installer::isInstalled()) {
        redirect('/install');
    }
    if (current_user()) {
        redirect('/m');
    }
    $_SESSION['ui_mode'] = 'mobile';
    $mview('login', ['title' => 'ورود موبایل', 'nav' => 'login']);
});

$router->post('/m/login', function () {
    verify_csrf();
    $_SESSION['ui_mode'] = 'mobile';
    $email = trim($_POST['email'] ?? '');
    $pass = (string) ($_POST['password'] ?? '');
    if (attempt_login($email, $pass)) {
        Audit::log('auth.login.mobile');
        redirect('/m');
    }
    flash('err', 'ایمیل یا رمز عبور نادرست است.');
    redirect('/m/login');
});

$router->get('/m/vouchers', function () use ($mview) {
    Permission::require('vouchers.create');
    $status = $_GET['status'] ?? '';
    $sql = 'SELECT v.*, t.title type_title FROM vouchers v LEFT JOIN voucher_types t ON t.id=v.voucher_type_id';
    $params = [];
    if ($status !== '') {
        $sql .= ' WHERE v.status=?';
        $params[] = $status;
    }
    $sql .= ' ORDER BY v.id DESC LIMIT 200';
    $rows = Database::query($sql, $params)->fetchAll();
    $mview('vouchers', ['title' => 'اسناد', 'nav' => 'vouchers', 'rows' => $rows, 'status' => $status]);
});

$router->get('/m/vouchers/create', function () use ($mview) {
    Permission::require('vouchers.create');
    $moeins = Database::query('SELECT id, code, title FROM accounts_moein WHERE is_active=1 ORDER BY code+0')->fetchAll();
    $types = Database::query('SELECT * FROM voucher_types WHERE is_active=1')->fetchAll();
    $tafsili = Database::query(
        'SELECT i.id, i.code, i.title, i.type_id, t.title type_title
         FROM tafsili_items i JOIN tafsili_types t ON t.id=i.type_id
         WHERE i.is_active=1 ORDER BY t.id, i.code'
    )->fetchAll();
    $mview('voucher_form', compact('moeins', 'types', 'tafsili') + ['title' => 'سند جدید', 'nav' => 'create']);
});

$router->post('/m/vouchers/create', function () {
    Permission::require('vouchers.create');
    verify_csrf();
    try {
        $lines = [];
        foreach ($_POST['moein_id'] ?? [] as $i => $mid) {
            $lines[] = [
                'moein_id' => $mid,
                'debit' => (float) str_replace(',', '', (string) ($_POST['debit'][$i] ?? 0)),
                'credit' => (float) str_replace(',', '', (string) ($_POST['credit'][$i] ?? 0)),
                'description' => $_POST['line_desc'][$i] ?? '',
                'tafsili1_id' => $_POST['tafsili1_id'][$i] ?? 0,
                'tafsili2_id' => $_POST['tafsili2_id'][$i] ?? 0,
                'tafsili3_id' => $_POST['tafsili3_id'][$i] ?? 0,
                'project_id' => $_POST['project_id'][$i] ?? 0,
                'cost_center_id' => $_POST['cost_center_id'][$i] ?? 0,
                'branch_id' => $_POST['branch_id'][$i] ?? 0,
            ];
        }
        $status = $_POST['save_as'] ?? 'draft';
        if (!in_array($status, ['draft', 'operational'], true)) {
            $status = 'draft';
        }
        $vid = Accounting::createVoucher([
            'voucher_date' => $_POST['voucher_date'] ?? date('Y-m-d'),
            'description' => trim($_POST['description'] ?? ''),
            'voucher_type_id' => (int) ($_POST['voucher_type_id'] ?? 0) ?: null,
            'source_module' => 'mobile',
        ], $lines, $status);
        flash('ok', 'سند موبایل ذخیره شد.');
        redirect('/m/vouchers/view?id=' . $vid);
    } catch (Throwable $e) {
        flash('err', $e->getMessage());
        redirect('/m/vouchers/create');
    }
});

$router->get('/m/vouchers/view', function () use ($mview) {
    Permission::require('vouchers.create');
    $id = (int) ($_GET['id'] ?? 0);
    $v = Database::query('SELECT v.*, t.title type_title FROM vouchers v LEFT JOIN voucher_types t ON t.id=v.voucher_type_id WHERE v.id=?', [$id])->fetch();
    if (!$v) {
        flash('err', 'سند یافت نشد.');
        redirect('/m/vouchers');
    }
    $lines = Database::query(
        'SELECT l.*, m.code, m.title, t1.title t1title
         FROM voucher_lines l
         JOIN accounts_moein m ON m.id=l.moein_id
         LEFT JOIN tafsili_items t1 ON t1.id=l.tafsili1_id
         WHERE voucher_id=? ORDER BY line_no',
        [$id]
    )->fetchAll();
    $mview('voucher_view', compact('v', 'lines') + ['title' => 'سند ' . $v['number'], 'nav' => 'vouchers']);
});

$router->post('/m/vouchers/status', function () {
    verify_csrf();
    $id = (int) ($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    if ($status === 'reviewed') {
        Permission::require('vouchers.review');
    } elseif ($status === 'locked') {
        Permission::require('vouchers.lock');
    } else {
        Permission::require('vouchers.create');
    }
    try {
        Accounting::setStatus($id, $status);
        flash('ok', 'وضعیت سند به‌روز شد.');
    } catch (Throwable $e) {
        flash('err', $e->getMessage());
    }
    redirect('/m/vouchers/view?id=' . $id);
});

$router->get('/m/treasury', function () use ($mview) {
    Permission::require('treasury.manage');
    $banks = Database::query('SELECT * FROM bank_accounts ORDER BY id DESC')->fetchAll();
    $docs = Database::query('SELECT * FROM treasury_docs ORDER BY id DESC LIMIT 40')->fetchAll();
    $persons = Database::query(
        "SELECT i.* FROM tafsili_items i
         JOIN tafsili_types t ON t.id=i.type_id
         WHERE t.code IN ('PERSON','01','12') AND i.is_active=1
         ORDER BY i.code LIMIT 300"
    )->fetchAll();
    $mview('treasury', compact('banks', 'docs', 'persons') + ['title' => 'خزانه', 'nav' => 'treasury']);
});

$router->post('/m/treasury/doc', function () {
    // reuse desktop treasury logic via internal redirect-like call
    Permission::require('treasury.manage');
    verify_csrf();
    $type = $_POST['doc_type'] === 'pay' ? 'pay' : 'receive';
    $amount = (float) str_replace(',', '', (string) ($_POST['amount'] ?? 0));
    $num = (int) Database::query('SELECT COALESCE(MAX(number),0)+1 n FROM treasury_docs WHERE doc_type=?', [$type])->fetch()['n'];
    $cash = Database::query("SELECT id FROM accounts_moein WHERE code LIKE '1102%' OR title LIKE '%صندوق%' LIMIT 1")->fetch();
    $bankMoein = Database::query("SELECT id FROM accounts_moein WHERE code LIKE '1101%' OR title LIKE '%بانک%' LIMIT 1")->fetch();
    $partyRecv = Database::query("SELECT id FROM accounts_moein WHERE code IN ('1301','1302','110401') LIMIT 1")->fetch();
    $partyPay = Database::query("SELECT id FROM accounts_moein WHERE code IN ('3101','3103','210101') LIMIT 1")->fetch();
    $method = $_POST['method'] ?? 'cash';
    $cashId = $method === 'bank' ? ($bankMoein['id'] ?? $cash['id'] ?? null) : ($cash['id'] ?? null);
    $contra = $type === 'receive' ? ($partyRecv['id'] ?? null) : ($partyPay['id'] ?? null);
    if (!$cashId || !$contra || $amount <= 0) {
        flash('err', 'حساب صندوق/طرف‌حساب یافت نشد یا مبلغ نامعتبر است.');
        redirect('/m/treasury');
    }
    $lines = $type === 'receive'
        ? [
            ['moein_id' => $cashId, 'debit' => $amount, 'credit' => 0, 'description' => 'دریافت موبایل', 'tafsili1_id' => $_POST['party_tafsili_id'] ?? 0],
            ['moein_id' => $contra, 'debit' => 0, 'credit' => $amount, 'description' => 'طرف دریافت', 'tafsili1_id' => $_POST['party_tafsili_id'] ?? 0],
        ]
        : [
            ['moein_id' => $contra, 'debit' => $amount, 'credit' => 0, 'description' => 'طرف پرداخت', 'tafsili1_id' => $_POST['party_tafsili_id'] ?? 0],
            ['moein_id' => $cashId, 'debit' => 0, 'credit' => $amount, 'description' => 'پرداخت موبایل', 'tafsili1_id' => $_POST['party_tafsili_id'] ?? 0],
        ];
    try {
        $vid = Accounting::createVoucher([
            'voucher_date' => $_POST['doc_date'] ?? date('Y-m-d'),
            'description' => ($type === 'receive' ? 'دریافت موبایل ' : 'پرداخت موبایل ') . trim($_POST['description'] ?? ''),
            'voucher_type_id' => Database::query("SELECT id FROM voucher_types WHERE code='BANK'")->fetch()['id'] ?? null,
            'source_module' => 'mobile_treasury',
        ], $lines, 'operational');
        Database::query(
            'INSERT INTO treasury_docs (doc_type, number, doc_date, party_tafsili_id, bank_account_id, amount, method, description, voucher_id, created_by)
             VALUES (?,?,?,?,?,?,?,?,?,?)',
            [$type, $num, $_POST['doc_date'] ?? date('Y-m-d'), (int) ($_POST['party_tafsili_id'] ?? 0) ?: null, (int) ($_POST['bank_account_id'] ?? 0) ?: null, $amount, $method, trim($_POST['description'] ?? ''), $vid, current_user()['id']]
        );
        flash('ok', 'سند خزانه موبایل صادر شد.');
    } catch (Throwable $e) {
        flash('err', $e->getMessage());
    }
    redirect('/m/treasury');
});

$router->get('/m/more', function () use ($mview) {
    require_login();
    $mview('more', ['title' => 'بیشتر', 'nav' => 'more']);
});

$router->get('/m/reports', function () use ($mview) {
    Permission::require('reports.view');
    $mview('reports', ['title' => 'گزارش‌ها', 'nav' => 'reports']);
});

$router->get('/m/accounts', function () use ($mview) {
    Permission::require('accounts.manage');
    $rows = Database::query(
        'SELECT m.*, k.code kc, k.title kt, g.code gc, g.title gt
         FROM accounts_moein m
         JOIN accounts_kol k ON k.id=m.kol_id
         JOIN account_groups g ON g.id=k.group_id
         ORDER BY g.code+0, k.code+0, m.code+0
         LIMIT 500'
    )->fetchAll();
    $mview('accounts', ['title' => 'کدینگ', 'nav' => 'accounts', 'rows' => $rows]);
});

$router->get('/m/tafsili', function () use ($mview) {
    Permission::require('tafsili.manage');
    $types = Database::query('SELECT * FROM tafsili_types ORDER BY code, id')->fetchAll();
    $typeId = (int) ($_GET['type_id'] ?? ($types[0]['id'] ?? 0));
    $items = $typeId ? Database::query('SELECT * FROM tafsili_items WHERE type_id=? ORDER BY code LIMIT 300', [$typeId])->fetchAll() : [];
    $mview('tafsili', compact('types', 'typeId', 'items') + ['title' => 'تفصیلی', 'nav' => 'more']);
});

$router->get('/m/trial-balance', function () use ($mview) {
    Permission::require('reports.view');
    $rows = Accounting::trialBalance();
    $mview('trial_balance', ['title' => 'تراز آزمایشی', 'nav' => 'reports', 'rows' => $rows]);
});

$router->get('/m/pl', function () use ($mview) {
    Permission::require('reports.view');
    $all = Accounting::trialBalance();
    $rows = [];
    foreach ($all as $r) {
        $c = (string) $r['code'];
        if (str_starts_with($c, '6') || str_starts_with($c, '7') || str_starts_with($c, '8') || str_starts_with($c, '4') || str_starts_with($c, '5')) {
            $rows[] = $r;
        }
    }
    $mview('report_simple', ['title' => 'سود و زیان', 'nav' => 'reports', 'rows' => $rows]);
});

$router->get('/m/balance-sheet', function () use ($mview) {
    Permission::require('reports.view');
    $all = Accounting::trialBalance();
    $rows = [];
    foreach ($all as $r) {
        $c = (string) $r['code'];
        if (str_starts_with($c, '1') || str_starts_with($c, '2') || str_starts_with($c, '3')) {
            $bal = (float) $r['debit'] - (float) $r['credit'];
            $rows[] = $r + ['balance' => $bal];
        }
    }
    $mview('report_simple', ['title' => 'ترازنامه', 'nav' => 'reports', 'rows' => $rows]);
});

$router->get('/m/journal', function () use ($mview) {
    Permission::require('reports.view');
    $rows = Database::query(
        "SELECT v.number, v.voucher_date, v.description, m.code, m.title, l.debit, l.credit
         FROM voucher_lines l
         JOIN vouchers v ON v.id=l.voucher_id
         JOIN accounts_moein m ON m.id=l.moein_id
         WHERE v.status IN ('operational','reviewed','locked','posted')
         ORDER BY v.voucher_date DESC, v.number DESC, l.line_no
         LIMIT 200"
    )->fetchAll();
    $mapped = [];
    foreach ($rows as $r) {
        $mapped[] = [
            'code' => $r['code'],
            'title' => $r['title'] . ' · سند ' . $r['number'],
            'debit' => $r['debit'],
            'credit' => $r['credit'],
            'description' => $r['description'],
        ];
    }
    $mview('report_simple', ['title' => 'دفتر روزنامه', 'nav' => 'reports', 'rows' => $mapped]);
});
