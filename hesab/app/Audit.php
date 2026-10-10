<?php
declare(strict_types=1);

final class Audit
{
    public static function log(string $action, ?string $entity = null, ?int $entityId = null, ?string $detail = null): void
    {
        try {
            $uid = current_user()['id'] ?? null;
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            Database::query(
                'INSERT INTO audit_logs (user_id, action, entity, entity_id, detail, ip) VALUES (?,?,?,?,?,?)',
                [$uid, $action, $entity, $entityId, $detail, $ip]
            );
        } catch (Throwable $e) {
            error_log('audit: ' . $e->getMessage());
        }
    }
}
