<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\RecoveryJob;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Services\WhatsApp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StaffPortalController extends Controller
{
    public function dashboard(): View
    {
        return view('portal.staff.dashboard', [
            'ordersOpen' => Order::query()->where('status', 'new')->count(),
            'ticketsOpen' => Ticket::query()->whereIn('status', ['open', 'pending'])->count(),
            'jobs' => RecoveryJob::query()->latest()->take(5)->get(),
            'invoicesOpen' => Invoice::query()->whereIn('status', ['open', 'late'])->count(),
            'lowStock' => Product::query()->where('stock', '<', 10)->orderBy('stock')->take(5)->get(),
        ]);
    }

    public function orders(): View
    {
        $orders = Order::query()->latest()->paginate(25);

        return view('portal.staff.orders', compact('orders'));
    }

    public function tickets(): View
    {
        $tickets = Ticket::query()->latest()->paginate(25);

        return view('portal.staff.tickets', compact('tickets'));
    }

    public function showTicket(Ticket $ticket): View
    {
        $ticket->load('replies');

        return view('portal.staff.ticket-show', compact('ticket'));
    }

    public function replyTicket(Request $request, Ticket $ticket): RedirectResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'status' => ['required', 'in:open,pending,answered,closed'],
        ]);
        TicketReply::query()->create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'author_name' => auth()->user()->name,
            'is_staff' => true,
            'body' => $data['body'],
        ]);
        $ticket->update(['status' => $data['status'], 'last_reply_at' => now()]);
        WhatsApp::logOutbound(
            "Ticket {$ticket->number} update: {$data['status']}\n{$data['body']}",
            $ticket->phone ?: WhatsApp::number(),
            'ticket_reply',
            Ticket::class,
            $ticket->id
        );

        return back()->with('success', 'Reply posted and logged for WhatsApp.');
    }

    public function accounting(): View
    {
        $revenue = (float) Order::query()->whereIn('status', ['paid', 'dispatched', 'collected'])->sum('total');
        $cogs = (float) DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->whereIn('orders.status', ['paid', 'dispatched', 'collected'])
            ->selectRaw('COALESCE(SUM(order_items.qty * products.cost),0) as total')
            ->value('total');
        $expenses = (float) Expense::query()->sum('amount');
        $openInvoices = (float) Invoice::query()->whereIn('status', ['open', 'late'])->sum('amount');
        $gross = $revenue - $cogs;
        $net = $gross - $expenses;

        return view('portal.staff.accounting', [
            'revenue' => $revenue,
            'cogs' => $cogs,
            'expenses' => $expenses,
            'gross' => $gross,
            'net' => $net,
            'openInvoices' => $openInvoices,
            'expenseRows' => Expense::query()->latest('spent_on')->take(20)->get(),
            'recentOrders' => Order::query()->whereIn('status', ['paid', 'dispatched', 'collected'])->latest()->take(10)->get(),
        ]);
    }
}
