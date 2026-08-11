<?php
declare(strict_types=1);

namespace HddLand\Bot\Repositories;

final class TicketRepository
{
    public static function create(int $userId, string $subject): int
    {
        $pdo = db();
        $pdo->prepare('INSERT INTO tickets (user_id, subject, status) VALUES (?,?,?)')
            ->execute([$userId, $subject, 'open']);
        $tid = (int)$pdo->lastInsertId();
        $pdo->prepare('INSERT INTO ticket_messages (ticket_id, sender_id, is_admin, text) VALUES (?,?,0,?)')
            ->execute([$tid, $userId, $subject]);
        return $tid;
    }

    public static function createAdvanced(
        int $userId,
        string $subject,
        string $fullMessage,
        string $contactName = '',
        string $phone = '',
        string $metaJson = ''
    ): int {
        $pdo = db();
        try {
            $pdo->prepare(
                'INSERT INTO tickets (user_id, subject, status, contact_name, phone, meta_json) VALUES (?,?,?,?,?,?)'
            )->execute([$userId, $subject, 'open', $contactName !== '' ? $contactName : null, $phone !== '' ? $phone : null, $metaJson !== '' ? $metaJson : null]);
        } catch (\Throwable $e) {
            $pdo->prepare('INSERT INTO tickets (user_id, subject, status) VALUES (?,?,?)')
                ->execute([$userId, $subject, 'open']);
        }
        $tid = (int)$pdo->lastInsertId();
        $pdo->prepare('INSERT INTO ticket_messages (ticket_id, sender_id, is_admin, text) VALUES (?,?,0,?)')
            ->execute([$tid, $userId, $fullMessage]);
        return $tid;
    }

    /** @return list<array<string,mixed>> */
    public static function forUser(int $userId, int $limit = 10): array
    {
        $stmt = db()->prepare('SELECT id, subject, status FROM tickets WHERE user_id = ? ORDER BY id DESC LIMIT ' . (int)$limit);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public static function forUserDetailed(int $userId, int $limit = 10): array
    {
        try {
            $stmt = db()->prepare(
                'SELECT id, subject, status, contact_name, phone, created_at FROM tickets WHERE user_id = ? ORDER BY id DESC LIMIT '
                . (int)$limit
            );
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            return self::forUser($userId, $limit);
        }
    }

    /** @return list<array<string,mixed>> */
    public static function messages(int $ticketId): array
    {
        $stmt = db()->prepare('SELECT * FROM ticket_messages WHERE ticket_id = ? ORDER BY id ASC');
        $stmt->execute([$ticketId]);
        return $stmt->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public static function openTickets(int $limit = 20): array
    {
        return db()->query("SELECT id, user_id, subject FROM tickets WHERE status='open' ORDER BY id DESC LIMIT " . (int)$limit)->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public static function find(int $id): ?array
    {
        $stmt = db()->prepare('SELECT * FROM tickets WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function addAdminReply(int $ticketId, int $adminId, string $text): void
    {
        db()->prepare('INSERT INTO ticket_messages (ticket_id, sender_id, is_admin, text) VALUES (?,?,1,?)')
            ->execute([$ticketId, $adminId, $text]);
    }

    public static function close(int $ticketId): void
    {
        db()->prepare("UPDATE tickets SET status='closed' WHERE id=?")->execute([$ticketId]);
    }
}
