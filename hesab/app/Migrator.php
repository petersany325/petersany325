<?php
declare(strict_types=1);

final class Migrator
{
    public static function migrate(): void
    {
        $pdo = Database::pdo();
        self::exec($pdo, "CREATE TABLE IF NOT EXISTS permissions (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          code VARCHAR(80) NOT NULL UNIQUE,
          title VARCHAR(190) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        self::exec($pdo, "CREATE TABLE IF NOT EXISTS role_permissions (
          role VARCHAR(40) NOT NULL,
          permission_code VARCHAR(80) NOT NULL,
          PRIMARY KEY (role, permission_code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        self::exec($pdo, "CREATE TABLE IF NOT EXISTS user_permissions (
          user_id INT UNSIGNED NOT NULL,
          permission_code VARCHAR(80) NOT NULL,
          allowed TINYINT(1) NOT NULL DEFAULT 1,
          PRIMARY KEY (user_id, permission_code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        self::exec($pdo, "CREATE TABLE IF NOT EXISTS audit_logs (
          id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          user_id INT UNSIGNED NULL,
          action VARCHAR(80) NOT NULL,
          entity VARCHAR(80) NULL,
          entity_id INT UNSIGNED NULL,
          detail TEXT NULL,
          ip VARCHAR(64) NULL,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          KEY idx_audit_user (user_id),
          KEY idx_audit_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        self::exec($pdo, "CREATE TABLE IF NOT EXISTS tafsili_types (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          code VARCHAR(40) NOT NULL UNIQUE,
          title VARCHAR(190) NOT NULL,
          is_active TINYINT(1) NOT NULL DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        self::exec($pdo, "CREATE TABLE IF NOT EXISTS tafsili_items (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          type_id INT UNSIGNED NOT NULL,
          code VARCHAR(60) NOT NULL,
          title VARCHAR(190) NOT NULL,
          is_active TINYINT(1) NOT NULL DEFAULT 1,
          meta_json JSON NULL,
          UNIQUE KEY uq_tafsili (type_id, code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        self::exec($pdo, "CREATE TABLE IF NOT EXISTS moein_tafsili_map (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          moein_id INT UNSIGNED NOT NULL,
          level TINYINT UNSIGNED NOT NULL,
          tafsili_type_id INT UNSIGNED NOT NULL,
          is_required TINYINT(1) NOT NULL DEFAULT 0,
          UNIQUE KEY uq_moein_level (moein_id, level)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        self::exec($pdo, "CREATE TABLE IF NOT EXISTS voucher_types (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          code VARCHAR(40) NOT NULL UNIQUE,
          title VARCHAR(190) NOT NULL,
          description_template VARCHAR(500) NULL,
          is_active TINYINT(1) NOT NULL DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        self::addColumn($pdo, 'accounts_moein', 'nature', "ENUM('debit','credit','neutral') NOT NULL DEFAULT 'neutral'");
        self::addColumn($pdo, 'accounts_moein', 'allow_debit', 'TINYINT(1) NOT NULL DEFAULT 1');
        self::addColumn($pdo, 'accounts_moein', 'allow_credit', 'TINYINT(1) NOT NULL DEFAULT 1');
        self::addColumn($pdo, 'accounts_moein', 'control_flags', 'VARCHAR(255) NULL');

        self::addColumn($pdo, 'vouchers', 'voucher_type_id', 'INT UNSIGNED NULL');
        self::addColumn($pdo, 'vouchers', 'reviewed_by', 'INT UNSIGNED NULL');
        self::addColumn($pdo, 'vouchers', 'reviewed_at', 'DATETIME NULL');
        self::addColumn($pdo, 'vouchers', 'locked_by', 'INT UNSIGNED NULL');
        self::addColumn($pdo, 'vouchers', 'locked_at', 'DATETIME NULL');
        self::addColumn($pdo, 'vouchers', 'source_module', 'VARCHAR(80) NULL');
        self::addColumn($pdo, 'vouchers', 'source_id', 'INT UNSIGNED NULL');
        self::exec($pdo, "ALTER TABLE vouchers MODIFY COLUMN status ENUM('draft','operational','reviewed','locked','void','posted') NOT NULL DEFAULT 'draft'");

        self::addColumn($pdo, 'voucher_lines', 'tafsili1_id', 'INT UNSIGNED NULL');
        self::addColumn($pdo, 'voucher_lines', 'tafsili2_id', 'INT UNSIGNED NULL');
        self::addColumn($pdo, 'voucher_lines', 'tafsili3_id', 'INT UNSIGNED NULL');

        self::addColumn($pdo, 'fiscal_years', 'is_closed', 'TINYINT(1) NOT NULL DEFAULT 0');
        self::addColumn($pdo, 'fiscal_years', 'closed_at', 'DATETIME NULL');

        self::exec($pdo, "CREATE TABLE IF NOT EXISTS bank_accounts (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          title VARCHAR(190) NOT NULL,
          bank_name VARCHAR(120) NULL,
          account_no VARCHAR(80) NULL,
          moein_id INT UNSIGNED NULL,
          tafsili_id INT UNSIGNED NULL,
          is_active TINYINT(1) NOT NULL DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        self::exec($pdo, "CREATE TABLE IF NOT EXISTS checkbooks (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          bank_account_id INT UNSIGNED NOT NULL,
          series VARCHAR(80) NOT NULL,
          from_no INT UNSIGNED NOT NULL,
          to_no INT UNSIGNED NOT NULL,
          next_no INT UNSIGNED NOT NULL,
          is_active TINYINT(1) NOT NULL DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        self::exec($pdo, "CREATE TABLE IF NOT EXISTS checks (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          checkbook_id INT UNSIGNED NULL,
          bank_account_id INT UNSIGNED NULL,
          check_no VARCHAR(40) NOT NULL,
          check_date DATE NOT NULL,
          due_date DATE NULL,
          amount DECIMAL(18,0) NOT NULL,
          payee VARCHAR(190) NULL,
          status ENUM('blank','issued','paid','returned','cancelled','in_hand','deposited') NOT NULL DEFAULT 'blank',
          direction ENUM('payable','receivable') NOT NULL DEFAULT 'payable',
          voucher_id INT UNSIGNED NULL,
          description VARCHAR(500) NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        self::exec($pdo, "CREATE TABLE IF NOT EXISTS bank_patterns (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          title VARCHAR(190) NOT NULL,
          voucher_type_id INT UNSIGNED NULL,
          payload_json JSON NOT NULL,
          is_active TINYINT(1) NOT NULL DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        self::exec($pdo, "CREATE TABLE IF NOT EXISTS treasury_docs (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          doc_type ENUM('receive','pay') NOT NULL,
          number INT UNSIGNED NOT NULL,
          doc_date DATE NOT NULL,
          party_tafsili_id INT UNSIGNED NULL,
          bank_account_id INT UNSIGNED NULL,
          amount DECIMAL(18,0) NOT NULL DEFAULT 0,
          method ENUM('cash','bank','check') NOT NULL DEFAULT 'cash',
          check_id INT UNSIGNED NULL,
          description VARCHAR(500) NULL,
          voucher_id INT UNSIGNED NULL,
          created_by INT UNSIGNED NULL,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        self::exec($pdo, "CREATE TABLE IF NOT EXISTS print_templates (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          code VARCHAR(60) NOT NULL UNIQUE,
          title VARCHAR(190) NOT NULL,
          entity VARCHAR(60) NOT NULL,
          body_html MEDIUMTEXT NOT NULL,
          is_default TINYINT(1) NOT NULL DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        self::exec($pdo, "CREATE TABLE IF NOT EXISTS moadian_settings (
          id TINYINT UNSIGNED PRIMARY KEY DEFAULT 1,
          economic_code VARCHAR(40) NULL,
          private_key TEXT NULL,
          memory_id VARCHAR(80) NULL,
          is_enabled TINYINT(1) NOT NULL DEFAULT 0,
          last_sync_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        self::exec($pdo, "CREATE TABLE IF NOT EXISTS moadian_invoices (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          invoice_id INT UNSIGNED NULL,
          tax_id VARCHAR(80) NULL,
          status ENUM('pending','sent','accepted','rejected') NOT NULL DEFAULT 'pending',
          payload_json JSON NULL,
          response_json JSON NULL,
          sent_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        self::exec($pdo, "CREATE TABLE IF NOT EXISTS bank_reconciliations (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          bank_account_id INT UNSIGNED NOT NULL,
          statement_date DATE NOT NULL,
          statement_balance DECIMAL(18,0) NOT NULL DEFAULT 0,
          book_balance DECIMAL(18,0) NOT NULL DEFAULT 0,
          note VARCHAR(500) NULL,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        self::seedDefaults($pdo);
        // migrate posted -> locked for backward compat display
        self::exec($pdo, "UPDATE vouchers SET status='locked' WHERE status='posted'");
    }

    private static function exec(PDO $pdo, string $sql): void
    {
        try {
            $pdo->exec($sql);
        } catch (Throwable $e) {
            // ignore duplicate/exists style errors
            if (!str_contains($e->getMessage(), 'Duplicate') && !str_contains($e->getMessage(), 'already exists')) {
                // still ignore alter race
            }
        }
    }

    private static function addColumn(PDO $pdo, string $table, string $column, string $definition): void
    {
        $st = $pdo->prepare('SELECT COUNT(*) c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?');
        $st->execute([$table, $column]);
        if ((int) $st->fetch()['c'] > 0) {
            return;
        }
        self::exec($pdo, "ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
    }

    private static function seedDefaults(PDO $pdo): void
    {
        $perms = [
            ['accounts.manage', 'مدیریت کدینگ'],
            ['tafsili.manage', 'مدیریت تفصیلی شناور'],
            ['vouchers.create', 'ثبت سند'],
            ['vouchers.review', 'بررسی سند'],
            ['vouchers.lock', 'قطعی کردن سند'],
            ['vouchers.renumber', 'مرتب‌سازی شماره اسناد'],
            ['treasury.manage', 'خزانه‌داری'],
            ['reports.view', 'مشاهده گزارش‌ها'],
            ['fiscal.manage', 'مدیریت دوره مالی'],
            ['users.manage', 'مدیریت کاربران'],
            ['moadian.manage', 'سامانه مودیان'],
            ['audit.view', 'تاریخچه فعالیت'],
        ];
        $ins = $pdo->prepare('INSERT IGNORE INTO permissions (code, title) VALUES (?,?)');
        foreach ($perms as $p) {
            $ins->execute($p);
        }

        $roleMap = [
            'admin' => array_column($perms, 0),
            'accountant' => ['accounts.manage','tafsili.manage','vouchers.create','vouchers.review','treasury.manage','reports.view','fiscal.manage','moadian.manage'],
            'viewer' => ['reports.view','audit.view'],
        ];
        $rp = $pdo->prepare('INSERT IGNORE INTO role_permissions (role, permission_code) VALUES (?,?)');
        foreach ($roleMap as $role => $codes) {
            foreach ($codes as $code) {
                $rp->execute([$role, $code]);
            }
        }

        $types = [
            ['GENERAL', 'سند عمومی', 'سند حسابداری'],
            ['OPENING', 'افتتاحیه', 'سند افتتاحیه دوره'],
            ['CLOSING', 'اختتامیه', 'سند اختتامیه دوره'],
            ['BANK', 'بانکی', 'سند عملیات بانکی'],
            ['AUTO', 'اتوماتیک', 'صادر شده از سایر محیط‌ها'],
        ];
        $vt = $pdo->prepare('INSERT IGNORE INTO voucher_types (code, title, description_template) VALUES (?,?,?)');
        foreach ($types as $t) {
            $vt->execute($t);
        }

        $tt = [
            ['PERSON', 'اشخاص'],
            ['COSTCENTER', 'مرکز هزینه'],
            ['PROJECT', 'پروژه'],
            ['BANK', 'بانک/صندوق'],
            ['OTHER', 'سایر تفصیلی'],
        ];
        $insT = $pdo->prepare('INSERT IGNORE INTO tafsili_types (code, title) VALUES (?,?)');
        foreach ($tt as $t) {
            $insT->execute($t);
        }

        // migrate parties into PERSON tafsili
        try {
            $typeId = (int) $pdo->query("SELECT id FROM tafsili_types WHERE code='PERSON'")->fetch()['id'];
            $parties = $pdo->query('SELECT code, name FROM parties')->fetchAll();
            $insI = $pdo->prepare('INSERT IGNORE INTO tafsili_items (type_id, code, title) VALUES (?,?,?)');
            foreach ($parties as $p) {
                $insI->execute([$typeId, $p['code'], $p['name']]);
            }
        } catch (Throwable $e) {
            // parties table may be empty/missing on fresh installs
        }

        $pdo->exec("INSERT IGNORE INTO print_templates (code, title, entity, body_html, is_default) VALUES
          ('VOUCHER_DEFAULT','چاپ سند پیش‌فرض','voucher','<h2>سند {{number}}</h2><p>{{date}} - {{description}}</p><table border=1 width=100%>{{rows}}</table>',1),
          ('RECEIPT_DEFAULT','رسید دریافت/پرداخت','treasury','<h2>رسید {{type}}</h2><p>شماره {{number}} - تاریخ {{date}}</p><p>مبلغ: {{amount}}</p><p>{{description}}</p>',1)
        ");

        $pdo->exec("INSERT IGNORE INTO moadian_settings (id, is_enabled) VALUES (1, 0)");
    }
}
