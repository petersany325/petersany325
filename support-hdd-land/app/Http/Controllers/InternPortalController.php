<?php

namespace App\Http\Controllers;

use App\Models\DailyLogCategory;
use App\Models\DailyLogEntry;
use App\Models\Intern;
use App\Models\Reception;
use App\Support\DailyLogSettings;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InternPortalController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user && ($user->isIntern() || $user->isAdmin() || $user->canAccess('employees')), 403);

        $intern = Intern::query()->where('user_id', $user->id)->first();
        $date = now('Asia/Tehran')->startOfDay();
        $categories = DailyLogCategory::query()->active()->ordered()->get();

        $entries = DailyLogEntry::query()
            ->with(['category', 'reception.customer'])
            ->where('user_id', $user->id)
            ->whereDate('work_date', $date->toDateString())
            ->orderByDesc('id')
            ->get();

        return view('intern.portal', [
            'intern' => $intern,
            'user' => $user,
            'categories' => $categories,
            'entries' => $entries,
            'date' => $date,
            'canLog' => $user->canAccess('daily_logs'),
            'requireNote' => DailyLogSettings::requireNote(),
            'showQuantity' => DailyLogSettings::showQuantity(),
            'ticketSearchUrl' => \Illuminate\Support\Facades\Route::has('daily-logs.tickets')
                ? route('daily-logs.tickets')
                : url('/daily-logs/tickets'),
            'workHints' => [
                'تعویض قطعه',
                'تشخیص ایراد',
                'تست نهایی',
                'نصب قطعه',
                'تمیزکاری برد',
                'هماهنگی با مشتری',
            ],
            'summary' => [
                'count' => $entries->count(),
                'quantity' => (int) $entries->sum(fn ($e) => (int) ($e->quantity ?? 0)),
                'with_ticket' => $entries->whereNotNull('reception_id')->count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        abort_unless($user && $user->canAccess('daily_logs'), 403);

        $data = $request->validate([
            'daily_log_category_id' => ['required', 'exists:daily_log_categories,id'],
            'reception_id' => ['nullable', 'integer', 'exists:receptions,id'],
            'body' => [DailyLogSettings::requireNote() ? 'required' : 'nullable', 'string', 'max:2000'],
            'quantity' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
        ], [
            'daily_log_category_id.required' => 'یک خدمت را انتخاب کنید.',
            'body.required' => 'توضیح رویداد الزامی است.',
            'reception_id.exists' => 'قبض انتخاب‌شده معتبر نیست.',
        ]);

        $category = DailyLogCategory::query()->active()->findOrFail($data['daily_log_category_id']);
        $reception = null;
        if (! empty($data['reception_id'])) {
            $reception = Reception::query()->find($data['reception_id']);
        }

        if ($category->needsReceipt() && ! $reception) {
            throw ValidationException::withMessages([
                'reception_id' => 'برای «'.$category->name.'» باید قبض را جستجو و انتخاب کنید.',
            ]);
        }

        $title = $category->name;
        if ($reception) {
            $ticket = $reception->ticket_no ?: $reception->receipt_no;
            $title = $category->name.' — '.$ticket;
        }

        DailyLogEntry::create([
            'user_id' => $user->id,
            'created_by' => $user->id,
            'daily_log_category_id' => $category->id,
            'reception_id' => $reception?->id,
            'category_name' => $category->name,
            'title' => $title,
            'work_date' => now('Asia/Tehran')->toDateString(),
            'body' => $data['body'] ?? null,
            'quantity' => $category->ask_quantity ? (int) ($data['quantity'] ?? 1) : null,
            'minutes' => isset($data['minutes']) ? (int) $data['minutes'] : null,
        ]);

        $msg = 'خدمت «'.$category->name.'» در دفتر روز ثبت شد.';
        if ($reception) {
            $msg .= ' (قبض '.$reception->ticket_no.')';
        }

        return back()->with('success', $msg);
    }
}
