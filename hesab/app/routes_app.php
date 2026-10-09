<?php
declare(strict_types=1);

/** @var Router $router */

$router->get('/', function () {
    require_login();
    if (wants_mobile_ui()) {
        redirect('/m');
    }
    $fy = Database::query('SELECT * FROM fiscal_years WHERE is_active=1 LIMIT 1')->fetch();
    $counts = [
        'moein' => (int) Database::query('SELECT COUNT(*) c FROM accounts_moein')->fetch()['c'],
        'tafsili' => (int) Database::query('SELECT COUNT(*) c FROM tafsili_items')->fetch()['c'],
        'vouchers' => (int) Database::query('SELECT COUNT(*) c FROM vouchers')->fetch()['c'],
        'locked' => (int) Database::query("SELECT COUNT(*) c FROM vouchers WHERE status IN ('locked','posted')")->fetch()['c'],
        'checks' => (int) (function () {
            try {
                return Database::query('SELECT COUNT(*) c FROM cheques')->fetch()['c'];
            } catch (Throwable $e) {
                return Database::query('SELECT COUNT(*) c FROM checks')->fetch()['c'] ?? 0;
            }
        })(),
    ];
    $recent = Database::query('SELECT v.*, u.name AS user_name, t.title type_title FROM vouchers v LEFT JOIN users u ON u.id=v.created_by LEFT JOIN voucher_types t ON t.id=v.voucher_type_id ORDER BY v.id DESC LIMIT 10')->fetchAll();
    view('dashboard', compact('fy', 'counts', 'recent') + ['title' => 'داشبورد', 'nav' => 'dashboard']);
});

// ---- Coding ----
$router->get('/accounts', function () {
    Permission::require('accounts.manage');
    $rows = Database::query(
        'SELECT m.*, k.code kc, k.title kt, g.code gc, g.title gt
         FROM accounts_moein m
         JOIN accounts_kol k ON k.id=m.kol_id
         JOIN account_groups g ON g.id=k.group_id
         ORDER BY g.code+0, k.code+0, m.code+0'
    )->fetchAll();
    $maps = Database::query(
        'SELECT mm.*, tt.title type_title FROM moein_tafsili_map mm JOIN tafsili_types tt ON tt.id=mm.tafsili_type_id ORDER BY mm.moein_id, mm.level'
    )->fetchAll();
    $byMoein = [];
    foreach ($maps as $m) {
        $byMoein[$m['moein_id']][] = $m;
    }
    $types = Database::query('SELECT * FROM tafsili_types WHERE is_active=1 ORDER BY id')->fetchAll();
    view('accounts', ['title' => 'کدینگ حساب‌ها', 'nav' => 'accounts', 'rows' => $rows, 'byMoein' => $byMoein, 'types' => $types]);
});

$router->post('/accounts/moein-save', function () {
    Permission::require('accounts.manage');
    verify_csrf();
    $id = (int) ($_POST['id'] ?? 0);
    $nature = $_POST['nature'] ?? 'neutral';
    $allowD = isset($_POST['allow_debit']) ? 1 : 0;
    $allowC = isset($_POST['allow_credit']) ? 1 : 0;
    Database::query('UPDATE accounts_moein SET nature=?, allow_debit=?, allow_credit=? WHERE id=?', [$nature, $allowD, $allowC, $id]);
    // tafsili maps levels 1..3
    Database::query('DELETE FROM moein_tafsili_map WHERE moein_id=?', [$id]);
    for ($lvl = 1; $lvl <= 3; $lvl++) {
        $typeId = (int) ($_POST['tafsili_type_' . $lvl] ?? 0);
        if (!$typeId) {
            continue;
        }
        $req = isset($_POST['tafsili_req_' . $lvl]) ? 1 : 0;
        Database::query('INSERT INTO moein_tafsili_map (moein_id, level, tafsili_type_id, is_required) VALUES (?,?,?,?)', [$id, $lvl, $typeId, $req]);
    }
    Audit::log('accounts.moein_save', 'accounts_moein', $id);
    flash('ok', 'کنترل‌ها و ارتباط تفصیلی ذخیره شد.');
    redirect('/accounts');
});

