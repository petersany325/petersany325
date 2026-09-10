<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Route;

class NavMenu
{
    /**
     * Grouped menus with optional children, filtered by permission.
     *
     * @return list<array{key:string,label:string,route:?string,match:string,mark:string,hint:string,children:list<array>}>
     */
    public static function forUser(User $user): array
    {
        $homeRoute = $user->isIntern() ? 'intern.portal' : 'dashboard';
        $homeMatch = $user->isIntern() ? 'intern.*|dashboard' : 'dashboard';

        $groups = [
            [
                'key' => 'home',
                'label' => $user->isIntern() ? 'پرتال کارآموز' : 'میز کار',
                'permission' => 'dashboard',
                'route' => $homeRoute,
                'match' => $homeMatch,
                'mark' => $user->isIntern() ? 'آ' : 'م',
                'hint' => $user->isIntern() ? 'خدمات شرکت و ثبت کار' : 'شورت‌کارت‌ها و خلاصه',
                'children' => [],
            ],
            [
                'key' => 'reception',
                'label' => 'پذیرش',
                'permission' => null,
                'route' => null,
                'match' => 'receptions.*|deliveries.*|trash.*|remote-preorders.*',
                'mark' => 'پ',
                'hint' => 'قبض، جستجو، تحویل',
                'any_of' => ['receptions', 'remote.preorders', 'trash'],
                'children' => [
                    ['label' => 'پذیرش جدید', 'route' => 'receptions.create', 'match' => 'receptions.create', 'hint' => 'ثبت قبض تکی/گروهی', 'mark' => 'جد', 'permission' => 'receptions'],
                    ['label' => 'ورود قطعه از راه دور', 'route' => 'remote-preorders.index', 'match' => 'remote-preorders.*', 'hint' => 'پیش‌سفارش عکس و باربری', 'mark' => 'ور', 'permission' => 'remote.preorders'],
                    ['label' => 'جستجوی قبض', 'route' => 'receptions.search', 'match' => 'receptions.search', 'hint' => 'سریال، موبایل، شماره', 'mark' => 'ج', 'permission' => 'receptions'],
                    ['label' => 'لیست قبض‌ها', 'route' => 'receptions.index', 'match' => 'receptions.index|receptions.show|receptions.edit', 'hint' => 'همه پذیرش‌ها', 'mark' => 'لی', 'permission' => 'receptions'],
                    ['label' => 'تحویل گروهی', 'route' => 'deliveries.group', 'match' => 'deliveries.*', 'hint' => 'خروج چند قبض', 'mark' => 'تح', 'permission' => 'receptions'],
                    ['label' => 'سطل زباله', 'route' => 'trash.index', 'match' => 'trash.*', 'hint' => 'بازیابی یا حذف دائم', 'mark' => 'سط', 'permission' => 'trash'],
                ],
            ],
            [
                'key' => 'handoffs',
                'label' => 'ارجاع / کارتابل تعمیر',
                'permission' => 'handoffs',
                'route' => 'handoffs.index',
                'match' => 'handoffs.*',
                'mark' => 'ا',
                'hint' => 'دریافت دستگاه و هاردهای دست تعمیر',
                'children' => [
                    ['label' => 'کارتابل ارجاع', 'route' => 'handoffs.index', 'match' => 'handoffs.index', 'hint' => 'جستجو، تأیید دریافت، دست تعمیر', 'mark' => 'ک'],
                    ['label' => 'گزارش ارجاع / محل', 'route' => 'reports.custody', 'match' => 'reports.custody', 'hint' => 'گزارش سریال و قبض', 'mark' => 'گ', 'permission' => 'reports.custody'],
                ],
            ],
            [
                'key' => 'notifications',
                'label' => 'اعلان‌ها',
                'permission' => 'notifications',
                'route' => 'notifications.index',
                'match' => 'notifications.*',
                'mark' => 'ن',
                'hint' => 'پیام مشتری و اعلان ارجاع',
                'children' => [],
            ],
            [
                'key' => 'daily_logs',
                'label' => 'دفتر روز',
                'permission' => 'daily_logs',
                'route' => 'daily-logs.index',
                'match' => 'daily-logs.index|daily-logs.report',
                'mark' => 'ر',
                'hint' => 'ثبت کار و رویداد روزانه',
                'any_of' => ['daily_logs', 'daily_logs.manage'],
                'children' => [
                    ['label' => 'ثبت امروز', 'route' => 'daily-logs.index', 'match' => 'daily-logs.index', 'hint' => 'رویدادهای روز جاری', 'mark' => 'ام', 'permission' => 'daily_logs'],
                    ['label' => 'گزارش همه', 'route' => 'daily-logs.report', 'match' => 'daily-logs.report', 'hint' => 'مرور کارمندان', 'mark' => 'گ', 'permission' => 'daily_logs.manage'],
                ],
            ],
            [
                'key' => 'cost_approvals',
                'label' => 'تأیید هزینه',
                'permission' => 'receptions',
                'route' => null,
                'match' => 'cost-approvals.*',
                'mark' => 'ت',
                'hint' => 'جراحی، بازیابی، لینک تأیید',
                'children' => [
                    ['label' => 'کارتابل تأییدها', 'route' => 'cost-approvals.index', 'match' => 'cost-approvals.index', 'hint' => 'در انتظار و تاریخچه', 'mark' => 'ک'],
                    ['label' => 'خدمات مشمول', 'route' => 'cost-approvals.settings', 'match' => 'cost-approvals.settings', 'hint' => 'جراحی / بازیابی و…', 'mark' => 'خ'],
                ],
            ],
            [
                'key' => 'customers',
                'label' => 'مشتریان',
                'permission' => null,
                'route' => 'customers.index',
                'match' => 'customers.*|portal-invites.*|device-blacklists.*',
                'mark' => 'ش',
                'hint' => 'فهرست و پرونده مشتری',
                'any_of' => ['customers', 'portal.invites', 'device.blacklists'],
                'children' => [
                    ['label' => 'فهرست مشتریان', 'route' => 'customers.index', 'match' => 'customers.index|customers.show|customers.edit', 'hint' => 'جستجو، ویرایش، حذف', 'mark' => 'ف', 'permission' => 'customers'],
                    ['label' => 'مشتری جدید', 'route' => 'customers.create', 'match' => 'customers.create', 'hint' => 'نام و موبایل یکتا', 'mark' => '+', 'permission' => 'customers'],
                    ['label' => 'ارسال لینک کارتابل', 'route' => 'portal-invites.index', 'match' => 'portal-invites.*', 'hint' => 'تکی + گروهی + گزارش', 'mark' => 'ل', 'permission' => 'portal.invites'],
                    ['label' => 'لیست سیاه مشتریان', 'route' => 'customers.index', 'params' => ['filter' => 'blacklist'], 'match' => 'customers.index', 'hint' => 'مشتریان مسدود', 'mark' => 'س', 'permission' => 'customers'],
                    ['label' => 'لیست سیاه دستگاه', 'route' => 'device-blacklists.index', 'match' => 'device-blacklists.*', 'hint' => 'سریال/مدل ممنوع', 'mark' => 'د', 'permission' => 'device.blacklists'],
                ],
            ],
            [
                'key' => 'parts',
                'label' => 'انبار',
                'permission' => 'parts',
                'route' => 'parts.index',
                'match' => 'parts.*|warehouses.*|part-categories.*|warehouse-transfers.*|stocktakes.*|price-tiers.*',
                'mark' => 'ق',
                'hint' => 'انبار حسابداری قطعات',
                'children' => [
                    ['label' => 'میز انبار', 'route' => 'parts.index', 'match' => 'parts.index|parts.show|parts.edit', 'hint' => 'موجودی و ارزش', 'mark' => 'م'],
                    ['label' => 'انبارهای چندگانه', 'route' => 'warehouses.index', 'match' => 'warehouses.*', 'hint' => 'تعریف انبار ۱ و ۲…', 'mark' => 'چ'],
                    ['label' => 'گروه‌بندی درختی', 'route' => 'part-categories.index', 'match' => 'part-categories.*', 'hint' => 'فروشگاه/قطعه/اجرت', 'mark' => 'گ'],
                    ['label' => 'انتقال بین انبار', 'route' => 'warehouse-transfers.index', 'match' => 'warehouse-transfers.*', 'hint' => 'جابجایی کالا', 'mark' => 'ت'],
                    ['label' => 'انبارگردانی', 'route' => 'stocktakes.index', 'match' => 'stocktakes.*', 'hint' => 'شمارش موجودی', 'mark' => 'ا'],
                    ['label' => 'ورود از اکسل', 'route' => 'parts.import', 'match' => 'parts.import*', 'hint' => 'قیمت و موجودی', 'mark' => 'ف'],
                    ['label' => 'تیپ قیمتی', 'route' => 'price-tiers.index', 'match' => 'price-tiers.*', 'hint' => 'همکار/خاص/عمومی', 'mark' => 'ق'],
                    ['label' => 'رسید ورود', 'route' => 'parts.receipt', 'match' => 'parts.receipt*', 'hint' => 'خرید / ورود', 'mark' => 'ر'],
                    ['label' => 'حواله خروج', 'route' => 'parts.issue', 'match' => 'parts.issue*', 'hint' => 'خروج غیرقبض', 'mark' => 'ح'],
                    ['label' => 'کارتکس / گردش', 'route' => 'parts.movements', 'match' => 'parts.movements', 'hint' => 'دفتر انبار', 'mark' => 'ک'],
                    ['label' => 'ارزش موجودی', 'route' => 'parts.valuation', 'match' => 'parts.valuation', 'hint' => 'تراز ریالی', 'mark' => 'ا'],
                    ['label' => 'کالای جدید', 'route' => 'parts.create', 'match' => 'parts.create', 'hint' => 'تعریف کارت کالا', 'mark' => '+'],
                    ['label' => 'تنظیم برچسب/بارکد', 'route' => 'settings.index', 'params' => ['tab' => 'labels'], 'match' => 'settings.*|labels.*', 'hint' => 'سایز رول و حالت چاپ', 'mark' => 'ب', 'permission' => 'settings'],
                ],
            ],
            [
                'key' => 'employees',
                'label' => 'کارمندان',
                'permission' => null,
                'route' => null,
                'match' => 'employees.*|technicians.*|interns.*|staff-sms.*',
                'mark' => 'ک',
                'hint' => 'کارتابل، کارآموز، SMS',
                'any_of' => ['employees', 'technicians'],
                'children' => [
                    ['label' => 'کارتابل کارمند', 'route' => 'employees.index', 'match' => 'employees.index|employees.edit', 'hint' => 'لیست، وظیفه، دسترسی', 'mark' => 'ک', 'permission' => 'employees'],
                    ['label' => 'کارمند جدید', 'route' => 'employees.create', 'match' => 'employees.create', 'hint' => 'پذیرش / حسابدار / تعمیرکار…', 'mark' => '+', 'permission' => 'employees'],
                    ['label' => 'کارتابل کارآموز', 'route' => 'interns.index', 'match' => 'interns.index|interns.edit', 'hint' => 'دسترسی و پرتال ورود', 'mark' => 'آ', 'permission' => 'employees', 'sep' => true],
                    ['label' => 'کارآموز جدید', 'route' => 'interns.create', 'match' => 'interns.create', 'hint' => 'ثبت + فعال‌سازی ورود', 'mark' => '+', 'permission' => 'employees'],
                    ['label' => 'پرتال کارآموز (پیش‌نمایش)', 'route' => 'intern.portal', 'match' => 'intern.portal', 'hint' => 'نمای ورود کارآموز', 'mark' => 'پ', 'permission' => 'employees'],
                    ['label' => 'متن SMS خوش‌آمد', 'route' => 'staff-sms.templates', 'match' => 'staff-sms.*', 'hint' => 'کارمند و کارآموز', 'mark' => 'پ', 'permission' => 'employees'],
                    ['label' => 'تخصص و کمیسیون تعمیرکار', 'route' => 'technicians.index', 'match' => 'technicians.index|technicians.edit', 'hint' => 'هارد، بازیابی، قیمت/٪', 'mark' => 'ت', 'permission' => 'technicians', 'sep' => true],
                    ['label' => 'تعمیرکار جدید (قیمت)', 'route' => 'technicians.create', 'match' => 'technicians.create', 'hint' => 'تخصص + کمیسیون', 'mark' => '+', 'permission' => 'technicians'],
                ],
            ],
            [
                'key' => 'sms',
                'label' => 'پیامک‌ها',
                'permission' => null,
                'route' => null,
                'match' => 'sms-statuses.*|reports.sms',
                'mark' => 'پ',
                'hint' => 'وضعیت دستگاه و گزارش پیامک',
                'any_of' => ['sms.statuses', 'reports.sms'],
                'children' => [
                    ['label' => 'گزارش پیامک قبض‌ها', 'route' => 'reports.sms', 'match' => 'reports.sms', 'hint' => 'همه پیامک‌های ارسال‌شده', 'mark' => 'گ', 'permission' => 'reports.sms'],
                    ['label' => 'تعریف وضعیت / قالب', 'route' => 'sms-statuses.index', 'match' => 'sms-statuses.*', 'hint' => 'وضعیت دستگاه و متن SMS', 'mark' => 'و', 'permission' => 'sms.statuses'],
                ],
            ],
            [
                'key' => 'work',
                'label' => 'شرح کار',
                'permission' => 'receptions',
                'route' => 'work-reports.index',
                'match' => 'work-reports.*',
                'mark' => 'ش',
                'hint' => 'جستجو و تجربیات مشابه',
                'children' => [
                    ['label' => 'جستجوی شرح کارها', 'route' => 'work-reports.index', 'match' => 'work-reports.index', 'hint' => 'خصوصی/داخلی/عمومی', 'mark' => 'ج'],
                    ['label' => 'چاپ گزارش شرح کار', 'route' => 'work-reports.print', 'match' => 'work-reports.print', 'hint' => 'خروجی چاپی', 'mark' => 'چ'],
                ],
            ],
            [
                'key' => 'accounting',
                'label' => 'حسابداری',
                'permission' => null,
                'route' => 'accounting.index',
                'match' => 'accounting.*|reports.accounting|installments.*',
                'mark' => 'ح',
                'hint' => 'اسناد، خزانه، بدهکاران، اقساط',
                'any_of' => ['reports.accounting', 'installments'],
                'children' => [
                    ['label' => 'میز حسابداری', 'route' => 'accounting.index', 'match' => 'accounting.index', 'hint' => 'خلاصه خزانه و درآمد', 'mark' => 'م', 'permission' => 'reports.accounting'],
                    ['label' => 'اسناد روزنامه', 'route' => 'accounting.journals', 'match' => 'accounting.journals|accounting.show', 'hint' => 'لیست اسناد', 'mark' => 'ا', 'permission' => 'reports.accounting'],
                    ['label' => 'سرفصل حساب‌ها', 'route' => 'accounting.accounts', 'match' => 'accounting.accounts', 'hint' => 'کدینگ', 'mark' => 'س', 'permission' => 'reports.accounting'],
                    ['label' => 'دفتر معین', 'route' => 'accounting.ledger', 'match' => 'accounting.ledger', 'hint' => 'گردش حساب', 'mark' => 'د', 'permission' => 'reports.accounting'],
                    ['label' => 'تراز آزمایشی', 'route' => 'accounting.trial', 'match' => 'accounting.trial', 'hint' => 'تراز دوره', 'mark' => 'ت', 'permission' => 'reports.accounting'],
                    ['label' => 'بدهکاران', 'route' => 'accounting.receivables', 'match' => 'accounting.receivables', 'hint' => 'مانده مشتریان', 'mark' => 'ب', 'permission' => 'reports.accounting'],
                    ['label' => 'اقساط', 'route' => 'installments.index', 'match' => 'installments.*', 'hint' => 'طرح و دریافت قسط', 'mark' => 'ق', 'permission' => 'installments'],
                    ['label' => 'گزارش اقساط', 'route' => 'installments.report', 'match' => 'installments.report', 'hint' => 'پرداخت‌شده / معوق', 'mark' => 'گ', 'permission' => 'installments'],
                    ['label' => 'پیامک اقساط', 'route' => 'installments.settings', 'match' => 'installments.settings', 'hint' => 'یادآوری سررسید', 'mark' => 'پ', 'permission' => 'installments'],
                    ['label' => 'سند دستی', 'route' => 'accounting.manual', 'match' => 'accounting.manual', 'hint' => 'ثبت آزاد', 'mark' => '+', 'permission' => 'reports.accounting'],
                ],
            ],
            [
                'key' => 'reports',
                'label' => 'گزارش‌ها',
                'permission' => null,
                'route' => null,
                'match' => 'reports.*',
                'mark' => 'گ',
                'hint' => 'عملکرد و مشتریان',
                'any_of' => [
                    'reports.technicians',
                    'reports.customers',
                    'reports.parts',
                    'reports.operations',
                    'reports.custody',
                    'reports.payments',
                    'payment.receipts',
                    'reports.sms',
                    'reports.messages',
                ],
                'children' => [
                    ['label' => 'عملکرد تعمیرکاران', 'route' => 'reports.technicians', 'match' => 'reports.technicians*', 'permission' => 'reports.technicians', 'hint' => '', 'mark' => 'ت'],
                    ['label' => 'گزارش مشتریان', 'route' => 'reports.customers', 'match' => 'reports.customers*', 'permission' => 'reports.customers', 'hint' => '', 'mark' => 'ش'],
                    ['label' => 'کالای خرج‌شده', 'route' => 'reports.parts-used', 'match' => 'reports.parts-used', 'permission' => 'reports.parts', 'hint' => '', 'mark' => 'ق'],
                    ['label' => 'عملیات کارگاه', 'route' => 'reports.operations', 'match' => 'reports.operations', 'permission' => 'reports.operations', 'hint' => '', 'mark' => 'ع'],
                    ['label' => 'ارجاع / محل دستگاه', 'route' => 'reports.custody', 'match' => 'reports.custody', 'permission' => 'reports.custody', 'hint' => '', 'mark' => 'ا'],
                    ['label' => 'صندوق و دریافت‌ها', 'route' => 'reports.payments', 'match' => 'reports.payments', 'permission' => 'reports.payments', 'hint' => '', 'mark' => 'ص'],
                    ['label' => 'تأیید فیش بانکی', 'route' => 'payment-receipts.index', 'match' => 'payment-receipts.*', 'permission' => 'payment.receipts', 'hint' => 'فیش کارت‌به‌کارت پرتال', 'mark' => 'ف'],
                    ['label' => 'پیام مشتری', 'route' => 'reports.messages', 'match' => 'reports.messages', 'permission' => 'reports.messages', 'hint' => '', 'mark' => 'م'],
                    ['label' => 'گزارش پیامک', 'route' => 'reports.sms', 'match' => 'reports.sms', 'permission' => 'reports.sms', 'hint' => 'از منوی پیامک‌ها هم هست', 'mark' => 'پ'],
                ],
            ],
            [
                'key' => 'licenses',
                'label' => 'لایسنس‌ها',
                'permission' => 'licenses',
                'route' => 'licenses.index',
                'match' => 'licenses.*',
                'mark' => 'ل',
                'hint' => 'ساخت سریال و گزارش آنلاین نصب مشتریان',
                'admin_only' => true,
                'seller_only' => true,
                'children' => [
                    ['label' => 'مرکز لایسنس', 'route' => 'licenses.index', 'match' => 'licenses.index|licenses.issue|licenses.sms|licenses.revoke|licenses.unbind|licenses.extend', 'hint' => 'ساخت، ارسال SMS، باطل‌سازی', 'mark' => 'ل'],
                    ['label' => 'پلن و قیمت', 'route' => 'licenses.plans', 'match' => 'licenses.plans*', 'hint' => '۶ ماهه / یک‌ساله و قیمت‌ها', 'mark' => 'ق'],
                    ['label' => 'گزارش آنلاین', 'route' => 'licenses.online', 'match' => 'licenses.online', 'hint' => 'نصب‌های آنلاین / آفلاین', 'mark' => 'آ'],
                    ['label' => 'انتشار آپدیت', 'route' => 'licenses.releases', 'match' => 'licenses.releases*', 'hint' => 'ZIP برای پنل مشتریان', 'mark' => 'ن', 'admin_only' => true],
                ],
            ],
            [
                'key' => 'my_license',
                'label' => 'لایسنس',
                'permission' => null,
                'route' => 'my-license.index',
                'match' => 'my-license.*',
                'mark' => 'ل',
                'hint' => 'روز باقی‌مانده، تمدید و آپدیت نرم‌افزار',
                'admin_only' => true,
                'customer_only' => true,
                'children' => [
                    ['label' => 'وضعیت و روز باقی‌مانده', 'route' => 'my-license.index', 'match' => 'my-license.index', 'hint' => 'پلن، انقضا و روز مانده', 'mark' => 'و'],
                    ['label' => 'تمدید لایسنس', 'route' => 'my-license.index', 'params' => ['focus' => 'renew'], 'match' => 'my-license.index', 'hint' => 'راهنمای تمدید با فروشنده', 'mark' => 'ت'],
                    ['label' => 'آپدیت نرم‌افزار', 'route' => 'system-tools.updates', 'match' => 'system-tools.updates*', 'hint' => 'بررسی و نصب نسخه جدید', 'mark' => 'آ', 'permission' => 'system.tools'],
                ],
            ],
            [
                'key' => 'system_tools',
                'label' => 'ابزارهای سیستم',
                'permission' => 'system.tools',
                'route' => 'system-tools.index',
                'match' => 'system-tools.*',
                'mark' => 'س',
                'hint' => 'کش، تعمیر، بکاپ و آپدیت زنده',
                'children' => [
                    ['label' => 'نگهداری و بکاپ', 'route' => 'system-tools.index', 'match' => 'system-tools.index|system-tools.run|system-tools.backups*', 'hint' => 'کش، تعمیر، بکاپ و ریستور', 'mark' => 'ن'],
                    ['label' => 'آپدیت نرم‌افزار', 'route' => 'system-tools.updates', 'match' => 'system-tools.updates*', 'hint' => 'بررسی زنده و نصب نسخه جدید', 'mark' => 'آ'],
                ],
            ],
            [
                'key' => 'settings',
                'label' => 'تنظیمات',
                'permission' => null,
                'route' => null,
                'match' => 'settings.*|profile.*|daily-logs.settings',
                'mark' => 'ظ',
                'hint' => 'سیستم و منوها',
                'any_of' => ['settings', 'profile', 'daily_logs.manage'],
                'children' => [
                    ['label' => 'تنظیمات سیستم', 'route' => 'settings.index', 'match' => 'settings.*', 'hint' => 'منو، فاکتور، برچسب، SMS، بکاپ', 'mark' => 'ظ', 'permission' => 'settings'],
                    ['label' => 'برچسب / بارکد', 'route' => 'settings.index', 'params' => ['tab' => 'labels'], 'match' => 'labels.*', 'hint' => 'رول، حالت چاپ، پرینتر', 'mark' => 'ب', 'permission' => 'settings'],
                    ['label' => 'پیش‌نمایش برچسب', 'route' => 'labels.preview', 'match' => 'labels.preview', 'hint' => 'تست چاپ بارکد', 'mark' => 'پ', 'permission' => 'settings'],
                    ['label' => 'تنظیمات دفتر روز', 'route' => 'daily-logs.settings', 'match' => 'daily-logs.settings', 'hint' => 'دسته و قوانین — فقط ادمین', 'mark' => 'ر', 'permission' => 'daily_logs.manage'],
                    ['label' => 'پروفایل من', 'route' => 'profile.edit', 'match' => 'profile.*', 'hint' => 'نام و رمز', 'mark' => 'پ', 'permission' => 'profile'],
                ],
            ],
        ];

        $out = [];
        $isSeller = LicenseStatus::isSellerSite();
        foreach ($groups as $group) {
            if (! empty($group['seller_only']) && ! $isSeller) {
                continue;
            }
            if (! empty($group['customer_only']) && $isSeller) {
                continue;
            }
            if (! empty($group['admin_only']) && ! $user->isAdmin()) {
                continue;
            }
            if (! empty($group['any_of'])) {
                $allowed = collect($group['any_of'])->contains(fn ($p) => $user->canAccess($p));
                if (! $allowed) {
                    continue;
                }
            } elseif (! empty($group['permission']) && ! $user->canAccess($group['permission'])) {
                continue;
            }

            $children = [];
            foreach ($group['children'] as $child) {
                if (! empty($child['seller_only']) && ! $isSeller) {
                    continue;
                }
                if (! empty($child['customer_only']) && $isSeller) {
                    continue;
                }
                if (! empty($child['admin_only']) && ! $user->isAdmin()) {
                    continue;
                }
                $perm = $child['permission'] ?? $group['permission'] ?? null;
                if ($perm && ! $user->canAccess($perm)) {
                    continue;
                }
                if (! empty($child['route']) && ! Route::has($child['route'])) {
                    continue;
                }
                $children[] = $child;
            }

            if (! empty($group['any_of']) && $children === [] && empty($group['route'])) {
                continue;
            }

            $group['children'] = $children;
            if (empty($group['route']) && $children !== []) {
                $group['route'] = $children[0]['route'];
            }
            $out[] = $group;
        }

        return $out;
    }

