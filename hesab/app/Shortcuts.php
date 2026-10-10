<?php
declare(strict_types=1);

/**
 * Keyboard shortcut catalog for Hesab desktop shell.
 * Defaults follow the internal ERP standard; users can override per-account.
 */
final class Shortcuts
{
    public const MODE_ACCOUNTANT = 'accountant';
    public const MODE_MANAGER = 'manager';

    /** @return array<string, array{group:string,label:string,key:string,action:string,available:bool,hint?:string}> */
    public static function catalog(): array
    {
        return [
            // General
            'help' => ['group' => 'عمومی', 'label' => 'راهنمای صفحه', 'key' => 'F1', 'action' => 'help', 'available' => true],
            'search' => ['group' => 'عمومی', 'label' => 'جست‌وجو / انتخاب حساب', 'key' => 'F2', 'action' => 'search', 'available' => true],
            'edit' => ['group' => 'عمومی', 'label' => 'ویرایش رکورد', 'key' => 'F3', 'action' => 'edit', 'available' => true],
            'list' => ['group' => 'عمومی', 'label' => 'نمایش فهرست', 'key' => 'F4', 'action' => 'list', 'available' => true],
            'refresh' => ['group' => 'عمومی', 'label' => 'به‌روزرسانی', 'key' => 'F5', 'action' => 'refresh', 'available' => true],
            'new_record' => ['group' => 'عمومی', 'label' => 'ایجاد سند / رکورد جدید', 'key' => 'F6', 'action' => 'nav', 'available' => true, 'hint' => '/vouchers/create'],
            'calculator' => ['group' => 'عمومی', 'label' => 'ماشین‌حساب', 'key' => 'F7', 'action' => 'calculator', 'available' => true],
            'account_flow' => ['group' => 'عمومی', 'label' => 'گردش / دفتر معین', 'key' => 'F8', 'action' => 'nav', 'available' => true, 'hint' => '/ledger'],
            'recalc' => ['group' => 'عمومی', 'label' => 'محاسبه مجدد سند', 'key' => 'F9', 'action' => 'recalc', 'available' => true],
            'save' => ['group' => 'عمومی', 'label' => 'ذخیره اطلاعات', 'key' => 'F10', 'action' => 'save', 'available' => true],
            'related_report' => ['group' => 'عمومی', 'label' => 'گزارش مرتبط', 'key' => 'F11', 'action' => 'nav', 'available' => true, 'hint' => '/reports'],
            'print' => ['group' => 'عمومی', 'label' => 'چاپ', 'key' => 'F12', 'action' => 'print', 'available' => true],
            'cancel' => ['group' => 'عمومی', 'label' => 'بستن / انصراف', 'key' => 'Esc', 'action' => 'cancel', 'available' => true],

            // Documents
            'doc_new' => ['group' => 'اسناد و فاکتور', 'label' => 'سند جدید', 'key' => 'Ctrl+N', 'action' => 'nav', 'available' => true, 'hint' => '/vouchers/create'],
            'doc_save' => ['group' => 'اسناد و فاکتور', 'label' => 'ذخیره سند (پیش‌نویس)', 'key' => 'Ctrl+S', 'action' => 'save_draft', 'available' => true],
            'doc_confirm' => ['group' => 'اسناد و فاکتور', 'label' => 'تأیید سند (عملیاتی)', 'key' => 'Ctrl+Enter', 'action' => 'save_operational', 'available' => true],
            'doc_lock' => ['group' => 'اسناد و فاکتور', 'label' => 'ثبت قطعی با مجوز', 'key' => 'Ctrl+Shift+Enter', 'action' => 'save_locked', 'available' => true],
            'doc_print' => ['group' => 'اسناد و فاکتور', 'label' => 'چاپ سند', 'key' => 'Ctrl+P', 'action' => 'print', 'available' => true],
            'doc_find' => ['group' => 'اسناد و فاکتور', 'label' => 'جست‌وجو', 'key' => 'Ctrl+F', 'action' => 'search', 'available' => true],
            'doc_dup' => ['group' => 'اسناد و فاکتور', 'label' => 'تکثیر سند', 'key' => 'Ctrl+D', 'action' => 'duplicate', 'available' => false, 'hint' => 'به‌زودی'],
            'doc_edit' => ['group' => 'اسناد و فاکتور', 'label' => 'ویرایش', 'key' => 'Ctrl+E', 'action' => 'edit', 'available' => true],
            'doc_undo' => ['group' => 'اسناد و فاکتور', 'label' => 'بازگردانی فرم', 'key' => 'Ctrl+Z', 'action' => 'undo_form', 'available' => true],
            'doc_copy' => ['group' => 'اسناد و فاکتور', 'label' => 'کپی سند حسابداری', 'key' => 'Ctrl+Shift+C', 'action' => 'duplicate', 'available' => false, 'hint' => 'به‌زودی'],
            'row_delete' => ['group' => 'اسناد و فاکتور', 'label' => 'حذف ردیف', 'key' => 'Delete', 'action' => 'row_delete', 'available' => true],
            'row_insert' => ['group' => 'اسناد و فاکتور', 'label' => 'افزودن ردیف', 'key' => 'Insert', 'action' => 'row_insert', 'available' => true],

            // Specialized
            'nav_voucher' => ['group' => 'تخصصی', 'label' => 'سند حسابداری', 'key' => 'Ctrl+Alt+J', 'action' => 'nav', 'available' => true, 'hint' => '/vouchers/create'],
            'nav_sale' => ['group' => 'تخصصی', 'label' => 'فاکتور فروش', 'key' => 'Ctrl+Alt+S', 'action' => 'nav', 'available' => true, 'hint' => '/invoices'],
            'nav_parties' => ['group' => 'تخصصی', 'label' => 'طرف‌حساب‌ها', 'key' => 'Ctrl+Alt+U', 'action' => 'nav', 'available' => true, 'hint' => '/parties'],
            'nav_buy' => ['group' => 'تخصصی', 'label' => 'فاکتور خرید', 'key' => 'Ctrl+Alt+B', 'action' => 'nav', 'available' => false, 'hint' => 'ماژول خرید به‌زودی'],
            'nav_stock' => ['group' => 'تخصصی', 'label' => 'مدیریت موجودی انبار', 'key' => 'Ctrl+Alt+I', 'action' => 'nav', 'available' => false, 'hint' => 'ماژول انبار به‌زودی'],
            'nav_checks' => ['group' => 'تخصصی', 'label' => 'مدیریت چک‌ها', 'key' => 'Ctrl+Alt+C', 'action' => 'nav', 'available' => true, 'hint' => '/cheques'],
            'nav_receive' => ['group' => 'تخصصی', 'label' => 'دریافت وجه', 'key' => 'Ctrl+Alt+R', 'action' => 'nav', 'available' => true, 'hint' => '/treasury#receive'],
            'nav_pay' => ['group' => 'تخصصی', 'label' => 'پرداخت وجه', 'key' => 'Ctrl+Alt+P', 'action' => 'nav', 'available' => true, 'hint' => '/treasury#pay'],
            'nav_ledger' => ['group' => 'تخصصی', 'label' => 'دفتر معین / کل', 'key' => 'Ctrl+Alt+L', 'action' => 'nav', 'available' => true, 'hint' => '/ledger'],
            'nav_trial' => ['group' => 'تخصصی', 'label' => 'تراز آزمایشی', 'key' => 'Ctrl+Alt+T', 'action' => 'nav', 'available' => true, 'hint' => '/trial-balance'],
            'nav_kardex' => ['group' => 'تخصصی', 'label' => 'کاردکس کالا', 'key' => 'Ctrl+Alt+K', 'action' => 'nav', 'available' => false, 'hint' => 'ماژول انبار به‌زودی'],
        ];
    }