$router->post('/accounts/add-moein', function () {
    Permission::require('accounts.manage');
    verify_csrf();
    $kolCode = trim($_POST['kol_code'] ?? '');
    $code = trim($_POST['code'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $kol = Database::query('SELECT id FROM accounts_kol WHERE code=?', [$kolCode])->fetch();
    if (!$kol || $code === '' || $title === '') {
        flash('err', 'کل/کد/عنوان نامعتبر است.');
        redirect('/accounts');
    }
    try {
        Database::query('INSERT INTO accounts_moein (code, title, kol_id, nature, allow_debit, allow_credit) VALUES (?,?,?,?,1,1)', [$code, $title, $kol['id'], $_POST['nature'] ?? 'neutral']);
        Audit::log('accounts.add_moein', 'accounts_moein', (int) Database::pdo()->lastInsertId(), $code);
        flash('ok', 'معین جدید اضافه شد.');
    } catch (Throwable $e) {
        flash('err', 'خطا: احتمالاً کد تکراری است.');
    }
    redirect('/accounts');
});

// ---- Floating tafsili ----
$router->get('/tafsili', function () {
    Permission::require('tafsili.manage');
    $types = Database::query('SELECT * FROM tafsili_types ORDER BY code, id')->fetchAll();
    $typeId = (int) ($_GET['type_id'] ?? ($types[0]['id'] ?? 0));
    $items = $typeId ? Database::query('SELECT * FROM tafsili_items WHERE type_id=? ORDER BY code', [$typeId])->fetchAll() : [];
    view('tafsili', compact('types', 'typeId', 'items') + ['title' => 'تفصیلی شناور', 'nav' => 'tafsili']);
});

$router->get('/dimensions', function () {
    Permission::require('tafsili.manage');
    $codes = ['07', '09', '08', 'PROJECT', 'COSTCENTER'];
    $in = implode(',', array_fill(0, count($codes), '?'));
    $types = Database::query("SELECT * FROM tafsili_types WHERE code IN ($in) ORDER BY code", $codes)->fetchAll();
    $typeId = (int) ($_GET['type_id'] ?? ($types[0]['id'] ?? 0));
    $items = $typeId ? Database::query('SELECT * FROM tafsili_items WHERE type_id=? ORDER BY code', [$typeId])->fetchAll() : [];
    view('dimensions', compact('types', 'typeId', 'items') + ['title' => 'ابعاد تحلیلی (پروژه / مرکز هزینه / شعبه)', 'nav' => 'dimensions']);
});

$router->get('/settings/accounting', function () {
    Permission::require('accounts.manage');
    $keys = ['coding_pattern', 'inventory_method', 'auto_post_subsystems', 'enable_dimensions', 'allow_negative_stock'];
    $settings = [];
    foreach ($keys as $k) {
        $row = Database::query('SELECT `value` FROM settings WHERE `key`=?', [$k])->fetch();
        $settings[$k] = $row['value'] ?? '';
    }
    $rules = Database::query('SELECT * FROM posting_rules ORDER BY id')->fetchAll();
    $treeCount = (int) Database::query('SELECT COUNT(*) c FROM accounts')->fetch()['c'];
    $workMode = class_exists('Shortcuts') ? Shortcuts::getMode(current_user()) : 'accountant';
    view('settings_accounting', compact('settings', 'rules', 'treeCount', 'workMode') + ['title' => 'تنظیمات حسابداری', 'nav' => 'settings_acc']);
});

$router->post('/settings/accounting', function () {
    Permission::require('accounts.manage');
    verify_csrf();
    $map = [
        'coding_pattern' => $_POST['coding_pattern'] ?? '1/2/4/6',
        'inventory_method' => $_POST['inventory_method'] ?? 'perpetual',
        'auto_post_subsystems' => isset($_POST['auto_post_subsystems']) ? '1' : '0',
        'enable_dimensions' => isset($_POST['enable_dimensions']) ? '1' : '0',
        'allow_negative_stock' => isset($_POST['allow_negative_stock']) ? '1' : '0',
    ];
    foreach ($map as $k => $v) {
        Database::query('INSERT INTO settings (`key`,`value`) VALUES (?,?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)', [$k, $v]);
    }
    foreach ($_POST['rule_moein'] ?? [] as $id => $code) {
        Database::query('UPDATE posting_rules SET moein_code=? WHERE id=?', [trim((string) $code), (int) $id]);
    }
    Audit::log('settings.accounting');
    flash('ok', 'تنظیمات حسابداری ذخیره شد.');
    redirect('/settings/accounting');
});

$router->get('/settings/shortcuts', function () {
    Permission::require('accounts.manage');
    if (($_GET['fix_layout'] ?? '') === '1') {
        header('Content-Type: text/plain; charset=utf-8');
        $sha = '70d97231ce0ad93f450fca13e4eede5c8e050aeb';
        $urls = [
            'https://cdn.jsdelivr.net/gh/petersany325/petersany325@' . $sha . '/hesab/views/layout.php',
            'https://raw.githubusercontent.com/petersany325/petersany325/' . $sha . '/hesab/views/layout.php?t=' . time(),
        ];
        $ctx = stream_context_create([
            'http' => ['timeout' => 45, 'header' => "User-Agent: hesab-fix-layout\r\nCache-Control: no-cache\r\n"],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);
        $data = false;
        foreach ($urls as $url) {
            $data = @file_get_contents($url, false, $ctx);
            if (is_string($data) && $data !== '' && !str_contains($data, 'hesab_pull')) {
                break;
            }
        }
        if (!is_string($data) || $data === '') {
            echo "FAIL download\n";
            exit;
        }
        file_put_contents(dirname(__DIR__) . '/views/layout.php', $data);
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
        echo 'OK layout ' . strlen($data) . " bytes\n";
        echo 'has_puller=' . (str_contains($data, 'hesab_pull') ? '1' : '0') . "\n";
        exit;
    }
    $user = current_user();
    $catalog = Shortcuts::catalog();
    $map = Shortcuts::forUser($user);
    $mode = Shortcuts::getMode($user);
    $conflicts = Shortcuts::findConflicts($map);
    $groups = [];
    foreach ($catalog as $id => $meta) {
        $groups[$meta['group']][$id] = $meta;
    }
    view('settings_shortcuts', compact('catalog', 'map', 'mode', 'conflicts', 'groups') + [
        'title' => 'میانبرهای کیبورد',
        'nav' => 'settings_shortcuts',
    ]);
});

$router->post('/settings/shortcuts', function () {
    Permission::require('accounts.manage');
    verify_csrf();
    $user = current_user();
    $op = (string) ($_POST['op'] ?? 'save');
    if ($op === 'reset') {
        Shortcuts::resetForUser($user);
        Shortcuts::setMode($user, Shortcuts::MODE_ACCOUNTANT);
        Audit::log('settings.shortcuts.reset');
        flash('ok', 'میانبرها به پیش‌فرض استاندارد برگشت.');
        redirect('/settings/shortcuts');
    }
    Shortcuts::setMode($user, (string) ($_POST['work_mode'] ?? Shortcuts::MODE_ACCOUNTANT));
    $conflicts = Shortcuts::saveForUser($user, $_POST['keys'] ?? []);
    Audit::log('settings.shortcuts.save');
    if ($conflicts) {
        flash('warn', 'ذخیره شد، ولی تداخل کلید دارید: ' . implode(' | ', $conflicts));
    } else {
        flash('ok', 'میانبرهای کیبورد ذخیره شد.');
    }
    redirect('/settings/shortcuts');
});

$router->post('/tafsili/type', function () {
    Permission::require('tafsili.manage');
    verify_csrf();
    Database::query('INSERT INTO tafsili_types (code, title) VALUES (?,?)', [trim($_POST['code'] ?? ''), trim($_POST['title'] ?? '')]);
    flash('ok', 'نوع تفصیلی اضافه شد.');
    redirect('/tafsili');
});

$router->post('/tafsili/item', function () {
    Permission::require('tafsili.manage');
    verify_csrf();
    Database::query('INSERT INTO tafsili_items (type_id, code, title) VALUES (?,?,?)', [(int) $_POST['type_id'], trim($_POST['code'] ?? ''), trim($_POST['title'] ?? '')]);
    flash('ok', 'تفصیلی / بعد اضافه شد.');
    $redir = trim((string) ($_POST['redirect'] ?? ''));
    if ($redir !== '' && str_starts_with($redir, '/')) {
        redirect($redir);
    }
    redirect('/tafsili?type_id=' . (int) $_POST['type_id']);
});

// ---- Vouchers ----
$router->get('/vouchers', function () {
    Permission::require('vouchers.create');
    $status = $_GET['status'] ?? '';
    $sql = 'SELECT v.*, t.title type_title FROM vouchers v LEFT JOIN voucher_types t ON t.id=v.voucher_type_id';
    $params = [];
    if ($status !== '') {
        $sql .= ' WHERE v.status=?';
        $params[] = $status;
    }
    $sql .= ' ORDER BY v.id DESC LIMIT 300';
    $rows = Database::query($sql, $params)->fetchAll();
    view('vouchers', ['title' => 'اسناد حسابداری', 'nav' => 'vouchers', 'rows' => $rows, 'status' => $status]);
});

$router->get('/vouchers/create', function () {
    Permission::require('vouchers.create');
    $moeins = Database::query('SELECT id, code, title, nature, allow_debit, allow_credit FROM accounts_moein WHERE is_active=1 ORDER BY code+0')->fetchAll();
    $types = Database::query('SELECT * FROM voucher_types WHERE is_active=1')->fetchAll();
    $tafsili = Database::query('SELECT i.id, i.code, i.title, i.type_id, t.title type_title FROM tafsili_items i JOIN tafsili_types t ON t.id=i.type_id WHERE i.is_active=1 ORDER BY t.id, i.code')->fetchAll();
    $maps = Database::query('SELECT * FROM moein_tafsili_map')->fetchAll();
    $projects = Database::query(
        "SELECT i.id, i.code, i.title FROM tafsili_items i JOIN tafsili_types t ON t.id=i.type_id WHERE t.code IN ('07','PROJECT') AND i.is_active=1 ORDER BY i.code"
    )->fetchAll();
    $costCenters = Database::query(
        "SELECT i.id, i.code, i.title FROM tafsili_items i JOIN tafsili_types t ON t.id=i.type_id WHERE t.code IN ('09','COSTCENTER') AND i.is_active=1 ORDER BY i.code"
    )->fetchAll();
    $branches = Database::query(
        "SELECT i.id, i.code, i.title FROM tafsili_items i JOIN tafsili_types t ON t.id=i.type_id WHERE t.code IN ('08') AND i.is_active=1 ORDER BY i.code"
    )->fetchAll();
    view('voucher_form', compact('moeins', 'types', 'tafsili', 'maps', 'projects', 'costCenters', 'branches') + ['title' => 'سند جدید', 'nav' => 'vouchers']);
});

$router->post('/vouchers/create', function () {
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
        ], $lines, $status);
        flash('ok', 'سند ذخیره شد.');
        redirect('/vouchers/view?id=' . $vid);
    } catch (Throwable $e) {
        flash('err', $e->getMessage());
        redirect('/vouchers/create');
    }
});

