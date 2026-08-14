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
        $pub = $this->generatePublicCode();
        $st = $this->pdo->prepare(
            'INSERT INTO users (telegram_id, username, first_name, coins, display_name, referral_code, public_code, last_seen_at)
             VALUES (?, ?, ?, 35, ?, ?, ?, NOW())'
        );
        $st->execute([$telegramId, $username, $firstName, $display, $ref, $pub]);
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
        if (empty($user['public_code'])) {
            $fields['public_code'] = $this->generatePublicCode();
        }
        if ($fields) {
            $this->updateUser($tid, $fields);
        }
    }

    public function touchSeen(int $telegramId): void
    {
        $this->pdo->prepare('UPDATE users SET last_seen_at = NOW() WHERE telegram_id = ?')->execute([$telegramId]);
    }

    public function generatePublicCode(): string
    {
        for ($i = 0; $i < 8; $i++) {
            $code = 'HG' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
            $st = $this->pdo->prepare('SELECT id FROM users WHERE public_code = ? LIMIT 1');
            $st->execute([$code]);
            if (!$st->fetch()) {
                return $code;
            }
        }
        return 'HG' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    }

    public function findByPublicCode(string $code): ?array
    {
        $code = strtoupper(trim($code));
        $st = $this->pdo->prepare('SELECT * FROM users WHERE public_code = ? LIMIT 1');
        $st->execute([$code]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public function spendCoins(int $telegramId, int $amount, string $reason, ?string $meta = null): bool
    {
        if ($amount <= 0) {
            return true;
        }
        $pdo = $this->pdo;
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare('SELECT id, coins FROM users WHERE telegram_id = ? FOR UPDATE');
            $st->execute([$telegramId]);
            $row = $st->fetch();
            if (!$row || (int)$row['coins'] < $amount) {
                $pdo->rollBack();
                return false;
            }
            $pdo->prepare('UPDATE users SET coins = coins - ? WHERE telegram_id = ?')->execute([$amount, $telegramId]);
            $pdo->prepare(
                'INSERT INTO coin_transactions (user_id, amount, reason, meta) VALUES (?, ?, ?, ?)'
            )->execute([(int)$row['id'], -$amount, $reason, $meta]);
            $pdo->commit();
            return true;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function isBlocked(int $blockerId, int $blockedId): bool
    {
        $st = $this->pdo->prepare(
            'SELECT 1 FROM user_blocks WHERE blocker_id = ? AND blocked_id = ? LIMIT 1'
        );
        $st->execute([$blockerId, $blockedId]);
        return (bool)$st->fetchColumn();
    }

    public function blockUser(int $blockerId, int $blockedId): void
    {
        $this->pdo->prepare(
            'INSERT IGNORE INTO user_blocks (blocker_id, blocked_id) VALUES (?, ?)'
        )->execute([$blockerId, $blockedId]);
    }

    /**
     * Browse next complete profile matching optional filters.
     * @param array{province?:string,city?:string,gender?:string} $filters
     */
    public function nextBrowseProfile(int $viewerTid, int $afterId, array $filters = []): ?array
    {
        $sql = "SELECT u.* FROM users u
            WHERE u.telegram_id <> ?
              AND u.gender IS NOT NULL AND u.age IS NOT NULL
              AND u.province IS NOT NULL AND u.city IS NOT NULL
              AND u.status <> 'banned'
              AND u.id > ?
              AND NOT EXISTS (
                  SELECT 1 FROM user_blocks b
                  WHERE (b.blocker_id = ? AND b.blocked_id = u.telegram_id)
                     OR (b.blocker_id = u.telegram_id AND b.blocked_id = ?)
              )";
        $params = [$viewerTid, $afterId, $viewerTid, $viewerTid];
        if (!empty($filters['gender']) && in_array($filters['gender'], ['male', 'female'], true)) {
            $sql .= ' AND u.gender = ?';
            $params[] = $filters['gender'];
        }
        if (!empty($filters['province'])) {
            $sql .= ' AND u.province = ?';
            $params[] = $filters['province'];
        }
        if (!empty($filters['city'])) {
            $sql .= ' AND u.city = ?';
            $params[] = $filters['city'];
        }
        $sql .= ' ORDER BY u.id ASC LIMIT 1';
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        $row = $st->fetch();
        if ($row) {
            return $row;
        }
        // wrap around
        if ($afterId > 0) {
            return $this->nextBrowseProfile($viewerTid, 0, $filters);
        }
        return null;
    }

    public function countUsers(): array
    {
        $total = (int)$this->pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        $complete = (int)$this->pdo->query(
            "SELECT COUNT(*) FROM users WHERE gender IS NOT NULL AND age IS NOT NULL AND province IS NOT NULL AND city IS NOT NULL"
        )->fetchColumn();
        $chatting = (int)$this->pdo->query("SELECT COUNT(*) FROM users WHERE status = 'chatting'")->fetchColumn();
        $banned = (int)$this->pdo->query("SELECT COUNT(*) FROM users WHERE status = 'banned'")->fetchColumn();
        return compact('total', 'complete', 'chatting', 'banned');
    }

    public function createContactRequest(int $fromId, int $toId, string $kind, ?string $payload = null): int
    {
        $this->pdo->prepare(
            'INSERT INTO contact_requests (from_id, to_id, kind, payload, status) VALUES (?, ?, ?, ?, ?)'
        )->execute([$fromId, $toId, $kind, $payload, 'pending']);
        return (int)$this->pdo->lastInsertId();
    }

    /** @return list<array> */
    public function listSupportStaff(bool $activeOnly = true): array
    {
        $sql = 'SELECT * FROM support_staff';
        if ($activeOnly) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY id ASC';
        return $this->pdo->query($sql)->fetchAll() ?: [];
    }

    public function addSupportStaff(int $telegramId, ?string $label = null): void
    {
        $this->pdo->prepare(
            'INSERT INTO support_staff (telegram_id, display_label, is_active) VALUES (?, ?, 1)
             ON DUPLICATE KEY UPDATE display_label = VALUES(display_label), is_active = 1'
        )->execute([$telegramId, $label]);
    }

    public function deactivateSupportStaff(int $telegramId): void
    {
        $this->pdo->prepare('UPDATE support_staff SET is_active = 0 WHERE telegram_id = ?')->execute([$telegramId]);
    }

    public function wipePublicProfile(int $telegramId): void
    {
        $this->updateUser($telegramId, [
            'avatar_file_id' => null,
            'display_name' => $this->generateDisplayName(),
            'public_code' => $this->generatePublicCode(),
        ]);
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
