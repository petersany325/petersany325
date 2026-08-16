<?php
declare(strict_types=1);

final class Matcher
{
    public function __construct(
        private Database $db,
        private ?Settings $settings = null
    ) {
    }

    public static function costFor(string $pref, ?Settings $settings = null): int
    {
        if ($settings) {
            return match ($pref) {
                'any' => $settings->getInt('connect_any_cost', 0),
                'male', 'female', 'shemale' => $settings->getInt('connect_gender_cost', 0),
                'province' => $settings->getInt('connect_province_cost', 0),
                'age' => $settings->getInt('connect_age_cost', 0),
                default => 0,
            };
        }
        // Default: all connect/search free; monetize message/request instead.
        return 0;
    }

    /**
     * @param array{province?:string,city?:string} $filters Optional location filters (find flow).
     */
    public function startSearch(array $user, string $pref, array $filters = [], string $mode = 'normal'): array
    {
        $tid = (int)$user['telegram_id'];
        $cost = self::costFor($pref, $this->settings);
        require_once __DIR__ . '/ChatModes.php';
        if (!ChatModes::isValid($mode)) {
            $mode = ChatModes::NORMAL;
        }

        if (in_array($pref, ['province', 'age'], true)) {
            if ($pref === 'province' && empty($user['province'])) {
                return ['ok' => false, 'error' => 'need_province'];
            }
            if ($pref === 'age' && empty($user['age'])) {
                return ['ok' => false, 'error' => 'need_age'];
            }
        }

        if ($cost > 0 && (int)$user['coins'] < $cost) {
            return ['ok' => false, 'error' => 'no_coins'];
        }

        if (($user['status'] ?? '') === 'chatting') {
            $this->endChat($user, false);
            $user = $this->db->findUser($tid) ?? $user;
        }

        $this->db->updateUser($tid, [
            'status' => 'searching',
            'search_pref' => $pref,
            'chat_mode' => $mode,
            'partner_id' => null,
        ]);
        $user = $this->db->findUser($tid) ?? $user;

        $partner = $this->findPartner($user, $pref, $filters, $mode);
        if (!$partner) {
            return ['ok' => true, 'matched' => false];
        }

        return $this->connect($user, $partner, $pref, $cost, $mode);
    }