    /**
     * @return list<array{label:string,route:string,hint:string,mark:string,group:string,tone:string}>
     */
    public static function shortcuts(User $user): array
    {
        $cards = [];
        foreach (self::forUser($user) as $group) {
            if (empty($group['route'])) {
                continue;
            }
            $hint = $group['hint'];
            if ($group['children']) {
                $hint = collect($group['children'])->pluck('label')->implode(' · ');
            }
            $cards[] = [
                'label' => $group['label'],
                'route' => $group['route'],
                'hint' => $hint,
                'mark' => $group['mark'],
                'group' => $group['label'],
                'tone' => self::tone($group['key']),
            ];
        }

        return $cards;
    }

    public static function tone(string $key): string
    {
        return match ($key) {
            'home' => 'slate',
            'reception' => 'blue',
            'handoffs' => 'green',
            'notifications' => 'violet',
            'daily_logs' => 'amber',
            'cost_approvals' => 'amber',
            'customers' => 'teal',
            'parts' => 'amber',
            'technicians' => 'green',
            'employees' => 'teal',
            'sms' => 'violet',
            'accounting' => 'teal',
            'reports' => 'green',
            'system_tools' => 'teal',
            'licenses' => 'violet',
            'my_license' => 'violet',
            'settings' => 'slate',
            default => 'slate',
        };
    }

