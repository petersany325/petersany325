<?php
declare(strict_types=1);

namespace HddLand\Bot\AdminBot;

/**
 * Session + credential auth for the English Admin Telegram bot.
 */
final class AdminAuth
{
    public static function ensureSchema(): void
    {
        db()->exec(
            "CREATE TABLE IF NOT EXISTS admin_bot_sessions (
                telegram_id BIGINT PRIMARY KEY,
                username VARCHAR(80) NOT NULL,
                is_super TINYINT(1) NOT NULL DEFAULT 0,
                login_at DATETIME NOT NULL,
                last_seen DATETIME NOT NULL,
                fails INT NOT NULL DEFAULT 0,
                locked_until DATETIME NULL,
                state_name VARCHAR(40) NULL,
                state_payload TEXT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    }

    public static function session(int $telegramId): ?array
    {
        self::ensureSchema();
        $st = db()->prepare('SELECT * FROM admin_bot_sessions WHERE telegram_id=? LIMIT 1');
        $st->execute(array($telegramId));
        $row = $st->fetch();
        return $row ?: null;
    }

    public static function isLoggedIn(int $telegramId): bool
    {
        $s = self::session($telegramId);
        return $s && trim((string)($s['username'] ?? '')) !== '';
    }

    public static function username(int $telegramId): string
    {
        $s = self::session($telegramId);
        return $s ? (string)$s['username'] : '';
    }

    public static function touch(int $telegramId): void
    {
        self::ensureSchema();
        db()->prepare('UPDATE admin_bot_sessions SET last_seen=NOW() WHERE telegram_id=?')->execute(array($telegramId));
    }

    public static function isLocked(int $telegramId): bool
    {
        $s = self::session($telegramId);
        if (!$s || empty($s['locked_until'])) {
            return false;
        }
        return strtotime((string)$s['locked_until']) > time();
    }

    public static function beginLogin(int $telegramId): void
    {
        self::ensureSchema();
        $s = self::session($telegramId);
        if ($s) {
            db()->prepare('UPDATE admin_bot_sessions SET state_name=?, state_payload=?, username=\'\', last_seen=NOW() WHERE telegram_id=?')
                ->execute(array('await_username', '{}', $telegramId));
            return;
        }
        db()->prepare(
            'INSERT INTO admin_bot_sessions (telegram_id, username, is_super, login_at, last_seen, fails, state_name, state_payload)
             VALUES (?,\'\',0,NOW(),NOW(),0,?,?)'
        )->execute(array($telegramId, 'await_username', '{}'));
    }

    public static function setState(int $telegramId, string $name, array $payload = array()): void
    {
        self::ensureSchema();
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $s = self::session($telegramId);
        if ($s) {
            db()->prepare('UPDATE admin_bot_sessions SET state_name=?, state_payload=?, last_seen=NOW() WHERE telegram_id=?')
                ->execute(array($name, $json, $telegramId));
            return;
        }
        db()->prepare(
            'INSERT INTO admin_bot_sessions (telegram_id, username, is_super, login_at, last_seen, fails, state_name, state_payload)
             VALUES (?,\'\',0,NOW(),NOW(),0,?,?)'
        )->execute(array($telegramId, $name, $json));
    }

    public static function clearState(int $telegramId): void
    {
        db()->prepare('UPDATE admin_bot_sessions SET state_name=NULL, state_payload=NULL, last_seen=NOW() WHERE telegram_id=?')
            ->execute(array($telegramId));
    }

    public static function getState(int $telegramId): array
    {
        $s = self::session($telegramId);
        if (!$s || empty($s['state_name'])) {
            return array('name' => '', 'payload' => array());
        }
        $payload = json_decode((string)($s['state_payload'] ?? '{}'), true);
        return array(
            'name' => (string)$s['state_name'],
            'payload' => is_array($payload) ? $payload : array(),
        );
    }

    public static function attemptLogin(int $telegramId, string $user, string $pass): array
    {
        self::ensureSchema();
        if (self::isLocked($telegramId)) {
            return array('ok' => false, 'msg' => 'Too many failed attempts. Try again later.');
        }
        if (!function_exists('verify_admin_credentials')) {
            require_once dirname(__DIR__, 2) . '/admin/auth.php';
        }
        $row = verify_admin_credentials($user, $pass);
        if (!$row) {
            $s = self::session($telegramId);
            $fails = $s ? ((int)$s['fails'] + 1) : 1;
            $lock = $fails >= 5 ? date('Y-m-d H:i:s', time() + 900) : null;
            if ($s) {
                db()->prepare('UPDATE admin_bot_sessions SET fails=?, locked_until=?, state_name=?, state_payload=?, last_seen=NOW() WHERE telegram_id=?')
                    ->execute(array($fails, $lock, 'await_username', '{}', $telegramId));
            } else {
                db()->prepare(
                    'INSERT INTO admin_bot_sessions (telegram_id, username, is_super, login_at, last_seen, fails, locked_until, state_name, state_payload)
                     VALUES (?,\'\',0,NOW(),NOW(),?,?,?,?)'
                )->execute(array($telegramId, $fails, $lock, 'await_username', '{}'));
            }
            $left = max(0, 5 - $fails);
            return array(
                'ok' => false,
                'msg' => $lock
                    ? 'Invalid credentials. Account locked for 15 minutes.'
                    : "Invalid username or password. Attempts left: {$left}",
            );
        }

        db()->prepare(
            'INSERT INTO admin_bot_sessions (telegram_id, username, is_super, login_at, last_seen, fails, locked_until, state_name, state_payload)
             VALUES (?,?,?,NOW(),NOW(),0,NULL,NULL,NULL)
             ON DUPLICATE KEY UPDATE username=VALUES(username), is_super=VALUES(is_super), login_at=NOW(), last_seen=NOW(),
               fails=0, locked_until=NULL, state_name=NULL, state_payload=NULL'
        )->execute(array(
            $telegramId,
            (string)$row['username'],
            (int)($row['is_super'] ?? 0),
        ));

        return array('ok' => true, 'msg' => 'Welcome, ' . $row['username'] . '.');
    }

    public static function logout(int $telegramId): void
    {
        self::ensureSchema();
        db()->prepare(
            'UPDATE admin_bot_sessions SET username=\'\', is_super=0, state_name=NULL, state_payload=NULL, last_seen=NOW() WHERE telegram_id=?'
        )->execute(array($telegramId));
    }
}
