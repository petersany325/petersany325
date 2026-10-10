<?php
declare(strict_types=1);

/** @var Router $router */

$router->get('/visitors', function () {
    Permission::require('visitors.manage');
    $rows = Database::query(
        'SELECT v.*,
            (SELECT COUNT(*) FROM visitor_customers vc WHERE vc.visitor_id=v.id) customers_count,
            (SELECT COUNT(*) FROM visitor_visits vv WHERE vv.visitor_id=v.id AND vv.status="done") visits_done
         FROM visitors v ORDER BY v.id DESC'
    )->fetchAll();
    $edit = null;
    $editId = (int) ($_GET['edit'] ?? 0);
    if ($editId > 0) {
        $edit = Visitor::find($editId);
    }
    $customers = [];
    $assigned = [];
    if ($edit) {
        $customers = Database::query(
            "SELECT id, code, name FROM parties WHERE type IN ('customer','both') ORDER BY name"
        )->fetchAll();
        $assigned = array_column(
            Database::query('SELECT party_id FROM visitor_customers WHERE visitor_id=?', [$editId])->fetchAll(),
            'party_id'
        );
        $assigned = array_map('intval', $assigned);
    }
    view('visitors', compact('rows', 'edit', 'customers', 'assigned') + [
        'title' => 'تعریف ویزیتور',
        'nav' => 'visitors',
    ]);
});

