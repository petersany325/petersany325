<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\FixedCost;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FixedCostController extends Controller
{
    public function __construct(private AccountingService $accounting)
    {
    }

    public function index(Request $request)
    {
        $month = trim((string) $request->get('month', now()->format('Y-m')));
        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = now()->format('Y-m');
        }

        $costs = FixedCost::query()
            ->with('bankAccount')
            ->orderByDesc('is_active')
            ->orderBy('category')
            ->orderBy('title')
            ->get();

        $banks = BankAccount::query()->where('is_active', true)->orderBy('name')->get();
        $activeSum = (int) $costs->where('is_active', true)->sum('amount');
        $dueCount = $costs->filter(fn (FixedCost $c) => $c->isDueInPeriod($month))->count();

        return view('accounting.fixed-costs', compact('costs', 'banks', 'month', 'activeSum', 'dueCount'));
    }

    public function store(Request $request)
    {
        FixedCost::create($this->validated($request));

        return back()->with('success', 'هزینه ثابت ثبت شد.');
    }

    public function update(Request $request, FixedCost $fixedCost)
    {
        $fixedCost->update($this->validated($request));

        return back()->with('success', 'هزینه ثابت به‌روزرسانی شد.');
    }

    public function destroy(FixedCost $fixedCost)
    {
        $fixedCost->delete();

        return back()->with('success', 'هزینه ثابت حذف شد.');
    }

    public function postMonth(Request $request)
    {
        $data = $request->validate([
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'cost_id' => ['nullable', 'integer', 'exists:fixed_costs,id'],
        ]);
        $month = $data['month'];
        [$y, $m] = array_map('intval', explode('-', $month));
        $day = min(28, (int) now()->day);
        $entryDate = sprintf('%04d-%02d-%02d', $y, $m, $day);

        $query = FixedCost::query()->with('bankAccount')->where('is_active', true);
        if (! empty($data['cost_id'])) {
            $query->where('id', (int) $data['cost_id']);
        }
        $costs = $query->get()->filter(fn (FixedCost $c) => $c->isDueInPeriod($month));

        if ($costs->isEmpty()) {
            return back()->with('success', 'هزینه معوقی برای ثبت در این ماه نیست.');
        }

        $posted = 0;
        DB::transaction(function () use ($costs, $month, $entryDate, &$posted) {
            foreach ($costs as $cost) {
                $amount = (int) $cost->amount;
                if ($amount <= 0) {
                    continue;
                }
                $creditCode = $cost->bankAccount?->resolvedGlCode()
                    ?? match ($cost->pay_method) {
                        'card' => AccountingService::CARD,
                        'transfer' => AccountingService::TRANSFER,
                        default => AccountingService::CASH,
                    };
                $expenseCode = $cost->expense_account_code ?: '5310';
                $desc = 'هزینه ثابت '.$cost->title.' — دوره '.$month;
                try {
                    $this->accounting->createManual($desc, [
                        [$expenseCode, $amount, 0, $cost->category ?: 'هزینه ثابت'],
                        [$creditCode, 0, $amount, 'پرداخت از '.$cost->payMethodLabel()],
                    ], $entryDate);
                } catch (ValidationException $e) {
                    throw $e;
                }
                $cost->forceFill([
                    'last_posted_period' => $month,
                    'last_posted_at' => now(),
                ])->save();
                $posted++;
            }
        });

        return back()->with('success', "{$posted} هزینه ثابت برای ماه {$month} سند شد.");
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'category' => ['nullable', 'string', 'max:80'],
            'amount' => ['required', 'integer', 'min:0'],
            'day_of_month' => ['nullable', 'integer', 'min:1', 'max:28'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'pay_method' => ['required', Rule::in(['cash', 'card', 'transfer'])],
            'bank_account_id' => ['nullable', 'integer', 'exists:bank_accounts,id'],
            'expense_account_code' => ['nullable', 'string', 'max:20'],
            'note' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['day_of_month'] = (int) ($data['day_of_month'] ?? 1);
        $data['expense_account_code'] = $data['expense_account_code'] ?: '5310';

        return $data;
    }
}
