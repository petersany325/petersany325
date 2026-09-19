<?php

namespace App\Http\Controllers;

use App\Models\CustomerMessage;
use App\Models\StaffNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = StaffNotification::query()
            ->where('user_id', $request->user()->id)
            ->latest('id')
            ->paginate(30);

        $messages = CustomerMessage::query()
            ->with(['customer', 'reception'])
            ->latest('id')
            ->paginate(20, ['*'], 'messages_page');

        return view('notifications.index', compact('notifications', 'messages'));
    }

    public function markRead(Request $request, StaffNotification $notification): RedirectResponse
    {
        abort_unless((int) $notification->user_id === (int) $request->user()->id, 403);
        if (! $notification->read_at) {
            $notification->forceFill(['read_at' => now()])->save();
        }

        if ($notification->link) {
            return redirect()->to($notification->link);
        }

        return back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        StaffNotification::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back()->with('success', 'همه اعلان‌ها خوانده شد.');
    }

    public function openMessage(Request $request, CustomerMessage $message): RedirectResponse
    {
        if (! $message->staff_read_at) {
            $message->forceFill([
                'staff_read_at' => now(),
                'handled_by' => $request->user()->id,
            ])->save();
        }

        StaffNotification::query()
            ->where('user_id', $request->user()->id)
            ->where('type', 'customer_message')
            ->whereNull('read_at')
            ->where('data->message_id', $message->id)
            ->update(['read_at' => now()]);

        if ($message->reception_id) {
            return redirect()->route('receptions.show', $message->reception_id)
                ->with('success', 'پیام مشتری: '.$message->body);
        }

        return redirect()->route('notifications.index')->with('success', 'پیام مشتری باز شد.');
    }
}
