<?php

declare(strict_types=1);

function warehouse_roles(): array
{
    return function_exists('accounting_roles') ? accounting_roles() : ['admin', 'employee', 'accountant'];
}

function warehouse_schema(Database $db): void
{
    $db->exec(
        'CREATE TABLE IF NOT EXISTS ak_warehouses (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            code VARCHAR(16) NOT NULL,
            place VARCHAR(120) NOT NULL DEFAULT "",
            active TINYINT(1) NOT NULL DEFAULT 1,
            is_default TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            UNIQUE KEY code (code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $db->exec(
        'CREATE TABLE IF NOT EXISTS ak_warehouse_stock (
            warehouse_id INT UNSIGNED NOT NULL,
            machine_id INT UNSIGNED NOT NULL,
            qty INT NOT NULL DEFAULT 0,
            value BIGINT NOT NULL DEFAULT 0,
            PRIMARY KEY (warehouse_id, machine_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $db->exec(
        'CREATE TABLE IF NOT EXISTS ak_warehouse_moves (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            warehouse_id INT UNSIGNED NOT NULL,
            machine_id INT UNSIGNED NOT NULL,
            direction VARCHAR(8) NOT NULL,
            kind VARCHAR(16) NOT NULL,
            qty INT NOT NULL,
            unit_cost BIGINT NOT NULL,
            total BIGINT NOT NULL,
            peer_id INT UNSIGNED NOT NULL DEFAULT 0,
            note VARCHAR(240) NOT NULL DEFAULT "",
            moved_on DATE NOT NULL,
            created_at DATETIME NOT NULL,
            INDEX (warehouse_id),
            INDEX (machine_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $count = $db->one('SELECT COUNT(*) AS s FROM ak_warehouses');
    if ((int) ($count['s'] ?? 0) === 0) {
        $db->run(
            'INSERT INTO ak_warehouses (name, code, place, active, is_default, created_at) VALUES (?, ?, ?, 1, 1, NOW())',
            ['انبار مرکزی', 'MARKAZ', 'کارخانه']
        );
    }
}

function warehouse_routes(App $app): void
{
    if (!$app->pluginEnabled('warehouses')) {
        return;
    }
    $app->router->get('/admin/accounting/warehouses', function () use ($app): void {
        warehouse_screen($app);
    });
    $app->router->post('/admin/accounting/warehouses', function () use ($app): void {
        csrf_check();
        require_role($app, warehouse_roles());
        warehouse_save($app, 0);
    });
    $app->router->post('/admin/accounting/warehouses/move', function () use ($app): void {
        csrf_check();
        require_role($app, warehouse_roles());
        warehouse_post_move($app);
    });
    $app->router->post('/admin/accounting/warehouses/{id}/delete', function (array $params) use ($app): void {
        csrf_check();
        require_role($app, warehouse_roles());
        warehouse_delete($app, (int) ($params['id'] ?? 0));
    });
    $app->router->get('/admin/accounting/warehouses/{id}', function (array $params) use ($app): void {
        require_role($app, warehouse_roles());
        warehouse_schema($app->db);
        $id = (int) ($params['id'] ?? 0);
        $house = $id > 0 ? $app->db->one('SELECT * FROM ak_warehouses WHERE id = ?', [$id]) : null;
        if (!$house) {
            flash('error', 'این انبار پیدا نشد.');
            redirect('/admin/accounting/warehouses');
        }
        $app->view->plugin('warehouses', 'form', [
            'title' => 'ویرایش انبار',
            'house' => $house,
        ]);
    });
    $app->router->post('/admin/accounting/warehouses/{id}', function (array $params) use ($app): void {
        csrf_check();
        require_role($app, warehouse_roles());
        warehouse_save($app, (int) ($params['id'] ?? 0));
    });
}

function warehouse_screen(App $app): void
{
    require_role($app, warehouse_roles());
    warehouse_schema($app->db);
    $app->view->plugin('warehouses', 'index', [
        'title' => 'انبارها',
        'houses' => warehouse_houses($app),
        'stock' => warehouse_stock($app),
        'moves' => warehouse_moves($app),
        'machines' => warehouse_machines($app),
        'today' => date('Y-m-d'),
    ]);
}

/** @return list<array<string, mixed>> */
function warehouse_houses(App $app): array
{
    return $app->db->all(
        'SELECT w.*,
            COALESCE((SELECT SUM(s.qty) FROM ak_warehouse_stock s WHERE s.warehouse_id = w.id), 0) AS qty,
            COALESCE((SELECT SUM(s.value) FROM ak_warehouse_stock s WHERE s.warehouse_id = w.id), 0) AS stock_value
         FROM ak_warehouses w
         ORDER BY w.is_default DESC, w.active DESC, w.name'
    );
}

/** @return list<array<string, mixed>> */
function warehouse_machines(App $app): array
{
    $found = $app->db->one(
        'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "ak_machines"'
    );
    if (!$found) {
        return [];
    }
    return $app->db->all('SELECT id, code, name FROM ak_machines ORDER BY name');
}

/** @return list<array<string, mixed>> */
function warehouse_stock(App $app): array
{
    try {
        return $app->db->all(
            'SELECT s.warehouse_id, s.machine_id, s.qty, s.value, w.name AS warehouse_name,
                m.code AS machine_code, m.name AS machine_name
             FROM ak_warehouse_stock s
             JOIN ak_warehouses w ON w.id = s.warehouse_id
             LEFT JOIN ak_machines m ON m.id = s.machine_id
             WHERE s.qty <> 0 OR s.value <> 0
             ORDER BY w.name, m.name'
        );
    } catch (Throwable) {
        return [];
    }
}

/** @return list<array<string, mixed>> */
function warehouse_moves(App $app): array
{
    try {
        return $app->db->all(
            'SELECT mv.*, w.name AS warehouse_name, m.code AS machine_code, m.name AS machine_name, p.name AS peer_name
             FROM ak_warehouse_moves mv
             JOIN ak_warehouses w ON w.id = mv.warehouse_id
             LEFT JOIN ak_machines m ON m.id = mv.machine_id
             LEFT JOIN ak_warehouses p ON p.id = mv.peer_id AND mv.peer_id > 0
             ORDER BY mv.id DESC
             LIMIT 40'
        );
    } catch (Throwable) {
        return [];
    }
}

function warehouse_move_label(string $kind, string $direction): string
{
    if ($kind === 'receipt') {
        return 'ورود';
    }
    if ($kind === 'issue') {
        return 'خروج';
    }
    if ($kind === 'transfer') {
        return $direction === 'in' ? 'انتقال ورودی' : 'انتقال خروجی';
    }
    if ($kind === 'adjust') {
        return $direction === 'in' ? 'اصلاح افزایش' : 'اصلاح کاهش';
    }
    return $kind;
}

function warehouse_back(int $id): string
{
    return $id > 0 ? '/admin/accounting/warehouses/' . $id : '/admin/accounting/warehouses';
}

function warehouse_save(App $app, int $id): void
{
    warehouse_schema($app->db);
    $back = warehouse_back($id);
    $name = post_string('name', 120);
    $code = strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', post_string('code', 16)) ?? '');
    $place = post_string('place', 120);
    if (mb_strlen($name) < 2 || strlen($code) < 2) {
        flash('error', 'نام و کد حداقل ۲ نویسه برای انبار لازم است.');
        redirect($back);
    }
    $active = isset($_POST['active']) ? 1 : 0;
    $default = isset($_POST['is_default']) ? 1 : 0;
    $taken = $app->db->one('SELECT id FROM ak_warehouses WHERE code = ? AND id != ?', [$code, max(0, $id)]);
    if ($taken) {
        flash('error', 'این کد انبار قبلاً ثبت شده است.');
        redirect($back);
    }
    if ($id > 0) {
        $existing = $app->db->one('SELECT * FROM ak_warehouses WHERE id = ?', [$id]);
        if (!$existing) {
            flash('error', 'این انبار پیدا نشد.');
            redirect('/admin/accounting/warehouses');
        }
        if ($default === 0 && (int) $existing['is_default'] === 1) {
            flash('error', 'یک انبار باید پیش‌فرض بماند. اول انبار دیگری را پیش‌فرض کنید.');
            redirect($back);
        }
        $app->db->transaction(function (Database $db) use ($id, $name, $place, $code, $active, $default): void {
            if ($default === 1) {
                $db->run('UPDATE ak_warehouses SET is_default = 0');
            }
            $db->run(
                'UPDATE ak_warehouses SET name = ?, place = ?, code = ?, active = ?, is_default = ? WHERE id = ?',
                [$name, $place, $code, $active, $default, $id]
            );
        });
        ak_audit($app, 'انبار', $name, 'ویرایش ' . $code);
        flash('success', 'ویرایش انبار ذخیره شد. گردش قبلی عوض نشد.');
        redirect('/admin/accounting/warehouses/' . $id);
    }
    $any = $app->db->one('SELECT id FROM ak_warehouses LIMIT 1');
    if (!$any) {
        $default = 1;
    }
    $app->db->transaction(function (Database $db) use ($name, $place, $code, $active, $default): void {
        if ($default === 1) {
            $db->run('UPDATE ak_warehouses SET is_default = 0');
        }
        $db->run(
            'INSERT INTO ak_warehouses (name, code, place, active, is_default, created_at) VALUES (?, ?, ?, ?, ?, NOW())',
            [$name, $code, $place, $active, $default]
        );
    });
    ak_audit($app, 'انبار', $name, 'انبار تازه ' . $code);
    flash('success', 'انبار ذخیره شد.');
    redirect('/admin/accounting/warehouses');
}

function warehouse_delete(App $app, int $id): void
{
    warehouse_schema($app->db);
    if (!isset($_POST['confirm'])) {
        flash('error', 'حذف انبار را تأیید کنید.');
        redirect('/admin/accounting/warehouses');
    }
    $existing = $id > 0 ? $app->db->one('SELECT * FROM ak_warehouses WHERE id = ?', [$id]) : null;
    if (!$existing) {
        flash('error', 'این انبار پیدا نشد.');
        redirect('/admin/accounting/warehouses');
    }
    $count = (int) ($app->db->one('SELECT COUNT(*) AS s FROM ak_warehouses')['s'] ?? 0);
    if ($count < 2) {
        flash('error', 'آخرین انبار حذف نمی‌شود.');
        redirect('/admin/accounting/warehouses/' . $id);
    }
    $stock = $app->db->one(
        'SELECT warehouse_id FROM ak_warehouse_stock WHERE warehouse_id = ? AND (qty <> 0 OR value <> 0) LIMIT 1',
        [$id]
    );
    $move = $app->db->one('SELECT id FROM ak_warehouse_moves WHERE warehouse_id = ? OR peer_id = ? LIMIT 1', [$id, $id]);
    if ($stock || $move) {
        flash('error', 'این انبار موجودی یا گردش دارد و حذف نمی‌شود. از ویرایش غیرفعال کنید. این گردش داخل سود و زیان نیست.');
        redirect('/admin/accounting/warehouses/' . $id);
    }
    $app->db->transaction(function (Database $db) use ($id, $existing): void {
        $db->run('DELETE FROM ak_warehouse_stock WHERE warehouse_id = ?', [$id]);
        $db->run('DELETE FROM ak_warehouses WHERE id = ?', [$id]);
        if ((int) $existing['is_default'] === 1) {
            $next = $db->one('SELECT id FROM ak_warehouses ORDER BY id LIMIT 1');
            if ($next) {
                $db->run('UPDATE ak_warehouses SET is_default = 1 WHERE id = ?', [(int) $next['id']]);
            }
        }
    });
    ak_audit($app, 'انبار', (string) $existing['name'], 'حذف شد؛ کد ' . (string) $existing['code']);
    flash('success', 'انبار حذف شد.');
    redirect('/admin/accounting/warehouses');
}

function warehouse_post_move(App $app): void
{
    warehouse_schema($app->db);
    try {
        warehouse_move($app);
    } catch (RuntimeException $e) {
        flash('error', $e->getMessage());
        redirect('/admin/accounting/warehouses');
    }
    flash('success', 'گردش انبار ثبت شد و در سود و زیان نیامد.');
    redirect('/admin/accounting/warehouses');
}

function warehouse_move(App $app): void
{
    $move = post_string('move', 20);
    $known = [
        'receipt' => ['in', 'receipt', true],
        'issue' => ['out', 'issue', false],
        'transfer' => ['out', 'transfer', false],
        'adjust_in' => ['in', 'adjust', true],
        'adjust_out' => ['out', 'adjust', false],
    ];
    if (!isset($known[$move])) {
        throw new RuntimeException('نوع گردش انبار مشخص نیست.');
    }
    [$direction, $kind, $needsCost] = $known[$move];
    $warehouseId = (int) post_string('warehouse_id', 12);
    $machineId = (int) post_string('machine_id', 12);
    $peerId = (int) post_string('peer_id', 12);
    $qty = function_exists('accounting_amount') ? accounting_amount(post_string('qty', 12)) : (int) post_string('qty', 12);
    $unitCost = function_exists('accounting_amount') ? accounting_amount(post_string('unit_cost', 15)) : (int) post_string('unit_cost', 15);
    $day = function_exists('accounting_date') ? accounting_date(post_string('moved_on', 12)) : null;
    $note = post_string('note', 240);
    if ($qty < 1 || $qty > 1000000) {
        throw new RuntimeException('تعداد باید بین ۱ و ۱,۰۰۰,۰۰۰ باشد.');
    }
    if ($day === null) {
        throw new RuntimeException('تاریخ گردش معتبر نیست.');
    }
    if ($kind === 'transfer' && ($peerId < 1 || $peerId === $warehouseId)) {
        throw new RuntimeException('انتقال به انبار دیگری لازم است.');
    }
    $machine = null;
    foreach (warehouse_machines($app) as $row) {
        if ((int) $row['id'] === $machineId) {
            $machine = $row;
            break;
        }
    }
    if ($machine === null) {
        throw new RuntimeException('این ماشین در کاتالوگ پیدا نشد.');
    }
    $detail = $app->db->transaction(function (Database $db) use ($warehouseId, $machineId, $peerId, $qty, $unitCost, $needsCost, $direction, $kind, $note, $day, $machine): string {
        $house = warehouse_open_house($db, $warehouseId);
        if ($kind === 'transfer') {
            warehouse_open_house($db, $peerId);
        }
        $stock = warehouse_lock_stock($db, $warehouseId, $machineId);
        if ($needsCost) {
            $added = warehouse_add_stock($qty, $unitCost, $stock['qty'], $stock['value']);
            warehouse_put_stock($db, $warehouseId, $machineId, $added['qty'], $added['value']);
            warehouse_insert_move($db, $warehouseId, $machineId, $direction, $kind, $qty, $added['unit'], $added['total'], 0, $note, $day);
            $label = $kind === 'adjust' ? 'اصلاح افزایش' : 'ورود';
        } else {
            $taken = warehouse_take_stock($qty, $stock['qty'], $stock['value']);
            warehouse_put_stock($db, $warehouseId, $machineId, $stock['qty'] - $qty, $taken['left']);
            warehouse_insert_move($db, $warehouseId, $machineId, 'out', $kind, $qty, $taken['unit'], $taken['total'], $kind === 'transfer' ? $peerId : 0, $note, $day);
            if ($kind === 'transfer') {
                $dest = warehouse_lock_stock($db, $peerId, $machineId);
                if ($dest['qty'] > 10000000 - $qty || $dest['value'] > PHP_INT_MAX - $taken['total']) {
                    throw new RuntimeException('ارزش انبار مقصد بیش از حد می‌شود.');
                }
                warehouse_put_stock($db, $peerId, $machineId, $dest['qty'] + $qty, $dest['value'] + $taken['total']);
                warehouse_insert_move($db, $peerId, $machineId, 'in', 'transfer', $qty, $taken['unit'], $taken['total'], $warehouseId, $note, $day);
            }
            $label = $kind === 'transfer' ? 'انتقال' : ($kind === 'adjust' ? 'اصلاح کاهش' : 'خروج');
        }
        return (string) $house['name'] . ' — ' . $label . ' ' . (string) $machine['code'] . ' — ' . (string) $qty;
    });
    ak_audit($app, 'انبار', (string) $machine['name'], $detail);
}

/** @return array{id:int|string,name:string} */
function warehouse_open_house(Database $db, int $id): array
{
    $house = $id > 0 ? $db->one('SELECT id, name, active FROM ak_warehouses WHERE id = ?', [$id]) : null;
    if (!$house || (int) $house['active'] !== 1) {
        throw new RuntimeException('انبار فعال پیدا نشد.');
    }
    return $house;
}

/** @return array{qty:int,value:int} */
function warehouse_lock_stock(Database $db, int $warehouseId, int $machineId): array
{
    $row = $db->one(
        'SELECT qty, value FROM ak_warehouse_stock WHERE warehouse_id = ? AND machine_id = ? FOR UPDATE',
        [$warehouseId, $machineId]
    );
    if ($row) {
        return ['qty' => (int) $row['qty'], 'value' => (int) $row['value']];
    }
    $db->run(
        'INSERT INTO ak_warehouse_stock (warehouse_id, machine_id, qty, value) VALUES (?, ?, 0, 0)',
        [$warehouseId, $machineId]
    );
    return ['qty' => 0, 'value' => 0];
}

function warehouse_put_stock(Database $db, int $warehouseId, int $machineId, int $qty, int $value): void
{
    $db->run(
        'UPDATE ak_warehouse_stock SET qty = ?, value = ? WHERE warehouse_id = ? AND machine_id = ?',
        [$qty, $value, $warehouseId, $machineId]
    );
}

/** @return array{qty:int,value:int,unit:int,total:int} */
function warehouse_add_stock(int $qty, int $unit, int $haveQty, int $haveValue): array
{
    if ($unit < 0 || $haveQty < 0 || $haveValue < 0) {
        throw new RuntimeException('بهای واحد یا موجودی نامعتبر است.');
    }
    if ($haveQty > 10000000 || $qty > 10000000 - $haveQty) {
        throw new RuntimeException('موجودی این انبار بیش از حد می‌شود.');
    }
    if ($unit > 0 && $qty > intdiv(PHP_INT_MAX, $unit)) {
        throw new RuntimeException('مبلغ این ردیف بیش از حد است.');
    }
    $total = $unit * $qty;
    if ($haveValue > PHP_INT_MAX - $total) {
        throw new RuntimeException('ارزش انبار بیش از حد می‌شود.');
    }
    return ['qty' => $haveQty + $qty, 'value' => $haveValue + $total, 'unit' => $unit, 'total' => $total];
}

/** @return array{unit:int,total:int,left:int} */
function warehouse_take_stock(int $qty, int $haveQty, int $haveValue): array
{
    if ($haveQty < 1 || $qty > $haveQty || $haveValue < 0) {
        throw new RuntimeException('موجودی این انبار برای این تعداد کافی نیست.');
    }
    if ($qty === $haveQty) {
        return ['unit' => intdiv($haveValue, $haveQty), 'total' => $haveValue, 'left' => 0];
    }
    $unit = intdiv($haveValue, $haveQty);
    $total = $unit * $qty;
    return ['unit' => $unit, 'total' => $total, 'left' => $haveValue - $total];
}

function warehouse_insert_move(Database $db, int $warehouseId, int $machineId, string $direction, string $kind, int $qty, int $unit, int $total, int $peerId, string $note, string $day): void
{
    $db->run(
        'INSERT INTO ak_warehouse_moves (warehouse_id, machine_id, direction, kind, qty, unit_cost, total, peer_id, note, moved_on, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())',
        [$warehouseId, $machineId, $direction, $kind, $qty, $unit, $total, $peerId, $note, $day]
    );
}

function warehouse_value(App $app): int
{
    if (!function_exists('accounting_optional_sum')) {
        return 0;
    }
    return accounting_optional_sum($app, 'ak_warehouse_stock', 'SELECT COALESCE(SUM(value),0) AS s FROM ak_warehouse_stock');
}

function warehouse_qty(App $app): int
{
    if (!function_exists('accounting_optional_sum')) {
        return 0;
    }
    return accounting_optional_sum($app, 'ak_warehouse_stock', 'SELECT COALESCE(SUM(qty),0) AS s FROM ak_warehouse_stock');
}
