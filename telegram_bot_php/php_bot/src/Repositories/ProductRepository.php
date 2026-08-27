<?php
declare(strict_types=1);

namespace HddLand\Bot\Repositories;

final class ProductRepository
{
    /** @return list<array<string,mixed>> */
    public static function activeList(): array
    {
        return db()->query('SELECT id, name, price, buy_url FROM products WHERE is_active = 1 ORDER BY id')->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public static function findActive(int $id): ?array
    {
        $stmt = db()->prepare('SELECT * FROM products WHERE id = ? AND is_active = 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