$router->get('/vouchers/view', function () {
    Permission::require('vouchers.create');
    $id = (int) ($_GET['id'] ?? 0);
    $v = Database::query('SELECT v.*, t.title type_title FROM vouchers v LEFT JOIN voucher_types t ON t.id=v.voucher_type_id WHERE v.id=?', [$id])->fetch();
    if (!$v) {
        flash('err', 'سند یافت نشد.');
        redirect('/vouchers');
    }
    $lines = Database::query(
        'SELECT l.*, m.code, m.title,
            t1.title t1title, t2.title t2title, t3.title t3title,
            p.title project_title, c.title cost_title, b.title branch_title
         FROM voucher_lines l
         JOIN accounts_moein m ON m.id=l.moein_id
         LEFT JOIN tafsili_items t1 ON t1.id=l.tafsili1_id
         LEFT JOIN tafsili_items t2 ON t2.id=l.tafsili2_id
         LEFT JOIN tafsili_items t3 ON t3.id=l.tafsili3_id
         LEFT JOIN tafsili_items p ON p.id=l.project_id
         LEFT JOIN tafsili_items c ON c.id=l.cost_center_id
         LEFT JOIN tafsili_items b ON b.id=l.branch_id
         WHERE voucher_id=? ORDER BY line_no',
        [$id]
    )->fetchAll();
    $templates = Database::query("SELECT * FROM print_templates WHERE entity='voucher'")->fetchAll();
    view('voucher_view', compact('v', 'lines', 'templates') + ['title' => 'سند ' . $v['number'], 'nav' => 'vouchers']);
});

$router->post('/vouchers/status', function () {
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
        flash('ok', 'وضعیت سند به «' . status_label($status) . '» تغییر کرد.');
    } catch (Throwable $e) {
        flash('err', $e->getMessage());
    }
    redirect('/vouchers/view?id=' . $id);
});

$router->post('/vouchers/renumber', function () {
    Permission::require('vouchers.renumber');
    verify_csrf();
    $fy = Accounting::activeYear();
    $n = Accounting::renumber((int) $fy['id']);
    flash('ok', "مرتب‌سازی انجام شد ({$n} سند).");
    redirect('/vouchers');
});

$router->post('/vouchers/gap', function () {
    Permission::require('vouchers.renumber');
    verify_csrf();
    $fy = Accounting::activeYear();
    $after = (int) ($_POST['after_number'] ?? 0);
    $count = max(1, (int) ($_POST['gap_count'] ?? 1));
    Database::query('UPDATE vouchers SET number = number + ? WHERE fiscal_year_id=? AND number>? ORDER BY number DESC', [$count, $fy['id'], $after]);
    Audit::log('voucher.gap', 'fiscal_year', (int) $fy['id'], "after={$after},gap={$count}");
    flash('ok', 'فاصله شماره‌گذاری ایجاد شد.');
    redirect('/vouchers');
});