    /**
     * @param array{province?:string,city?:string} $filters
     */
    private function findPartner(array $user, string $pref, array $filters = [], string $mode = 'normal'): ?array
    {
        $pdo = $this->db->pdo();
        $myGender = (string)$user['gender'];
        $myId = (int)$user['telegram_id'];
        $myProvince = (string)($user['province'] ?? '');
        $myAge = (int)($user['age'] ?? 0);
        $filterProvince = isset($filters['province']) ? (string)$filters['province'] : '';
        $filterCity = isset($filters['city']) ? (string)$filters['city'] : '';
        $mode = $mode !== '' ? $mode : 'normal';

        $pdo->beginTransaction();
        try {
            $st = $pdo->query("SELECT * FROM users WHERE status = 'searching' AND gender IS NOT NULL FOR UPDATE");
            $partner = null;
            foreach ($st->fetchAll() as $row) {
                if ((int)$row['telegram_id'] === $myId) {
                    continue;
                }
                if ($this->db->isBlocked($myId, (int)$row['telegram_id']) || $this->db->isBlocked((int)$row['telegram_id'], $myId)) {
                    continue;
                }
                $theirMode = (string)($row['chat_mode'] ?? 'normal');
                if ($theirMode === '') {
                    $theirMode = 'normal';
                }
                if ($theirMode !== $mode) {
                    continue;
                }
                $theirPref = (string)($row['search_pref'] ?? 'any');
                $theirGender = (string)$row['gender'];

                if (in_array($pref, ['any', 'male', 'female', 'shemale'], true)) {
                    $acceptsMe = ($theirPref === 'any' || $theirPref === $myGender || in_array($theirPref, ['province', 'age'], true));
                    $iAcceptThem = ($pref === 'any' || $pref === $theirGender);
                    if (!$acceptsMe || !$iAcceptThem) {
                        continue;
                    }
                }

                if ($pref === 'province') {
                    if ($myProvince === '' || (string)($row['province'] ?? '') !== $myProvince) {
                        continue;
                    }
                }

                if ($pref === 'age') {
                    $theirAge = (int)($row['age'] ?? 0);
                    if ($myAge <= 0 || $theirAge <= 0 || abs($myAge - $theirAge) > 3) {
                        continue;
                    }
                }

                if ($theirPref === 'province') {
                    if ($myProvince === '' || (string)($row['province'] ?? '') !== $myProvince) {
                        continue;
                    }
                }
                if ($theirPref === 'age') {
                    $theirAge = (int)($row['age'] ?? 0);
                    if ($myAge <= 0 || $theirAge <= 0 || abs($myAge - $theirAge) > 3) {
                        continue;
                    }
                }
                if (in_array($theirPref, ['male', 'female', 'shemale'], true) && $theirPref !== $myGender) {
                    continue;
                }

                if ($filterProvince !== '' && (string)($row['province'] ?? '') !== $filterProvince) {
                    continue;
                }
                if ($filterCity !== '' && (string)($row['city'] ?? '') !== $filterCity) {
                    continue;
                }

                $partner = $row;
                break;
            }
            $pdo->commit();
            return $partner;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    private function connect(array $user, array $partner, string $matchType, int $cost, string $mode = 'normal'): array
    {
        $pdo = $this->db->pdo();
        $a = (int)$user['telegram_id'];
        $b = (int)$partner['telegram_id'];
        $storedType = $mode !== '' && $mode !== 'normal' ? ($mode . ':' . $matchType) : $matchType;

        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare('SELECT * FROM users WHERE telegram_id IN (?, ?) FOR UPDATE');
            $st->execute([$a, $b]);
            $map = [];
            foreach ($st->fetchAll() as $row) {
                $map[(int)$row['telegram_id']] = $row;
            }

            if (
                !isset($map[$a], $map[$b]) ||
                $map[$a]['status'] !== 'searching' ||
                $map[$b]['status'] !== 'searching'
            ) {
                $pdo->commit();
                return ['ok' => true, 'matched' => false];
            }

            if ($cost > 0) {
                if ((int)$map[$a]['coins'] < $cost) {
                    $pdo->commit();
                    return ['ok' => false, 'error' => 'no_coins'];
                }
                $pdo->prepare('UPDATE users SET coins = coins - ? WHERE telegram_id = ?')->execute([$cost, $a]);
                $pdo->prepare(
                    'INSERT INTO coin_transactions (user_id, amount, reason, meta) VALUES (?, ?, ?, ?)'
                )->execute([(int)$map[$a]['id'], -$cost, 'match_' . $matchType, (string)$b]);
            }

            $pdo->prepare(
                "INSERT INTO chats (user_a, user_b, match_type, status) VALUES (?, ?, ?, 'active')"
            )->execute([$a, $b, $storedType]);

            $pdo->prepare(
                "UPDATE users SET status = 'chatting', partner_id = ?, search_pref = NULL, chat_mode = ? WHERE telegram_id = ?"
            )->execute([$b, $mode, $a]);
            $pdo->prepare(
                "UPDATE users SET status = 'chatting', partner_id = ?, search_pref = NULL, chat_mode = ? WHERE telegram_id = ?"
            )->execute([$a, $mode, $b]);

            $pdo->commit();
            return [
                'ok' => true,
                'matched' => true,
                'partner' => $map[$b],
                'me' => $this->db->findUser($a),
                'mode' => $mode,
            ];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function endChat(array $user, bool $notifyPartner = true): ?int
    {
        $tid = (int)$user['telegram_id'];
        $partnerId = !empty($user['partner_id']) ? (int)$user['partner_id'] : null;

        // Hard-delete chat rows — no message history / logs retained
        if ($partnerId) {
            $this->db->wipeChatPair($tid, $partnerId);
            $this->db->updateUser($partnerId, [
                'status' => 'idle',
                'partner_id' => null,
                'search_pref' => null,
                'chat_mode' => null,
            ]);
        } else {
            $this->db->wipeUserChats($tid);
        }

        $this->db->updateUser($tid, [
            'status' => 'idle',
            'partner_id' => null,
            'search_pref' => null,
            'chat_mode' => null,
        ]);

        return $partnerId;
    }

    public function cancelSearch(array $user): void
    {
        $this->db->updateUser((int)$user['telegram_id'], [
            'status' => 'idle',
            'search_pref' => null,
            'chat_mode' => null,
            'partner_id' => null,
        ]);
    }
}
