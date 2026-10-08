<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Services\WhatsApp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function dashboard(): View
    {
        $user = auth()->user();
        $orders = Order::query()->where('customer_email', $user->email)->latest()->take(8)->get();
        $tickets = Ticket::query()->where('user_id', $user->id)->latest()->take(8)->get();
        $invoices = Invoice::query()->where('customer_phone', $user->phone)
            ->orWhere('customer_name', $user->name)
            ->latest()->take(8)->get();

        return view('portal.account.dashboard', compact('orders', 'tickets', 'invoices'));
    }

    public function orders(): View
    {
        $orders = Order::query()->where('customer_email', auth()->user()->email)->latest()->paginate(20);

        return view('portal.account.orders', compact('orders'));
    }

    public function tickets(): View
    {
        $tickets = Ticket::query()->where('user_id', auth()->id())->latest()->paginate(20);

        return view('portal.account.tickets', compact('tickets'));
    }

    public function createTicket(): View
    {
        return view('portal.account.ticket-create');
    }

    public function storeTicket(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:180'],
            'department' => ['required', 'in:support,sales,recovery,accounts'],
            'priority' => ['required', 'in:low,normal,high,urgent'],
            'body' => ['required', 'string', 'max:5000'],
        ]);
        $user = auth()->user();
        $ticket = Ticket::query()->create([
            'number' => 'TCK-'.now()->format('ymd').'-'.Str::upper(Str::random(4)),
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'subject' => $data['subject'],
            'department' => $data['department'],
            'priority' => $data['priority'],
            'status' => 'open',
            'last_reply_at' => now(),
        ]);
        TicketReply::query()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'author_name' => $user->name,
            'is_staff' => false,
            'body' => $data['body'],
        ]);
        if (Setting::bool('whatsapp_enabled', true) && Setting::bool('whatsapp_notify_tickets', true)) {
            $tpl = Setting::getValue('whatsapp_ticket_template', 'New ticket {number}: {subject}');
            $msg = strtr($tpl, [
                '{number}' => $ticket->number,
                '{subject}' => $ticket->subject,
            ]);
            WhatsApp::logOutbound($msg, WhatsApp::number(), 'ticket', Ticket::class, $ticket->id);
        }

        return redirect()->route('account.tickets.show', $ticket)->with('success', 'Ticket opened.');
    }

    public function showTicket(Ticket $ticket): View
    {
        abort_unless($ticket->user_id === auth()->id(), 403);
        $ticket->load('replies');

        return view('portal.account.ticket-show', compact('ticket'));
    }

    public function replyTicket(Request $request, Ticket $ticket): RedirectResponse
    {
        abort_unless($ticket->user_id === auth()->id(), 403);
        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);
        TicketReply::query()->create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'author_name' => auth()->user()->name,
            'is_staff' => false,
            'body' => $data['body'],
        ]);
        $ticket->update(['status' => 'pending', 'last_reply_at' => now()]);

        return back()->with('success', 'Reply sent.');
    }
}
