<?php
declare(strict_types=1);

namespace HddLand\Bot\Services;

/**
 * Online health checks for every menu row and built-in callback targets.
 */
final class MenuHealthService
{
    /** @return list<array{ok:bool,level:string,code:string,title:string,detail:string}> */
    public static function run(): array
    {
        $out = [];
        try {
            $pdo = db();
            $rows = $pdo->query('SELECT id, parent_id, title, menu_type, value_text, is_active, category FROM menus ORDER BY id ASC')->fetchAll();
        } catch (\Throwable $e) {
            return [[
                'ok' => false,
                'level' => 'err',
                'code' => 'db',
                'title' => 'Database',
                'detail' => $e->getMessage(),
            ]];
        }

        $knownCallbacks = self::knownCallbacks();
        $ids = [];
        foreach ($rows as $r) {
            $ids[(int)$r['id']] = true;
        }

        $active = 0;
        $broken = 0;
        foreach ($rows as $r) {
            $id = (int)$r['id'];
            $title = (string)$r['title'];
            $type = (string)$r['menu_type'];
            $val = trim((string)$r['value_text']);
            $isActive = (int)$r['is_active'] === 1;
            if ($isActive) {
                $active++;
            }

            $parent = $r['parent_id'] !== null ? (int)$r['parent_id'] : null;
            if ($parent && empty($ids[$parent])) {
                $broken++;
                $out[] = self::row(false, 'err', 'menu:' . $id, $title, 'Parent menu #' . $parent . ' missing');
                continue;
            }

            if (!$isActive) {
                $out[] = self::row(true, 'warn', 'menu:' . $id, $title, 'Inactive (hidden)');
                continue;
            }

            switch ($type) {
                case 'url':
                    if ($val === '' || !preg_match('#^https?://#i', $val)) {
                        $broken++;
                        $out[] = self::row(false, 'err', 'menu:' . $id, $title, 'Invalid URL value');
                    } else {
                        $out[] = self::row(true, 'ok', 'menu:' . $id, $title, 'URL OK → ' . $val);
                    }
                    break;
                case 'callback':
                    if ($val === '') {
                        $broken++;
                        $out[] = self::row(false, 'err', 'menu:' . $id, $title, 'Empty callback');
                    } elseif (!isset($knownCallbacks[$val]) && strpos($val, 'product:') !== 0 && strpos($val, 'faq:') !== 0) {
                        $out[] = self::row(true, 'warn', 'menu:' . $id, $title, 'Callback "' . $val . '" not in built-in map (may still work via menu id)');
                    } else {
                        $out[] = self::row(true, 'ok', 'menu:' . $id, $title, 'Callback OK → ' . $val);
                    }
                    break;
                case 'command':
                    $allowed = array('training', 'website', 'help', 'support', 'shop', 'forum', 'faq', 'lang');
                    if ($val === '' || !in_array($val, $allowed, true)) {
                        $out[] = self::row(true, 'warn', 'menu:' . $id, $title, 'Command "' . $val . '" — verify handler exists');
                    } else {
                        $out[] = self::row(true, 'ok', 'menu:' . $id, $title, 'Command OK → /' . $val);
                    }
                    break;
                case 'submenu':
                    $child = 0;
                    foreach ($rows as $c) {
                        if ((int)$c['parent_id'] === $id && (int)$c['is_active'] === 1) {
                            $child++;
                        }
                    }
                    if ($child === 0) {
                        $broken++;
                        $out[] = self::row(false, 'err', 'menu:' . $id, $title, 'Submenu has no active children');
                    } else {
                        $out[] = self::row(true, 'ok', 'menu:' . $id, $title, 'Submenu OK (' . $child . ' children)');
                    }
                    break;
                case 'faq_list':
                    $out[] = self::row(true, 'ok', 'menu:' . $id, $title, 'FAQ list OK');
                    break;
                case 'text':
                    if ($val === '') {
                        $out[] = self::row(true, 'warn', 'menu:' . $id, $title, 'Text item empty');
                    } else {
                        $out[] = self::row(true, 'ok', 'menu:' . $id, $title, 'Text OK');
                    }
                    break;
                default:
                    $broken++;
                    $out[] = self::row(false, 'err', 'menu:' . $id, $title, 'Unknown menu_type: ' . $type);
            }
        }

        // Built-in professional targets
        foreach ($knownCallbacks as $cb => $label) {
            $feat = self::featureForCallback($cb);
            if ($feat && function_exists('feature_on') && !feature_on($feat)) {
                $out[] = self::row(true, 'warn', 'builtin:' . $cb, $label, 'Feature "' . $feat . '" is OFF in Settings');
            } else {
                $out[] = self::row(true, 'ok', 'builtin:' . $cb, $label, 'Built-in target online');
            }
        }

        array_unshift($out, self::row(
            $broken === 0,
            $broken === 0 ? 'ok' : 'err',
            'summary',
            'Menu health summary',
            $active . ' active menus · ' . $broken . ' broken · ' . count($rows) . ' total rows'
        ));

        return $out;
    }

    /** @return array<string,string> */
    public static function knownCallbacks(): array
    {
        return array(
            'shop' => 'Shop',
            'forum' => 'Forum',
            'support' => 'Support hub',
            'reqhub' => 'Pro Desk',
            'req:support' => 'Technical Support request',
            'req:sales' => 'Software Sales request',
            'req:mediahelp' => 'Media help',
            'help' => 'Help',
            'lang' => 'Language',
            'main' => 'Main menu',
            'menu:root' => 'Menu root',
            'faqcat:all' => 'All FAQs',
            'cart' => 'Cart',
            'orders' => 'My Orders',
            'checkout' => 'Checkout',
            'license' => 'License Status',
            'renew' => 'Renew License',
            'demo' => 'Request Demo',
            'profile' => 'My Profile',
            'feedback' => 'Feedback',
            'referral' => 'Referral',
            'contact' => 'Contact Human',
            'brands' => 'Brand Search',
            'news' => 'News / Updates',
            'miniapp' => 'Mini App',
        );
    }

    private static function featureForCallback(string $cb): ?string
    {
        $map = array(
            'shop' => 'shop',
            'forum' => 'forum',
            'support' => 'prodesk',
            'reqhub' => 'prodesk',
            'req:support' => 'prodesk',
            'req:sales' => 'prodesk',
            'cart' => 'cart',
            'orders' => 'orders',
            'checkout' => 'payments',
            'license' => 'license',
            'renew' => 'renewal',
            'demo' => 'demo',
            'profile' => 'profile',
            'feedback' => 'feedback',
            'referral' => 'referral',
            'contact' => 'contact',
            'brands' => 'brand_search',
            'news' => 'news',
            'miniapp' => 'miniapp',
        );
        return $map[$cb] ?? null;
    }

    /** @return array{ok:bool,level:string,code:string,title:string,detail:string} */
    private static function row(bool $ok, string $level, string $code, string $title, string $detail): array
    {
        return compact('ok', 'level', 'code', 'title', 'detail');
    }
}
