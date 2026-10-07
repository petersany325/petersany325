<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\WhatsappMessage;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $revenue = (float) Order::query()->whereIn('status', ['paid', 'dispatched', 'collected'])->sum('total');
        $openInvoices = (float) Invoice::query()->whereIn('status', ['open', 'late'])->sum('amount');
        $waToday = WhatsappMessage::query()->whereDate('sent_at', today())->count();

        return [
            Stat::make('Orders', (string) Order::query()->count())
                ->description('All time')
                ->color('primary'),
            Stat::make('Paid revenue', 'R '.number_format($revenue, 2))
                ->description('Paid / dispatched / collected')
                ->color('success'),
            Stat::make('Open invoices', 'R '.number_format($openInvoices, 2))
                ->description('Waiting on WhatsApp / EFT')
                ->color('warning'),
            Stat::make('WhatsApp today', (string) $waToday)
                ->description('Logged outbound messages')
                ->color('success'),
        ];
    }
}
