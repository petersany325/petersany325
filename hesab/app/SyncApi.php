<?php
declare(strict_types=1);

/**
 * Desktop ↔ web sync API (JSON).
 * Entities exchange rows keyed by sync_id; conflict = higher sync_version wins.
 */
final class SyncApi
{
    /** @var list<string> */
    public const ENTITIES = [
        'parties',
        'accounts_moein',
        'vouchers',
        'voucher_lines',
        'invoices',
        'invoice_items',
        'posting_rules',
        'settings',
    ];

    public static function json(mixed $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public static function status(): void
    {
        self::json([
            'ok' => true,
            'service' => 'hesab-sync',
            'version' => 1,
            'entities' => self::ENTITIES,
            'conflict_policy' => 'LastWriteWins',
            'server_time' => gmdate('c'),
        ]);
    }

    public static function push(): void
    {
        $body = self::readJsonBody();
        $entity = (string) ($body['Entity'] ?? $body['entity'] ?? '');
        $deviceId = (string) ($body['DeviceId'] ?? $body['deviceId'] ?? '');
        $rows = $body['Rows'] ?? $body['rows'] ?? [];
        if (!in_array($entity, self::ENTITIES, true)) {
            self::json(['ok' => false, 'error' => 'unknown_entity'], 400);
            return;
        }
        if (!is_array($rows)) {
            self::json(['ok' => false, 'error' => 'rows_required'], 400);
            return;
        }

        $applied = 0;
        $conflicts = 0;
        $errors = [];

        if ($entity === 'parties') {
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                try {
                    $r = self::upsertParty($row);
                    if ($r === 'conflict') {
                        $conflicts++;
                    } else {
                        $applied++;
                    }
                } catch (Throwable $e) {
                    $errors[] = $e->getMessage();
                }
            }
        } else {
            // Other entities: accepted into sync_inbox for later merge
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                try {
                    self::inbox($deviceId, $entity, $row);
                    $applied++;
                } catch (Throwable $e) {
                    $errors[] = $e->getMessage();
                }
            }
        }

        self::json([
            'ok' => $errors === [],
            'entity' => $entity,
            'applied' => $applied,
            'conflicts' => $conflicts,
            'errors' => $errors,
        ]);
    }

    public static function pull(): void
    {
        $entity = (string) ($_GET['entity'] ?? '');
        $since = (string) ($_GET['since'] ?? '');
        if (!in_array($entity, self::ENTITIES, true)) {
            self::json(['ok' => false, 'error' => 'unknown_entity'], 400);
            return;
        }

        $rows = [];
        if ($entity === 'parties') {
            $rows = self::pullParties($since);
        }

        $cursor = gmdate('Y-m-d H:i:s');
        self::json([
            'ok' => true,
            'Cursor' => $cursor,
            'cursor' => $cursor,
            'Rows' => $rows,
            'rows' => $rows,
        ]);
    }

    /** @return array<string, mixed> */
    private static function readJsonBody(): array
    {
        $raw = file_get_contents('php://input') ?: '';
        if ($raw === '') {
            return [];
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    /** @param array<string, mixed> $row */
    private static function upsertParty(array $row): string
    {
        $syncId = self::guid($row['sync_id'] ?? $row['SyncId'] ?? null);
        $code = trim((string) ($row['code'] ?? ''));
        $name = trim((string) ($row['name'] ?? ''));
        $type = (string) ($row['type'] ?? 'customer');
        $phone = $row['phone'] ?? null;
        $ver = (int) ($row['sync_version'] ?? 1);
        $deleted = (int) ((bool) ($row['is_deleted'] ?? false));
        $updated = (string) ($row['updated_at'] ?? gmdate('Y-m-d H:i:s'));

        if ($syncId === '' || ($code === '' && $deleted === 0)) {
            throw new InvalidArgumentException('party requires sync_id and code');
        }
        if (!in_array($type, ['customer', 'supplier', 'both', 'other'], true)) {
            $type = 'customer';
        }

        $existing = Database::fetch(
            'SELECT id, sync_version FROM parties WHERE sync_id = ? LIMIT 1',
            [$syncId]
        );
        if (!$existing && $code !== '') {
            $existing = Database::fetch(
                'SELECT id, sync_version FROM parties WHERE code = ? LIMIT 1',
                [$code]
            );
        }

        if ($existing) {
            if ((int) $existing['sync_version'] > $ver) {
                return 'conflict';
            }
            Database::query(
                'UPDATE parties SET code=?, name=?, type=?, phone=?, sync_id=?, updated_at=?, sync_version=?, is_deleted=? WHERE id=?',
                [$code !== '' ? $code : ('P' . $existing['id']), $name !== '' ? $name : '—', $type, $phone, $syncId, $updated, $ver, $deleted, (int) $existing['id']]
            );
            return 'updated';
        }

        if ($deleted) {
            return 'skipped';
        }

        Database::query(
            'INSERT INTO parties (code, name, type, phone, sync_id, updated_at, sync_version, is_deleted) VALUES (?,?,?,?,?,?,?,0)',
            [$code, $name, $type, $phone, $syncId, $updated, $ver]
        );
        return 'inserted';
    }

    /** @return list<array<string, mixed>> */
    private static function pullParties(string $since): array
    {
        $sql = 'SELECT id, sync_id, code, name, type, phone, created_at, updated_at, sync_version, is_deleted FROM parties';
        $params = [];
        if ($since !== '') {
            $sql .= ' WHERE updated_at > ?';
            $params[] = $since;
        }
        $sql .= ' ORDER BY updated_at ASC LIMIT 500';
        $rows = Database::fetchAll($sql, $params);
        return array_map(static function (array $r): array {
            return [
                'id' => (int) $r['id'],
                'sync_id' => $r['sync_id'],
                'code' => $r['code'],
                'name' => $r['name'],
                'type' => $r['type'],
                'phone' => $r['phone'],
                'created_at' => $r['created_at'],
                'updated_at' => $r['updated_at'],
                'sync_version' => (int) $r['sync_version'],
                'is_deleted' => (int) $r['is_deleted'],
            ];
        }, $rows);
    }

    /** @param array<string, mixed> $row */
    private static function inbox(string $deviceId, string $entity, array $row): void
    {
        $syncId = self::guid($row['sync_id'] ?? $row['SyncId'] ?? null);
        Database::query(
            'INSERT INTO sync_inbox (device_id, entity, sync_id, payload_json, created_at) VALUES (?,?,?,?,UTC_TIMESTAMP())',
            [$deviceId !== '' ? $deviceId : null, $entity, $syncId !== '' ? $syncId : null, json_encode($row, JSON_UNESCAPED_UNICODE)]
        );
    }

    private static function guid(mixed $v): string
    {
        $s = strtolower(trim((string) $v));
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $s)) {
            return $s;
        }
        return '';
    }
}
