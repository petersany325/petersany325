<?php
declare(strict_types=1);

final class Matcher
{
    public function __construct(private Database $db)
    {
    }

    public function startSearch(array $user, string $pref): array
    {
        $tid = (int)$user['telegram_id'];
        $cost = $pref === 'any' ? 0 : 1;

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
            'partner_id' => null,
        ]);
        $user = $this->db->findUser($tid) ?? $user;

        $partner = $this->findPartner($user, $pref);
        if (!$partner) {
            return ['ok' => true, 'matched' => false];
        }

        return $this->connect($user, $partner, $pref, $cost);
    }

    private function findPartner(array $user, string $pref): ?array
    {
        $pdo = $this->db->pdo();
        $myGender = (string)$user['gender'];
        $myId = (int)$user['telegram_id'];

        $pdo->beginTransaction();
        try {
            $st = $pdo->query("SELECT * FROM users WHERE status = 'searching' AND gender IS NOT NULL FOR UPDATE");
            $partner = null;
            foreach ($st->fetchAll() as $row) {
                if ((int)$row['telegram_id'] === $myId) {
                    continue;
                }
                $theirPref = $row['search_pref'] ?? 'any';
                $theirGender = (string)$row['gender'];
                $acceptsMe = ($theirPref === 'any' || $theirPref === $myGender);
                $iAcceptThem = ($pref === 'any' || $pref === $theirGender);
                if ($acceptsMe && $iAcceptThem) {
                    $partner = $row;
                    break;
                }
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

    private function connect(array $user, array $partner, string $matchType, int $cost): array
    {
        $pdo = $this->db->pdo();
        $a = (int)$user['telegram_id'];
        $b = (int)$partner['telegram_id'];

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
            )->execute([$a, $b, $matchType]);

            $pdo->prepare(
                "UPDATE users SET status = 'chatting', partner_id = ?, search_pref = NULL WHERE telegram_id = ?"
            )->execute([$b, $a]);
            $pdo->prepare(
                "UPDATE users SET status = 'chatting', partner_id = ?, search_pref = NULL WHERE telegram_id = ?"
            )->execute([$a, $b]);

            $pdo->commit();
            return [
                'ok' => true,
                'matched' => true,
                'partner' => $map[$b],
                'me' => $this->db->findUser($a),
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

        $this->db->updateUser($tid, [
            'status' => 'idle',
            'partner_id' => null,
            'search_pref' => null,
        ]);

        if ($partnerId) {
            $this->db->pdo()->prepare(
                "UPDATE chats SET status = 'ended', ended_at = NOW()
                 WHERE status = 'active' AND ((user_a = ? AND user_b = ?) OR (user_a = ? AND user_b = ?))"
            )->execute([$tid, $partnerId, $partnerId, $tid]);

            $this->db->updateUser($partnerId, [
                'status' => 'idle',
                'partner_id' => null,
                'search_pref' => null,
            ]);
        }

        return $partnerId;
    }

    public function cancelSearch(array $user): void
    {
        $this->db->updateUser((int)$user['telegram_id'], [
            'status' => 'idle',
            'search_pref' => null,
            'partner_id' => null,
        ]);
    }
}