$router->post('/visitors/save', function () {
    Permission::require('visitors.manage');
    verify_csrf();
    $op = (string) ($_POST['op'] ?? 'save');
    $id = (int) ($_POST['id'] ?? 0);

    if ($op === 'assign' && $id > 0) {
        Database::query('DELETE FROM visitor_customers WHERE visitor_id=?', [$id]);
        foreach ($_POST['party_ids'] ?? [] as $pid) {
            $pid = (int) $pid;
            if ($pid > 0) {
                Database::query(
                    'INSERT IGNORE INTO visitor_customers (visitor_id, party_id) VALUES (?,?)',
                    [$id, $pid]
                );
            }
        }
        Audit::log('visitors.assign', 'visitor', $id);
        flash('ok', 'مشتریان ویزیتور ذخیره شد.');
        redirect('/visitors?edit=' . $id);
    }

    $code = trim($_POST['code'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $phone = Sms::normalizeMobile(trim($_POST['phone'] ?? ''));
    $region = trim($_POST['region'] ?? '');
    $pct = (float) str_replace(',', '.', (string) ($_POST['commission_percent'] ?? 0));
    $notes = trim($_POST['notes'] ?? '');
    $active = isset($_POST['is_active']) ? 1 : 0;
    if ($code === '' || $name === '') {
        flash('err', 'کد و نام ویزیتور الزامی است.');
        redirect('/visitors' . ($id ? '?edit=' . $id : ''));
    }
    $pct = max(0, min(100, $pct));

    if ($id > 0) {
        Database::query(
            'UPDATE visitors SET code=?, name=?, phone=?, region=?, commission_percent=?, notes=?, is_active=? WHERE id=?',
            [$code, $name, $phone !== '' ? $phone : null, $region !== '' ? $region : null, $pct, $notes !== '' ? $notes : null, $active, $id]
        );
        Audit::log('visitors.update', 'visitor', $id);
        flash('ok', 'ویزیتور به‌روز شد.');
        redirect('/visitors?edit=' . $id);
    }

    Database::query(
        'INSERT INTO visitors (code, name, phone, region, commission_percent, notes, is_active) VALUES (?,?,?,?,?,?,1)',
        [$code, $name, $phone !== '' ? $phone : null, $region !== '' ? $region : null, $pct, $notes !== '' ? $notes : null]
    );
    $newId = (int) Database::pdo()->lastInsertId();
    Visitor::cartablePush([
        'visitor_id' => $newId,
        'title' => 'ویزیتور جدید: ' . $name,
        'body' => 'درصد پورسانت پیش‌فرض: ' . $pct . '٪ — منطقه: ' . ($region ?: '—'),
        'kind' => 'task',
    ]);
    Audit::log('visitors.create', 'visitor', $newId);
    flash('ok', 'ویزیتور ثبت شد.');
    redirect('/visitors?edit=' . $newId);
});

$router->get('/visitors/cartable', function () {
    Permission::require('visitors.cartable');
    $status = $_GET['status'] ?? 'open';
    $params = [];
    $sql = 'SELECT c.*, v.name visitor_name FROM visitor_cartable c
            LEFT JOIN visitors v ON v.id=c.visitor_id';
    if ($status !== 'all') {
        $sql .= ' WHERE c.status=?';
        $params[] = $status;
    }
    $sql .= ' ORDER BY c.id DESC LIMIT 300';
    $rows = Database::query($sql, $params)->fetchAll();
    $visitors = Visitor::listActive();
    $openCount = (int) Database::query("SELECT COUNT(*) c FROM visitor_cartable WHERE status IN ('open','in_progress')")->fetch()['c'];
    view('visitor_cartable', compact('rows', 'visitors', 'status', 'openCount') + [
        'title' => 'کارتابل ویزیتور',
        'nav' => 'visitor_cartable',
    ]);
});

$router->post('/visitors/cartable', function () {
    Permission::require('visitors.cartable');
    verify_csrf();
    $op = (string) ($_POST['op'] ?? 'create');

    if ($op === 'status') {
        $id = (int) ($_POST['id'] ?? 0);
        $st = (string) ($_POST['status'] ?? 'done');
        if (!in_array($st, ['open', 'in_progress', 'done', 'rejected'], true)) {
            $st = 'done';
        }
        Database::query(
            'UPDATE visitor_cartable SET status=?, handled_by=?, handled_at=NOW() WHERE id=?',
            [$st, current_user()['id'] ?? null, $id]
        );
        $row = Database::query('SELECT * FROM visitor_cartable WHERE id=?', [$id])->fetch();
        if ($row && !empty($row['visitor_id']) && in_array($st, ['done', 'rejected'], true)) {
            $v = Visitor::find((int) $row['visitor_id']);
            if ($v) {
                Visitor::smsNotify($v, 'sms_visitor_task', [
                    'title' => ($st === 'done' ? 'انجام شد: ' : 'رد شد: ') . $row['title'],
                ]);
            }
        }
        flash('ok', 'وضعیت کارتابل به‌روز شد.');
        redirect('/visitors/cartable?status=' . urlencode((string) ($_POST['back'] ?? 'open')));
    }

    $visitorId = (int) ($_POST['visitor_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $body = trim($_POST['body'] ?? '');
    $kind = (string) ($_POST['kind'] ?? 'task');
    $due = trim($_POST['due_date'] ?? '');
    $sendSms = isset($_POST['send_sms']);
    if ($title === '') {
        flash('err', 'عنوان کارتابل الزامی است.');
        redirect('/visitors/cartable');
    }
    $cid = Visitor::cartablePush([
        'visitor_id' => $visitorId ?: null,
        'title' => $title,
        'body' => $body,
        'kind' => in_array($kind, ['task', 'visit', 'commission', 'sms', 'other'], true) ? $kind : 'task',
        'due_date' => $due !== '' ? $due : null,
    ]);
    if ($sendSms && $visitorId > 0) {
        $v = Visitor::find($visitorId);
        if ($v) {
            $res = Visitor::smsNotify($v, 'sms_visitor_task', ['title' => $title]);
            flash($res['ok'] ? 'ok' : 'err', $res['ok'] ? 'کارتابل ثبت و پیامک ارسال شد.' : ('کارتابل ثبت شد؛ SMS: ' . $res['message']));
            Audit::log('visitors.cartable.sms', 'cartable', $cid, $res['message'] ?? '');
            redirect('/visitors/cartable');
        }
    }
    Audit::log('visitors.cartable.create', 'cartable', $cid);
    flash('ok', 'آیتم کارتابل ثبت شد.');
    redirect('/visitors/cartable');
});

$router->get('/visitors/visits', function () {
    Permission::require('visitors.manage');
    $visitorFilter = (int) ($_GET['visitor_id'] ?? 0);
    $sql = 'SELECT vv.*, v.name visitor_name, p.name party_name
            FROM visitor_visits vv
            LEFT JOIN visitors v ON v.id=vv.visitor_id
            LEFT JOIN parties p ON p.id=vv.party_id';
    $params = [];
    if ($visitorFilter > 0) {
        $sql .= ' WHERE vv.visitor_id=?';
        $params[] = $visitorFilter;
    }
    $sql .= ' ORDER BY vv.visit_date DESC, vv.id DESC LIMIT 300';
    $rows = Database::query($sql, $params)->fetchAll();
    $visitors = Visitor::listActive();
    $parties = Database::query(
        "SELECT id, name FROM parties WHERE type IN ('customer','both') ORDER BY name"
    )->fetchAll();
    view('visitor_visits', compact('rows', 'visitors', 'parties', 'visitorFilter') + [
        'title' => 'بازدیدهای ویزیتور',
        'nav' => 'visitor_visits',
    ]);
});

$router->post('/visitors/visits', function () {
    Permission::require('visitors.manage');
    verify_csrf();
    $visitorId = (int) ($_POST['visitor_id'] ?? 0);
    $partyId = (int) ($_POST['party_id'] ?? 0);
    $date = trim($_POST['visit_date'] ?? date('Y-m-d'));
    $time = trim($_POST['visit_time'] ?? '');
    $status = (string) ($_POST['status'] ?? 'planned');
    $note = trim($_POST['result_note'] ?? '');
    $follow = trim($_POST['next_followup'] ?? '');
    $sendSms = isset($_POST['send_sms']);
    if ($visitorId <= 0 || $date === '') {
        flash('err', 'ویزیتور و تاریخ الزامی است.');
        redirect('/visitors/visits');
    }
    if (!in_array($status, ['planned', 'done', 'cancelled', 'no_sale'], true)) {
        $status = 'planned';
    }
    Database::query(
        'INSERT INTO visitor_visits
          (visitor_id, party_id, visit_date, visit_time, status, result_note, next_followup, created_by)
         VALUES (?,?,?,?,?,?,?,?)',
        [
            $visitorId,
            $partyId ?: null,
            $date,
            $time !== '' ? $time : null,
            $status,
            $note !== '' ? $note : null,
            $follow !== '' ? $follow : null,
            current_user()['id'] ?? null,
        ]
    );
    $visitId = (int) Database::pdo()->lastInsertId();
    $partyName = '';
    if ($partyId) {
        $partyName = (string) (Database::query('SELECT name FROM parties WHERE id=?', [$partyId])->fetch()['name'] ?? '');
    }
    Visitor::cartablePush([
        'visitor_id' => $visitorId,
        'title' => 'بازدید ' . $date . ($partyName ? ' — ' . $partyName : ''),
        'body' => $note ?: ('وضعیت: ' . $status),
        'kind' => 'visit',
        'ref_type' => 'visit',
        'ref_id' => $visitId,
        'due_date' => $follow !== '' ? $follow : $date,
        'status' => $status === 'done' ? 'done' : 'open',
    ]);
    if ($sendSms) {
        $v = Visitor::find($visitorId);
        if ($v) {
            Visitor::smsNotify($v, 'sms_visitor_visit', [
                'date' => $date,
                'customer' => $partyName ?: '—',
                'title' => 'ثبت بازدید',
            ]);
        }
    }
    Audit::log('visitors.visit', 'visit', $visitId);
    flash('ok', 'بازدید ثبت شد.');
    redirect('/visitors/visits');
});

$router->get('/visitors/commissions', function () {
    Permission::require('visitors.commission');
    $rows = Database::query(
        'SELECT c.*, v.name visitor_name, i.number invoice_number, i.invoice_date
         FROM visitor_commissions c
         LEFT JOIN visitors v ON v.id=c.visitor_id
         LEFT JOIN invoices i ON i.id=c.invoice_id
         ORDER BY c.id DESC LIMIT 400'
    )->fetchAll();
    $sum = Database::query(
        "SELECT
            COALESCE(SUM(CASE WHEN status='accrued' THEN commission_amount ELSE 0 END),0) accrued,
            COALESCE(SUM(CASE WHEN status='approved' THEN commission_amount ELSE 0 END),0) approved,
            COALESCE(SUM(CASE WHEN status='paid' THEN commission_amount ELSE 0 END),0) paid
         FROM visitor_commissions"
    )->fetch() ?: ['accrued' => 0, 'approved' => 0, 'paid' => 0];
    view('visitor_commissions', compact('rows', 'sum') + [
        'title' => 'پورسانت ویزیتور',
        'nav' => 'visitor_commissions',
    ]);
});

$router->post('/visitors/commissions', function () {
    Permission::require('visitors.commission');
    verify_csrf();
    $id = (int) ($_POST['id'] ?? 0);
    $st = (string) ($_POST['status'] ?? '');
    if (!in_array($st, ['approved', 'paid', 'void', 'accrued'], true) || $id <= 0) {
        flash('err', 'درخواست نامعتبر');
        redirect('/visitors/commissions');
    }
    $paidAt = $st === 'paid' ? date('Y-m-d H:i:s') : null;
    Database::query(
        'UPDATE visitor_commissions SET status=?, paid_at=COALESCE(?, paid_at) WHERE id=?',
        [$st, $paidAt, $id]
    );
    $row = Database::query('SELECT * FROM visitor_commissions WHERE id=?', [$id])->fetch();
    if ($row && $st === 'paid') {
        Visitor::cartablePush([
            'visitor_id' => (int) $row['visitor_id'],
            'title' => 'تسویه پورسانت #' . $id,
            'body' => 'مبلغ ' . number_format((float) $row['commission_amount']) . ' ریال پرداخت شد.',
            'kind' => 'commission',
            'ref_type' => 'commission',
            'ref_id' => $id,
            'status' => 'done',
        ]);
        $v = Visitor::find((int) $row['visitor_id']);
        if ($v) {
            Visitor::smsNotify($v, 'sms_visitor_commission', [
                'invoice' => (string) $id,
                'amount' => number_format((float) $row['commission_amount']),
                'title' => 'تسویه پورسانت',
            ]);
        }
    }
    Audit::log('visitors.commission.' . $st, 'commission', $id);
    flash('ok', 'وضعیت پورسانت به‌روز شد.');
    redirect('/visitors/commissions');
});

$router->get('/visitors/reports', function () {
    Permission::require('visitors.reports');
    $from = trim($_GET['from'] ?? date('Y-m-01'));
    $to = trim($_GET['to'] ?? date('Y-m-d'));
    $perf = Database::query(
        'SELECT v.id, v.code, v.name, v.commission_percent,
            (SELECT COUNT(*) FROM visitor_visits vv WHERE vv.visitor_id=v.id AND vv.visit_date BETWEEN ? AND ?) visits_total,
            (SELECT COUNT(*) FROM visitor_visits vv WHERE vv.visitor_id=v.id AND vv.status="done" AND vv.visit_date BETWEEN ? AND ?) visits_done,
            (SELECT COALESCE(SUM(i.total),0) FROM invoices i WHERE i.visitor_id=v.id AND i.invoice_date BETWEEN ? AND ?) sales_total,
            (SELECT COALESCE(SUM(c.commission_amount),0) FROM visitor_commissions c
              JOIN invoices i ON i.id=c.invoice_id
              WHERE c.visitor_id=v.id AND i.invoice_date BETWEEN ? AND ?) commission_total
         FROM visitors v
         WHERE v.is_active=1
         ORDER BY sales_total DESC, v.name',
        [$from, $to, $from, $to, $from, $to, $from, $to]
    )->fetchAll();
    if (isset($_GET['excel'])) {
        $export = [];
        foreach ($perf as $r) {
            $export[] = [
                $r['code'], $r['name'], $r['commission_percent'],
                $r['visits_total'], $r['visits_done'], $r['sales_total'], $r['commission_total'],
            ];
        }
        ExcelExport::download('visitor-report.xls', [
            'کد', 'ویزیتور', 'درصد', 'بازدید', 'انجام‌شده', 'فروش', 'پورسانت',
        ], $export);
    }
    view('visitor_reports', compact('perf', 'from', 'to') + [
        'title' => 'گزارش ویزیتورها',
        'nav' => 'visitor_reports',
    ]);
});
