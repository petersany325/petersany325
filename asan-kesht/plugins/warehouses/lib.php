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
    $db->exec(
        'CREATE TABLE IF NOT EXISTS ak_warehouse_counts (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            warehouse_id INT UNSIGNED NOT NULL,
            machine_id INT UNSIGNED NOT NULL,
            book_qty INT NOT NULL,
            counted_qty INT NOT NULL,
            difference INT NOT NULL,
            cause VARCHAR(16) NOT NULL,
            expense_id INT UNSIGNED NOT NULL DEFAULT 0,
            voided TINYINT(1) NOT NULL DEFAULT 0,
            note VARCHAR(240) NOT NULL DEFAULT "",
            counted_on DATE NOT NULL,
            created_at DATETIME NOT NULL,
            INDEX (warehouse_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    warehouse_extend($db);
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
    $app->router->post('/admin/accounting/warehouses/consign', function () use ($app): void {
        csrf_check();
        require_role($app, warehouse_roles());
        warehouse_post_consign($app);
    });
    $app->router->post('/admin/accounting/warehouses/count', function () use ($app): void {
        csrf_check();
        require_role($app, warehouse_roles());
        warehouse_post_count($app);
    });
    $app->router->post('/admin/accounting/warehouses/count-void', function () use ($app): void {
        csrf_check();
        require_role($app, warehouse_roles());
        warehouse_void_count($app);
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
            'dealers' => warehouse_dealers($app),
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
        'dealers' => warehouse_dealers($app),
        'counts' => warehouse_counts($app),
        'parties' => warehouse_parties($app),
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
    [$kind, $dealerId] = warehouse_posted_place($app);
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
        $app->db->transaction(function (Database $db) use ($id, $name, $place, $code, $active, $default, $kind, $dealerId): void {
            if ($default === 1) {
                $db->run('UPDATE ak_warehouses SET is_default = 0');
            }
            $db->run(
                'UPDATE ak_warehouses SET name = ?, place = ?, code = ?, active = ?, is_default = ?, kind = ?, dealer_id = ? WHERE id = ?',
                [$name, $place, $code, $active, $default, $kind, $dealerId, $id]
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
    $app->db->transaction(function (Database $db) use ($name, $place, $code, $active, $default, $kind, $dealerId): void {
        if ($default === 1) {
            $db->run('UPDATE ak_warehouses SET is_default = 0');
        }
        $db->run(
            'INSERT INTO ak_warehouses (name, code, place, active, is_default, kind, dealer_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())',
            [$name, $code, $place, $active, $default, $kind, $dealerId]
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

/** @return array{id:int|string,name:string,active:int|string,kind:string,dealer_id:int|string} */
function warehouse_open_house(Database $db, int $id): array
{
    $house = $id > 0 ? $db->one('SELECT id, name, active, kind, dealer_id FROM ak_warehouses WHERE id = ?', [$id]) : null;
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

function warehouse_insert_move(Database $db, int $warehouseId, int $machineId, string $direction, string $kind, int $qty, int $unit, int $total, int $peerId, string $note, string $day, string $source = 'manual', int $sourceId = 0, int $sourceLine = 0): void
{
    if ($sourceId > 0 && warehouse_move_exists($db, $source, $sourceId, $sourceLine)) {
        return;
    }
    if ($source === 'manual' && $sourceId === 0) {
        $saved = false;
        for ($try = 0; $try < 3; $try++) {
            $sourceId = warehouse_next_source($db, 'manual');
            try {
                warehouse_write_move($db, $warehouseId, $machineId, $direction, $kind, $qty, $unit, $total, $peerId, $note, $day, $source, $sourceId, $sourceLine);
                $saved = true;
                break;
            } catch (PDOException $e) {
                $duplicate = str_contains($e->getMessage(), 'Duplicate');
                if (!$duplicate || $try === 2) {
                    throw $e;
                }
            }
        }
        if (!$saved) {
            throw new RuntimeException('گردش انبار تکراری شد. یک بار دیگر ثبت کنید.');
        }
        return;
    }
    warehouse_write_move($db, $warehouseId, $machineId, $direction, $kind, $qty, $unit, $total, $peerId, $note, $day, $source, $sourceId, $sourceLine);
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

function warehouse_extend(Database $db): void
{
    warehouse_add_column($db, 'ak_warehouses', 'kind', 'VARCHAR(20) NOT NULL DEFAULT "central"');
    warehouse_add_column($db, 'ak_warehouses', 'dealer_id', 'INT UNSIGNED NOT NULL DEFAULT 0');
    warehouse_add_column($db, 'ak_warehouse_moves', 'source', 'VARCHAR(20) NOT NULL DEFAULT "manual"');
    warehouse_add_column($db, 'ak_warehouse_moves', 'source_id', 'INT UNSIGNED NOT NULL DEFAULT 0');
    warehouse_add_column($db, 'ak_warehouse_moves', 'source_line', 'INT UNSIGNED NOT NULL DEFAULT 0');
    if (warehouse_has_index($db, 'ak_warehouse_moves', 'source_key')) {
        return;
    }
    $db->run('UPDATE ak_warehouse_moves SET source_id = id WHERE source = "manual" AND source_id = 0');
    try {
        $db->exec('ALTER TABLE `ak_warehouse_moves` ADD UNIQUE KEY source_key (source, source_id, source_line)');
    } catch (Throwable $e) {
        if (!warehouse_has_index($db, 'ak_warehouse_moves', 'source_key')) {
            throw $e;
        }
    }
}

function warehouse_add_column(Database $db, string $table, string $column, string $definition): void
{
    $allowed = [
        'ak_warehouses' => ['kind' => true, 'dealer_id' => true],
        'ak_warehouse_moves' => ['source' => true, 'source_id' => true, 'source_line' => true],
    ];
    if (!isset($allowed[$table][$column])) {
        return;
    }
    $row = $db->one(
        'SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
        [$table, $column]
    );
    if ($row) {
        return;
    }
    $db->exec('ALTER TABLE `' . $table . '` ADD COLUMN `' . $column . '` ' . $definition);
}

function warehouse_has_index(Database $db, string $table, string $index): bool
{
    $row = $db->one(
        'SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?',
        [$table, $index]
    );
    return $row !== null;
}

/** @return array<string, string> */
function warehouse_kinds(): array
{
    return [
        'central' => 'مرکزی',
        'shop' => 'فروشگاه',
        'branch' => 'شعبه',
        'dealer' => 'نماینده',
        'consignment' => 'امانی',
        'transit' => 'در راه',
        'return' => 'مرجوعی',
        'quarantine' => 'قرنطینه',
        'scrap' => 'ضایعات',
        'material' => 'مواد',
    ];
}

function warehouse_kind_label(string $kind): string
{
    return warehouse_kinds()[$kind] ?? $kind;
}

/** @return array<string, string> */
function warehouse_causes(): array
{
    return [
        'count' => 'خطای شمارش',
        'theft' => 'سرقت',
        'damage' => 'آسیب',
        'scrap' => 'اسقاط',
    ];
}

function warehouse_cause_label(string $cause): string
{
    return match ($cause) {
        'count' => 'خطای شمارش',
        'theft' => 'سرقت',
        'damage' => 'آسیب',
        'scrap' => 'اسقاط',
        default => warehouse_cause_unknown($cause),
    };
}

function warehouse_cause_unknown(string $cause): never
{
    throw new RuntimeException('علت شمارش شناخته نشد.');
}

function warehouse_loss_cause(string $cause): bool
{
    return $cause === 'theft' || $cause === 'damage' || $cause === 'scrap';
}

/** @return array{0: string, 1: int} */
function warehouse_posted_place(App $app): array
{
    $kind = post_string('kind', 20);
    if (!isset(warehouse_kinds()[$kind])) {
        $kind = 'central';
    }
    $dealerId = (int) post_string('dealer_id', 12);
    $known = false;
    if ($dealerId > 0) {
        foreach (warehouse_dealers($app) as $dealer) {
            if ((int) $dealer['id'] === $dealerId) {
                $known = true;
                break;
            }
        }
    }
    return [$kind, $known ? $dealerId : 0];
}

/** @return list<array<string, mixed>> */
function warehouse_dealers(App $app): array
{
    $found = $app->db->one(
        'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "ak_dealers"'
    );
    if (!$found) {
        return [];
    }
    try {
        return $app->db->all('SELECT id, name, code, sale_model FROM ak_dealers ORDER BY name');
    } catch (Throwable) {
        return [];
    }
}

/** @return list<array<string, mixed>> */
function warehouse_parties(App $app): array
{
    try {
        return $app->db->all('SELECT id, name FROM ak_parties ORDER BY name LIMIT 200');
    } catch (Throwable) {
        return [];
    }
}

/** @return list<array<string, mixed>> */
function warehouse_counts(App $app): array
{
    try {
        return $app->db->all(
            'SELECT c.*, w.name AS warehouse_name, m.code AS machine_code
             FROM ak_warehouse_counts c
             JOIN ak_warehouses w ON w.id = c.warehouse_id
             LEFT JOIN ak_machines m ON m.id = c.machine_id
             ORDER BY c.id DESC
             LIMIT 20'
        );
    } catch (Throwable) {
        return [];
    }
}

function warehouse_next_source(Database $db, string $source): int
{
    $row = $db->one('SELECT COALESCE(MAX(source_id), 0) AS s FROM ak_warehouse_moves WHERE source = ?', [$source]);
    $next = (int) ($row['s'] ?? 0) + 1;
    if ($next < 1 || $next > 4000000000) {
        throw new RuntimeException('شناسه گردش انبار بیش از حد است.');
    }
    return $next;
}

function warehouse_move_exists(Database $db, string $source, int $sourceId, int $sourceLine): bool
{
    $row = $db->one(
        'SELECT id FROM ak_warehouse_moves WHERE source = ? AND source_id = ? AND source_line = ?',
        [$source, $sourceId, $sourceLine]
    );
    return $row !== null;
}

function warehouse_write_move(Database $db, int $warehouseId, int $machineId, string $direction, string $kind, int $qty, int $unit, int $total, int $peerId, string $note, string $day, string $source, int $sourceId, int $sourceLine): void
{
    $db->run(
        'INSERT INTO ak_warehouse_moves (warehouse_id, machine_id, direction, kind, qty, unit_cost, total, peer_id, note, moved_on, source, source_id, source_line, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())',
        [$warehouseId, $machineId, $direction, $kind, $qty, $unit, $total, $peerId, $note, $day, $source, $sourceId, $sourceLine]
    );
}

function warehouse_default_id(Database $db): int
{
    $row = $db->one('SELECT id FROM ak_warehouses WHERE active = 1 AND is_default = 1 ORDER BY id LIMIT 1');
    if (!$row) {
        $row = $db->one('SELECT id FROM ak_warehouses WHERE active = 1 ORDER BY id LIMIT 1');
    }
    if (!$row) {
        throw new RuntimeException('انبار پیش‌فرض فعال پیدا نشد.');
    }
    return (int) $row['id'];
}

/** @return array{id: int|string, code: string, name: string} */
function warehouse_find_machine(Database $db, int $machineId): array
{
    $row = $machineId > 0 ? $db->one('SELECT id, code, name FROM ak_machines WHERE id = ?', [$machineId]) : null;
    if (!$row) {
        throw new RuntimeException('این ماشین در کاتالوگ پیدا نشد.');
    }
    return $row;
}

/** @return array{qty: int, value: int, unit: int, total: int} */
function warehouse_add_exact(int $qty, int $total, int $haveQty, int $haveValue): array
{
    if ($qty < 1 || $total < 0 || $haveQty < 0 || $haveValue < 0) {
        throw new RuntimeException('برگشت موجودی نامعتبر است.');
    }
    if ($haveQty > 10000000 || $qty > 10000000 - $haveQty) {
        throw new RuntimeException('موجودی این انبار بیش از حد می‌شود.');
    }
    if ($haveValue > PHP_INT_MAX - $total) {
        throw new RuntimeException('ارزش انبار بیش از حد می‌شود.');
    }
    return [
        'qty' => $haveQty + $qty,
        'value' => $haveValue + $total,
        'unit' => intdiv($total, $qty),
        'total' => $total,
    ];
}

/** @return array{unit: int, total: int, leftQty: int, leftValue: int} */
function warehouse_take_exact(int $qty, int $total, int $haveQty, int $haveValue): array
{
    if ($qty < 1 || $total < 0 || $haveQty < $qty || $haveValue < $total) {
        throw new RuntimeException('موجودی برای برگشت این سند کافی نیست.');
    }
    $leftQty = $haveQty - $qty;
    $leftValue = $haveValue - $total;
    if ($leftQty === 0 && $leftValue !== 0) {
        throw new RuntimeException('برگشت این سند ارزش بدون تعداد باقی می‌گذارد.');
    }
    return ['unit' => intdiv($total, $qty), 'total' => $total, 'leftQty' => $leftQty, 'leftValue' => $leftValue];
}

function warehouse_apply_invoice(Database $db, int $invoiceId, string $kind, string $day): int
{
    if ($kind !== 'sale' && $kind !== 'purchase') {
        return 0;
    }
    $invoice = $db->one('SELECT number FROM ak_invoices WHERE id = ?', [$invoiceId]);
    $number = (string) ($invoice['number'] ?? (string) $invoiceId);
    $items = $db->all('SELECT * FROM ak_invoice_items WHERE invoice_id = ? ORDER BY id', [$invoiceId]);
    $costSum = 0;
    $source = $kind === 'purchase' ? 'purchase' : 'sale';
    foreach ($items as $item) {
        $qty = (int) $item['qty'];
        $price = (int) $item['unit_price'];
        $machineId = (int) ($item['machine_id'] ?? 0);
        $line = (int) $item['id'];
        if ($machineId < 1) {
            $costSum += $qty * (int) $item['unit_cost'];
            continue;
        }
        if ($qty < 1 || $qty > 1000000) {
            throw new RuntimeException('تعداد ردیف انبار باید بین ۱ و ۱,۰۰۰,۰۰۰ باشد.');
        }
        $machine = warehouse_find_machine($db, $machineId);
        $warehouseId = (int) ($item['warehouse_id'] ?? 0);
        if ($warehouseId < 1) {
            $warehouseId = warehouse_default_id($db);
        }
        warehouse_open_house($db, $warehouseId);
        if (warehouse_move_exists($db, $source, $invoiceId, $line)) {
            $prev = $db->one(
                'SELECT total FROM ak_warehouse_moves WHERE source = ? AND source_id = ? AND source_line = ?',
                [$source, $invoiceId, $line]
            );
            $costSum += (int) ($prev['total'] ?? 0);
            continue;
        }
        $stock = warehouse_lock_stock($db, $warehouseId, $machineId);
        $note = mb_substr($number . ' ' . (string) $machine['code'], 0, 240);
        if ($kind === 'purchase') {
            $added = warehouse_add_stock($qty, $price, $stock['qty'], $stock['value']);
            warehouse_put_stock($db, $warehouseId, $machineId, $added['qty'], $added['value']);
            warehouse_insert_move($db, $warehouseId, $machineId, 'in', 'receipt', $qty, $added['unit'], $added['total'], 0, $note, $day, $source, $invoiceId, $line);
            $db->run(
                'UPDATE ak_invoice_items SET unit_cost = ?, cost_total = ?, warehouse_id = ? WHERE id = ?',
                [$price, $added['total'], $warehouseId, $line]
            );
            $costSum += $added['total'];
            continue;
        }
        $taken = warehouse_take_stock($qty, $stock['qty'], $stock['value']);
        warehouse_put_stock($db, $warehouseId, $machineId, $stock['qty'] - $qty, $taken['left']);
        warehouse_insert_move($db, $warehouseId, $machineId, 'out', 'issue', $qty, $taken['unit'], $taken['total'], 0, $note, $day, $source, $invoiceId, $line);
        $db->run(
            'UPDATE ak_invoice_items SET unit_cost = ?, cost_total = ?, warehouse_id = ? WHERE id = ?',
            [$taken['unit'], $taken['total'], $warehouseId, $line]
        );
        $costSum += $taken['total'];
    }
    return $costSum;
}

function warehouse_reverse_invoice(Database $db, int $invoiceId, string $kind): void
{
    if ($kind !== 'sale' && $kind !== 'purchase') {
        return;
    }
    $source = $kind === 'purchase' ? 'purchase' : 'sale';
    $voidSource = $kind === 'purchase' ? 'purchase-void' : 'sale-void';
    $moves = $db->all(
        'SELECT * FROM ak_warehouse_moves WHERE source = ? AND source_id = ? ORDER BY id DESC',
        [$source, $invoiceId]
    );
    $day = date('Y-m-d');
    foreach ($moves as $move) {
        $line = (int) $move['source_line'];
        if (warehouse_move_exists($db, $voidSource, $invoiceId, $line)) {
            continue;
        }
        $warehouseId = (int) $move['warehouse_id'];
        $machineId = (int) $move['machine_id'];
        $qty = (int) $move['qty'];
        $total = (int) $move['total'];
        $stock = warehouse_lock_stock($db, $warehouseId, $machineId);
        $note = mb_substr('ابطال ' . (string) $move['note'], 0, 240);
        if ($kind === 'sale') {
            $added = warehouse_add_exact($qty, $total, $stock['qty'], $stock['value']);
            warehouse_put_stock($db, $warehouseId, $machineId, $added['qty'], $added['value']);
            warehouse_insert_move($db, $warehouseId, $machineId, 'in', 'receipt', $qty, $added['unit'], $total, 0, $note, $day, $voidSource, $invoiceId, $line);
            continue;
        }
        $taken = warehouse_take_exact($qty, $total, $stock['qty'], $stock['value']);
        warehouse_put_stock($db, $warehouseId, $machineId, $taken['leftQty'], $taken['leftValue']);
        warehouse_insert_move($db, $warehouseId, $machineId, 'out', 'issue', $qty, $taken['unit'], $total, 0, $note, $day, $voidSource, $invoiceId, $line);
    }
}

function warehouse_commission_table(Database $db): bool
{
    $found = $db->one(
        'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "ak_dealer_commissions"'
    );
    return $found !== null;
}

function warehouse_reverse_consign_commission(Database $db, int $invoiceId): void
{
    if (!warehouse_commission_table($db)) {
        return;
    }
    $db->run(
        'UPDATE ak_dealer_commissions SET status = "reversed" WHERE note LIKE ? AND status <> "reversed"',
        ['INV:' . $invoiceId . ':%']
    );
}

function warehouse_earn_consign_commission(App $app, int $invoiceId): void
{
    if (!warehouse_commission_table($app->db)) {
        return;
    }
    $rows = $app->db->all(
        'SELECT id, settle_on FROM ak_dealer_commissions WHERE note LIKE ? AND status = "pending"',
        ['INV:' . $invoiceId . ':%']
    );
    foreach ($rows as $row) {
        if ((string) $row['settle_on'] !== 'collection') {
            continue;
        }
        $app->db->run(
            'UPDATE ak_dealer_commissions SET status = "earned" WHERE id = ? AND status = "pending"',
            [(int) $row['id']]
        );
    }
}

function warehouse_post_consign(App $app): void
{
    warehouse_schema($app->db);
    $action = post_string('action', 12);
    try {
        if ($action === 'send') {
            warehouse_consign_send($app);
            flash('success', 'ارسال امانی ثبت شد. این انتقال فروش نیست و ارزش شرکت عوض نمی‌شود.');
        } elseif ($action === 'sale') {
            $number = warehouse_consign_sale($app);
            flash('success', 'فروش امانی ' . $number . ' به‌نام مشتری نهایی ثبت شد.');
        } else {
            throw new RuntimeException('نوع سند امانی مشخص نیست.');
        }
    } catch (RuntimeException $e) {
        flash('error', $e->getMessage());
    } catch (PDOException) {
        flash('error', 'این گردش انبار تکراری است و دوباره ثبت نشد.');
    }
    redirect('/admin/accounting/warehouses');
}

function warehouse_consign_send(App $app): void
{
    $from = (int) post_string('from_id', 12);
    $to = (int) post_string('to_id', 12);
    $machineId = (int) post_string('machine_id', 12);
    $qty = function_exists('accounting_amount') ? accounting_amount(post_string('qty', 12)) : (int) post_string('qty', 12);
    $day = function_exists('accounting_date') ? accounting_date(post_string('moved_on', 12)) : null;
    $note = post_string('note', 180);
    if ($note === '') {
        $note = 'ارسال امانی';
    }
    if ($qty < 1 || $qty > 1000000 || $day === null) {
        throw new RuntimeException('تعداد و تاریخ ارسال امانی را کامل کنید.');
    }
    $machine = warehouse_find_machine($app->db, $machineId);
    $app->db->transaction(function (Database $db) use ($from, $to, $machineId, $qty, $day, $note): void {
        $sourceHouse = warehouse_open_house($db, $from);
        $dest = warehouse_open_house($db, $to);
        $destKind = (string) $dest['kind'];
        if ($destKind !== 'consignment' && $destKind !== 'transit') {
            throw new RuntimeException('مقصد ارسال امانی باید انبار امانی یا در راه باشد.');
        }
        if ((int) $sourceHouse['id'] === (int) $dest['id']) {
            throw new RuntimeException('انتقال به همان انبار ممکن نیست.');
        }
        $sourceId = warehouse_next_source($db, 'consign');
        $stock = warehouse_lock_stock($db, $from, $machineId);
        $taken = warehouse_take_stock($qty, $stock['qty'], $stock['value']);
        warehouse_put_stock($db, $from, $machineId, $stock['qty'] - $qty, $taken['left']);
        warehouse_insert_move($db, $from, $machineId, 'out', 'transfer', $qty, $taken['unit'], $taken['total'], $to, $note, $day, 'consign', $sourceId, 1);
        $destStock = warehouse_lock_stock($db, $to, $machineId);
        if ($destStock['qty'] > 10000000 - $qty || $destStock['value'] > PHP_INT_MAX - $taken['total']) {
            throw new RuntimeException('ارزش انبار مقصد بیش از حد می‌شود.');
        }
        warehouse_put_stock($db, $to, $machineId, $destStock['qty'] + $qty, $destStock['value'] + $taken['total']);
        warehouse_insert_move($db, $to, $machineId, 'in', 'transfer', $qty, $taken['unit'], $taken['total'], $from, $note, $day, 'consign', $sourceId, 2);
    });
    ak_audit($app, 'امانی', (string) $machine['name'], 'ارسال ' . (string) $qty);
}

function warehouse_consign_sale(App $app): string
{
    if (!function_exists('accounting_party') || !function_exists('accounting_prefix') || !function_exists('accounting_amount') || !function_exists('accounting_date')) {
        throw new RuntimeException('فروش امانی به حسابداری وصل است.');
    }
    $warehouseId = (int) post_string('warehouse_id', 12);
    $machineId = (int) post_string('machine_id', 12);
    $qty = accounting_amount(post_string('qty', 12));
    $price = accounting_amount(post_string('unit_price', 15));
    $day = accounting_date(post_string('moved_on', 12));
    if ($qty < 1 || $qty > 1000000 || $price < 1 || $day === null) {
        throw new RuntimeException('انبار امانی، تعداد و فی فروش را کامل کنید.');
    }
    if ($qty > intdiv(PHP_INT_MAX, $price)) {
        throw new RuntimeException('مبلغ این فروش بیش از حد است.');
    }
    $machine = warehouse_find_machine($app->db, $machineId);
    $partyId = accounting_party($app);
    if ($partyId < 1) {
        throw new RuntimeException('مشتری نهایی لازم است. طرف‌حساب نماینده نیست.');
    }
    $house = $app->db->one('SELECT * FROM ak_warehouses WHERE id = ?', [$warehouseId]);
    if (!$house || (int) $house['active'] !== 1 || (string) $house['kind'] !== 'consignment') {
        throw new RuntimeException('فروش امانی فقط از انبار امانی فعال است.');
    }
    $total = $qty * $price;
    $number = accounting_prefix($app, 'sale') . '-' . date('ymd') . '-' . random_int(100, 999);
    $title = mb_substr((string) $machine['name'], 0, 180);
    $app->db->transaction(function (Database $db) use ($app, $number, $partyId, $total, $title, $qty, $price, $machineId, $warehouseId, $day, $house): void {
        warehouse_open_house($db, $warehouseId);
        $db->run(
            'INSERT INTO ak_invoices (number, party_id, kind, status, notes, total, source, source_id, visitor_id, created_at) VALUES (?, ?, "sale", "issued", ?, ?, "consign", ?, 0, NOW())',
            [$number, $partyId, 'فروش امانی', $total, $warehouseId]
        );
        $invoiceId = (int) $db->id();
        $db->run(
            'INSERT INTO ak_invoice_items (invoice_id, title, qty, unit_price, unit_cost, machine_id, warehouse_id, cost_total) VALUES (?, ?, ?, ?, 0, ?, ?, 0)',
            [$invoiceId, $title, $qty, $price, $machineId, $warehouseId]
        );
        $cost = warehouse_apply_invoice($db, $invoiceId, 'sale', $day);
        warehouse_consign_commission($app, $db, $house, $invoiceId, $number, $total, $cost);
    });
    ak_audit($app, 'امانی', (string) $machine['name'], $number . ' فروش ' . (string) $total);
    return $number;
}

function warehouse_consign_commission(App $app, Database $db, array $house, int $invoiceId, string $number, int $total, int $cost): void
{
    $dealerId = (int) ($house['dealer_id'] ?? 0);
    if ($dealerId < 1 || !warehouse_commission_table($db) || !function_exists('dealer_general_rule')) {
        return;
    }
    $dealer = $db->one('SELECT * FROM ak_dealers WHERE id = ?', [$dealerId]);
    if (!$dealer || (string) $dealer['sale_model'] !== 'commission') {
        return;
    }
    $rule = dealer_general_rule($app, $dealer);
    $basis = (string) ($rule['basis'] ?? '') === 'gross' ? 'gross' : 'sale';
    $settle = (string) ($rule['settle_on'] ?? '') === 'sale' ? 'sale' : 'collection';
    $percent = (int) ($rule['percent'] ?? 0);
    $base = $basis === 'gross' ? max(0, $total - $cost) : $total;
    if ($percent < 1 || $base < 1) {
        return;
    }
    $amount = intdiv($base * $percent, 100);
    if ($amount < 1) {
        return;
    }
    $status = $settle === 'sale' ? 'earned' : 'pending';
    $note = 'INV:' . $invoiceId . ': فروش امانی ' . $number;
    if ($basis === 'gross' && $cost <= 0) {
        $note .= '؛ بهای تمام‌شده ثبت نشده و پایه برابر مبلغ فروش شده است';
    }
    $db->run(
        'INSERT INTO ak_dealer_commissions (dealer_id, order_id, amount, status, basis, settle_on, note, created_at) VALUES (?, 0, ?, ?, ?, ?, ?, NOW())',
        [$dealerId, $amount, $status, $basis, $settle, mb_substr($note, 0, 240)]
    );
}

function warehouse_post_count(App $app): void
{
    warehouse_schema($app->db);
    try {
        flash('success', warehouse_count($app));
    } catch (RuntimeException $e) {
        flash('error', $e->getMessage());
    } catch (PDOException) {
        flash('error', 'این شمارش تکراری است و دوباره ثبت نشد.');
    }
    redirect('/admin/accounting/warehouses');
}

function warehouse_count(App $app): string
{
    $warehouseId = (int) post_string('warehouse_id', 12);
    $machineId = (int) post_string('machine_id', 12);
    $counted = function_exists('accounting_amount') ? accounting_amount(post_string('counted_qty', 12)) : (int) post_string('counted_qty', 12);
    $cause = post_string('cause', 16);
    $day = function_exists('accounting_date') ? accounting_date(post_string('counted_on', 12)) : null;
    $note = post_string('note', 240);
    if (!isset(warehouse_causes()[$cause]) || $day === null || $counted < 0 || $counted > 10000000) {
        throw new RuntimeException('شمارش، علت و تاریخ را کامل کنید.');
    }
    $machine = warehouse_find_machine($app->db, $machineId);
    $message = $app->db->transaction(function (Database $db) use ($warehouseId, $machineId, $counted, $cause, $day, $note, $machine): string {
        $house = warehouse_open_house($db, $warehouseId);
        $stock = warehouse_lock_stock($db, $warehouseId, $machineId);
        $book = $stock['qty'];
        $diff = $counted - $book;
        $db->run(
            'INSERT INTO ak_warehouse_counts (warehouse_id, machine_id, book_qty, counted_qty, difference, cause, expense_id, voided, note, counted_on, created_at) VALUES (?, ?, ?, ?, ?, ?, 0, 0, ?, ?, NOW())',
            [$warehouseId, $machineId, $book, $counted, $diff, $cause, $note, $day]
        );
        $countId = (int) $db->id();
        if ($diff === 0) {
            return 'شمارش با دفتر یکی بود و موجودی عوض نشد.';
        }
        $label = warehouse_cause_label($cause);
        $moveNote = mb_substr('شمارش ' . $label . ' ' . (string) $machine['code'], 0, 240);
        if ($diff > 0) {
            $unit = $book > 0 ? intdiv($stock['value'], $book) : 0;
            $added = warehouse_add_stock($diff, $unit, $stock['qty'], $stock['value']);
            warehouse_put_stock($db, $warehouseId, $machineId, $added['qty'], $added['value']);
            warehouse_insert_move($db, $warehouseId, $machineId, 'in', 'adjust', $diff, $added['unit'], $added['total'], 0, $moveNote, $day, 'count', $countId, 1);
            return 'اضافی شمارش به موجودی نشست و درآمد نشد.';
        }
        $short = -$diff;
        $taken = warehouse_take_stock($short, $stock['qty'], $stock['value']);
        warehouse_put_stock($db, $warehouseId, $machineId, $stock['qty'] - $short, $taken['left']);
        warehouse_insert_move($db, $warehouseId, $machineId, 'out', 'adjust', $short, $taken['unit'], $taken['total'], 0, $moveNote, $day, 'count', $countId, 1);
        if (!warehouse_loss_cause($cause)) {
            return 'اصلاح شمارش ثبت شد و هزینه نشد.';
        }
        $title = mb_substr('کسری انبار ' . $label . ' ' . (string) $house['name'] . ' ' . (string) $machine['code'], 0, 180);
        $db->run(
            'INSERT INTO ak_expenses (title, category, amount, spent_at) VALUES (?, ?, ?, ?)',
            [$title, 'کسری انبار', $taken['total'], $day]
        );
        $db->run('UPDATE ak_warehouse_counts SET expense_id = ? WHERE id = ?', [(int) $db->id(), $countId]);
        return 'کسری با علت ' . $label . ' به‌عنوان هزینه ثبت شد.';
    });
    ak_audit($app, 'انبارشمارش', (string) $machine['name'], $message);
    return $message;
}

function warehouse_void_count(App $app): void
{
    warehouse_schema($app->db);
    if (!isset($_POST['confirm'])) {
        flash('error', 'برگشت شمارش را تأیید کنید.');
        redirect('/admin/accounting/warehouses');
    }
    $countId = (int) post_string('count_id', 12);
    try {
        $app->db->transaction(function (Database $db) use ($countId): void {
            $count = $countId > 0 ? $db->one('SELECT * FROM ak_warehouse_counts WHERE id = ? FOR UPDATE', [$countId]) : null;
            if (!$count || (int) $count['voided'] === 1) {
                throw new RuntimeException('این شمارش برای برگشت پیدا نشد.');
            }
            $diff = (int) $count['difference'];
            $day = date('Y-m-d');
            if ($diff !== 0 && !warehouse_move_exists($db, 'count-void', $countId, 1)) {
                $move = $db->one('SELECT * FROM ak_warehouse_moves WHERE source = "count" AND source_id = ? AND source_line = 1', [$countId]);
                if (!$move) {
                    throw new RuntimeException('گردش این شمارش پیدا نشد.');
                }
                $warehouseId = (int) $move['warehouse_id'];
                $machineId = (int) $move['machine_id'];
                $qty = (int) $move['qty'];
                $total = (int) $move['total'];
                $stock = warehouse_lock_stock($db, $warehouseId, $machineId);
                $note = mb_substr('برگشت ' . (string) $move['note'], 0, 240);
                if ($diff > 0) {
                    $taken = warehouse_take_exact($qty, $total, $stock['qty'], $stock['value']);
                    warehouse_put_stock($db, $warehouseId, $machineId, $taken['leftQty'], $taken['leftValue']);
                    warehouse_insert_move($db, $warehouseId, $machineId, 'out', 'adjust', $qty, $taken['unit'], $total, 0, $note, $day, 'count-void', $countId, 1);
                } else {
                    $added = warehouse_add_exact($qty, $total, $stock['qty'], $stock['value']);
                    warehouse_put_stock($db, $warehouseId, $machineId, $added['qty'], $added['value']);
                    warehouse_insert_move($db, $warehouseId, $machineId, 'in', 'adjust', $qty, $added['unit'], $total, 0, $note, $day, 'count-void', $countId, 1);
                }
            }
            $expenseId = (int) $count['expense_id'];
            if ($expenseId > 0) {
                $expense = $db->one('SELECT title, amount FROM ak_expenses WHERE id = ?', [$expenseId]);
                if ($expense) {
                    $db->run(
                        'INSERT INTO ak_expenses (title, category, amount, spent_at) VALUES (?, ?, ?, ?)',
                        [mb_substr('برگشت کسری ' . (string) $expense['title'], 0, 180), 'کسری انبار', -1 * (int) $expense['amount'], $day]
                    );
                }
            }
            $db->run('UPDATE ak_warehouse_counts SET voided = 1 WHERE id = ?', [$countId]);
        });
    } catch (RuntimeException $e) {
        flash('error', $e->getMessage());
        redirect('/admin/accounting/warehouses');
    } catch (PDOException) {
        flash('error', 'برگشت این شمارش تکراری است و ثبت نشد.');
        redirect('/admin/accounting/warehouses');
    }
    ak_audit($app, 'انبارشمارش', (string) $countId, 'برگشت شد');
    flash('success', 'شمارش برگشت. اگر هزینه داشت، مبلغ منفی‌اش سود را به حالت قبل برمی‌گرداند.');
    redirect('/admin/accounting/warehouses');
}