    /** Short label for mobile tabbar. */
    public static function shortLabel(string $key, string $fallback = ''): string
    {
        return match ($key) {
            'home' => 'میز',
            'reception' => 'پذیرش',
            'handoffs' => 'ارجاع',
            'work' => 'شرح‌کار',
            'notifications' => 'اعلان',
            'daily_logs' => 'دفتر',
            'cost_approvals' => 'تأیید',
            'customers' => 'مشتری',
            'parts' => 'انبار',
            'employees' => 'کارمند',
            'sms' => 'پیامک',
            'accounting' => 'حساب',
            'reports' => 'گزارش',
            'system_tools' => 'ابزار',
            'settings' => 'تنظیم',
            default => $fallback !== '' ? mb_substr($fallback, 0, 8) : 'منو',
        };
    }

    /**
     * Primary bottom-tab items for mobile staff shell (max 4 + «بیشتر»).
     *
     * @return list<array{key:string,label:string,mark:string,route:?string,match:string,tone:string}>
     */
    public static function mobilePrimary(User $user, ?array $groups = null): array
    {
        $groups = collect($groups ?? self::forUser($user))->keyBy('key');
        // کارتابل تعمیرکار (handoffs) و شرح کار باید روی موبایل در دسترس باشند.
        $order = [
            'home', 'handoffs', 'reception', 'work', 'daily_logs', 'notifications',
            'customers', 'sms', 'parts', 'cost_approvals', 'accounting', 'reports',
        ];

        $tabs = [];
        foreach ($order as $key) {
            if (! $groups->has($key)) {
                continue;
            }
            $g = $groups->get($key);
            $route = $g['route'] ?? null;
            if (! $route && ! empty($g['children'][0]['route'])) {
                $route = $g['children'][0]['route'];
            }
            if (! $route || ! Route::has($route)) {
                continue;
            }
            $tabs[] = [
                'key' => $key,
                'label' => self::shortLabel($key, $g['label']),
                'mark' => $g['mark'],
                'route' => $route,
                'match' => $g['match'],
                'tone' => self::tone($key),
            ];
            if (count($tabs) >= 4) {
                break;
            }
        }

        return $tabs;
    }

    public static function isActive(string $match): bool
    {
        $patterns = array_values(array_filter(explode('|', $match)));
        if ($patterns === []) {
            return false;
        }

        return request()->routeIs(...$patterns);
    }
}
