<?php

declare(strict_types=1);

function dealership_schema(Database $db): void
{
    $db->exec(
        'CREATE TABLE IF NOT EXISTS ak_dealership_forms (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(160) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            province VARCHAR(80) NOT NULL DEFAULT "",
            city VARCHAR(80) NOT NULL DEFAULT "",
            shop VARCHAR(160) NOT NULL DEFAULT "",
            body TEXT NOT NULL,
            file VARCHAR(80) NOT NULL DEFAULT "",
            status VARCHAR(20) NOT NULL,
            note VARCHAR(500) NOT NULL DEFAULT "",
            created_at DATETIME NOT NULL,
            INDEX (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
}

function dealership_status_label(string $status): string
{
    return match ($status) {
        'new' => 'جدید',
        'reviewing' => 'در بررسی',
        'accepted' => 'پذیرفته برای بررسی پرونده',
        'rejected' => 'رد شده',
        default => $status,
    };
}
