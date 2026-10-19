<?php

declare(strict_types=1);

function warranty_schema(Database $db): void
{
    $db->exec(
        'CREATE TABLE IF NOT EXISTS ak_warranties (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            machine_id INT UNSIGNED NOT NULL DEFAULT 0,
            serial VARCHAR(80) NOT NULL DEFAULT "",
            holder_name VARCHAR(160) NOT NULL,
            holder_phone VARCHAR(20) NOT NULL DEFAULT "",
            dealer_id INT UNSIGNED NOT NULL DEFAULT 0,
            started_on DATE NOT NULL,
            months TINYINT UNSIGNED NOT NULL,
            note VARCHAR(500) NOT NULL DEFAULT "",
            status VARCHAR(20) NOT NULL,
            created_at DATETIME NOT NULL,
            INDEX (holder_phone),
            INDEX (serial)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
}

function warranty_ends(string $started, int $months): string
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $started);
    if (!$date || $months < 1) {
        return $started;
    }
    return $date->modify('+' . $months . ' months')->format('Y-m-d');
}

function warranty_label(array $row): string
{
    if ((string) ($row['status'] ?? '') === 'void') {
        return 'باطل';
    }
    $end = warranty_ends((string) ($row['started_on'] ?? ''), (int) ($row['months'] ?? 0));
    if ($end !== '' && $end < date('Y-m-d')) {
        return 'منقضی';
    }
    return 'فعال';
}

/** @return list<array<string, mixed>> */
function warranty_rows(App $app): array
{
    try {
        return $app->db->all(
            'SELECT w.*, m.name AS machine_name, d.name AS dealer_name
             FROM ak_warranties w
             LEFT JOIN ak_machines m ON m.id = w.machine_id
             LEFT JOIN ak_dealers d ON d.id = w.dealer_id
             ORDER BY w.id DESC LIMIT 200'
        );
    } catch (Throwable) {
        return $app->db->all('SELECT * FROM ak_warranties ORDER BY id DESC LIMIT 200');
    }
}