    /** Menu action id => shortcut catalog id (for accelerator labels). */
    public static function menuBindings(): array
    {
        return [
            '/vouchers/create' => 'nav_voucher',
            '/vouchers' => 'list',
            '/invoices' => 'nav_sale',
            '/parties' => 'nav_parties',
            '/treasury' => 'nav_receive',
            '/cheques' => 'nav_checks',
            '/reports/checks' => 'nav_checks',
            '/ledger' => 'nav_ledger',
            '/trial-balance' => 'nav_trial',
            '/reports' => 'related_report',
            '/settings/shortcuts' => 'help',
        ];
    }

    public static function defaults(): array
    {
        $out = [];
        foreach (self::catalog() as $id => $row) {
            $out[$id] = $row['key'];
        }
        return $out;
    }

    public static function normalizeKey(string $key): string
    {
        $key = trim($key);
        if ($key === '') {
            return '';
        }
        $key = str_replace([' ', '＋', '﹢'], ['', '+', '+'], $key);
        $key = str_ireplace(['control+', 'ctl+', 'cmd+', 'meta+'], 'Ctrl+', $key);
        $key = str_ireplace(['escape', 'esc'], 'Esc', $key);
        $parts = array_values(array_filter(explode('+', $key), static fn($p) => $p !== ''));
        $mods = [];
        $main = '';
        foreach ($parts as $p) {
            $u = strtoupper($p);
            if (in_array($u, ['CTRL', 'CONTROL', 'CTL', 'CMD', 'META'], true)) {
                $mods['Ctrl'] = true;
            } elseif ($u === 'ALT' || $u === 'OPTION') {
                $mods['Alt'] = true;
            } elseif ($u === 'SHIFT') {
                $mods['Shift'] = true;
            } else {
                if (preg_match('/^F([1-9]|1[0-2])$/i', $p)) {
                    $main = strtoupper($p);
                } elseif (strcasecmp($p, 'Esc') === 0 || strcasecmp($p, 'Escape') === 0) {
                    $main = 'Esc';
                } elseif (strcasecmp($p, 'Delete') === 0 || strcasecmp($p, 'Del') === 0) {
                    $main = 'Delete';
                } elseif (strcasecmp($p, 'Insert') === 0 || strcasecmp($p, 'Ins') === 0) {
                    $main = 'Insert';
                } elseif (strcasecmp($p, 'Enter') === 0 || strcasecmp($p, 'Return') === 0) {
                    $main = 'Enter';
                } elseif (strcasecmp($p, 'Tab') === 0) {
                    $main = 'Tab';
                } else {
                    $main = strlen($p) === 1 ? strtoupper($p) : $p;
                }
            }
        }
        $ordered = [];
        foreach (['Ctrl', 'Alt', 'Shift'] as $m) {
            if (!empty($mods[$m])) {
                $ordered[] = $m;
            }
        }
        if ($main !== '') {
            $ordered[] = $main;
        }
        return implode('+', $ordered);
    }

