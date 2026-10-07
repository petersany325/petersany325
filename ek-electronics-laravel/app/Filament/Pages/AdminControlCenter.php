<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class AdminControlCenter extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?string $navigationLabel = 'Control center';

    protected static ?string $title = 'EK admin control center';

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = -100;

    protected string $view = 'filament.pages.admin-control-center';

    /** @return array<string, array<int, array{label: string, url: string, hint: string}>> */
    public function sections(): array
    {
        return [
            'Content & menus' => [
                ['label' => 'Homepage editor', 'url' => '/admin/homepage-editor', 'hint' => 'Live edit the homepage'],
                ['label' => 'Menus (header & footer)', 'url' => '/admin/menus', 'hint' => 'Submenus, hints, highlight'],
                ['label' => 'Page builder', 'url' => '/admin/cms-pages/pages', 'hint' => 'Advanced CMS blocks'],
                ['label' => 'Footer settings', 'url' => '/admin/manage-footer-settings', 'hint' => 'Footer text & socials'],
            ],
            'Store' => [
                ['label' => 'Store settings', 'url' => '/admin/manage-store-settings', 'hint' => 'Shop, hero, VAT, hours'],
                ['label' => 'Quick contact', 'url' => '/admin/manage-site-settings', 'hint' => 'Phone, email, address'],
                ['label' => 'Categories', 'url' => '/admin/categories', 'hint' => 'Catalogue groups'],
                ['label' => 'Products', 'url' => '/admin/products', 'hint' => 'SKU, price, cost, stock'],
                ['label' => 'Orders', 'url' => '/admin/orders', 'hint' => 'Shop orders'],
            ],
            'Customer & staff login' => [
                ['label' => 'Login & portals', 'url' => '/admin/manage-auth-settings', 'hint' => 'Customer register + staff login'],
                ['label' => 'Admin users', 'url' => '/admin/users', 'hint' => 'Roles: admin / staff / customer'],
                ['label' => 'Customer portal', 'url' => '/account', 'hint' => 'Mobile customer desk'],
                ['label' => 'Staff portal', 'url' => '/staff', 'hint' => 'Mobile staff desk'],
            ],
            'Accounting' => [
                ['label' => 'P&L & reports', 'url' => '/admin/accounting-reports', 'hint' => 'Profit, COGS, expenses'],
                ['label' => 'Invoices', 'url' => '/admin/invoices', 'hint' => 'Linked to shop orders'],
                ['label' => 'Expenses', 'url' => '/admin/expenses', 'hint' => 'Operating costs'],
            ],
            'Support & WhatsApp' => [
                ['label' => 'Tickets', 'url' => '/admin/tickets', 'hint' => 'Customer ticket system'],
                ['label' => 'WhatsApp API', 'url' => '/admin/manage-whatsapp-api', 'hint' => 'Activate Cloud API'],
                ['label' => 'Chat settings', 'url' => '/admin/manage-whatsapp-settings', 'hint' => 'Widget, templates, hours'],
                ['label' => 'WhatsApp desk', 'url' => '/admin/whatsapp-messages', 'hint' => 'Message log'],
            ],
            'Services' => [
                ['label' => 'Recovery jobs', 'url' => '/admin/recovery-jobs', 'hint' => 'Data recovery pipeline'],
            ],
            'Site repair' => [
                ['label' => 'Site repair', 'url' => '/admin/site-repair', 'hint' => 'Clear cache, fix database, optimize'],
            ],
        ];
    }
}
