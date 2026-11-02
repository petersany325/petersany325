<?php

namespace App\Http\Controllers;

use App\Models\DailyLogCategory;
use App\Models\DailyLogEntry;
use App\Models\Intern;
use App\Support\DailyLogSettings;
use Illuminate\Http\Request;
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
            ->with('category')
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
            'summary' => [
                'count' => $entries->count(),
                'quantity' => (int) $entries->sum(fn ($e) => (int) ($e->quantity ?? 0)),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        abort_unless($user && $user->canAccess('daily_logs'), 403);

        $data = $request->validate([
            'daily_log_category_id' => ['required', 'exists:daily_log_categories,id'],
            'body' => [DailyLogSettings::requireNote() ? 'required' : 'nullable', 'string', 'max:2000'],
            'quantity' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
        ], [
            'daily_log_category_id.required' => 'یک خدمت را انتخاب کنید.',
            'body.required' => 'توضیح رویداد الزامی است.',
        ]);

        $category = DailyLogCategory::query()->active()->findOrFail($data['daily_log_category_id']);

        DailyLogEntry::create([
            'user_id' => $user->id,
            'created_by' => $user->id,
            'daily_log_category_id' => $category->id,
            'category_name' => $category->name,
            'title' => $category->name,
            'work_date' => now('Asia/Tehran')->toDateString(),
            'body' => $data['body'] ?? null,
            'quantity' => $category->ask_quantity ? (int) ($data['quantity'] ?? 1) : null,
            'minutes' => isset($data['minutes']) ? (int) $data['minutes'] : null,
        ]);

        return back()->with('success', 'خدمت «'.$category->name.'» در دفتر روز ثبت شد.');
    }
}
