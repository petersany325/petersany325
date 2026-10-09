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
        self::addColumn($pdo, 'voucher_lines', 'project_id', 'INT UNSIGNED NULL');
        self::addColumn($pdo, 'voucher_lines', 'cost_center_id', 'INT UNSIGNED NULL');
        self::addColumn($pdo, 'voucher_lines', 'branch_id', 'INT UNSIGNED NULL');

        self::exec($pdo, "CREATE TABLE IF NOT EXISTS accounts (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          parent_id INT UNSIGNED NULL,
          code VARCHAR(40) NOT NULL UNIQUE,
          title VARCHAR(190) NOT NULL,
          level TINYINT UNSIGNED NOT NULL DEFAULT 1,
          account_type ENUM('group','kol','moein','operational','other') NOT NULL DEFAULT 'other',
          nature ENUM('debit','credit','neutral') NOT NULL DEFAULT 'neutral',
          allow_debit TINYINT(1) NOT NULL DEFAULT 1,
          allow_credit TINYINT(1) NOT NULL DEFAULT 1,
          is_postable TINYINT(1) NOT NULL DEFAULT 0,
          exclude_from_fs TINYINT(1) NOT NULL DEFAULT 0,
          is_active TINYINT(1) NOT NULL DEFAULT 1,
          legacy_moein_id INT UNSIGNED NULL,
          KEY idx_acc_parent (parent_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        self::addColumn($pdo, 'accounts', 'is_postable', 'TINYINT(1) NOT NULL DEFAULT 0');
        self::addColumn($pdo, 'accounts', 'exclude_from_fs', 'TINYINT(1) NOT NULL DEFAULT 0');
        self::exec($pdo, "ALTER TABLE accounts MODIFY COLUMN account_type ENUM('group','kol','moein','operational','other') NOT NULL DEFAULT 'other'");

        self::exec($pdo, "CREATE TABLE IF NOT EXISTS posting_rules (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          code VARCHAR(60) NOT NULL UNIQUE,
          title VARCHAR(190) NOT NULL,
          moein_code VARCHAR(40) NULL,
          is_active TINYINT(1) NOT NULL DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

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

        // Users: mobile login + profile fields
        self::addColumn($pdo, 'users', 'phone', "VARCHAR(20) NULL");
        self::addColumn($pdo, 'users', 'is_active', "TINYINT(1) NOT NULL DEFAULT 1");
        self::addColumn($pdo, 'users', 'last_login_at', "DATETIME NULL");
        try {
            $pdo->exec('CREATE UNIQUE INDEX uq_users_phone ON users (phone)');
        } catch (Throwable $e) {
            // ignore
        }

        self::exec($pdo, "CREATE TABLE IF NOT EXISTS otp_codes (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          phone VARCHAR(20) NOT NULL,
          code VARCHAR(255) NOT NULL,
          purpose VARCHAR(40) NOT NULL DEFAULT 'login',
          attempts INT UNSIGNED NOT NULL DEFAULT 0,
          expires_at DATETIME NOT NULL,
          created_at DATETIME NOT NULL,
          KEY idx_otp_phone (phone)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        self::exec($pdo, "CREATE TABLE IF NOT EXISTS license_events (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          action VARCHAR(60) NOT NULL,
          detail VARCHAR(500) NULL,
          user_id INT UNSIGNED NULL,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // ---- Visitor (ویزیتور) subsystem ----
        self::exec($pdo, "CREATE TABLE IF NOT EXISTS visitors (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          code VARCHAR(40) NOT NULL,
          name VARCHAR(190) NOT NULL,
          phone VARCHAR(20) NULL,
          region VARCHAR(120) NULL,
          commission_percent DECIMAL(6,2) NOT NULL DEFAULT 0,
          user_id INT UNSIGNED NULL,
          notes VARCHAR(500) NULL,
          is_active TINYINT(1) NOT NULL DEFAULT 1,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          UNIQUE KEY uq_visitors_code (code),
          KEY idx_visitors_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        self::exec($pdo, "CREATE TABLE IF NOT EXISTS visitor_customers (
          visitor_id INT UNSIGNED NOT NULL,
          party_id INT UNSIGNED NOT NULL,
          assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (visitor_id, party_id),
          KEY idx_vc_party (party_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        self::exec($pdo, "CREATE TABLE IF NOT EXISTS visitor_visits (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          visitor_id INT UNSIGNED NOT NULL,
          party_id INT UNSIGNED NULL,
          visit_date DATE NOT NULL,
          visit_time VARCHAR(10) NULL,
          status ENUM('planned','done','cancelled','no_sale') NOT NULL DEFAULT 'planned',
          result_note VARCHAR(500) NULL,
          next_followup DATE NULL,
          created_by INT UNSIGNED NULL,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          KEY idx_vv_visitor (visitor_id),
          KEY idx_vv_date (visit_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        self::exec($pdo, "CREATE TABLE IF NOT EXISTS visitor_cartable (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          visitor_id INT UNSIGNED NULL,
          title VARCHAR(190) NOT NULL,
          body TEXT NULL,
          kind ENUM('task','visit','commission','sms','other') NOT NULL DEFAULT 'task',
          status ENUM('open','in_progress','done','rejected') NOT NULL DEFAULT 'open',
          ref_type VARCHAR(40) NULL,
          ref_id INT UNSIGNED NULL,
          due_date DATE NULL,
          created_by INT UNSIGNED NULL,
          handled_by INT UNSIGNED NULL,
          handled_at DATETIME NULL,
          created_at DATETIME NOT NULL,
          KEY idx_cart_status (status),
          KEY idx_cart_visitor (visitor_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        self::exec($pdo, "CREATE TABLE IF NOT EXISTS visitor_commissions (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          visitor_id INT UNSIGNED NOT NULL,
          invoice_id INT UNSIGNED NOT NULL,
          base_amount DECIMAL(18,0) NOT NULL DEFAULT 0,
          percent DECIMAL(6,2) NOT NULL DEFAULT 0,
          commission_amount DECIMAL(18,0) NOT NULL DEFAULT 0,
          status ENUM('accrued','approved','paid','void') NOT NULL DEFAULT 'accrued',
          paid_at DATETIME NULL,
          note VARCHAR(500) NULL,
          created_at DATETIME NOT NULL,
          UNIQUE KEY uq_vc_invoice (invoice_id),
          KEY idx_vc_visitor (visitor_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        self::addColumn($pdo, 'invoices', 'visitor_id', 'INT UNSIGNED NULL');
        try {
            $pdo->exec('CREATE INDEX idx_invoices_visitor ON invoices (visitor_id)');
        } catch (Throwable $e) {
            // ignore
        }

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
            ['settings.manage', 'تنظیمات سامانه'],
            ['invoices.manage', 'مدیریت فاکتور'],
            ['invoices.print', 'چاپ فاکتور'],
            ['sms.manage', 'تنظیمات پیامک'],
            ['license.manage', 'مدیریت لایسنس'],
            ['visitors.manage', 'مدیریت ویزیتورها'],
            ['visitors.cartable', 'کارتابل ویزیتور'],
            ['visitors.reports', 'گزارش ویزیتور'],
            ['visitors.commission', 'پورسانت ویزیتور'],
        ];
        $ins = $pdo->prepare('INSERT IGNORE INTO permissions (code, title) VALUES (?,?)');
        foreach ($perms as $p) {
            $ins->execute($p);
        }

        $roleMap = [
            'admin' => array_column($perms, 0),
            'accountant' => [
                'accounts.manage','tafsili.manage','vouchers.create','vouchers.review','treasury.manage',
                'reports.view','fiscal.manage','moadian.manage','invoices.manage','invoices.print','settings.manage',
                'visitors.manage','visitors.cartable','visitors.reports','visitors.commission',
            ],
            'viewer' => ['reports.view','audit.view','invoices.print','visitors.reports'],
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
            ['01', 'مشتریان'],
            ['02', 'تأمین‌کنندگان'],
            ['03', 'بانک‌ها'],
            ['04', 'کارکنان'],
            ['05', 'شرکا و سهامداران'],
            ['06', 'دارایی‌ها'],
            ['07', 'پروژه‌ها'],
            ['08', 'شعب و واحدها'],
            ['09', 'مراکز هزینه'],
            ['10', 'قراردادها'],
            ['11', 'صندوق و تنخواه'],
            ['12', 'سایر اشخاص'],
            // aliases kept for older UI labels
            ['PERSON', 'اشخاص (سازگاری)'],
            ['COSTCENTER', 'مرکز هزینه (سازگاری)'],
            ['PROJECT', 'پروژه (سازگاری)'],
            ['BANK', 'بانک/صندوق (سازگاری)'],
            ['OTHER', 'سایر تفصیلی'],
        ];
        $insT = $pdo->prepare('INSERT IGNORE INTO tafsili_types (code, title) VALUES (?,?)');
        foreach ($tt as $t) {
            $insT->execute($t);
        }

        $rules = [
            ['SALE', 'فروش کالا', '410101'],
            ['AR_CUSTOMER', 'دریافتنی مشتری', '110401'],
            ['VAT_SALE', 'مالیات فروش', '210301'],
            ['INVENTORY', 'موجودی کالا', '110601'],
            ['COGS', 'بهای کالای فروش‌رفته', '510101'],
            ['AP_SUPPLIER', 'پرداختنی تأمین‌کننده', '210101'],
            ['VAT_PURCHASE', 'مالیات خرید قابل اعتبار', '110801'],
            ['CHECK_RECV', 'چک دریافتی', '110403'],
            ['CHECK_PAY', 'چک پرداختنی', '210103'],
            ['SALARY_PAY', 'حقوق پرداختنی', '210401'],
        ];
        $insR = $pdo->prepare('INSERT IGNORE INTO posting_rules (code, title, moein_code) VALUES (?,?,?)');
        foreach ($rules as $r) {
            $insR->execute($r);
        }

        $pdo->exec("INSERT IGNORE INTO settings (`key`,`value`) VALUES
          ('coding_pattern','1/2/4/6'),
          ('inventory_method','perpetual'),
          ('auto_post_subsystems','1'),
          ('enable_dimensions','1'),
          ('allow_negative_stock','0')
        ");

        // bridge legacy moein into accounts tree (postable leaves)
        try {
            $rows = $pdo->query(
                'SELECT m.id, m.code, m.title, m.nature, m.allow_debit, m.allow_credit, k.code kc, g.code gc
                 FROM accounts_moein m
                 JOIN accounts_kol k ON k.id=m.kol_id
                 JOIN account_groups g ON g.id=k.group_id'
            )->fetchAll();
            $insA = $pdo->prepare(
                'INSERT IGNORE INTO accounts (parent_id, code, title, level, account_type, nature, allow_debit, allow_credit, is_postable, legacy_moein_id)
                 VALUES (NULL,?,?,?,?,?,?,?,1,?)'
            );
            foreach ($rows as $r) {
                $len = strlen((string) $r['code']);
                $level = $len >= 6 ? 4 : ($len >= 4 ? 3 : ($len >= 2 ? 2 : 1));
                $type = $level >= 3 ? 'moein' : 'kol';
                // treat current moein rows as postable operational until 6-digit children exist
                $insA->execute([
                    $r['code'], $r['title'], $level, $type === 'moein' ? 'operational' : $type,
                    $r['nature'] ?: 'neutral', (int) $r['allow_debit'], (int) $r['allow_credit'], (int) $r['id'],
                ]);
                $pdo->prepare("UPDATE accounts SET is_postable=1, account_type='operational' WHERE legacy_moein_id=?")->execute([(int) $r['id']]);
            }
        } catch (Throwable $e) {
            // ignore bridge errors
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
          ('RECEIPT_DEFAULT','رسید دریافت/پرداخت','treasury','<h2>رسید {{type}}</h2><p>شماره {{number}} - تاریخ {{date}}</p><p>مبلغ: {{amount}}</p><p>{{description}}</p>',1),
          ('INVOICE_DEFAULT','فاکتور فروش پیشرفته','invoice','',1)
        ");

        $pdo->exec("INSERT IGNORE INTO moadian_settings (id, is_enabled) VALUES (1, 0)");
        $pdo->exec("INSERT IGNORE INTO settings (`key`,`value`) VALUES
          ('sms_enabled','0'),
          ('sms_provider','niazpardaz'),
          ('sms_mode','classic'),
          ('inv_tax_percent','9'),
          ('print_paper','A4')
        ");
    }
}
