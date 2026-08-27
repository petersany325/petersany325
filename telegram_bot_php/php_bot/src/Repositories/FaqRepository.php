<?php
declare(strict_types=1);

namespace HddLand\Bot\Repositories;

final class FaqRepository
{
    /** @return list<array<string,mixed>> */
    public static function search(string $query, string $lang): array
    {
        if (!function_exists('search_faqs')) {
            return [];
        }
        return search_faqs($query, $lang) ?: [];
    }

    /** @return array<string,mixed>|null */
    public static function findActive(int $id): ?array
    {
        $st = db()->prepare('SELECT * FROM faqs WHERE id=? AND is_active=1');
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    /** @return array<string,mixed>|null */
    public static function findMenu(int $id): ?array
    {
        $st = db()->prepare('SELECT * FROM menus WHERE id=?');
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    /** @return array<string,mixed>|null */
    public static function findActiveMenu(int $id): ?array
    {
        $st = db()->prepare('SELECT * FROM menus WHERE id=? AND is_active=1');
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ?: null;
    }
}