    public static function storageKey(?array $user): string
    {
        $id = (int) ($user['id'] ?? 0);
        return 'shortcuts_user_' . ($id > 0 ? $id : 'guest');
    }

    public static function modeKey(?array $user): string
    {
        $id = (int) ($user['id'] ?? 0);
        return 'work_mode_user_' . ($id > 0 ? $id : 'guest');
    }

    public static function getMode(?array $user): string
    {
        $row = Database::query('SELECT `value` FROM settings WHERE `key`=?', [self::modeKey($user)])->fetch();
        $mode = $row['value'] ?? self::MODE_ACCOUNTANT;
        return in_array($mode, [self::MODE_ACCOUNTANT, self::MODE_MANAGER], true) ? $mode : self::MODE_ACCOUNTANT;
    }

    public static function setMode(?array $user, string $mode): void
    {
        if (!in_array($mode, [self::MODE_ACCOUNTANT, self::MODE_MANAGER], true)) {
            $mode = self::MODE_ACCOUNTANT;
        }
        Database::query(
            'INSERT INTO settings (`key`,`value`) VALUES (?,?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)',
            [self::modeKey($user), $mode]
        );
    }

    /** @return array<string,string> id => key */
    public static function forUser(?array $user): array
    {
        $defaults = self::defaults();
        $row = Database::query('SELECT `value` FROM settings WHERE `key`=?', [self::storageKey($user)])->fetch();
        if (!$row || $row['value'] === null || $row['value'] === '') {
            return $defaults;
        }
        $decoded = json_decode((string) $row['value'], true);
        if (!is_array($decoded)) {
            return $defaults;
        }
        $out = $defaults;
        foreach ($decoded as $id => $key) {
            if (!isset($out[$id])) {
                continue;
            }
            $norm = self::normalizeKey((string) $key);
            $out[$id] = $norm;
        }
        return $out;
    }

