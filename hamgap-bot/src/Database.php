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

    /** @param array<string,mixed> $filters */
    private function browseWhereSql(int $viewerTid, array $filters, array &$params): string
    {
        $sql = "FROM users u
            WHERE u.telegram_id <> ?
              AND u.gender IS NOT NULL AND u.age IS NOT NULL
              AND u.province IS NOT NULL AND u.city IS NOT NULL
              AND u.status <> 'banned'
              AND COALESCE(u.profile_visibility, 'public') <> 'hidden'
              AND NOT EXISTS (
                  SELECT 1 FROM user_blocks b
                  WHERE (b.blocker_id = ? AND b.blocked_id = u.telegram_id)
                     OR (b.blocker_id = u.telegram_id AND b.blocked_id = ?)
              )
              AND (
                  COALESCE(u.profile_visibility, 'public') = 'public'
                  OR (
                      COALESCE(u.profile_visibility, 'public') = 'friends'
                      AND EXISTS (
                          SELECT 1 FROM friendships f
                          WHERE f.status = 'accepted'
                            AND ((f.user_a = LEAST(?, u.telegram_id) AND f.user_b = GREATEST(?, u.telegram_id)))
                      )
                  )
              )";
        $params = [$viewerTid, $viewerTid, $viewerTid, $viewerTid, $viewerTid];
        if (!empty($filters['gender']) && Gender::isValid((string)$filters['gender'])) {
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
        if (!empty($filters['online_only'])) {
            $sql .= ' AND u.last_seen_at IS NOT NULL AND u.last_seen_at >= (NOW() - INTERVAL 15 MINUTE)';
        }
        if (!empty($filters['new_only'])) {
            $sql .= ' AND u.created_at >= (NOW() - INTERVAL 7 DAY)';
        }
        if (!empty($filters['nearby_city']) && empty($filters['nearby_rank'])) {
            $sql .= ' AND u.city = ?';
            $params[] = $filters['nearby_city'];
        }
        if (!empty($filters['same_province'])) {
            $sql .= ' AND u.province = ?';
            $params[] = $filters['same_province'];
        }
        if (isset($filters['age_min']) && $filters['age_min'] !== '' && $filters['age_min'] !== null) {
            $sql .= ' AND u.age >= ?';
            $params[] = (int)$filters['age_min'];
        }
        if (isset($filters['age_max']) && $filters['age_max'] !== '' && $filters['age_max'] !== null) {
            $sql .= ' AND u.age <= ?';
            $params[] = (int)$filters['age_max'];
        }
        if (!empty($filters['age_near']) && !empty($filters['viewer_age'])) {
            $sql .= ' AND ABS(u.age - ?) <= 3';
            $params[] = (int)$filters['viewer_age'];
        }
        return $sql;
    }

    /** @param array<string,mixed> $filters */
    private function browseOrderSql(array $filters, array &$params): string
    {
        if (!empty($filters['nearby_rank'])) {
            $city = (string)($filters['nearby_city'] ?? '');
            $prov = (string)($filters['nearby_province'] ?? '');
            $params[] = $city;
            $params[] = $prov;
            return ' ORDER BY CASE
                WHEN u.city = ? THEN 0
                WHEN u.province = ? THEN 1
                ELSE 2 END ASC,
                (u.last_seen_at IS NULL) ASC, u.last_seen_at DESC, u.id DESC';
        }
        if (!empty($filters['online_only'])) {
            return ' ORDER BY u.last_seen_at DESC, u.id DESC';
        }
        if (!empty($filters['new_only'])) {
            return ' ORDER BY u.created_at DESC, u.id DESC';
        }
        return ' ORDER BY u.id ASC';
    }

    /**
     * Browse next complete profile matching optional filters.
     * @param array<string,mixed> $filters
     */
    public function nextBrowseProfile(int $viewerTid, int $afterId, array $filters = []): ?array
    {
        $params = [];
        $where = $this->browseWhereSql($viewerTid, $filters, $params);
        // id cursor only works with id ASC; for ranked modes use cache instead
        if (empty($filters['nearby_rank']) && empty($filters['online_only']) && empty($filters['new_only'])) {
            $where .= ' AND u.id > ?';
            $params[] = $afterId;
        }
        $order = $this->browseOrderSql($filters, $params);
        $sql = 'SELECT u.* ' . $where . $order . ' LIMIT 1';
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        $row = $st->fetch();
        if ($row) {
            return $row;
        }
        if ($afterId > 0 && empty($filters['nearby_rank'])) {
            return $this->nextBrowseProfile($viewerTid, 0, $filters);
        }
        return null;
    }

    /**
     * Batch profiles for modern list/menu/photo views (e.g. nearest 100).
     * @param array<string,mixed> $filters
     * @return list<array>
     */
    public function listBrowseProfiles(int $viewerTid, array $filters = [], int $limit = 100): array
    {
        $limit = max(1, min(100, $limit));
        $params = [];
        $where = $this->browseWhereSql($viewerTid, $filters, $params);
        $order = $this->browseOrderSql($filters, $params);
        $sql = 'SELECT u.* ' . $where . $order . ' LIMIT ' . $limit;
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll() ?: [];
    }

    public function setBrowseCache(int $telegramId, array $payload): void
    {
        $this->updateUser($telegramId, [
            'browse_cache' => json_encode($payload, JSON_UNESCAPED_UNICODE),
        ]);
    }

    /** @return array<string,mixed>|null */
    public function getBrowseCache(array $user): ?array
    {
        $raw = (string)($user['browse_cache'] ?? '');
        if ($raw === '') {
            return null;
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }

    public function areFriends(int $a, int $b): bool
    {
        if ($a === $b) {
            return false;
        }
        $lo = min($a, $b);
        $hi = max($a, $b);
        $st = $this->pdo->prepare(
            "SELECT 1 FROM friendships WHERE user_a = ? AND user_b = ? AND status = 'accepted' LIMIT 1"
        );
        $st->execute([$lo, $hi]);
        return (bool)$st->fetchColumn();
    }

    public function requestFriendship(int $fromId, int $toId): string
    {
        if ($fromId === $toId) {
            return 'self';
        }
        $lo = min($fromId, $toId);
        $hi = max($fromId, $toId);
        $st = $this->pdo->prepare('SELECT * FROM friendships WHERE user_a = ? AND user_b = ? LIMIT 1');
        $st->execute([$lo, $hi]);
        $row = $st->fetch();
        if ($row) {
            if ($row['status'] === 'accepted') {
                return 'already';
            }
            if ($row['status'] === 'pending') {
                return 'pending';
            }
            $this->pdo->prepare(
                "UPDATE friendships SET status = 'pending', requested_by = ?, updated_at = NOW() WHERE id = ?"
            )->execute([$fromId, (int)$row['id']]);
            return 'ok';
        }
        $this->pdo->prepare(
            "INSERT INTO friendships (user_a, user_b, status, requested_by) VALUES (?, ?, 'pending', ?)"
        )->execute([$lo, $hi, $fromId]);
        return 'ok';
    }

    public function respondFriendship(int $responderId, int $otherId, bool $accept): bool
    {
        $lo = min($responderId, $otherId);
        $hi = max($responderId, $otherId);
        $st = $this->pdo->prepare(
            "SELECT * FROM friendships WHERE user_a = ? AND user_b = ? AND status = 'pending' LIMIT 1"
        );
        $st->execute([$lo, $hi]);
        $row = $st->fetch();
        if (!$row || (int)$row['requested_by'] === $responderId) {
            return false;
        }
        $status = $accept ? 'accepted' : 'declined';
        $this->pdo->prepare('UPDATE friendships SET status = ?, updated_at = NOW() WHERE id = ?')
            ->execute([$status, (int)$row['id']]);
        return true;
    }

    /** @return list<array> */
    public function listFriends(int $telegramId, int $limit = 40): array
    {
        $limit = max(1, min(100, $limit));
        $st = $this->pdo->prepare(
            "SELECT u.* FROM friendships f
             JOIN users u ON u.telegram_id = IF(f.user_a = ?, f.user_b, f.user_a)
             WHERE (f.user_a = ? OR f.user_b = ?) AND f.status = 'accepted'
             ORDER BY f.updated_at DESC LIMIT {$limit}"
        );
        $st->execute([$telegramId, $telegramId, $telegramId]);
        return $st->fetchAll() ?: [];
    }

    public function createFriendRoom(int $ownerId, string $title): array
    {
        $code = $this->generateRoomCode();
        $title = mb_substr(trim($title), 0, 64);
        if ($title === '') {
            $title = 'گپ دوستان';
        }
        $this->pdo->prepare(
            'INSERT INTO friend_rooms (code, owner_id, title, is_open, max_members) VALUES (?, ?, ?, 1, 50)'
        )->execute([$code, $ownerId, $title]);
        $roomId = (int)$this->pdo->lastInsertId();
        $this->pdo->prepare(
            "INSERT INTO friend_room_members (room_id, telegram_id, role) VALUES (?, ?, 'owner')"
        )->execute([$roomId, $ownerId]);
        $this->updateUser($ownerId, ['active_room_id' => $roomId, 'status' => 'room']);
        return $this->findFriendRoom($roomId) ?? ['id' => $roomId, 'code' => $code, 'title' => $title];
    }

    public function generateRoomCode(): string
    {
        for ($i = 0; $i < 10; $i++) {
            $code = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
            $st = $this->pdo->prepare('SELECT id FROM friend_rooms WHERE code = ? LIMIT 1');
            $st->execute([$code]);
            if (!$st->fetch()) {
                return $code;
            }
        }
        return strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    }

    public function findFriendRoom(int $id): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM friend_rooms WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public function findFriendRoomByCode(string $code): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM friend_rooms WHERE code = ? LIMIT 1');
        $st->execute([strtoupper(trim($code))]);
        $row = $st->fetch();
        return $row ?: null;
    }

    /** @return list<array> */
    public function listRoomMembers(int $roomId): array
    {
        $st = $this->pdo->prepare(
            'SELECT m.*, u.display_name, u.gender, u.public_code
             FROM friend_room_members m
             JOIN users u ON u.telegram_id = m.telegram_id
             WHERE m.room_id = ?
             ORDER BY m.joined_at ASC'
        );
        $st->execute([$roomId]);
        return $st->fetchAll() ?: [];
    }

    /** @return list<array> */
    public function listUserRooms(int $telegramId, int $limit = 20): array
    {
        $limit = max(1, min(50, $limit));
        $st = $this->pdo->prepare(
            "SELECT r.* FROM friend_rooms r
             JOIN friend_room_members m ON m.room_id = r.id
             WHERE m.telegram_id = ?
             ORDER BY r.created_at DESC LIMIT {$limit}"
        );
        $st->execute([$telegramId]);
        return $st->fetchAll() ?: [];
    }

    public function joinFriendRoom(int $telegramId, string $code): array
    {
        $room = $this->findFriendRoomByCode($code);
        if (!$room) {
            return ['ok' => false, 'error' => 'not_found'];
        }
        if (!(int)$room['is_open']) {
            return ['ok' => false, 'error' => 'closed'];
        }
        $members = $this->listRoomMembers((int)$room['id']);
        if (count($members) >= (int)$room['max_members']) {
            return ['ok' => false, 'error' => 'full'];
        }
        $this->pdo->prepare(
            "INSERT IGNORE INTO friend_room_members (room_id, telegram_id, role) VALUES (?, ?, 'member')"
        )->execute([(int)$room['id'], $telegramId]);
        $this->updateUser($telegramId, ['active_room_id' => (int)$room['id'], 'status' => 'room']);
        return ['ok' => true, 'room' => $room];
    }

    public function leaveFriendRoom(int $telegramId): void
    {
        $user = $this->findUser($telegramId);
        $roomId = (int)($user['active_room_id'] ?? 0);
        if ($roomId > 0) {
            $this->pdo->prepare(
                "DELETE FROM friend_room_members WHERE room_id = ? AND telegram_id = ? AND role <> 'owner'"
            )->execute([$roomId, $telegramId]);
        }
        $this->updateUser($telegramId, ['active_room_id' => null, 'status' => 'idle', 'flow' => null]);
    }

    public function enterFriendRoom(int $telegramId, int $roomId): bool
    {
        $st = $this->pdo->prepare(
            'SELECT 1 FROM friend_room_members WHERE room_id = ? AND telegram_id = ? LIMIT 1'
        );
        $st->execute([$roomId, $telegramId]);
        if (!$st->fetchColumn()) {
            return false;
        }
        $this->updateUser($telegramId, ['active_room_id' => $roomId, 'status' => 'room', 'flow' => null]);
        return true;
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

    public function setCoinsAbsolute(int $telegramId, int $coins, string $reason = 'admin_set'): bool
    {
        $user = $this->findUser($telegramId);
        if (!$user) {
            return false;
        }
        $coins = max(0, $coins);
        $delta = $coins - (int)$user['coins'];
        $this->pdo->prepare('UPDATE users SET coins = ? WHERE telegram_id = ?')->execute([$coins, $telegramId]);
        if ($delta !== 0) {
            $this->pdo->prepare(
                'INSERT INTO coin_transactions (user_id, amount, reason, meta) VALUES (?, ?, ?, ?)'
            )->execute([(int)$user['id'], $delta, $reason, null]);
        }
        return true;
    }

    public function deleteUserHard(int $telegramId): bool
    {
        $user = $this->findUser($telegramId);
        if (!$user) {
            return false;
        }
        $uid = (int)$user['id'];
        $pdo = $this->pdo;
        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM coin_transactions WHERE user_id = ?')->execute([$uid]);
            $pdo->prepare('DELETE FROM user_blocks WHERE blocker_id = ? OR blocked_id = ?')->execute([$telegramId, $telegramId]);
            $pdo->prepare('DELETE FROM contact_requests WHERE from_id = ? OR to_id = ?')->execute([$telegramId, $telegramId]);
            $pdo->prepare('DELETE FROM reports WHERE reporter_id = ? OR reported_id = ?')->execute([$telegramId, $telegramId]);
            $pdo->prepare("UPDATE chats SET status = 'ended', ended_at = NOW() WHERE status = 'active' AND (user_a = ? OR user_b = ?)")
                ->execute([$telegramId, $telegramId]);
            $pdo->prepare('DELETE FROM users WHERE telegram_id = ?')->execute([$telegramId]);
            $pdo->commit();
            return true;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function createAdminSession(int $telegramId, int $hours = 12): void
    {
        $hours = max(1, min(72, $hours));
        $this->pdo->prepare(
            'INSERT INTO admin_sessions (telegram_id, logged_in_at, expires_at)
             VALUES (?, NOW(), DATE_ADD(NOW(), INTERVAL ? HOUR))
             ON DUPLICATE KEY UPDATE logged_in_at = NOW(), expires_at = DATE_ADD(NOW(), INTERVAL ? HOUR)'
        )->execute([$telegramId, $hours, $hours]);
    }

    public function destroyAdminSession(int $telegramId): void
    {
        $this->pdo->prepare('DELETE FROM admin_sessions WHERE telegram_id = ?')->execute([$telegramId]);
    }

    public function hasValidAdminSession(int $telegramId): bool
    {
        $st = $this->pdo->prepare(
            'SELECT 1 FROM admin_sessions WHERE telegram_id = ? AND expires_at > NOW() LIMIT 1'
        );
        $st->execute([$telegramId]);
        return (bool)$st->fetchColumn();
    }

    /** @return list<array> */
    public function recentUsers(int $limit = 10): array
    {
        $limit = max(1, min(30, $limit));
        $st = $this->pdo->query(
            "SELECT telegram_id, display_name, public_code, status, coins, created_at
             FROM users ORDER BY id DESC LIMIT {$limit}"
        );
        return $st->fetchAll() ?: [];
    }

    /** @return list<array> */
    public function bannedUsers(int $limit = 20): array
    {
        $limit = max(1, min(50, $limit));
        $st = $this->pdo->query(
            "SELECT telegram_id, display_name, public_code, coins, updated_at
             FROM users WHERE status = 'banned' ORDER BY updated_at DESC LIMIT {$limit}"
        );
        return $st->fetchAll() ?: [];
    }

    public function countReports(): int
    {
        return (int)$this->pdo->query('SELECT COUNT(*) FROM reports')->fetchColumn();
    }

    /** @return list<array> */
    public function recentReports(int $limit = 15): array
    {
        $limit = max(1, min(50, $limit));
        $st = $this->pdo->query(
            "SELECT reporter_id, reported_id, reason, created_at FROM reports ORDER BY id DESC LIMIT {$limit}"
        );
        return $st->fetchAll() ?: [];
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
