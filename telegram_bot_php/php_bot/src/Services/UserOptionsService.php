<?php
declare(strict_types=1);

namespace HddLand\Bot\Services;

/**
 * Manageable user options / access flags.
 * Admin can add options and open/close them per user.
 */
final class UserOptionsService
{
    /** @return list<array{code:string,title_en:string,title_fa:string,description_en:string,description_fa:string,sort_order:int,is_active:int,default_open:int}> */
    public static function catalogDefaults(): array
    {
        return array(
            array(
                'code' => 'register',
                'title_en' => 'Registration',
                'title_fa' => 'ثبت‌نام',
                'description_en' => 'Name, family name, email',
                'description_fa' => 'نام، نام‌خانوادگی، ایمیل',
                'sort_order' => 10,
                'is_active' => 1,
                'default_open' => 1,
            ),
            array(
                'code' => 'support',
                'title_en' => 'Technical Support',
                'title_fa' => 'پشتیبانی فنی',
                'description_en' => 'SeDiv support desk',
                'description_fa' => 'میز پشتیبانی SeDiv',
                'sort_order' => 20,
                'is_active' => 1,
                'default_open' => 1,
            ),
            array(
                'code' => 'pay_receipt',
                'title_en' => 'Payment receipt',
                'title_fa' => 'ثبت فیش واریزی',
                'description_en' => 'PayPal / Western Union receipt upload',
                'description_fa' => 'آپلود فیش PayPal / Western Union',
                'sort_order' => 30,
                'is_active' => 1,
                'default_open' => 1,
            ),
            array(
                'code' => 'license',
                'title_en' => 'License file',
                'title_fa' => 'دریافت لایسنس',
                'description_en' => 'TXT license after receipt approval',
                'description_fa' => 'فایل TXT بعد از تایید فیش',
                'sort_order' => 40,
                'is_active' => 1,
                'default_open' => 0,
            ),
            array(
                'code' => 'activation',
                'title_en' => 'Activation file',
                'title_fa' => 'اکتیوسازی',
                'description_en' => 'Upload 200/300KB file → email sedivlic@list.ru',
                'description_fa' => 'ارسال فایل ۲۰۰/۳۰۰KB به sedivlic@list.ru',
                'sort_order' => 50,
                'is_active' => 1,
                'default_open' => 0,
            ),
            array(
                'code' => 'history',
                'title_en' => 'My reports',
                'title_fa' => 'گزارش مراحل',
                'description_en' => 'Order and activation history',
                'description_fa' => 'تاریخچه سفارش و اکتیو',
                'sort_order' => 60,
                'is_active' => 1,
                'default_open' => 1,
            ),
        );
    }

    public static function ensureSchema(): void
    {
        $pdo = db();
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS bot_options (
                code VARCHAR(40) PRIMARY KEY,
                title_en VARCHAR(120) NOT NULL,
                title_fa VARCHAR(120) NOT NULL,
                description_en VARCHAR(255) NULL,
                description_fa VARCHAR(255) NULL,
                sort_order INT NOT NULL DEFAULT 100,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                default_open TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS user_option_access (
                telegram_id BIGINT NOT NULL,
                option_code VARCHAR(40) NOT NULL,
                is_open TINYINT(1) NOT NULL DEFAULT 1,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (telegram_id, option_code)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $st = $pdo->prepare(
            'INSERT IGNORE INTO bot_options
            (code, title_en, title_fa, description_en, description_fa, sort_order, is_active, default_open)
            VALUES (?,?,?,?,?,?,?,?)'
        );
        foreach (self::catalogDefaults() as $row) {
            $st->execute(array(
                $row['code'], $row['title_en'], $row['title_fa'],
                $row['description_en'], $row['description_fa'],
                $row['sort_order'], $row['is_active'], $row['default_open'],
            ));
        }
    }

    /** @return list<array<string,mixed>> */
    public static function allOptions(bool $activeOnly = false): array
    {
        self::ensureSchema();
        $sql = 'SELECT * FROM bot_options';
        if ($activeOnly) {
            $sql .= ' WHERE is_active=1';
        }
        $sql .= ' ORDER BY sort_order ASC, code ASC';
        return db()->query($sql)->fetchAll() ?: array();
    }

    public static function addOption(string $code, string $titleEn, string $titleFa, int $defaultOpen = 0, int $sort = 100): void
    {
        self::ensureSchema();
        $code = strtolower(preg_replace('/[^a-z0-9_]/', '', $code) ?: '');
        if ($code === '' || strlen($code) < 2) {
            throw new \RuntimeException('Invalid option code');
        }
        db()->prepare(
            'INSERT INTO bot_options (code, title_en, title_fa, description_en, description_fa, sort_order, is_active, default_open)
             VALUES (?,?,?,?,?,?,1,?)
             ON DUPLICATE KEY UPDATE title_en=VALUES(title_en), title_fa=VALUES(title_fa),
             default_open=VALUES(default_open), sort_order=VALUES(sort_order), is_active=1'
        )->execute(array($code, $titleEn, $titleFa, '', '', $sort, $defaultOpen ? 1 : 0));
    }

    public static function setOptionActive(string $code, bool $active): void
    {
        self::ensureSchema();
        db()->prepare('UPDATE bot_options SET is_active=? WHERE code=?')->execute(array($active ? 1 : 0, $code));
    }

    public static function setUserAccess(int $telegramId, string $code, bool $open): void
    {
        self::ensureSchema();
        db()->prepare(
            'INSERT INTO user_option_access (telegram_id, option_code, is_open) VALUES (?,?,?)
             ON DUPLICATE KEY UPDATE is_open=VALUES(is_open)'
        )->execute(array($telegramId, $code, $open ? 1 : 0));
    }

    public static function isOpen(int $telegramId, string $code): bool
    {
        self::ensureSchema();
        try {
            $st = db()->prepare('SELECT is_active, default_open FROM bot_options WHERE code=? LIMIT 1');
            $st->execute(array($code));
            $opt = $st->fetch();
            if (!$opt || !(int)$opt['is_active']) {
                return false;
            }
            $st2 = db()->prepare('SELECT is_open FROM user_option_access WHERE telegram_id=? AND option_code=? LIMIT 1');
            $st2->execute(array($telegramId, $code));
            $row = $st2->fetch();
            if ($row) {
                return (int)$row['is_open'] === 1;
            }
            return (int)$opt['default_open'] === 1;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** @return list<array<string,mixed>> options visible for this user */
    public static function openOptionsFor(int $telegramId): array
    {
        $out = array();
        foreach (self::allOptions(true) as $opt) {
            if (self::isOpen($telegramId, (string)$opt['code'])) {
                $out[] = $opt;
            }
        }
        return $out;
    }

    public static function title(array $opt, string $lang): string
    {
        return $lang === 'fa'
            ? (string)($opt['title_fa'] ?: $opt['title_en'])
            : (string)($opt['title_en'] ?: $opt['title_fa']);
    }
}
