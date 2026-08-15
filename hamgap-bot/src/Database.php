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

    public function upsertUser(int $telegramId, ?string $username, ?string $firstName, int $welcomeCoins = 35): array
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

        $welcomeCoins = max(0, $welcomeCoins);
        $display = $this->generateDisplayName();
        $ref = $this->generateReferralCode();
        $pub = $this->generatePublicCode();
        $st = $this->pdo->prepare(
            'INSERT INTO users (telegram_id, username, first_name, coins, display_name, referral_code, public_code, last_seen_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
        );
        $st->execute([$telegramId, $username, $firstName, $welcomeCoins, $display, $ref, $pub]);
        $user = $this->findUser($telegramId);
        if (!$user) {
            throw new RuntimeException('Failed to create user');
        }
        if ($welcomeCoins > 0) {
            $this->pdo->prepare(
                'INSERT INTO coin_transactions (user_id, amount, reason) VALUES (?, ?, ?)'
            )->execute([(int)$user['id'], $welcomeCoins, 'welcome_gift']);
        }
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

    /** @param list<int> $telegramIds @return list<array> */
    public function findUsersByTelegramIds(array $telegramIds): array
    {
        $telegramIds = array_values(array_unique(array_filter(array_map('intval', $telegramIds))));
        if ($telegramIds === []) {
            return [];
        }
        $in = implode(',', array_fill(0, count($telegramIds), '?'));
        $st = $this->pdo->prepare("SELECT * FROM users WHERE telegram_id IN ({$in})");
        $st->execute($telegramIds);
        $rows = $st->fetchAll() ?: [];
        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row['telegram_id']] = $row;
        }
        $ordered = [];
        foreach ($telegramIds as $id) {
            if (isset($map[$id])) {
                $ordered[] = $map[$id];
            }
        }
        return $ordered;
    }

    public function findByPublicCode(string $code): ?array
    {
        $code = strtoupper(trim($code));
        $st = $this->pdo->prepare('SELECT * FROM users WHERE public_code = ? LIMIT 1');
        $st->execute([$code]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public function addChatWaitNotice(int $waiterId, int $targetId): string
    {
        if ($waiterId === $targetId) {
            return 'self';
        }
        $st = $this->pdo->prepare(
            'SELECT status FROM chat_wait_notices WHERE waiter_id = ? AND target_id = ? LIMIT 1'
        );
        $st->execute([$waiterId, $targetId]);
        $cur = $st->fetchColumn();
        if ($cur === 'pending') {
            return 'already';
        }
        $this->pdo->prepare(
            'INSERT INTO chat_wait_notices (waiter_id, target_id, status) VALUES (?, ?, \'pending\')
             ON DUPLICATE KEY UPDATE status = \'pending\', notified_at = NULL, created_at = CURRENT_TIMESTAMP'
        )->execute([$waiterId, $targetId]);
        return 'ok';
    }

    public function hasPendingChatWait(int $waiterId, int $targetId): bool
    {
        $st = $this->pdo->prepare(
            "SELECT 1 FROM chat_wait_notices WHERE waiter_id = ? AND target_id = ? AND status = 'pending' LIMIT 1"
        );
        $st->execute([$waiterId, $targetId]);
        return (bool)$st->fetchColumn();
    }

    /** @return list<array{waiter_id:int,target_id:int,id:int}> */
    public function listPendingChatWaiters(int $targetId): array
    {
        $st = $this->pdo->prepare(
            "SELECT id, waiter_id, target_id FROM chat_wait_notices
             WHERE target_id = ? AND status = 'pending'"
        );
        $st->execute([$targetId]);
        return $st->fetchAll() ?: [];
    }

    public function markChatWaitNotified(int $id): void
    {
        $this->pdo->prepare(
            "UPDATE chat_wait_notices SET status = 'notified', notified_at = NOW() WHERE id = ?"
        )->execute([$id]);
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

    public function unblockUser(int $blockerId, int $blockedId): void
    {
        $this->pdo->prepare(
            'DELETE FROM user_blocks WHERE blocker_id = ? AND blocked_id = ?'
        )->execute([$blockerId, $blockedId]);
    }

    /** @return list<array> */
    public function listBlockedUsers(int $blockerId, int $limit = 30): array
    {
        $limit = max(1, min(50, $limit));
        $st = $this->pdo->prepare(
            "SELECT u.telegram_id, u.display_name, u.public_code, b.created_at
             FROM user_blocks b
             JOIN users u ON u.telegram_id = b.blocked_id
             WHERE b.blocker_id = ?
             ORDER BY b.id DESC LIMIT {$limit}"
        );
        $st->execute([$blockerId]);
        return $st->fetchAll() ?: [];
    }

    public function addLike(int $fromId, int $toId): string
    {
        if ($fromId === $toId) {
            return 'self';
        }
        try {
            $this->pdo->prepare(
                'INSERT INTO user_likes (from_id, to_id) VALUES (?, ?)'
            )->execute([$fromId, $toId]);
            $this->pdo->prepare(
                'UPDATE users SET likes_count = likes_count + 1 WHERE telegram_id = ?'
            )->execute([$toId]);
            return 'ok';
        } catch (Throwable $e) {
            return 'already';
        }
    }

    public function countLikes(int $telegramId): int
    {
        $st = $this->pdo->prepare('SELECT likes_count FROM users WHERE telegram_id = ? LIMIT 1');
        $st->execute([$telegramId]);
        $n = $st->fetchColumn();
        if ($n !== false) {
            return (int)$n;
        }
        $st = $this->pdo->prepare('SELECT COUNT(*) FROM user_likes WHERE to_id = ?');
        $st->execute([$telegramId]);
        return (int)$st->fetchColumn();
    }

    public function createContactRequest(int $fromId, int $toId, string $kind, ?string $payload = null): int
    {
        $this->pdo->prepare(
            'INSERT INTO contact_requests (from_id, to_id, kind, payload, status) VALUES (?, ?, ?, ?, ?)'
        )->execute([$fromId, $toId, $kind, $payload, 'pending']);
        return (int)$this->pdo->lastInsertId();
    }

    public function findContactRequest(int $id): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM contact_requests WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public function updateContactRequest(int $id, array $fields): void
    {
        if (!$fields) {
            return;
        }
        $sets = [];
        $vals = [];
        foreach ($fields as $k => $v) {
            $sets[] = "`{$k}` = ?";
            $vals[] = $v;
        }
        $vals[] = $id;
        $this->pdo->prepare('UPDATE contact_requests SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($vals);
    }

    /** @return list<array> */
    public function listIncomingRequests(int $toId, array $statuses = ['pending', 'held']): array
    {
        if (!$statuses) {
            return [];
        }
        $in = implode(',', array_fill(0, count($statuses), '?'));
        $st = $this->pdo->prepare(
            "SELECT r.*, u.display_name, u.public_code, u.gender, u.age, u.city
             FROM contact_requests r
             JOIN users u ON u.telegram_id = r.from_id
             WHERE r.to_id = ? AND r.kind = 'request' AND r.status IN ({$in})
             ORDER BY FIELD(r.status,'pending','held'), r.id DESC
             LIMIT 30"
        );
        $st->execute(array_merge([$toId], $statuses));
        return $st->fetchAll() ?: [];
    }

    public function addReport(int $reporterId, int $reportedId, string $reason): int
    {
        $this->pdo->prepare(
            'INSERT INTO reports (reporter_id, reported_id, reason) VALUES (?, ?, ?)'
        )->execute([$reporterId, $reportedId, $reason]);
        $st = $this->pdo->prepare('SELECT COUNT(*) FROM reports WHERE reported_id = ?');
        $st->execute([$reportedId]);
        return (int)$st->fetchColumn();
    }

    public function banForReports(int $telegramId, string $reason = 'report_threshold'): void
    {
        $this->updateUser($telegramId, [
            'status' => 'banned',
            'ban_reason' => $reason,
            'partner_id' => null,
            'search_pref' => null,
            'flow' => null,
            'active_room_id' => null,
        ]);
    }

    public function openPrivateChat(int $a, int $b, string $matchType = 'request'): void
    {
        // Wipe any previous active chats for both — no history kept
        $this->wipeUserChats($a);
        $this->wipeUserChats($b);
        $this->pdo->prepare(
            "INSERT INTO chats (user_a, user_b, match_type, status) VALUES (?, ?, ?, 'active')"
        )->execute([$a, $b, $matchType]);
        $this->updateUser($a, ['status' => 'chatting', 'partner_id' => $b, 'search_pref' => null, 'flow' => null]);
        $this->updateUser($b, ['status' => 'chatting', 'partner_id' => $a, 'search_pref' => null, 'flow' => null]);
    }

    public function wipeUserChats(int $telegramId): void
    {
        $this->pdo->prepare(
            'DELETE FROM chats WHERE user_a = ? OR user_b = ?'
        )->execute([$telegramId, $telegramId]);
        $u = $this->findUser($telegramId);
        if ($u && ($u['status'] ?? '') === 'chatting') {
            $this->updateUser($telegramId, ['status' => 'idle', 'partner_id' => null]);
        }
    }

    public function wipeChatPair(int $a, int $b): void
    {
        $this->pdo->prepare(
            'DELETE FROM chats WHERE (user_a = ? AND user_b = ?) OR (user_a = ? AND user_b = ?)'
        )->execute([$a, $b, $b, $a]);
    }

    public function wipeFriendRoomCompletely(int $roomId): array
    {
        $members = $this->listRoomMembers($roomId);
        $this->pdo->prepare('DELETE FROM friend_room_members WHERE room_id = ?')->execute([$roomId]);
        $this->pdo->prepare('DELETE FROM friend_rooms WHERE id = ?')->execute([$roomId]);
        foreach ($members as $m) {
            $tid = (int)$m['telegram_id'];
            $u = $this->findUser($tid);
            if ($u && (int)($u['active_room_id'] ?? 0) === $roomId) {
                $this->updateUser($tid, ['active_room_id' => null, 'status' => 'idle', 'flow' => null]);
            }
        }
        return $members;
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
        if (!empty($filters['occupation']) && Occupation::isValid((string)$filters['occupation'])) {
            $sql .= ' AND u.occupation = ? AND COALESCE(u.show_occupation, 1) = 1';
            $params[] = $filters['occupation'];
        }
        if (!empty($filters['has_occupation'])) {
            $sql .= " AND u.occupation IS NOT NULL AND u.occupation <> '' AND COALESCE(u.show_occupation, 1) = 1";
        }
        if (!empty($filters['vip_only'])) {
            $sql .= ' AND u.vip_until IS NOT NULL AND u.vip_until > NOW()';
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

    public const ALBUM_MAX_PHOTOS = 3;

    /** @return list<array{id:int,telegram_id:int,file_id:string}> */
    public function listAlbumPhotos(int $telegramId): array
    {
        $st = $this->pdo->prepare(
            'SELECT id, telegram_id, file_id FROM user_album_photos
             WHERE telegram_id = ? ORDER BY id ASC'
        );
        $st->execute([$telegramId]);
        return $st->fetchAll() ?: [];
    }

    public function countAlbumPhotos(int $telegramId): int
    {
        $st = $this->pdo->prepare('SELECT COUNT(*) FROM user_album_photos WHERE telegram_id = ?');
        $st->execute([$telegramId]);
        return (int)$st->fetchColumn();
    }

    public function addAlbumPhoto(int $telegramId, string $fileId): string
    {
        $fileId = trim($fileId);
        if ($fileId === '') {
            return 'empty';
        }
        if ($this->countAlbumPhotos($telegramId) >= self::ALBUM_MAX_PHOTOS) {
            return 'full';
        }
        $this->pdo->prepare(
            'INSERT INTO user_album_photos (telegram_id, file_id) VALUES (?, ?)'
        )->execute([$telegramId, $fileId]);
        return 'ok';
    }

    public function deleteAlbumPhoto(int $telegramId, int $photoId): bool
    {
        $st = $this->pdo->prepare(
            'DELETE FROM user_album_photos WHERE id = ? AND telegram_id = ?'
        );
        $st->execute([$photoId, $telegramId]);
        return $st->rowCount() > 0;
    }

    public function getAlbumPhoto(int $photoId): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM user_album_photos WHERE id = ? LIMIT 1');
        $st->execute([$photoId]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public function createFriendRoom(int $ownerId, string $title, int $maxMembers = 50, string $kind = 'friends'): array
    {
        $code = $this->generateRoomCode();
        $title = mb_substr(trim($title), 0, 64);
        if ($title === '') {
            $title = 'گپ دوستان';
        }
        $maxMembers = max(2, min(50, $maxMembers));
        $kind = preg_replace('/[^a-z]/', '', strtolower($kind)) ?: 'friends';
        $this->pdo->prepare(
            'INSERT INTO friend_rooms (code, owner_id, title, is_open, max_members, room_kind) VALUES (?, ?, ?, 1, ?, ?)'
        )->execute([$code, $ownerId, $title, $maxMembers, $kind]);
        $roomId = (int)$this->pdo->lastInsertId();
        $this->pdo->prepare(
            "INSERT INTO friend_room_members (room_id, telegram_id, role) VALUES (?, ?, 'owner')"
        )->execute([$roomId, $ownerId]);
        $this->updateUser($ownerId, ['active_room_id' => $roomId, 'status' => 'room', 'partner_id' => null, 'search_pref' => null, 'chat_mode' => null]);
        return $this->findFriendRoom($roomId) ?? ['id' => $roomId, 'code' => $code, 'title' => $title, 'room_kind' => $kind];
    }

    /**
     * Dedicated private page after chat-request accept (2–4 people).
     * @return array{ok:bool,room?:array,error?:string}
     */
    public function openPrivateRequestRoom(int $a, int $b, int $entryCost = 2): array
    {
        $entryCost = max(0, $entryCost);
        $ua = $this->findUser($a);
        $ub = $this->findUser($b);
        if (!$ua || !$ub) {
            return ['ok' => false, 'error' => 'missing'];
        }
        if ($entryCost > 0) {
            if ((int)$ua['coins'] < $entryCost || (int)$ub['coins'] < $entryCost) {
                return ['ok' => false, 'error' => 'no_coins'];
            }
        }
        $this->wipeUserChats($a);
        $this->wipeUserChats($b);
        if (!empty($ua['active_room_id'])) {
            $this->leaveFriendRoom($a);
        }
        if (!empty($ub['active_room_id'])) {
            $this->leaveFriendRoom($b);
        }
        if ($entryCost > 0) {
            if (!$this->spendCoins($a, $entryCost, 'private_room_entry', (string)$b)) {
                return ['ok' => false, 'error' => 'no_coins'];
            }
            if (!$this->spendCoins($b, $entryCost, 'private_room_entry', (string)$a)) {
                $this->addCoins($a, $entryCost, 'private_room_entry_refund', 'peer_fail');
                return ['ok' => false, 'error' => 'no_coins'];
            }
        }
        $dnA = (string)($ua['display_name'] ?? 'کاربر');
        $dnB = (string)($ub['display_name'] ?? 'کاربر');
        $title = 'چت خصوصی ' . mb_substr($dnA, 0, 12) . ' · ' . mb_substr($dnB, 0, 12);
        $room = $this->createFriendRoom($a, $title, 4, 'private');
        $roomId = (int)$room['id'];
        $this->pdo->prepare(
            "INSERT IGNORE INTO friend_room_members (room_id, telegram_id, role) VALUES (?, ?, 'member')"
        )->execute([$roomId, $b]);
        $this->updateUser($b, ['active_room_id' => $roomId, 'status' => 'room', 'partner_id' => null, 'flow' => null]);
        return ['ok' => true, 'room' => $this->findFriendRoom($roomId) ?? $room];
    }

    public function addMemberToPrivateRoom(int $roomId, int $inviterId, int $targetId, int $addCost = 2): array
    {
        $room = $this->findFriendRoom($roomId);
        if (!$room || (string)($room['room_kind'] ?? '') !== 'private') {
            return ['ok' => false, 'error' => 'not_private'];
        }
        $members = $this->listRoomMembers($roomId);
        if (count($members) >= (int)$room['max_members']) {
            return ['ok' => false, 'error' => 'full'];
        }
        foreach ($members as $m) {
            if ((int)$m['telegram_id'] === $targetId) {
                return ['ok' => false, 'error' => 'already'];
            }
        }
        $isMember = false;
        foreach ($members as $m) {
            if ((int)$m['telegram_id'] === $inviterId) {
                $isMember = true;
                break;
            }
        }
        if (!$isMember) {
            return ['ok' => false, 'error' => 'not_member'];
        }
        $addCost = max(0, $addCost);
        if ($addCost > 0 && !$this->spendCoins($inviterId, $addCost, 'private_room_add', (string)$targetId)) {
            return ['ok' => false, 'error' => 'no_coins'];
        }
        $target = $this->findUser($targetId);
        if (!$target || ($target['status'] ?? '') === 'banned') {
            if ($addCost > 0) {
                $this->addCoins($inviterId, $addCost, 'private_room_add_refund', 'bad_target');
            }
            return ['ok' => false, 'error' => 'bad_target'];
        }
        // Leave previous room / chat
        if (($target['status'] ?? '') === 'room' && !empty($target['active_room_id'])) {
            $this->leaveFriendRoom($targetId);
        }
        if (($target['status'] ?? '') === 'chatting') {
            $this->wipeUserChats($targetId);
        }
        $this->pdo->prepare(
            "INSERT IGNORE INTO friend_room_members (room_id, telegram_id, role) VALUES (?, ?, 'member')"
        )->execute([$roomId, $targetId]);
        $this->updateUser($targetId, [
            'active_room_id' => $roomId,
            'status' => 'room',
            'partner_id' => null,
            'search_pref' => null,
            'chat_mode' => null,
            'flow' => null,
        ]);
        return ['ok' => true, 'room' => $room];
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

    public function leaveFriendRoom(int $telegramId): ?array
    {
        $user = $this->findUser($telegramId);
        $roomId = (int)($user['active_room_id'] ?? 0);
        if ($roomId <= 0) {
            $this->updateUser($telegramId, ['active_room_id' => null, 'status' => 'idle', 'flow' => null]);
            return null;
        }
        $room = $this->findFriendRoom($roomId);
        $isOwner = $room && (int)$room['owner_id'] === $telegramId;
        if ($isOwner) {
            $members = $this->wipeFriendRoomCompletely($roomId);
            return ['closed' => true, 'room' => $room, 'members' => $members];
        }
        $this->pdo->prepare(
            'DELETE FROM friend_room_members WHERE room_id = ? AND telegram_id = ?'
        )->execute([$roomId, $telegramId]);
        $this->updateUser($telegramId, ['active_room_id' => null, 'status' => 'idle', 'flow' => null]);
        return ['closed' => false, 'room' => $room, 'members' => []];
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

    public function createPaymentInvoice(
        int $telegramId,
        int $packCoins,
        int $baseAmount,
        int $ttlMinutes = 30,
        string $product = 'coins'
    ): array {
        $ttlMinutes = max(5, min(180, $ttlMinutes));
        $product = preg_replace('/[^a-z0-9_]/', '', strtolower($product)) ?: 'coins';
        $amount = $this->allocateUniqueInvoiceAmount($baseAmount);
        $invoiceNo = $this->generateInvoiceNo();
        $st = $this->pdo->prepare(
            "INSERT INTO payment_invoices
             (invoice_no, telegram_id, pack_coins, base_amount, amount_toman, status, expires_at, product)
             VALUES (?, ?, ?, ?, ?, 'pending', DATE_ADD(NOW(), INTERVAL ? MINUTE), ?)"
        );
        $st->execute([$invoiceNo, $telegramId, $packCoins, $baseAmount, $amount, $ttlMinutes, $product]);
        $id = (int)$this->pdo->lastInsertId();
        return $this->findPaymentInvoice($id) ?? [
            'id' => $id,
            'invoice_no' => $invoiceNo,
            'telegram_id' => $telegramId,
            'pack_coins' => $packCoins,
            'base_amount' => $baseAmount,
            'amount_toman' => $amount,
            'status' => 'pending',
            'product' => $product,
        ];
    }

    private function allocateUniqueInvoiceAmount(int $baseAmount): int
    {
        for ($i = 0; $i < 40; $i++) {
            $suffix = random_int(101, 989);
            $amount = $baseAmount + $suffix;
            $st = $this->pdo->prepare(
                "SELECT 1 FROM payment_invoices
                 WHERE amount_toman = ?
                   AND status IN ('pending','awaiting_receipt','submitted')
                   AND expires_at > NOW()
                 LIMIT 1"
            );
            $st->execute([$amount]);
            if (!$st->fetchColumn()) {
                return $amount;
            }
        }
        return $baseAmount + random_int(1000, 8999);
    }

    public function generateInvoiceNo(): string
    {
        for ($i = 0; $i < 8; $i++) {
            $no = (string)random_int(100000, 999999);
            $st = $this->pdo->prepare('SELECT id FROM payment_invoices WHERE invoice_no = ? LIMIT 1');
            $st->execute([$no]);
            if (!$st->fetch()) {
                return $no;
            }
        }
        return (string)random_int(1000000, 9999999);
    }

    public function findPaymentInvoice(int $id): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM payment_invoices WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public function findPaymentInvoiceByNo(string $no): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM payment_invoices WHERE invoice_no = ? LIMIT 1');
        $st->execute([$no]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public function updatePaymentInvoice(int $id, array $fields): void
    {
        if (!$fields) {
            return;
        }
        $sets = [];
        $vals = [];
        foreach ($fields as $k => $v) {
            $sets[] = "`{$k}` = ?";
            $vals[] = $v;
        }
        $vals[] = $id;
        $this->pdo->prepare('UPDATE payment_invoices SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($vals);
    }

    public function isInvoiceOpen(array $inv): bool
    {
        $status = (string)($inv['status'] ?? '');
        if (!in_array($status, ['pending', 'awaiting_receipt', 'submitted'], true)) {
            return false;
        }
        if (!empty($inv['expires_at'])) {
            $ts = strtotime((string)$inv['expires_at']);
            if ($ts && $ts < time()) {
                return false;
            }
        }
        return true;
    }

    public function expireOldInvoices(): void
    {
        $this->pdo->exec(
            "UPDATE payment_invoices
             SET status = 'expired'
             WHERE status IN ('pending','awaiting_receipt')
               AND expires_at < NOW()"
        );
    }

    /** @return list<array> */
    public function listPendingPaymentInvoices(int $limit = 15): array
    {
        $limit = max(1, min(50, $limit));
        $this->expireOldInvoices();
        return $this->pdo->query(
            "SELECT * FROM payment_invoices
             WHERE status IN ('pending','awaiting_receipt','submitted')
             ORDER BY FIELD(status,'submitted','awaiting_receipt','pending'), id DESC
             LIMIT {$limit}"
        )->fetchAll() ?: [];
    }

    public function approvePaymentInvoice(int $invoiceId, int $reviewerTid): array
    {
        $pdo = $this->pdo;
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare('SELECT * FROM payment_invoices WHERE id = ? FOR UPDATE');
            $st->execute([$invoiceId]);
            $inv = $st->fetch();
            if (!$inv) {
                $pdo->rollBack();
                return ['ok' => false, 'error' => 'not_found'];
            }
            if ((string)$inv['status'] === 'approved') {
                $pdo->rollBack();
                return ['ok' => false, 'error' => 'already'];
            }
            if (!in_array((string)$inv['status'], ['pending', 'awaiting_receipt', 'submitted'], true)) {
                $pdo->rollBack();
                return ['ok' => false, 'error' => 'closed'];
            }
            $pdo->prepare(
                "UPDATE payment_invoices
                 SET status = 'approved', reviewed_by = ?, reviewed_at = NOW()
                 WHERE id = ?"
            )->execute([$reviewerTid, $invoiceId]);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
        $coins = (int)$inv['pack_coins'];
        $tid = (int)$inv['telegram_id'];
        $product = (string)($inv['product'] ?? 'coins');
        if ($product === 'vip30' || $product === 'vip') {
            $days = max(1, (int)($coins > 0 ? $coins : 30));
            // pack_coins stores VIP days when product=vip30
            if ($product === 'vip30' && $coins <= 0) {
                $days = 30;
            }
            $this->extendVip($tid, $days);
            $this->updateUser($tid, ['vip_request' => null]);
            return [
                'ok' => true,
                'type' => 'vip',
                'days' => $days,
                'invoice' => $this->findPaymentInvoice($invoiceId),
                'coins' => 0,
                'telegram_id' => $tid,
            ];
        }
        if ($coins > 0) {
            $this->addCoins($tid, $coins, 'card_topup', (string)$inv['invoice_no']);
        }
        return ['ok' => true, 'type' => 'coins', 'invoice' => $this->findPaymentInvoice($invoiceId), 'coins' => $coins, 'telegram_id' => $tid];
    }

    public static function isVipActive(?array $user): bool
    {
        if (!$user) {
            return false;
        }
        $until = (string)($user['vip_until'] ?? '');
        if ($until === '') {
            return false;
        }
        $ts = strtotime($until);
        return $ts !== false && $ts > time();
    }

    public function extendVip(int $telegramId, int $days): void
    {
        $days = max(1, min(365, $days));
        $user = $this->findUser($telegramId);
        if (!$user) {
            return;
        }
        $base = time();
        $cur = (string)($user['vip_until'] ?? '');
        if ($cur !== '') {
            $ts = strtotime($cur);
            if ($ts && $ts > $base) {
                $base = $ts;
            }
        }
        $until = date('Y-m-d H:i:s', $base + ($days * 86400));
        $this->updateUser($telegramId, ['vip_until' => $until, 'vip_request' => null]);
    }

    public function countReportsAgainst(int $telegramId): int
    {
        $st = $this->pdo->prepare('SELECT COUNT(*) FROM reports WHERE reported_id = ?');
        $st->execute([$telegramId]);
        return (int)$st->fetchColumn();
    }

    public function accountAgeDays(array $user): int
    {
        $created = (string)($user['created_at'] ?? '');
        if ($created === '') {
            return 0;
        }
        $ts = strtotime($created);
        if (!$ts) {
            return 0;
        }
        return max(0, (int)floor((time() - $ts) / 86400));
    }

    /** @return list<array> */
    public function listPendingVipRequests(int $limit = 20): array
    {
        $limit = max(1, min(50, $limit));
        $st = $this->pdo->query(
            "SELECT * FROM users WHERE vip_request = 'pending' ORDER BY id DESC LIMIT {$limit}"
        );
        return $st ? ($st->fetchAll() ?: []) : [];
    }

    public function rejectPaymentInvoice(int $invoiceId, int $reviewerTid): array
    {
        $inv = $this->findPaymentInvoice($invoiceId);
        if (!$inv) {
            return ['ok' => false, 'error' => 'not_found'];
        }
        if ((string)$inv['status'] === 'approved') {
            return ['ok' => false, 'error' => 'already'];
        }
        $this->updatePaymentInvoice($invoiceId, [
            'status' => 'rejected',
            'reviewed_by' => $reviewerTid,
            'reviewed_at' => date('Y-m-d H:i:s'),
        ]);
        return ['ok' => true, 'invoice' => $this->findPaymentInvoice($invoiceId)];
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

    public function getSupportStaff(int $telegramId): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM support_staff WHERE telegram_id = ? LIMIT 1');
        $st->execute([$telegramId]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public function isActiveSupportStaff(int $telegramId): bool
    {
        $row = $this->getSupportStaff($telegramId);
        return $row !== null && (int)($row['is_active'] ?? 0) === 1;
    }

    public function findSupportTicket(int $id): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM support_tickets WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public function findOpenSupportTicketForUser(int $userTelegramId): ?array
    {
        $st = $this->pdo->prepare(
            "SELECT * FROM support_tickets
             WHERE user_telegram_id = ? AND status = 'open'
             ORDER BY id DESC LIMIT 1"
        );
        $st->execute([$userTelegramId]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public function findAssignedSupportTicketForStaff(int $staffTelegramId): ?array
    {
        $st = $this->pdo->prepare(
            "SELECT * FROM support_tickets
             WHERE staff_telegram_id = ? AND status = 'open'
             ORDER BY id DESC LIMIT 1"
        );
        $st->execute([$staffTelegramId]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public function openSupportTicket(int $userTelegramId, string $firstMessage): array
    {
        $existing = $this->findOpenSupportTicketForUser($userTelegramId);
        if ($existing) {
            $this->pdo->prepare(
                'UPDATE support_tickets SET last_message = ?, updated_at = NOW() WHERE id = ?'
            )->execute([$firstMessage, (int)$existing['id']]);
            return $this->findSupportTicket((int)$existing['id']) ?? $existing;
        }
        $this->pdo->prepare(
            "INSERT INTO support_tickets (user_telegram_id, status, last_message) VALUES (?, 'open', ?)"
        )->execute([$userTelegramId, $firstMessage]);
        $id = (int)$this->pdo->lastInsertId();
        return $this->findSupportTicket($id) ?? [
            'id' => $id,
            'user_telegram_id' => $userTelegramId,
            'staff_telegram_id' => null,
            'status' => 'open',
            'last_message' => $firstMessage,
        ];
    }

    public function assignSupportTicket(int $ticketId, int $staffTelegramId): bool
    {
        $st = $this->pdo->prepare(
            "UPDATE support_tickets
             SET staff_telegram_id = ?, updated_at = NOW()
             WHERE id = ? AND status = 'open' AND staff_telegram_id IS NULL"
        );
        $st->execute([$staffTelegramId, $ticketId]);
        return $st->rowCount() > 0;
    }

    public function touchSupportTicketMessage(int $ticketId, string $message): void
    {
        $this->pdo->prepare(
            'UPDATE support_tickets SET last_message = ?, updated_at = NOW() WHERE id = ?'
        )->execute([$message, $ticketId]);
    }

    public function closeSupportTicket(int $ticketId): void
    {
        $this->pdo->prepare(
            "UPDATE support_tickets SET status = 'closed', updated_at = NOW() WHERE id = ?"
        )->execute([$ticketId]);
    }

    /** @return list<int> */
    public function listPanelAdminIds(): array
    {
        $ids = [];
        try {
            $rows = $this->pdo->query('SELECT telegram_id FROM users WHERE is_admin = 1')->fetchAll() ?: [];
            foreach ($rows as $r) {
                $ids[] = (int)$r['telegram_id'];
            }
        } catch (Throwable $e) {
        }
        return array_values(array_unique(array_filter($ids)));
    }

    public function addSupportStaff(int $telegramId, ?string $label = null): void
    {
        $this->pdo->prepare(
            'INSERT INTO support_staff (telegram_id, display_label, is_active) VALUES (?, ?, 1)
             ON DUPLICATE KEY UPDATE display_label = VALUES(display_label), is_active = 1'
        )->execute([$telegramId, $label]);
    }

    public function setSupportStaffActive(int $telegramId, bool $active): void
    {
        $this->pdo->prepare('UPDATE support_staff SET is_active = ? WHERE telegram_id = ?')
            ->execute([$active ? 1 : 0, $telegramId]);
    }

    public function activateSupportStaff(int $telegramId): void
    {
        // Ensure row exists and is active (re-add if missing).
        $this->addSupportStaff($telegramId);
        $this->setSupportStaffActive($telegramId, true);
    }

    public function deactivateSupportStaff(int $telegramId): void
    {
        $this->setSupportStaffActive($telegramId, false);
    }

    public function findByUsername(string $username): ?array
    {
        $username = ltrim(trim($username), '@');
        if ($username === '') {
            return null;
        }
        $st = $this->pdo->prepare('SELECT * FROM users WHERE LOWER(username) = LOWER(?) LIMIT 1');
        $st->execute([$username]);
        $row = $st->fetch();
        return $row ?: null;
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
            // Owned friend rooms → full wipe outside FK constraints
            $owned = $pdo->prepare('SELECT id FROM friend_rooms WHERE owner_id = ?');
            $owned->execute([$telegramId]);
            $roomIds = $owned->fetchAll(PDO::FETCH_COLUMN) ?: [];

            $pdo->prepare('DELETE FROM coin_transactions WHERE user_id = ?')->execute([$uid]);
            $pdo->prepare('DELETE FROM user_blocks WHERE blocker_id = ? OR blocked_id = ?')
                ->execute([$telegramId, $telegramId]);
            $pdo->prepare('DELETE FROM contact_requests WHERE from_id = ? OR to_id = ?')
                ->execute([$telegramId, $telegramId]);
            $pdo->prepare('DELETE FROM reports WHERE reporter_id = ? OR reported_id = ?')
                ->execute([$telegramId, $telegramId]);
            try {
                $pdo->prepare('DELETE FROM user_likes WHERE from_id = ? OR to_id = ?')
                    ->execute([$telegramId, $telegramId]);
            } catch (Throwable $e) {
            }
            try {
                $pdo->prepare('DELETE FROM friendships WHERE user_a = ? OR user_b = ?')
                    ->execute([$telegramId, $telegramId]);
            } catch (Throwable $e) {
            }
            try {
                $pdo->prepare('DELETE FROM user_album_photos WHERE telegram_id = ?')->execute([$telegramId]);
            } catch (Throwable $e) {
            }
            try {
                $pdo->prepare('DELETE FROM chat_wait_notices WHERE waiter_id = ? OR target_id = ?')
                    ->execute([$telegramId, $telegramId]);
            } catch (Throwable $e) {
            }
            try {
                $pdo->prepare('DELETE FROM payment_invoices WHERE telegram_id = ?')->execute([$telegramId]);
            } catch (Throwable $e) {
            }
            try {
                $pdo->prepare('DELETE FROM support_tickets WHERE user_telegram_id = ?')->execute([$telegramId]);
            } catch (Throwable $e) {
            }
            try {
                $pdo->prepare('DELETE FROM admin_sessions WHERE telegram_id = ?')->execute([$telegramId]);
            } catch (Throwable $e) {
            }
            try {
                $pdo->prepare('DELETE FROM friend_room_members WHERE telegram_id = ?')->execute([$telegramId]);
            } catch (Throwable $e) {
            }
            foreach ($roomIds as $rid) {
                $rid = (int)$rid;
                try {
                    $pdo->prepare('DELETE FROM friend_room_members WHERE room_id = ?')->execute([$rid]);
                    $pdo->prepare('DELETE FROM friend_rooms WHERE id = ?')->execute([$rid]);
                } catch (Throwable $e) {
                }
            }
            $pdo->prepare('DELETE FROM chats WHERE user_a = ? OR user_b = ?')
                ->execute([$telegramId, $telegramId]);
            $pdo->prepare('UPDATE users SET referred_by = NULL WHERE referred_by = ?')
                ->execute([$telegramId]);
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

    public function createAdminSession(int $telegramId, int $hours = 2): void
    {
        $hours = max(1, min(72, $hours));
        $this->pdo->prepare(
            'INSERT INTO admin_sessions (telegram_id, logged_in_at, expires_at)
             VALUES (?, NOW(), DATE_ADD(NOW(), INTERVAL ? HOUR))
             ON DUPLICATE KEY UPDATE logged_in_at = NOW(), expires_at = DATE_ADD(NOW(), INTERVAL ? HOUR)'
        )->execute([$telegramId, $hours, $hours]);
    }

    public function touchAdminSession(int $telegramId, int $hours = 2): void
    {
        if (!$this->hasValidAdminSession($telegramId)) {
            return;
        }
        $hours = max(1, min(72, $hours));
        $this->pdo->prepare(
            'UPDATE admin_sessions SET expires_at = DATE_ADD(NOW(), INTERVAL ? HOUR) WHERE telegram_id = ?'
        )->execute([$hours, $telegramId]);
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

    public function createStaffSession(int $telegramId, int $hours = 2): void
    {
        $hours = max(1, min(72, $hours));
        $sql = 'INSERT INTO staff_sessions (telegram_id, logged_in_at, expires_at)
                VALUES (?, NOW(), DATE_ADD(NOW(), INTERVAL ? HOUR))
                ON DUPLICATE KEY UPDATE logged_in_at = NOW(), expires_at = DATE_ADD(NOW(), INTERVAL ? HOUR)';
        try {
            $this->pdo->prepare($sql)->execute([$telegramId, $hours, $hours]);
        } catch (Throwable $e) {
            $this->pdo->exec(
                "CREATE TABLE IF NOT EXISTS staff_sessions (
                    telegram_id BIGINT NOT NULL,
                    logged_in_at DATETIME NOT NULL,
                    expires_at DATETIME NOT NULL,
                    PRIMARY KEY (telegram_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
            $this->pdo->prepare($sql)->execute([$telegramId, $hours, $hours]);
        }
    }

    public function touchStaffSession(int $telegramId, int $hours = 2): void
    {
        if (!$this->hasValidStaffSession($telegramId)) {
            return;
        }
        $hours = max(1, min(72, $hours));
        try {
            $this->pdo->prepare(
                'UPDATE staff_sessions SET expires_at = DATE_ADD(NOW(), INTERVAL ? HOUR) WHERE telegram_id = ?'
            )->execute([$hours, $telegramId]);
        } catch (Throwable $e) {
        }
    }

    public function destroyStaffSession(int $telegramId): void
    {
        try {
            $this->pdo->prepare('DELETE FROM staff_sessions WHERE telegram_id = ?')->execute([$telegramId]);
        } catch (Throwable $e) {
        }
    }

    public function hasValidStaffSession(int $telegramId): bool
    {
        try {
            $st = $this->pdo->prepare(
                'SELECT 1 FROM staff_sessions WHERE telegram_id = ? AND expires_at > NOW() LIMIT 1'
            );
            $st->execute([$telegramId]);
            return (bool)$st->fetchColumn();
        } catch (Throwable $e) {
            return false;
        }
    }

    /** Remaining staff session minutes; 0 if none. */
    public function staffSessionMinutesLeft(int $telegramId): int
    {
        try {
            $st = $this->pdo->prepare(
                'SELECT TIMESTAMPDIFF(MINUTE, NOW(), expires_at) FROM staff_sessions
                 WHERE telegram_id = ? AND expires_at > NOW() LIMIT 1'
            );
            $st->execute([$telegramId]);
            $m = $st->fetchColumn();
            return $m === false ? 0 : max(0, (int)$m);
        } catch (Throwable $e) {
            return 0;
        }
    }

    public function setSupportStaffPassword(int $telegramId, string $plainPassword): bool
    {
        $plainPassword = trim($plainPassword);
        if ($plainPassword === '') {
            return false;
        }
        if (!$this->getSupportStaff($telegramId)) {
            $this->addSupportStaff($telegramId);
        }
        $hash = password_hash($plainPassword, PASSWORD_DEFAULT);
        try {
            $this->pdo->prepare(
                'UPDATE support_staff SET password_hash = ?, pending_password_hash = NULL WHERE telegram_id = ?'
            )->execute([$hash, $telegramId]);
        } catch (Throwable $e) {
            // Column pending may not exist yet
            $this->pdo->prepare(
                'UPDATE support_staff SET password_hash = ? WHERE telegram_id = ?'
            )->execute([$hash, $telegramId]);
        }
        $row = $this->getSupportStaff($telegramId);
        $stored = (string)($row['password_hash'] ?? '');
        return $stored !== '' && password_verify($plainPassword, $stored);
    }

    public function setSupportStaffPendingPassword(int $telegramId, string $plainPassword): void
    {
        $hash = password_hash($plainPassword, PASSWORD_DEFAULT);
        try {
            $this->pdo->prepare(
                'UPDATE support_staff SET pending_password_hash = ? WHERE telegram_id = ?'
            )->execute([$hash, $telegramId]);
        } catch (Throwable $e) {
            // Fallback: keep in password flow via temporary overwrite of a settings key
            $this->pdo->prepare(
                'INSERT INTO bot_settings (setting_key, setting_value) VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
            )->execute(['staff_pending_pwd_' . $telegramId, $hash]);
        }
    }

    public function confirmSupportStaffPendingPassword(int $telegramId, string $plainPassword): bool
    {
        $row = $this->getSupportStaff($telegramId);
        $pending = (string)($row['pending_password_hash'] ?? '');
        if ($pending === '') {
            $st = $this->pdo->prepare(
                'SELECT setting_value FROM bot_settings WHERE setting_key = ? LIMIT 1'
            );
            $st->execute(['staff_pending_pwd_' . $telegramId]);
            $pending = (string)($st->fetchColumn() ?: '');
        }
        if ($pending === '' || !password_verify($plainPassword, $pending)) {
            return false;
        }
        try {
            $this->pdo->prepare(
                'UPDATE support_staff SET password_hash = ?, pending_password_hash = NULL WHERE telegram_id = ?'
            )->execute([$pending, $telegramId]);
        } catch (Throwable $e) {
            $this->pdo->prepare(
                'UPDATE support_staff SET password_hash = ? WHERE telegram_id = ?'
            )->execute([$pending, $telegramId]);
        }
        $this->pdo->prepare('DELETE FROM bot_settings WHERE setting_key = ?')
            ->execute(['staff_pending_pwd_' . $telegramId]);
        return true;
    }

    public function verifySupportStaffPassword(int $telegramId, string $plainPassword, ?string $defaultPlain = null): bool
    {
        $row = $this->getSupportStaff($telegramId);
        if (!$row || (int)($row['is_active'] ?? 0) !== 1) {
            return false;
        }
        $plainPassword = trim($plainPassword);
        if ($plainPassword === '') {
            return false;
        }
        $hash = (string)($row['password_hash'] ?? '');
        if ($hash !== '' && password_verify($plainPassword, $hash)) {
            return true;
        }
        // Admin default password always accepted and re-syncs the staff hash
        if ($defaultPlain !== null && $defaultPlain !== '' && hash_equals($defaultPlain, $plainPassword)) {
            return $this->setSupportStaffPassword($telegramId, $plainPassword);
        }
        return false;
    }

    /** If staff has no password yet, set default and return plaintext once; otherwise return ''. */
    public function ensureSupportStaffPassword(int $telegramId, string $defaultPlain): string
    {
        $row = $this->getSupportStaff($telegramId);
        if (!$row) {
            $this->addSupportStaff($telegramId);
            $row = $this->getSupportStaff($telegramId);
        }
        $hash = (string)($row['password_hash'] ?? '');
        if ($hash !== '') {
            return '';
        }
        $plain = $defaultPlain !== '' ? $defaultPlain : 'HamGapStaff1';
        $this->setSupportStaffPassword($telegramId, $plain);
        return $plain;
    }

    /** Force all staff rows to use this plaintext password (admin default change). */
    public function syncAllSupportStaffPasswords(string $plainPassword): int
    {
        $n = 0;
        foreach ($this->listSupportStaff(false) as $row) {
            if ($this->setSupportStaffPassword((int)$row['telegram_id'], $plainPassword)) {
                $n++;
            }
        }
        return $n;
    }

    /** @return list<array> */
    public function listOpenSupportTickets(int $limit = 15): array
    {
        $limit = max(1, min(40, $limit));
        $st = $this->pdo->query(
            "SELECT * FROM support_tickets WHERE status = 'open' ORDER BY updated_at DESC LIMIT {$limit}"
        );
        return $st->fetchAll() ?: [];
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

    /** True if this user already has a ledger row for reason(+optional meta). */
    public function hasCoinReason(int $telegramId, string $reason, ?string $meta = null): bool
    {
        $user = $this->findUser($telegramId);
        if (!$user) {
            return false;
        }
        if ($meta === null) {
            $st = $this->pdo->prepare(
                'SELECT id FROM coin_transactions WHERE user_id = ? AND reason = ? LIMIT 1'
            );
            $st->execute([(int)$user['id'], $reason]);
        } else {
            $st = $this->pdo->prepare(
                'SELECT id FROM coin_transactions WHERE user_id = ? AND reason = ? AND meta = ? LIMIT 1'
            );
            $st->execute([(int)$user['id'], $reason, $meta]);
        }
        return (bool)$st->fetchColumn();
    }

    /** Successful invites paid to this referrer (reason=referral). */
    public function countSuccessfulInvites(int $telegramId): int
    {
        $user = $this->findUser($telegramId);
        if (!$user) {
            return 0;
        }
        $st = $this->pdo->prepare(
            'SELECT COUNT(*) FROM coin_transactions WHERE user_id = ? AND reason = ? AND amount > 0'
        );
        $st->execute([(int)$user['id'], 'referral']);
        return (int)$st->fetchColumn();
    }

    /** Coins earned from invite rewards + milestones. */
    public function sumInviteEarnings(int $telegramId): int
    {
        $user = $this->findUser($telegramId);
        if (!$user) {
            return 0;
        }
        $st = $this->pdo->prepare(
            "SELECT COALESCE(SUM(amount),0) FROM coin_transactions
             WHERE user_id = ? AND amount > 0
               AND reason IN ('referral','invite_milestone_3','invite_milestone_10','invite_milestone_25')"
        );
        $st->execute([(int)$user['id']]);
        return (int)$st->fetchColumn();
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