    /** @param array<string,string> $map */
    public static function saveForUser(?array $user, array $map): array
    {
        $catalog = self::catalog();
        $clean = [];
        foreach ($catalog as $id => $_) {
            if (!array_key_exists($id, $map)) {
                continue;
            }
            $clean[$id] = self::normalizeKey((string) $map[$id]);
        }
        $conflicts = self::findConflicts($clean);
        Database::query(
            'INSERT INTO settings (`key`,`value`) VALUES (?,?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)',
            [self::storageKey($user), json_encode($clean, JSON_UNESCAPED_UNICODE)]
        );
        return $conflicts;
    }

    public static function resetForUser(?array $user): void
    {
        Database::query('DELETE FROM settings WHERE `key`=?', [self::storageKey($user)]);
    }

    /** @param array<string,string> $map @return list<string> */
    public static function findConflicts(array $map): array
    {
        $byKey = [];
        foreach ($map as $id => $key) {
            if ($key === '') {
                continue;
            }
            $byKey[$key][] = $id;
        }
        $msgs = [];
        $catalog = self::catalog();
        foreach ($byKey as $key => $ids) {
            if (count($ids) < 2) {
                continue;
            }
            $labels = array_map(static fn($id) => $catalog[$id]['label'] ?? $id, $ids);
            $msgs[] = $key . ' → ' . implode('، ', $labels);
        }
        return $msgs;
    }

    /** Payload for frontend. */
    public static function clientConfig(?array $user): array
    {
        $map = self::forUser($user);
        $catalog = self::catalog();
        $items = [];
        foreach ($catalog as $id => $meta) {
            $items[$id] = [
                'key' => $map[$id] ?? $meta['key'],
                'action' => $meta['action'],
                'label' => $meta['label'],
                'available' => $meta['available'],
                'path' => $meta['hint'] ?? null,
            ];
        }
        $titles = [
            '/vouchers/create' => 'ثبت سند حسابداری',
            '/vouchers' => 'فهرست اسناد',
            '/invoices' => 'فاکتور فروش',
            '/treasury' => 'خزانه‌داری',
            '/treasury#receive' => 'رسید دریافت',
            '/treasury#pay' => 'رسید پرداخت',
            '/cheques' => 'مدیریت چک‌ها',
            '/reports/checks' => 'چک‌ها',
            '/ledger' => 'دفتر معین',
            '/trial-balance' => 'تراز آزمایشی',
            '/reports' => 'مرکز گزارش‌ها',
            '/settings/shortcuts' => 'میانبرهای کیبورد',
        ];
        return [
            'mode' => self::getMode($user),
            'items' => $items,
            'titles' => $titles,
            'basePath' => base_path(),
        ];
    }

    public static function labelForPath(string $path, ?array $user = null): string
    {
        $path = parse_url($path, PHP_URL_PATH) ?: $path;
        $base = base_path();
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base)) ?: '/';
        }
        $bindings = self::menuBindings();
        $id = $bindings[$path] ?? null;
        if (!$id) {
            return '';
        }
        $map = self::forUser($user ?? current_user());
        return $map[$id] ?? '';
    }
}
