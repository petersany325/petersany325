<?php

declare(strict_types=1);

/** @return array<string, string> */
function cooperation_levels(): array
{
    return [
        'عادی' => 'margin_normal',
        'نقره‌ای' => 'margin_silver',
        'طلایی' => 'margin_gold',
        'ویژه' => 'margin_special',
    ];
}

function cooperation_margin(App $app, string $level): int
{
    $key = cooperation_levels()[$level] ?? 'margin_normal';
    $defaults = [
        'margin_normal' => '10',
        'margin_silver' => '15',
        'margin_gold' => '20',
        'margin_special' => '25',
    ];
    $raw = (int) setting($app, 'cooperation', $key, $defaults[$key]);
    if ($raw < 0) {
        return 0;
    }
    if ($raw > 90) {
        return 90;
    }
    return $raw;
}

function cooperation_floor(App $app, int $cost): int
{
    $min = (int) setting($app, 'cooperation', 'min_company_profit', '0');
    if ($min < 0) {
        $min = 0;
    }
    return ($cost > 0 ? $cost : 0) + $min;
}

function cooperation_percent_label(int $part, int $whole): string
{
    if ($whole < 1) {
        return '';
    }
    $tenths = intdiv(abs($part) * 1000, $whole);
    $sign = $part < 0 ? '−' : '';
    $text = $sign . fa_digits((string) intdiv($tenths, 10));
    $frac = $tenths % 10;
    if ($frac !== 0) {
        $text .= '٫' . fa_digits((string) $frac);
    }
    return $text . '٪';
}

function cooperation_source_label(string $source): string
{
    return match ($source) {
        'fixed' => 'قیمت ثابت این نماینده',
        'level' => 'قیمت سطح',
        'base' => 'قیمت پایه',
        'formula' => 'فرمول سطح',
        default => 'نامشخص',
    };
}

/**
 * @return array{price:int,cost:int,retail:int,source:string,margin_label:string,markup_label:string}|null
 */
function cooperation_quote(App $app, array $dealer, int $machineId): ?array
{
    if ($machineId < 1) {
        return null;
    }
    $retail = function_exists('shop_unit_price') ? shop_unit_price($app, $machineId) : 0;
    $shopCost = function_exists('shop_unit_cost') ? shop_unit_cost($app, $machineId) : 0;
    $row = null;
    try {
        $row = $app->db->one(
            'SELECT price, cost, dealer_id, level FROM ak_dealer_prices
             WHERE machine_id = ? AND price > 0
               AND (dealer_id = ? OR (dealer_id = 0 AND level = ?) OR (dealer_id = 0 AND level = ""))
             ORDER BY (dealer_id = 0) ASC, (level = "") ASC LIMIT 1',
            [$machineId, (int) $dealer['id'], (string) ($dealer['level'] ?? '')]
        );
    } catch (Throwable) {
        $row = null;
    }
    $source = '';
    $price = 0;
    $cost = $shopCost;
    if ($row) {
        $price = (int) $row['price'];
        if ((int) $row['cost'] > 0) {
            $cost = (int) $row['cost'];
        }
        if ((int) $row['dealer_id'] > 0) {
            $source = 'fixed';
        } elseif ((string) $row['level'] !== '') {
            $source = 'level';
        } else {
            $source = 'base';
        }
    } elseif ($retail > 0) {
        $margin = cooperation_margin($app, (string) ($dealer['level'] ?? ''));
        $price = intdiv($retail * (100 - $margin), 100);
        $floor = cooperation_floor($app, $cost);
        if ($price < $floor) {
            $price = $floor;
        }
        $source = 'formula';
    }
    if ($price < 1 || $source === '') {
        return null;
    }
    $gap = $retail - $price;
    return [
        'price' => $price,
        'cost' => $cost,
        'retail' => $retail,
        'source' => $source,
        'margin_label' => $retail > 0 ? cooperation_percent_label($gap, $retail) : '',
        'markup_label' => $retail > 0 && $price > 0 ? cooperation_percent_label($gap, $price) : '',
    ];
}

function cooperation_extend(Database $db): void
{
    $table = $db->one(
        'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
        ['ak_dealer_order_items']
    );
    if (!$table) {
        return;
    }
    foreach (['retail_price' => 'BIGINT NOT NULL DEFAULT 0', 'potential' => 'BIGINT NOT NULL DEFAULT 0'] as $column => $definition) {
        $row = $db->one(
            'SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            ['ak_dealer_order_items', $column]
        );
        if ($row) {
            continue;
        }
        $db->exec('ALTER TABLE `ak_dealer_order_items` ADD COLUMN `' . $column . '` ' . $definition);
    }
}

/** @return array{earned:int,potential:int} */
function cooperation_rep_figures(App $app, int $dealerId): array
{
    $earned = $app->db->one(
        'SELECT COALESCE(SUM(amount), 0) AS s FROM ak_dealer_commissions WHERE dealer_id = ? AND status = "earned"',
        [$dealerId]
    );
    $potential = 0;
    try {
        $row = $app->db->one(
            'SELECT COALESCE(SUM(i.potential), 0) AS s
             FROM ak_dealer_order_items i
             JOIN ak_dealer_orders o ON o.id = i.order_id
             WHERE o.dealer_id = ? AND o.status <> "cancelled" AND i.retail_price > 0',
            [$dealerId]
        );
        $potential = (int) ($row['s'] ?? 0);
    } catch (Throwable) {
        $potential = 0;
    }
    return [
        'earned' => (int) ($earned['s'] ?? 0),
        'potential' => $potential,
    ];
}

function cooperation_seed(Database $db): void
{
    $pairs = [
        'margin_normal' => '10',
        'margin_silver' => '15',
        'margin_gold' => '20',
        'margin_special' => '25',
        'min_company_profit' => '0',
    ];
    foreach ($pairs as $key => $value) {
        $db->run(
            'INSERT INTO ak_settings (plugin, skey, svalue) VALUES ("cooperation", ?, ?) ON DUPLICATE KEY UPDATE skey = skey',
            [$key, $value]
        );
    }
}
