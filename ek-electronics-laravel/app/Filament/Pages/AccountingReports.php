<?php

namespace App\Filament\Pages;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use UnitEnum;

class AccountingReports extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Accounting';

    protected static ?string $navigationLabel = 'P&L & reports';

    protected static ?string $title = 'Profit & loss and shop reports';

    protected string $view = 'filament.pages.accounting-reports';

    protected static ?int $navigationSort = 1;

    /** @var array<string, mixed> */
    public array $report = [];

    public function mount(): void
    {
        $revenue = (float) Order::query()->whereIn('status', ['paid', 'dispatched', 'collected'])->sum('total');
        $ordersCount = (int) Order::query()->whereIn('status', ['paid', 'dispatched', 'collected'])->count();
        $cogs = (float) DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->whereIn('orders.status', ['paid', 'dispatched', 'collected'])
            ->selectRaw('COALESCE(SUM(order_items.qty * products.cost),0) as total')
            ->value('total');
        $expenses = (float) Expense::query()->sum('amount');
        $expensesByCategory = Expense::query()
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();
        $openInvoices = (float) Invoice::query()->whereIn('status', ['open', 'late'])->sum('amount');
        $paidInvoices = (float) Invoice::query()->where('status', 'paid')->sum('amount');
        $inventoryValue = (float) Product::query()->selectRaw('COALESCE(SUM(cost * stock),0) as v')->value('v');
        $inventoryRetail = (float) Product::query()->selectRaw('COALESCE(SUM(price * stock),0) as v')->value('v');
        $gross = $revenue - $cogs;
        $net = $gross - $expenses;
        $byStatus = Order::query()
            ->selectRaw('status, COUNT(*) as cnt, COALESCE(SUM(total),0) as total')
            ->groupBy('status')
            ->get();
        $recentExpenses = Expense::query()->latest('spent_on')->take(12)->get();
        $topProducts = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('orders.status', ['paid', 'dispatched', 'collected'])
            ->selectRaw('product_name, SUM(qty) as units, SUM(line_total) as revenue')
            ->groupBy('product_name')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

        $this->report = compact(
            'revenue', 'ordersCount', 'cogs', 'expenses', 'gross', 'net',
            'openInvoices', 'paidInvoices', 'inventoryValue', 'inventoryRetail',
            'expensesByCategory', 'byStatus', 'recentExpenses', 'topProducts'
        );
    }
}
