<?php
declare(strict_types=1);

final class Database
{
    private PDO $pdo;

    public function __construct(array $db)
    {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            $db['host'],
            $db['name'],
            $db['charset'] ?? 'utf8mb4'
        );
        $this->pdo = new PDO($dsn, $db['user'], $db['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function migrate(string $sqlFile): void
    {
        $sql = file_get_contents($sqlFile);
        if ($sql === false) {
            throw new RuntimeException('Cannot read schema');
        }
        $this->pdo->exec($sql);
    }

    public function findUser(int $telegramId): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM users WHERE telegram_id = ? LIMIT 1');
        $st->execute([$telegramId]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public function upsertUser(int $telegramId, ?string $username, ?string $firstName): array
    {
        $user = $this->findUser($telegramId);
        if ($user) {
            $st = $this->pdo->prepare(
                'UPDATE users SET username = ?, first_name = ? WHERE telegram_id = ?'
            );
            $st->execute([$username, $firstName, $telegramId]);
            $user = $this->findUser($telegramId) ?? $user;
            $this->ensureIdentity($user);
            return $this->findUser($telegramId) ?? $user;
        }

        $display = $this->generateDisplayName();
        $ref = $this->generateReferralCode();
        $st = $this->pdo->prepare(
            'INSERT INTO users (telegram_id, username, first_name, coins, display_name, referral_code)
             VALUES (?, ?, ?, 35, ?, ?)'
        );
        $st->execute([$telegramId, $username, $firstName, $display, $ref]);
        $user = $this->findUser($telegramId);
        if (!$user) {
            throw new RuntimeException('Failed to create user');
        }
        $this->pdo->prepare(
            'INSERT INTO coin_transactions (user_id, amount, reason) VALUES (?, 35, ?)'
        )->execute([(int)$user['id'], 'welcome_gift']);
        return $user;
    }

    public function ensureIdentity(array $user): void
    {
        $tid = (int)$user['telegram_id'];
        $fields = [];
        if (empty($user['display_name'])) {
            $fields['display_name'] = $this->generateDisplayName();
        }
        if (empty($user['referral_code'])) {
            $fields['referral_code'] = $this->generateReferralCode();
        }
        if ($fields) {
            $this->updateUser($tid, $fields);
        }
    }

    public function generateDisplayName(): string
    {
        for ($i = 0; $i < 8; $i++) {
            $name = 'همگپ_' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
            $st = $this->pdo->prepare('SELECT id FROM users WHERE display_name = ? LIMIT 1');
            $st->execute([$name]);
            if (!$st->fetch()) {
                return $name;
            }
        }
        return 'همگپ_' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    }

    public function generateReferralCode(): string
    {
        for ($i = 0; $i < 8; $i++) {
            $code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
            $st = $this->pdo->prepare('SELECT id FROM users WHERE referral_code = ? LIMIT 1');
            $st->execute([$code]);
            if (!$st->fetch()) {
                return $code;
            }
        }
        return strtoupper(substr(bin2hex(random_bytes(5)), 0, 10));
    }

    public function findByReferralCode(string $code): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM users WHERE referral_code = ? LIMIT 1');
        $st->execute([strtoupper(trim($code))]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public function addCoins(int $telegramId, int $amount, string $reason, ?string $meta = null): void
    {
        $user = $this->findUser($telegramId);
        if (!$user) {
            return;
        }
        $this->pdo->prepare('UPDATE users SET coins = coins + ? WHERE telegram_id = ?')->execute([$amount, $telegramId]);
        $this->pdo->prepare(
            'INSERT INTO coin_transactions (user_id, amount, reason, meta) VALUES (?, ?, ?, ?)'
        )->execute([(int)$user['id'], $amount, $reason, $meta]);
    }

    public function updateUser(int $telegramId, array $fields): void
    {
        if (!$fields) {
            return;
        }
        $cols = [];
        $vals = [];
        foreach ($fields as $k => $v) {
            $cols[] = "`$k` = ?";
            $vals[] = $v;
        }
        $vals[] = $telegramId;
        $sql = 'UPDATE users SET ' . implode(', ', $cols) . ' WHERE telegram_id = ?';
        $this->pdo->prepare($sql)->execute($vals);
    }

    /** @return list<int> */
    public function getUiMessages(array $user): array
    {
        $raw = $user['ui_messages'] ?? null;
        if (!$raw) {
            return [];
        }
        $decoded = json_decode((string)$raw, true);
        if (!is_array($decoded)) {
            return [];
        }
        $ids = [];
        foreach ($decoded as $id) {
            if (is_numeric($id)) {
                $ids[] = (int)$id;
            }
        }
        return array_values(array_unique($ids));
    }

    public function setUiMessages(int $telegramId, array $messageIds): void
    {
        $messageIds = array_values(array_unique(array_map('intval', $messageIds)));
        // Keep last 30 UI messages max
        if (count($messageIds) > 30) {
            $messageIds = array_slice($messageIds, -30);
        }
        $this->updateUser($telegramId, [
            'ui_messages' => $messageIds ? json_encode($messageIds) : null,
        ]);
    }

    public function addUiMessage(int $telegramId, array $user, int $messageId): array
    {
        $ids = $this->getUiMessages($user);
        $ids[] = $messageId;
        $this->setUiMessages($telegramId, $ids);
        $user['ui_messages'] = json_encode($this->getUiMessages(array_merge($user, [
            'ui_messages' => json_encode($ids),
        ])));
        // refresh
        return $this->findUser($telegramId) ?? $user;
    }
}
