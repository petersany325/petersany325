<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerMessage;
use App\Models\Reception;
use App\Services\StaffNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MessageController extends Controller
{
    public function index(Request $request): View
    {
        $customer = $this->customer($request);
        $messages = CustomerMessage::query()
            ->with(['reception', 'preorder'])
            ->where('customer_id', $customer->id)
            ->latest('id')
            ->paginate(20);

        CustomerMessage::query()
            ->where('customer_id', $customer->id)
            ->where('direction', CustomerMessage::DIRECTION_OUTBOUND)
            ->whereNull('customer_read_at')
            ->update(['customer_read_at' => now('Asia/Tehran')]);

        $tickets = $customer->receptions()
            ->where('status', '!=', 'cancelled')
            ->latest('id')
            ->limit(40)
            ->get(['id', 'ticket_no', 'serial_number', 'status', 'product_name']);

        return view('portal.messages', compact('customer', 'messages', 'tickets'));
    }

    public function store(Request $request, StaffNotifier $notifier): RedirectResponse
    {
        $customer = $this->customer($request);
        $data = $request->validate([
            'reception_id' => ['nullable', 'integer', 'exists:receptions,id'],
            'body' => ['required', 'string', 'min:5', 'max:2000'],
            'priority' => ['nullable', 'in:normal,urgent'],
        ]);

        $receptionId = $data['reception_id'] ?? null;
        if ($receptionId) {
            $owns = Reception::query()
                ->where('id', $receptionId)
                ->where('customer_id', $customer->id)
                ->exists();
            abort_unless($owns, 404);
        }

        // soft rate-limit: max 5 messages / 10 minutes
        $recent = CustomerMessage::query()
            ->where('customer_id', $customer->id)
            ->where('created_at', '>=', now()->subMinutes(10))
            ->count();
        if ($recent >= 5) {
            return back()->withErrors(['body' => 'لطفاً کمی صبر کنید؛ پیام‌های زیادی ارسال کرده‌اید.'])->withInput();
        }

        $message = CustomerMessage::query()->create([
            'customer_id' => $customer->id,
            'reception_id' => $receptionId,
            'body' => trim($data['body']),
            'priority' => $data['priority'] ?? 'normal',
            'direction' => CustomerMessage::DIRECTION_INBOUND,
        ]);

        $ticket = $receptionId
            ? (Reception::query()->find($receptionId)?->ticket_no ?? '')
            : 'عمومی';

        $notifier->notifyMany(
            $notifier->messageRecipients(),
            'customer_message',
            'پیام جدید از مشتری',
            $customer->name.' — قبض '.$ticket.' — '.mb_strimwidth($message->body, 0, 120, '…'),
            route('notifications.messages.show', $message),
            ['message_id' => $message->id, 'reception_id' => $receptionId]
        );

        return back()->with('success', 'پیام شما برای تعمیرگاه ارسال شد. به‌زودی پاسخ می‌دهیم.');
    }

    private function customer(Request $request): Customer
    {
        /** @var Customer $customer */
        $customer = $request->attributes->get('portalCustomer');

        return $customer;
    }
}
