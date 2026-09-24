<?php

namespace Plugins\Accounting\src\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * HDD Land chart of accounts — Iranian 9-group tree, shop + repair + training.
 * Group 7 = COGS, group 8 = expenses (standard sample FS, not the swapped file).
 */
class AccChart
{
    public const LEVELS = [
        'group' => 'گروه',
        'kol' => 'کل',
        'moeen' => 'معین',
        'tafsil' => 'تفصیل',
    ];

    public const TYPES = [
        'asset' => 'دارایی',
        'liability' => 'بدهی',
        'equity' => 'سرمایه',
        'income' => 'درآمد',
        'cogs' => 'بهای تمام‌شده',
        'expense' => 'هزینه',
        'memo' => 'انتظامی',
    ];

    /** @return list<array{code:string,name:string,type:string,nature:string,level:string,parent:?string,postable:bool}> */
    public static function tree(): array
    {
        $rows = [];
        $add = static function (array $item, ?string $parent, string $level) use (&$rows, &$add): void {
            $rows[] = [
                'code' => $item[0],
                'name' => $item[1],
                'type' => $item[2],
                'nature' => $item[3],
                'level' => $level,
                'parent' => $parent,
                'postable' => ! empty($item[4]),
            ];
            foreach ($item[5] ?? [] as $child) {
                $next = $level === 'group' ? 'kol' : ($level === 'kol' ? 'moeen' : 'tafsil');
                $add($child, $item[0], $next);
            }
        };
        foreach (self::definition() as $group) {
            $add($group, null, 'group');
        }

        return $rows;
    }

    /** Default posting map: logical key => account code. */
    public static function defaultMap(): array
    {
        return [
            'cash' => '1101',
            'bank' => '1102',
            'gateway' => '1104',
            'ar' => '1301',
            'ar_install' => '1302',
            'notes' => '1303',
            'inventory' => '1401',
            'parts' => '1402',
            'vat_in' => '1603',
            'ap' => '3101',
            'comm_pay' => '3102',
            'notes_pay' => '3103',
            'vat_out' => '3205',
            'wages' => '3207',
            'wallet' => '3208',
            'warranty_res' => '3501',
            'sales' => '6101',
            'recovery' => '6102',
            'repair' => '6103',
            'training' => '6104',
            'returns' => '6106',
            'discount' => '6107',
            'cogs' => '7101',
            'cogs_svc' => '7102',
            'exp_ops' => '8209',
            'exp_pay' => '8301',
            'exp_comm' => '8305',
            'exp_warranty' => '8501',
            'capital' => '5001',
        ];
    }

    public static function setting(string $key, ?string $fallback = null): string
    {
        $fallback = $fallback ?? (self::defaultMap()[$key] ?? '');
        try {
            if (Schema::hasTable('acc_settings')) {
                $v = DB::table('acc_settings')->where('k', $key)->value('v');
                if (is_string($v) && $v !== '') {
                    return $v;
                }
            }
        } catch (\Throwable) {
        }

        return $fallback;
    }

