<?php

namespace Plugins\Accounting\src\Support;

use Illuminate\Support\Facades\View;

/**
 * Accounting pages must never 500.
 * Try the plugin view, then the core admin fallback, then raw HTML.
 */
class AccSafe
{
    /** @return list<array{label:string,href:string}> */
    public static function menus(): array
    {
        return [
            ['label' => 'داشبورد', 'href' => '/admin/accounting'],
            ['label' => 'اسناد', 'href' => '/admin/accounting/docs'],
            ['label' => 'فاکتور فروش', 'href' => '/admin/accounting/docs/create?type=sale'],
            ['label' => 'فاکتور خرید', 'href' => '/admin/accounting/docs/create?type=purchase'],
            ['label' => 'پیش‌فاکتور', 'href' => '/admin/accounting/docs/create?type=proforma'],
            ['label' => 'سند دستی', 'href' => '/admin/accounting/docs/create?type=voucher'],
            ['label' => 'تعریف کالا', 'href' => '/admin/accounting/goods'],
            ['label' => 'انبارها', 'href' => '/admin/accounting/warehouses'],
            ['label' => 'حواله / رسید', 'href' => '/admin/accounting/stock'],
            ['label' => 'بانک‌ها', 'href' => '/admin/accounting/banks'],
            ['label' => 'هزینه‌ها', 'href' => '/admin/accounting/expenses'],
            ['label' => 'کارمند و ویزیتور', 'href' => '/admin/accounting/staff'],
            ['label' => 'حقوق', 'href' => '/admin/accounting/payroll'],
            ['label' => 'دسته چک', 'href' => '/admin/accounting/checkbooks'],
            ['label' => 'چک دریافتی', 'href' => '/admin/accounting/checks/received'],
            ['label' => 'چک خرج‌شده', 'href' => '/admin/accounting/checks/spent'],
            ['label' => 'اخطار چک', 'href' => '/admin/accounting/checks/alerts'],
            ['label' => 'اقساط', 'href' => '/admin/accounting/installments'],
            ['label' => 'کدینگ', 'href' => '/admin/accounting/chart'],
            ['label' => 'مرکز گزارش', 'href' => '/admin/accounting/reports'],
            ['label' => 'فروش / خرید', 'href' => '/admin/accounting/reports/sales'],
            ['label' => 'تراز آزمایشی', 'href' => '/admin/accounting/reports/trial'],
            ['label' => 'سود و زیان', 'href' => '/admin/accounting/reports/income'],
            ['label' => 'ترازنامه', 'href' => '/admin/accounting/reports/balance'],
            ['label' => 'موجودی سایت', 'href' => '/admin/accounting/reports/shop-stock'],
            ['label' => 'تنظیمات', 'href' => '/admin/accounting/settings'],
        ];
    }

    /**
     * Run a GET action; any throw becomes a working page instead of HTTP 500.
     *
     * @return \Illuminate\Http\Response|\Illuminate\Contracts\View\View|\Symfony\Component\HttpFoundation\Response
     */
    public static function wrap(string $pluginView, callable $builder, string $title = 'حسابداری')
    {
        try {
            $data = $builder();
            if ($data instanceof \Symfony\Component\HttpFoundation\Response) {
                return $data;
            }
            if ($data instanceof \Illuminate\Contracts\View\View) {
                try {
                    return response($data->render(), 200, ['Content-Type' => 'text/html; charset=utf-8']);
                } catch (\Throwable) {
                    return self::page($pluginView, [], $title);
                }
            }

            return self::page($pluginView, is_array($data) ? $data : [], $title);
        } catch (\Throwable) {
            return self::page($pluginView, [], $title);
        }
    }

    /**
     * @param  array<string,mixed>  $data
     * @return \Illuminate\Http\Response|\Illuminate\Contracts\View\View
     */
    public static function page(string $pluginView, array $data = [], string $title = 'حسابداری')
    {
        try {
            AccRoutes::registerViews();
        } catch (\Throwable) {
        }
        $payload = $data + [
            'accTitle' => $title,
            'accMenus' => self::menus(),
        ];
        foreach ([$pluginView, 'admin.accounting-page'] as $name) {
            try {
                if (! View::exists($name)) {
                    continue;
                }
                $html = view($name, $payload)->render();
                if (is_string($html) && $html !== '') {
                    return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
                }
            } catch (\Throwable) {
            }
        }

        return response(self::html($title), 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public static function html(string $title = 'حسابداری'): string
    {
        $items = '';
        foreach (self::menus() as $m) {
            $items .= '<a href="'.htmlspecialchars($m['href'], ENT_QUOTES, 'UTF-8').'">'.htmlspecialchars($m['label'], ENT_QUOTES, 'UTF-8').'</a>';
        }

        return '<!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.htmlspecialchars($title, ENT_QUOTES, 'UTF-8').'</title><link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;700&display=swap" rel="stylesheet"><style>body{font-family:Vazirmatn,Tahoma,sans-serif;background:#f2f6f7;margin:0;padding:1.2rem}h1{color:#0b4f4c}a{display:inline-block;margin:.25rem;padding:.55rem .85rem;border-radius:12px;background:#0f6e6a;color:#fff;text-decoration:none}</style></head><body><h1>'.$title.'</h1><p>منوهای حسابداری:</p>'.$items.'</body></html>';
    }
}