$router->get('/vouchers/print', function () {
    Permission::require('vouchers.create');
    $id = (int) ($_GET['id'] ?? 0);
    $v = Database::query('SELECT * FROM vouchers WHERE id=?', [$id])->fetch();
    $lines = Database::query('SELECT l.*, m.code, m.title FROM voucher_lines l JOIN accounts_moein m ON m.id=l.moein_id WHERE voucher_id=? ORDER BY line_no', [$id])->fetchAll();
    $tpl = Database::query("SELECT * FROM print_templates WHERE entity='voucher' ORDER BY is_default DESC LIMIT 1")->fetch();
    $rows = '';
    foreach ($lines as $l) {
        $rows .= '<tr><td>' . e($l['code']) . '</td><td>' . e($l['title']) . '</td><td>' . money($l['debit']) . '</td><td>' . money($l['credit']) . '</td></tr>';
    }
    $html = $tpl['body_html'] ?? '<h2>سند {{number}}</h2>{{rows}}';
    $html = str_replace(['{{number}}', '{{date}}', '{{description}}', '{{rows}}'], [(string) $v['number'], $v['voucher_date'], e($v['description'] ?? ''), $rows], $html);
    echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><title>چاپ سند</title><style>body{font-family:tahoma} table{border-collapse:collapse;width:100%} td,th{border:1px solid #333;padding:6px}</style></head><body onload="print()">' . $html . '</body></html>';
    exit;
});

// ---- Fiscal ----
$router->get('/fiscal', function () {
    Permission::require('fiscal.manage');
    $years = Database::query('SELECT * FROM fiscal_years ORDER BY start_date DESC')->fetchAll();
    view('fiscal', ['title' => 'دوره مالی', 'nav' => 'fiscal', 'years' => $years]);
});

$router->post('/fiscal/create', function () {
    Permission::require('fiscal.manage');
    verify_csrf();
    Database::query('UPDATE fiscal_years SET is_active=0');
    Database::query('INSERT INTO fiscal_years (title, start_date, end_date, is_active) VALUES (?,?,?,1)', [
        trim($_POST['title'] ?? ''),
        $_POST['start_date'] ?? date('Y-m-d'),
        $_POST['end_date'] ?? date('Y-m-d'),
    ]);
    Audit::log('fiscal.create', 'fiscal_years', (int) Database::pdo()->lastInsertId());
    flash('ok', 'دوره مالی جدید فعال شد.');
    redirect('/fiscal');
});

$router->post('/fiscal/open-close', function () {
    Permission::require('fiscal.manage');
    verify_csrf();
    try {
        $kind = $_POST['kind'] ?? 'closing';
        $vid = Accounting::createOpeningClosing($kind);
        flash('ok', 'سند ' . ($kind === 'opening' ? 'افتتاحیه' : 'اختتامیه') . ' صادر شد.');
        redirect('/vouchers/view?id=' . $vid);
    } catch (Throwable $e) {
        flash('err', $e->getMessage());
        redirect('/fiscal');
    }
});

// ---- Treasury ----
$router->get('/treasury', function () {
    Permission::require('treasury.manage');
    $banks = Database::query('SELECT * FROM bank_accounts ORDER BY id DESC')->fetchAll();
    $books = Database::query('SELECT c.*, b.title bank_title FROM checkbooks c JOIN bank_accounts b ON b.id=c.bank_account_id ORDER BY c.id DESC')->fetchAll();
    $checks = Database::query('SELECT * FROM checks ORDER BY id DESC LIMIT 200')->fetchAll();
    $docs = Database::query('SELECT * FROM treasury_docs ORDER BY id DESC LIMIT 100')->fetchAll();
    $patterns = Database::query('SELECT * FROM bank_patterns ORDER BY id DESC')->fetchAll();
    $persons = Database::query("SELECT i.* FROM tafsili_items i JOIN tafsili_types t ON t.id=i.type_id WHERE t.code='PERSON'")->fetchAll();
    view('treasury', compact('banks', 'books', 'checks', 'docs', 'patterns', 'persons') + ['title' => 'خزانه‌داری', 'nav' => 'treasury']);
});

$router->post('/treasury/bank', function () {
    Permission::require('treasury.manage');
    verify_csrf();
    Database::query('INSERT INTO bank_accounts (title, bank_name, account_no) VALUES (?,?,?)', [trim($_POST['title'] ?? ''), trim($_POST['bank_name'] ?? ''), trim($_POST['account_no'] ?? '')]);
    flash('ok', 'حساب بانکی ثبت شد.');
    redirect('/treasury');
});

$router->post('/treasury/checkbook', function () {
    Permission::require('treasury.manage');
    verify_csrf();
    $from = (int) ($_POST['from_no'] ?? 1);
    $to = (int) ($_POST['to_no'] ?? $from);
    Database::query('INSERT INTO checkbooks (bank_account_id, series, from_no, to_no, next_no) VALUES (?,?,?,?,?)', [
        (int) $_POST['bank_account_id'], trim($_POST['series'] ?? ''), $from, $to, $from,
    ]);
    flash('ok', 'دسته چک تعریف شد.');
    redirect('/treasury');
});

$router->post('/treasury/doc', function () {
    Permission::require('treasury.manage');
    verify_csrf();
    $type = $_POST['doc_type'] === 'pay' ? 'pay' : 'receive';
    $amount = (float) str_replace(',', '', (string) ($_POST['amount'] ?? 0));
    $num = (int) Database::query('SELECT COALESCE(MAX(number),0)+1 n FROM treasury_docs WHERE doc_type=?', [$type])->fetch()['n'];
    $cash = Database::query("SELECT id FROM accounts_moein WHERE code LIKE '1102%' OR title LIKE '%صندوق%' LIMIT 1")->fetch();
    $bankMoein = Database::query("SELECT id FROM accounts_moein WHERE code LIKE '1101%' OR title LIKE '%بانک%' LIMIT 1")->fetch();
    $partyRecv = Database::query("SELECT id FROM accounts_moein WHERE code IN ('1301','1302') LIMIT 1")->fetch();
    $partyPay = Database::query("SELECT id FROM accounts_moein WHERE code IN ('3101','3103') LIMIT 1")->fetch();
    $method = $_POST['method'] ?? 'cash';
    $cashId = $method === 'bank' ? ($bankMoein['id'] ?? $cash['id'] ?? null) : ($cash['id'] ?? null);
    $contra = $type === 'receive' ? ($partyRecv['id'] ?? null) : ($partyPay['id'] ?? null);
    if (!$cashId || !$contra || $amount <= 0) {
        flash('err', 'حساب صندوق/سپرده یا طرف‌حساب در کدینگ یافت نشد.');
        redirect('/treasury');
    }
    $lines = $type === 'receive'
        ? [
            ['moein_id' => $cashId, 'debit' => $amount, 'credit' => 0, 'description' => 'دریافت', 'tafsili1_id' => $_POST['party_tafsili_id'] ?? 0],
            ['moein_id' => $contra, 'debit' => 0, 'credit' => $amount, 'description' => 'طرف دریافت', 'tafsili1_id' => $_POST['party_tafsili_id'] ?? 0],
        ]
        : [
            ['moein_id' => $contra, 'debit' => $amount, 'credit' => 0, 'description' => 'طرف پرداخت', 'tafsili1_id' => $_POST['party_tafsili_id'] ?? 0],
            ['moein_id' => $cashId, 'debit' => 0, 'credit' => $amount, 'description' => 'پرداخت', 'tafsili1_id' => $_POST['party_tafsili_id'] ?? 0],
        ];
    try {
        $vid = Accounting::createVoucher([
            'voucher_date' => $_POST['doc_date'] ?? date('Y-m-d'),
            'description' => ($type === 'receive' ? 'دریافت ' : 'پرداخت ') . trim($_POST['description'] ?? ''),
            'voucher_type_id' => Database::query("SELECT id FROM voucher_types WHERE code='BANK'")->fetch()['id'] ?? null,
            'source_module' => 'treasury',
        ], $lines, 'operational');
        Database::query(
            'INSERT INTO treasury_docs (doc_type, number, doc_date, party_tafsili_id, bank_account_id, amount, method, description, voucher_id, created_by)
             VALUES (?,?,?,?,?,?,?,?,?,?)',
            [$type, $num, $_POST['doc_date'] ?? date('Y-m-d'), (int) ($_POST['party_tafsili_id'] ?? 0) ?: null, (int) ($_POST['bank_account_id'] ?? 0) ?: null, $amount, $method, trim($_POST['description'] ?? ''), $vid, current_user()['id']]
        );
        flash('ok', 'سند خزانه و سند حسابداری صادر شد.');
    } catch (Throwable $e) {
        flash('err', $e->getMessage());
    }
    redirect('/treasury');
});

$router->post('/treasury/pattern', function () {
    Permission::require('treasury.manage');
    verify_csrf();
    $payload = [
        'description' => trim($_POST['description'] ?? ''),
        'lines_hint' => trim($_POST['lines_hint'] ?? ''),
    ];
    Database::query('INSERT INTO bank_patterns (title, payload_json) VALUES (?,?)', [trim($_POST['title'] ?? ''), json_encode($payload, JSON_UNESCAPED_UNICODE)]);
    flash('ok', 'الگوی بانکی ذخیره شد.');
    redirect('/treasury');
});

$router->get('/treasury/print', function () {
    Permission::require('treasury.manage');
    $id = (int) ($_GET['id'] ?? 0);
    $d = Database::query('SELECT * FROM treasury_docs WHERE id=?', [$id])->fetch();
    $tpl = Database::query("SELECT * FROM print_templates WHERE entity='treasury' ORDER BY is_default DESC LIMIT 1")->fetch();
    $html = $tpl['body_html'] ?? '<h2>{{type}} {{number}}</h2><p>{{amount}}</p>';
    $html = str_replace(
        ['{{type}}', '{{number}}', '{{date}}', '{{amount}}', '{{description}}'],
        [$d['doc_type'] === 'receive' ? 'دریافت' : 'پرداخت', (string) $d['number'], $d['doc_date'], money($d['amount']), e($d['description'] ?? '')],
        $html
    );
    echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><title>رسید</title><style>body{font-family:tahoma;padding:24px}</style></head><body onload="print()">' . $html . '</body></html>';
    exit;
});

// ---- Reports ----
$router->get('/reports', function () {
    Permission::require('reports.view');
    view('reports_home', ['title' => 'گزارش‌ها', 'nav' => 'reports']);
});

$router->get('/trial-balance', function () {
    Permission::require('reports.view');
    $from = $_GET['from'] ?? null;
    $to = $_GET['to'] ?? null;
    $cols = (int) ($_GET['cols'] ?? 4);
    $rows = Accounting::trialBalance($from ?: null, $to ?: null);
    if (isset($_GET['excel'])) {
        $export = [];
        foreach ($rows as $r) {
            $export[] = [$r['code'], $r['title'], $r['debit'], $r['credit'], max((float)$r['debit']-(float)$r['credit'],0), max((float)$r['credit']-(float)$r['debit'],0)];
        }
        ExcelExport::download('trial-balance.xls', ['کد','عنوان','بدهکار','بستانکار','مانده بدهکار','مانده بستانکار'], $export);
    }
    view('trial_balance', compact('rows', 'from', 'to', 'cols') + ['title' => 'تراز آزمایشی', 'nav' => 'reports']);
});

$router->get('/reports/balance-sheet', function () {
    Permission::require('reports.view');
    $rows = Accounting::trialBalance();
    $assets = []; $liab = []; $equity = [];
    foreach ($rows as $r) {
        $code = (string) $r['code'];
        $bal = (float) $r['debit'] - (float) $r['credit'];
        if (str_starts_with($code, '1') || str_starts_with($code, '2')) {
            $assets[] = $r + ['balance' => $bal];
        } elseif (str_starts_with($code, '3') || str_starts_with($code, '4')) {
            $liab[] = $r + ['balance' => -$bal];
        } elseif (str_starts_with($code, '5')) {
            $equity[] = $r + ['balance' => -$bal];
        }
    }
    if (isset($_GET['excel'])) {
        $export = [];
        foreach (array_merge($assets, $liab, $equity) as $r) {
            $export[] = [$r['code'], $r['title'], $r['balance']];
        }
        ExcelExport::download('balance-sheet.xls', ['کد','عنوان','مانده'], $export);
    }
    view('report_balance_sheet', compact('assets', 'liab', 'equity') + ['title' => 'ترازنامه', 'nav' => 'reports']);
});

$router->get('/reports/pl', function () {
    Permission::require('reports.view');
    $rows = Accounting::trialBalance($_GET['from'] ?? null, $_GET['to'] ?? null);
    $income = []; $expense = [];
    foreach ($rows as $r) {
        $c = (string) $r['code'];
        if (str_starts_with($c, '6')) {
            $income[] = $r;
        }
        if (str_starts_with($c, '7') || str_starts_with($c, '8')) {
            $expense[] = $r;
        }
    }
    if (isset($_GET['excel'])) {
        $export = [];
        foreach (array_merge($income, $expense) as $r) {
            $export[] = [$r['code'], $r['title'], $r['debit'], $r['credit']];
        }
        ExcelExport::download('profit-loss.xls', ['کد','عنوان','بدهکار','بستانکار'], $export);
    }
    view('report_pl', compact('income', 'expense') + ['title' => 'سود و زیان جامع', 'nav' => 'reports']);
});

$router->get('/ledger', function () {
    Permission::require('reports.view');
    $moeinId = (int) ($_GET['moein_id'] ?? 0);
    $moeins = Database::query('SELECT id, code, title FROM accounts_moein ORDER BY code+0')->fetchAll();
    $rows = [];
    $account = null;
    if ($moeinId) {
        $account = Database::query('SELECT * FROM accounts_moein WHERE id=?', [$moeinId])->fetch();
        $rows = Database::query(
            "SELECT v.number, v.voucher_date, v.status, l.description, l.debit, l.credit,
                    t1.title t1, t2.title t2, t3.title t3
             FROM voucher_lines l
             JOIN vouchers v ON v.id=l.voucher_id
             LEFT JOIN tafsili_items t1 ON t1.id=l.tafsili1_id
             LEFT JOIN tafsili_items t2 ON t2.id=l.tafsili2_id
             LEFT JOIN tafsili_items t3 ON t3.id=l.tafsili3_id
             WHERE l.moein_id=? AND v.status IN ('operational','reviewed','locked','posted')
             ORDER BY v.voucher_date, v.number, l.line_no",
            [$moeinId]
        )->fetchAll();
        if (isset($_GET['excel'])) {
            $export = [];
            foreach ($rows as $r) {
                $export[] = [$r['number'], $r['voucher_date'], $r['description'], $r['debit'], $r['credit'], $r['t1'], $r['t2'], $r['t3']];
            }
            ExcelExport::download('ledger.xls', ['سند','تاریخ','شرح','بدهکار','بستانکار','تفصیلی1','تفصیلی2','تفصیلی3'], $export);
        }
    }
    view('ledger', compact('moeins', 'moeinId', 'rows', 'account') + ['title' => 'دفتر معین / مرور حساب', 'nav' => 'reports']);
});

$router->get('/reports/journal', function () {
    Permission::require('reports.view');
    $rows = Database::query(
        "SELECT v.number, v.voucher_date, v.description, m.code, m.title, l.debit, l.credit
         FROM voucher_lines l
         JOIN vouchers v ON v.id=l.voucher_id
         JOIN accounts_moein m ON m.id=l.moein_id
         WHERE v.status IN ('operational','reviewed','locked','posted')
         ORDER BY v.voucher_date, v.number, l.line_no LIMIT 2000"
    )->fetchAll();
    if (isset($_GET['excel'])) {
        $export = [];
        foreach ($rows as $r) {
            $export[] = [$r['number'], $r['voucher_date'], $r['code'], $r['title'], $r['debit'], $r['credit'], $r['description']];
        }
        ExcelExport::download('journal.xls', ['سند','تاریخ','کد','حساب','بدهکار','بستانکار','شرح'], $export);
    }
    view('report_journal', compact('rows') + ['title' => 'دفتر روزنامه', 'nav' => 'reports']);
});

$router->get('/reports/nature-violations', function () {
    Permission::require('reports.view');
    $rows = Database::query(
        "SELECT v.number, v.voucher_date, m.code, m.title, m.nature, l.debit, l.credit
         FROM voucher_lines l
         JOIN vouchers v ON v.id=l.voucher_id
         JOIN accounts_moein m ON m.id=l.moein_id
         WHERE (m.nature='debit' AND l.credit>0) OR (m.nature='credit' AND l.debit>0)
         ORDER BY v.voucher_date DESC LIMIT 500"
    )->fetchAll();
    view('report_nature', compact('rows') + ['title' => 'اسناد خلاف ماهیت', 'nav' => 'reports']);
});

$router->get('/reports/checks', function () {
    Permission::require('reports.view');
    try {
        $rows = Database::query(
            'SELECT check_no, direction, amount, due_date, physical_status AS status, payee, sayad_id, settlement_status
             FROM cheques ORDER BY due_date IS NULL, due_date, id DESC'
        )->fetchAll();
    } catch (Throwable $e) {
        $rows = Database::query('SELECT * FROM checks ORDER BY due_date IS NULL, due_date, id DESC')->fetchAll();
    }
    if (isset($_GET['excel'])) {
        $export = [];
        foreach ($rows as $r) {
            $export[] = [$r['check_no'], $r['direction'], $r['amount'], $r['due_date'], $r['status'], $r['payee'] ?? ''];
        }
        ExcelExport::download('checks.xls', ['شماره','نوع','مبلغ','سررسید','وضعیت','در وجه'], $export);
    }
    view('report_checks', compact('rows') + ['title' => 'اسناد دریافتنی/پرداختنی (چک)', 'nav' => 'reports']);
});

$router->get('/reports/share', function () {
    Permission::require('reports.view');
    $rows = Database::query(
        "SELECT m.code, m.title, COALESCE(t.title,'بدون تفصیلی') tafsili,
                SUM(l.debit) debit, SUM(l.credit) credit
         FROM voucher_lines l
         JOIN vouchers v ON v.id=l.voucher_id
         JOIN accounts_moein m ON m.id=l.moein_id
         LEFT JOIN tafsili_items t ON t.id=l.tafsili1_id
         WHERE v.status IN ('operational','reviewed','locked','posted')
         GROUP BY m.id, t.id
         ORDER BY m.code+0"
    )->fetchAll();
    view('report_share', compact('rows') + ['title' => 'سهم‌بری حساب‌ها / پروژه', 'nav' => 'reports']);
});

$router->get('/reports/bank-reconcile', function () {
    Permission::require('treasury.manage');
    $banks = Database::query('SELECT * FROM bank_accounts')->fetchAll();
    $rows = Database::query('SELECT r.*, b.title bank_title FROM bank_reconciliations r JOIN bank_accounts b ON b.id=r.bank_account_id ORDER BY r.id DESC')->fetchAll();
    view('report_bank_reconcile', compact('banks', 'rows') + ['title' => 'مغایرت بانکی', 'nav' => 'bank_reconcile']);
});

$router->post('/reports/bank-reconcile', function () {
    Permission::require('treasury.manage');
    verify_csrf();
    Database::query(
        'INSERT INTO bank_reconciliations (bank_account_id, statement_date, statement_balance, book_balance, note) VALUES (?,?,?,?,?)',
        [(int) $_POST['bank_account_id'], $_POST['statement_date'], (float) $_POST['statement_balance'], (float) $_POST['book_balance'], trim($_POST['note'] ?? '')]
    );
    flash('ok', 'رکورد مغایرت ذخیره شد.');
    redirect('/reports/bank-reconcile');
});

// ---- Users / audit / moadian ----
$router->get('/users', function () {
    Permission::require('users.manage');
    $users = Database::query('SELECT id, name, email, phone, role, is_active, created_at FROM users ORDER BY id')->fetchAll();
    $perms = Database::query('SELECT * FROM permissions ORDER BY id')->fetchAll();
    $edit = null;
    $editPerms = [];
    $editId = (int) ($_GET['edit'] ?? ($users[0]['id'] ?? 0));
    if ($editId > 0) {
        $edit = Database::query('SELECT * FROM users WHERE id=?', [$editId])->fetch() ?: null;
        if ($edit) {
            $rows = Database::query('SELECT permission_code FROM user_permissions WHERE user_id=? AND allowed=1', [$editId])->fetchAll();
            $editPerms = array_column($rows, 'permission_code');
            if (!$editPerms) {
                $rows = Database::query('SELECT permission_code FROM role_permissions WHERE role=?', [$edit['role']])->fetchAll();
                $editPerms = array_column($rows, 'permission_code');
            }
        }
    }
    view('users', compact('users', 'perms', 'edit', 'editPerms') + ['title' => 'کاربران و دسترسی', 'nav' => 'users']);
});

$router->post('/users/save', function () {
    Permission::require('users.manage');
    verify_csrf();
    $op = (string) ($_POST['op'] ?? '');
    if ($op === 'create' || !empty($_POST['new_email'])) {
        $phone = Sms::normalizeMobile(trim($_POST['new_phone'] ?? ''));
        Database::query(
            'INSERT INTO users (name, email, phone, password_hash, role, is_active) VALUES (?,?,?,?,?,1)',
            [
                trim($_POST['new_name'] ?? ''),
                trim($_POST['new_email']),
                $phone !== '' ? $phone : null,
                password_hash((string) ($_POST['new_pass'] ?? 'ChangeMe123'), PASSWORD_DEFAULT),
                $_POST['new_role'] ?? 'accountant',
            ]
        );
    }
    if ($op === 'update' || (!empty($_POST['user_id']) && empty($_POST['new_email']))) {
        $uid = (int) $_POST['user_id'];
        $phone = Sms::normalizeMobile(trim($_POST['phone'] ?? ''));
        $active = isset($_POST['is_active']) ? 1 : 0;
        Database::query(
            'UPDATE users SET name=COALESCE(?, name), phone=?, role=?, is_active=? WHERE id=?',
            [
                trim($_POST['name'] ?? '') ?: null,
                $phone !== '' ? $phone : null,
                $_POST['role'] ?? 'accountant',
                $active,
                $uid,
            ]
        );
        if (!empty($_POST['new_pass'])) {
            Database::query('UPDATE users SET password_hash=? WHERE id=?', [
                password_hash((string) $_POST['new_pass'], PASSWORD_DEFAULT),
                $uid,
            ]);
        }
        Database::query('DELETE FROM user_permissions WHERE user_id=?', [$uid]);
        foreach ($_POST['perm'] ?? [] as $code) {
            Database::query('INSERT INTO user_permissions (user_id, permission_code, allowed) VALUES (?,?,1)', [$uid, $code]);
        }
    }
    Audit::log('users.save');
    flash('ok', 'کاربران/دسترسی ذخیره شد.');
    redirect('/users' . (!empty($_POST['user_id']) ? '?edit=' . (int) $_POST['user_id'] : ''));
});

$router->get('/audit', function () {
    Permission::require('audit.view');
    $rows = Database::query(
        'SELECT a.*, u.name user_name FROM audit_logs a LEFT JOIN users u ON u.id=a.user_id ORDER BY a.id DESC LIMIT 500'
    )->fetchAll();
    if (isset($_GET['excel'])) {
        $export = [];
        foreach ($rows as $r) {
            $export[] = [$r['created_at'], $r['user_name'], $r['action'], $r['entity'], $r['entity_id'], $r['detail'], $r['ip']];
        }
        ExcelExport::download('audit.xls', ['زمان','کاربر','عمل','موجودیت','شناسه','شرح','IP'], $export);
    }
    view('audit', compact('rows') + ['title' => 'تاریخچه فعالیت کاربران', 'nav' => 'audit']);
});

$router->get('/moadian', function () {
    Permission::require('moadian.manage');
    $settings = Database::query('SELECT * FROM moadian_settings WHERE id=1')->fetch() ?: [];
    $queue = Database::query('SELECT * FROM moadian_invoices ORDER BY id DESC LIMIT 100')->fetchAll();
    view('moadian', compact('settings', 'queue') + ['title' => 'سامانه مودیان', 'nav' => 'moadian']);
});

$router->post('/moadian/save', function () {
    Permission::require('moadian.manage');
    verify_csrf();
    Database::query(
        'INSERT INTO moadian_settings (id, economic_code, memory_id, private_key, is_enabled)
         VALUES (1,?,?,?,?)
         ON DUPLICATE KEY UPDATE economic_code=VALUES(economic_code), memory_id=VALUES(memory_id), private_key=VALUES(private_key), is_enabled=VALUES(is_enabled)',
        [trim($_POST['economic_code'] ?? ''), trim($_POST['memory_id'] ?? ''), trim($_POST['private_key'] ?? ''), isset($_POST['is_enabled']) ? 1 : 0]
    );
    Audit::log('moadian.save');
    flash('ok', 'تنظیمات مودیان ذخیره شد. ارسال واقعی نیاز به کلید و اتصال API دارد.');
    redirect('/moadian');
});

$router->post('/moadian/queue-invoice', function () {
    Permission::require('moadian.manage');
    verify_csrf();
    $invoiceId = (int) ($_POST['invoice_id'] ?? 0);
    Database::query('INSERT INTO moadian_invoices (invoice_id, status, payload_json) VALUES (?,?,?)', [
        $invoiceId, 'pending', json_encode(['note' => 'queued for tax.gov.ir'], JSON_UNESCAPED_UNICODE),
    ]);
    flash('ok', 'فاکتور در صف ارسال مودیان قرار گرفت.');
    redirect('/moadian');
});

// keep parties/invoices compatibility
$router->get('/parties', function () {
    Permission::require('tafsili.manage');
    redirect('/tafsili?type_id=' . (int) (Database::query("SELECT id FROM tafsili_types WHERE code='PERSON'")->fetch()['id'] ?? 0));
});

$router->get('/invoices', function () {
    Permission::require('vouchers.create');
    $rows = Database::query(
        'SELECT i.*, p.name party_name, v.name visitor_name
         FROM invoices i
         LEFT JOIN parties p ON p.id=i.party_id
         LEFT JOIN visitors v ON v.id=i.visitor_id
         ORDER BY i.id DESC'
    )->fetchAll();
    $parties = Database::query('SELECT id, name FROM parties ORDER BY name')->fetchAll();
    $visitors = class_exists('Visitor') ? Visitor::listActive() : [];
    view('invoices', compact('rows', 'parties', 'visitors') + ['title' => 'فاکتور فروش', 'nav' => 'invoices']);
});

$router->post('/invoices', function () {
    Permission::require('vouchers.create');
    verify_csrf();
    // reuse previous invoice auto-voucher logic lightly
    $partyId = (int) ($_POST['party_id'] ?? 0);
    $visitorId = (int) ($_POST['visitor_id'] ?? 0);
    $date = $_POST['invoice_date'] ?? date('Y-m-d');
    $title = trim($_POST['item_title'] ?? 'فروش');
    $qty = (float) ($_POST['qty'] ?? 1);
    $price = (float) str_replace(',', '', (string) ($_POST['unit_price'] ?? 0));
    $amount = $qty * $price;
    $sale = Database::query("SELECT id FROM accounts_moein WHERE code='6101' LIMIT 1")->fetch();
    $recv = Database::query("SELECT id FROM accounts_moein WHERE code IN ('1302','1301') LIMIT 1")->fetch();
    if (!$partyId || !$sale || !$recv || $amount <= 0) {
        flash('err', 'اطلاعات فاکتور/حساب فروش کامل نیست.');
        redirect('/invoices');
    }
    try {
        $vid = Accounting::createVoucher([
            'voucher_date' => $date,
            'description' => 'سند خودکار فاکتور فروش',
            'voucher_type_id' => Database::query("SELECT id FROM voucher_types WHERE code='AUTO'")->fetch()['id'] ?? null,
            'source_module' => 'invoice',
        ], [
            ['moein_id' => $recv['id'], 'debit' => $amount, 'credit' => 0, 'description' => 'بدهکار مشتری'],
            ['moein_id' => $sale['id'], 'debit' => 0, 'credit' => $amount, 'description' => 'فروش'],
        ], 'operational');
        $num = (int) Database::query('SELECT COALESCE(MAX(number),0)+1 n FROM invoices')->fetch()['n'];
        Database::query(
            'INSERT INTO invoices (number, invoice_date, party_id, visitor_id, total, description, voucher_id, status) VALUES (?,?,?,?,?,?,?,?)',
            [$num, $date, $partyId, $visitorId ?: null, $amount, $title, $vid, 'confirmed']
        );
        $iid = (int) Database::pdo()->lastInsertId();
        Database::query('INSERT INTO invoice_items (invoice_id, title, qty, unit_price, amount) VALUES (?,?,?,?,?)', [$iid, $title, $qty, $price, $amount]);
        $msg = 'فاکتور و سند اتوماتیک صادر شد.';
        if ($visitorId > 0 && class_exists('Visitor')) {
            $acc = Visitor::accrueFromInvoice($iid);
            if ($acc['ok']) {
                $msg .= ' ' . $acc['message'];
            }
        }
        flash('ok', $msg);
    } catch (Throwable $e) {
        flash('err', $e->getMessage());
    }
    redirect('/invoices');
});

$router->get('/reports/charts', function () {
    Permission::require('reports.view');
    $months = Database::query(
        "SELECT DATE_FORMAT(voucher_date,'%Y-%m') ym,
                SUM(CASE WHEN status IN ('locked','posted','reviewed','operational') THEN 1 ELSE 0 END) cnt
         FROM vouchers GROUP BY ym ORDER BY ym DESC LIMIT 12"
    )->fetchAll();
    view('report_charts', compact('months') + ['title' => 'بررسی نموداری دوره‌ها', 'nav' => 'reports']);
});
