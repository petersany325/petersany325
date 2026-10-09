<?php
declare(strict_types=1);

/** @var Router $router */

$router->get('/cheques', function () {
    Permission::require('cheques.manage');
    $dir = ($_GET['dir'] ?? 'receivable') === 'payable' ? 'payable' : 'receivable';
    $status = trim((string) ($_GET['status'] ?? ''));
    $params = [$dir];
    $sql = 'SELECT c.*, t.title party_name FROM cheques c
            LEFT JOIN tafsili_items t ON t.id=c.party_tafsili_id
            WHERE c.direction=?';
    if ($status !== '') {
        $sql .= ' AND c.physical_status=?';
        $params[] = $status;
    }
    $sql .= ' ORDER BY c.due_date IS NULL, c.due_date, c.id DESC LIMIT 400';
    $rows = Database::query($sql, $params)->fetchAll();
    $stats = ChequeEngine::dashboardStats();
    view('cheques', compact('rows', 'dir', 'status', 'stats') + [
        'title' => 'مدیریت چک‌ها',
        'nav' => 'cheques',
    ]);
});

$router->get('/cheques/receive', function () {
    Permission::require('cheques.manage');
    $persons = Database::query(
        "SELECT i.id, i.code, i.title FROM tafsili_items i
         JOIN tafsili_types t ON t.id=i.type_id WHERE t.code IN ('PERSON','01') AND i.is_active=1
         ORDER BY i.title LIMIT 500"
    )->fetchAll();
    $banks = Database::query('SELECT * FROM bank_accounts WHERE is_active=1 ORDER BY title')->fetchAll();
    view('cheque_receive', compact('persons', 'banks') + ['title' => 'دریافت چک', 'nav' => 'cheques']);
});

$router->post('/cheques/receive', function () {
    Permission::require('cheques.manage');
    verify_csrf();
    $res = ChequeEngine::receive($_POST);
    flash($res['ok'] ? 'ok' : 'err', $res['message'] . (!empty($res['voucher_id']) ? ' — سند #' . $res['voucher_id'] : ''));
    if ($res['ok']) {
        Audit::log('cheque.receive', 'cheque', (int) $res['id']);
        redirect('/cheques/view?id=' . (int) $res['id']);
    }
    redirect('/cheques/receive');
});

$router->get('/cheques/pay', function () {
    Permission::require('cheques.manage');
    $persons = Database::query(
        "SELECT i.id, i.code, i.title FROM tafsili_items i
         JOIN tafsili_types t ON t.id=i.type_id WHERE t.code IN ('PERSON','01') AND i.is_active=1
         ORDER BY i.title LIMIT 500"
    )->fetchAll();
    $banks = Database::query('SELECT * FROM bank_accounts WHERE is_active=1 ORDER BY title')->fetchAll();
    $books = Database::query(
        'SELECT c.*, b.title bank_title FROM checkbooks c
         JOIN bank_accounts b ON b.id=c.bank_account_id WHERE c.is_active=1 ORDER BY c.id DESC'
    )->fetchAll();
    view('cheque_pay', compact('persons', 'banks', 'books') + ['title' => 'صدور چک پرداختی', 'nav' => 'cheques']);
});

$router->post('/cheques/pay', function () {
    Permission::require('cheques.manage');
    verify_csrf();
    $res = ChequeEngine::issuePayable($_POST);
    flash($res['ok'] ? 'ok' : 'err', $res['message'] . (!empty($res['voucher_id']) ? ' — سند #' . $res['voucher_id'] : ''));
    if ($res['ok']) {
        Audit::log('cheque.issue', 'cheque', (int) $res['id']);
        redirect('/cheques/view?id=' . (int) $res['id']);
    }
    redirect('/cheques/pay');
});

$router->get('/cheques/view', function () {
    Permission::require('cheques.manage');
    $id = (int) ($_GET['id'] ?? 0);
    $cheque = ChequeEngine::find($id);
    if (!$cheque) {
        flash('err', 'چک یافت نشد');
        redirect('/cheques');
    }
    $events = ChequeEngine::events($id);
    $party = null;
    if (!empty($cheque['party_tafsili_id'])) {
        $party = Database::query('SELECT * FROM tafsili_items WHERE id=?', [(int) $cheque['party_tafsili_id']])->fetch() ?: null;
    }
    $banks = Database::query('SELECT * FROM bank_accounts WHERE is_active=1 ORDER BY title')->fetchAll();
    $persons = Database::query(
        "SELECT i.id, i.code, i.title FROM tafsili_items i
         JOIN tafsili_types t ON t.id=i.type_id WHERE t.code IN ('PERSON','01') AND i.is_active=1
         ORDER BY i.title LIMIT 500"
    )->fetchAll();
    view('cheque_view', compact('cheque', 'events', 'party', 'banks', 'persons') + [
        'title' => 'چک ' . $cheque['check_no'],
        'nav' => 'cheques',
    ]);
});

$router->post('/cheques/action', function () {
    Permission::require('cheques.manage');
    verify_csrf();
    $id = (int) ($_POST['id'] ?? 0);
    $action = trim((string) ($_POST['action'] ?? ''));
    $res = ChequeEngine::apply($id, $action, $_POST);
    flash($res['ok'] ? 'ok' : 'err', $res['message'] . (!empty($res['voucher_id']) ? ' — سند #' . $res['voucher_id'] : ''));
    Audit::log('cheque.' . $action, 'cheque', $id, $res['message']);
    redirect('/cheques/view?id=' . $id);
});