    public static function setSetting(string $key, string $value): void
    {
        if (! Schema::hasTable('acc_settings')) {
            return;
        }
        $now = now();
        if (DB::table('acc_settings')->where('k', $key)->exists()) {
            DB::table('acc_settings')->where('k', $key)->update(['v' => $value, 'updated_at' => $now]);
        } else {
            DB::table('acc_settings')->insert(['k' => $key, 'v' => $value, 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    public static function idByCode(string $code): ?int
    {
        if ($code === '' || ! Schema::hasTable('acc_accounts')) {
            return null;
        }
        $id = DB::table('acc_accounts')->where('code', $code)->where('is_active', 1)->value('id');

        return $id ? (int) $id : null;
    }

    public static function paymentAccountCode(?string $method, bool $paid = true): string
    {
        $m = strtolower(trim((string) $method));
        if (in_array($m, ['installment', 'اقساط', 'ghest'], true)) {
            return self::setting('ar_install');
        }
        if (in_array($m, ['check', 'cheque', 'چک'], true)) {
            return self::setting('notes');
        }
        if (in_array($m, ['wallet', 'کیف پول'], true)) {
            return self::setting('wallet');
        }
        if (! $paid) {
            return self::setting('ar');
        }
        if (in_array($m, ['cash', 'نقد', 'صندوق'], true)) {
            return self::setting('cash');
        }
        if (in_array($m, ['bank', 'transfer', 'card_to_card', 'کارت به کارت', 'حواله'], true)) {
            return self::setting('bank');
        }

        return self::setting('gateway');
    }

    public static function seed(): void
    {
        if (! Schema::hasTable('acc_accounts')) {
            return;
        }
        try {
            if (DB::table('acc_accounts')->where('code', '6102')->where('is_active', 1)->exists()
                && Schema::hasTable('acc_settings')
                && DB::table('acc_settings')->where('k', 'chart_version')->value('v') === '1.4.0') {
                return;
            }
        } catch (\Throwable) {
        }
        $now = now();
        foreach (self::tree() as $i => $row) {
            $parentId = null;
            if ($row['parent']) {
                $parentId = DB::table('acc_accounts')->where('code', $row['parent'])->value('id');
            }
            $payload = [
                'name' => $row['name'],
                'type' => $row['type'],
                'is_active' => true,
                'updated_at' => $now,
            ];
            if (Schema::hasColumn('acc_accounts', 'parent_id')) {
                $payload['parent_id'] = $parentId;
            }
            if (Schema::hasColumn('acc_accounts', 'level')) {
                $payload['level'] = $row['level'];
            }
            if (Schema::hasColumn('acc_accounts', 'nature')) {
                $payload['nature'] = $row['nature'];
            }
            if (Schema::hasColumn('acc_accounts', 'is_postable')) {
                $payload['is_postable'] = $row['postable'];
            }
            if (Schema::hasColumn('acc_accounts', 'is_system')) {
                $payload['is_system'] = true;
            }
            if (Schema::hasColumn('acc_accounts', 'sort')) {
                $payload['sort'] = $i;
            }
            $exists = DB::table('acc_accounts')->where('code', $row['code'])->exists();
            if ($exists) {
                DB::table('acc_accounts')->where('code', $row['code'])->update($payload);
            } else {
                $payload['code'] = $row['code'];
                $payload['created_at'] = $now;
                DB::table('acc_accounts')->insert($payload);
            }
        }
        foreach (['1110', '1120', '1210', '1310', '2110', '3110', '4110', '5110', '5210', '5220', '5230'] as $legacy) {
            DB::table('acc_accounts')->where('code', $legacy)->update(['is_active' => false, 'updated_at' => $now]);
        }
        if (Schema::hasTable('acc_settings') && DB::table('acc_settings')->count() === 0) {
            foreach (self::defaultMap() as $k => $v) {
                DB::table('acc_settings')->insert(['k' => $k, 'v' => $v, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
        self::setSetting('chart_version', '1.4.0');
    }

    /**
     * @return list<array{0:string,1:string,2:string,3:string,4?:bool,5?:list}>
     */
    public static function definition(): array
    {
        $m = static fn (string $c, string $n, string $t, string $nat = 'debit') => [$c, $n, $t, $nat, true];

        return [
            ['1', 'دارایی‌های جاری', 'asset', 'debit', false, [
                ['11', 'موجودی نقد', 'asset', 'debit', false, [
                    $m('1101', 'صندوق ریالی', 'asset'),
                    $m('1102', 'بانک‌ها', 'asset'),
                    $m('1103', 'تنخواه‌گردان', 'asset'),
                    $m('1104', 'درگاه و کارت‌خوان', 'asset'),
                ]],
                ['13', 'دریافتنی‌های تجاری', 'asset', 'debit', false, [
                    $m('1301', 'مشتریان فروشگاه', 'asset'),
                    $m('1302', 'اقساط مشتریان', 'asset'),
                    $m('1303', 'اسناد و چک دریافتنی', 'asset'),
                    $m('1305', 'ذخیره مطالبات مشکوک‌الوصول', 'asset', 'credit'),
                ]],
                ['14', 'موجودی کالا', 'asset', 'debit', false, [
                    $m('1401', 'کالای آماده فروش', 'asset'),
                    $m('1402', 'قطعات تعمیر', 'asset'),
                    $m('1403', 'کالا در تعمیر / امانی', 'asset'),
                ]],
                ['16', 'پیش‌پرداخت‌ها', 'asset', 'debit', false, [
                    $m('1601', 'پیش‌پرداخت خرید', 'asset'),
                    $m('1602', 'پیش‌پرداخت هزینه', 'asset'),
                    $m('1603', 'مالیات ارزش افزوده خرید', 'asset'),
                ]],
            ]],
            ['2', 'دارایی‌های غیرجاری', 'asset', 'debit', false, [
                ['24', 'دارایی نامشهود', 'asset', 'debit', false, [
                    $m('2402', 'نرم‌افزارها', 'asset'),
                ]],
                ['25', 'دارایی ثابت مشهود', 'asset', 'debit', false, [
                    $m('2502', 'ساختمان', 'asset'),
                    $m('2503', 'وسایل نقلیه', 'asset'),
                    $m('2504', 'اثاثیه و منصوبات', 'asset'),
                    $m('2505', 'تجهیزات تعمیرگاه', 'asset'),
                ]],
                ['26', 'استهلاک انباشته', 'asset', 'credit', false, [
                    $m('2602', 'استهلاک انباشته ساختمان', 'asset', 'credit'),
                    $m('2603', 'استهلاک انباشته وسایل نقلیه', 'asset', 'credit'),
                    $m('2605', 'استهلاک انباشته تجهیزات', 'asset', 'credit'),
                ]],
            ]],
            ['3', 'بدهی‌های جاری', 'liability', 'credit', false, [
                ['31', 'پرداختنی‌های تجاری', 'liability', 'credit', false, [
                    $m('3101', 'تأمین‌کنندگان', 'liability', 'credit'),
                    $m('3102', 'پورسانت پرداختنی', 'liability', 'credit'),
                    $m('3103', 'اسناد و چک پرداختنی', 'liability', 'credit'),
                ]],
                ['32', 'سایر پرداختنی‌ها', 'liability', 'credit', false, [
                    $m('3202', 'مالیات حقوق', 'liability', 'credit'),
                    $m('3204', 'بیمه تأمین اجتماعی', 'liability', 'credit'),
                    $m('3205', 'ارزش افزوده فروش', 'liability', 'credit'),
                    $m('3207', 'حقوق پرداختنی', 'liability', 'credit'),
                    $m('3208', 'کیف پول مشتریان', 'liability', 'credit'),
                ]],
                ['35', 'ذخایر', 'liability', 'credit', false, [
                    $m('3501', 'ذخیره گارانتی', 'liability', 'credit'),
                    $m('3502', 'ذخیره مرخصی', 'liability', 'credit'),
                ]],
                ['36', 'پیش‌دریافت‌ها', 'liability', 'credit', false, [
                    $m('3601', 'پیش‌دریافت فروش', 'liability', 'credit'),
                    $m('3602', 'پیش‌دریافت آموزش', 'liability', 'credit'),
                ]],
            ]],
            ['4', 'بدهی‌های غیرجاری', 'liability', 'credit', false, [
                ['43', 'مزایای پایان خدمت', 'liability', 'credit', false, [
                    $m('4301', 'ذخیره سنوات کارکنان', 'liability', 'credit'),
                ]],
            ]],
            ['5', 'سرمایه', 'equity', 'credit', false, [
                ['50', 'سرمایه', 'equity', 'credit', false, [
                    $m('5001', 'سرمایه ثبت‌شده', 'equity', 'credit'),
                ]],
                ['58', 'سود و زیان', 'equity', 'credit', false, [
                    $m('5801', 'سود و زیان جاری', 'equity', 'credit'),
                    $m('5802', 'سود و زیان انباشته', 'equity', 'credit'),
                ]],
            ]],
            ['6', 'درآمدها', 'income', 'credit', false, [
                ['61', 'درآمد عملیاتی', 'income', 'credit', false, [
                    $m('6101', 'فروش کالا', 'income', 'credit'),
                    $m('6102', 'درآمد ریکاوری داده', 'income', 'credit'),
                    $m('6103', 'درآمد تعمیر سخت‌افزار', 'income', 'credit'),
                    $m('6104', 'درآمد آموزش', 'income', 'credit'),
                    $m('6105', 'درآمد گارانتی و خدمات', 'income', 'credit'),
                    $m('6106', 'برگشت از فروش', 'income', 'debit'),
                    $m('6107', 'تخفیفات فروش', 'income', 'debit'),
                ]],
            ]],
            ['7', 'بهای تمام‌شده', 'cogs', 'debit', false, [
                ['71', 'بهای تمام‌شده فروش', 'cogs', 'debit', false, [
                    $m('7101', 'بهای کالای فروش‌رفته', 'cogs'),
                    $m('7102', 'بهای خدمات فنی', 'cogs'),
                ]],
            ]],
            ['8', 'هزینه‌ها', 'expense', 'debit', false, [
                ['81', 'هزینه‌های مالی', 'expense', 'debit', false, [
                    $m('8101', 'سود و کارمزد تسهیلات', 'expense'),
                ]],
                ['82', 'هزینه عملیاتی تعمیرگاه', 'expense', 'debit', false, [
                    $m('8201', 'اجاره محل', 'expense'),
                    $m('8202', 'تعمیر و نگهداری', 'expense'),
                    $m('8203', 'حمل و نقل', 'expense'),
                    $m('8204', 'آب، برق، گاز، تلفن', 'expense'),
                    $m('8205', 'ملزومات', 'expense'),
                    $m('8206', 'اینترنت', 'expense'),
                    $m('8207', 'استهلاک', 'expense'),
                    $m('8209', 'سایر هزینه‌های عملیاتی', 'expense'),
                ]],
                ['83', 'هزینه کارکنان', 'expense', 'debit', false, [
                    $m('8301', 'حقوق پایه', 'expense'),
                    $m('8302', 'اضافه‌کار و مزایا', 'expense'),
                    $m('8303', 'بیمه سهم کارفرما', 'expense'),
                    $m('8305', 'پورسانت فروش', 'expense'),
                ]],
                ['84', 'بازاریابی', 'expense', 'debit', false, [
                    $m('8401', 'تبلیغات', 'expense'),
                ]],
                ['85', 'گارانتی و مطالبات', 'expense', 'debit', false, [
                    $m('8501', 'هزینه گارانتی', 'expense'),
                    $m('8502', 'مطالبات مشکوک‌الوصول', 'expense'),
                ]],
                ['86', 'اداری', 'expense', 'debit', false, [
                    $m('8601', 'حسابرسی و مشاوره', 'expense'),
                    $m('8602', 'سایر اداری', 'expense'),
                ]],
            ]],
            ['9', 'حساب‌های انتظامی', 'memo', 'debit', false, [
                ['91', 'انتظامی', 'memo', 'debit', false, [
                    $m('9101', 'حساب انتظامی', 'memo'),
                    $m('9102', 'طرف حساب انتظامی', 'memo', 'credit'),
                ]],
            ]],
        ];
    }
}
