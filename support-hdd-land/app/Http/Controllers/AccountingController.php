<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Customer;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Payment;
use App\Models\Reception;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccountingController extends Controller
{
    public function __construct(private AccountingService $accounting)
    {
    }

    /** @return array{0:string,1:string} */
    private function period(Request $request): array
    {
        [$defaultFrom] = jalali_period_range('this_month');
        $from = resolve_request_date($request->get('from'), $defaultFrom) ?: $defaultFrom;
        $to = resolve_request_date($request->get('to'), now()->toDateString()) ?: now()->toDateString();

        return [$from, $to];
    }

    public function index(Request $request)
    {
        [$from, $to] = $this->period($request);

        // خزانه = مانده پایانی دارایی‌های نقدی تا پایان بازه (نه فقط گردش دوره)
        $cash = $this->balanceAsOf(AccountingService::CASH, $to);
        $card = $this->balanceAsOf(AccountingService::CARD, $to);
        $transfer = $this->balanceAsOf(AccountingService::TRANSFER, $to);
        $receivable = $this->balanceAsOf(AccountingService::RECEIVABLE, $to);

        // گردش دوره برای درآمد/بهای تمام‌شده
        $incomeService = $this->sumCredit(AccountingService::INC_SERVICE, $from, $to);
        $incomeParts = $this->sumCredit(AccountingService::INC_PARTS, $from, $to);
        $incomeAdmission = $this->sumCredit(AccountingService::INC_ADMISSION, $from, $to);
        $cogs = $this->sumDebit(AccountingService::COGS, $from, $to);

        // ورودی نقدی دوره (بدهکار شدن صندوق/کارت در بازه)
        $cashIn = $this->sumDebit(AccountingService::CASH, $from, $to)
            + $this->sumDebit(AccountingService::CARD, $from, $to)
            + $this->sumDebit(AccountingService::TRANSFER, $from, $to);

        $payments = Payment::with(['reception', 'customer'])
            ->whereDate('paid_at', '>=', $from)
            ->whereDate('paid_at', '<=', $to)
            ->latest('paid_at')
            ->limit(30)
            ->get();

        $recentEntries = JournalEntry::with(['customer', 'reception'])
            ->whereDate('entry_date', '>=', $from)
            ->whereDate('entry_date', '<=', $to)
            ->latest('id')
            ->limit(15)
            ->get();

        $delivered = Reception::where('status', 'delivered')
            ->whereDate('delivered_at', '>=', $from)
            ->whereDate('delivered_at', '<=', $to)
            ->get();

        return view('accounting.index', [
            'from' => $from,
            'to' => $to,
            'cash' => $cash,
            'card' => $card,
            'transfer' => $transfer,
            'treasury' => $cash + $card + $transfer,
            'cashIn' => $cashIn,
            'receivable' => $receivable,
            'incomeService' => $incomeService,
            'incomeParts' => $incomeParts,
            'incomeTotal' => $incomeService + $incomeParts + $incomeAdmission,
            'cogs' => $cogs,
            'gross' => ($incomeService + $incomeParts) - $cogs,
            'payments' => $payments,
            'recentEntries' => $recentEntries,
            'deliveredCount' => $delivered->count(),
            'laborTotal' => $delivered->sum('labor_cost'),
            'partsTotal' => $delivered->sum('parts_cost'),
            'byMethod' => $payments->groupBy('method')->map->sum('amount'),
            'entryCount' => JournalEntry::whereDate('entry_date', '>=', $from)->whereDate('entry_date', '<=', $to)->count(),
        ]);
    }

    public function accounts()
    {
        $accounts = Account::query()->orderBy('sort_order')->orderBy('code')->get()
            ->map(function (Account $a) {
                $b = $this->accounting->accountBalance($a);

                return [
                    'model' => $a,
                    'debit' => $b['debit'],
                    'credit' => $b['credit'],
                    'balance' => $b['balance'],
                ];
            });

        return view('accounting.accounts', compact('accounts'));
    }

    public function journals(Request $request)
    {
        [$from, $to] = $this->period($request);
        $q = trim((string) $request->get('q', ''));

        $entries = JournalEntry::with(['customer', 'reception', 'lines.account'])
            ->whereDate('entry_date', '>=', $from)
            ->whereDate('entry_date', '<=', $to)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('entry_no', 'like', "%{$q}%")
                        ->orWhere('description', 'like', "%{$q}%")
                        ->orWhereHas('reception', fn ($r) => $r->where('ticket_no', 'like', "%{$q}%"))
                        ->orWhereHas('customer', function ($c) use ($q) {
                            $c->where('name', 'like', "%{$q}%")
                                ->orWhere('phone', 'like', "%{$q}%");
                        });
                });
            })
            ->latest('entry_date')
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('accounting.journals', compact('entries', 'from', 'to', 'q'));
    }

    public function show(JournalEntry $journal)
    {
        $journal->load(['lines.account', 'customer', 'reception', 'creator']);

        return view('accounting.show', ['entry' => $journal]);
    }

    public function ledger(Request $request)
    {
        $code = $request->get('account', AccountingService::CASH);
        [$from, $to] = $this->period($request);
        $customerId = $request->integer('customer_id') ?: null;
        $account = Account::byCode($code) ?: Account::query()->orderBy('code')->firstOrFail();

        // مانده ابتدای دوره (قبل از from)
        $opening = 0;
        if ($from) {
            $before = \Illuminate\Support\Carbon::parse($from)->subDay()->toDateString();
            $openingBal = $this->accounting->accountBalance($account, null, $before);
            $opening = (int) $openingBal['balance'];
            if ($customerId && $account->code === AccountingService::RECEIVABLE) {
                // برای معین مشتری روی ۱۲۱۰، مانده افتتاحیه فقط اسناد همان مشتری
                $opening = $this->customerBalanceAsOf($customerId, $before);
            }
        }

        $lines = JournalLine::with(['entry.reception', 'entry.customer'])
            ->where('account_id', $account->id)
            ->whereHas('entry', function ($e) use ($from, $to, $customerId) {
                $e->whereDate('entry_date', '>=', $from)->whereDate('entry_date', '<=', $to);
                if ($customerId) {
                    $e->where('customer_id', $customerId);
                }
            })
            ->orderBy('id')
            ->get();

        $running = $opening;
        $rows = [];
        foreach ($lines as $line) {
            $delta = $account->nature === 'credit'
                ? ((int) $line->credit - (int) $line->debit)
                : ((int) $line->debit - (int) $line->credit);
            $running += $delta;
            $rows[] = ['line' => $line, 'balance' => $running];
        }

        $accounts = Account::orderBy('sort_order')->orderBy('code')->get();
        $customer = $customerId ? Customer::find($customerId) : null;

        return view('accounting.ledger', compact(
            'account', 'accounts', 'rows', 'from', 'to', 'running', 'opening', 'customer', 'customerId'
        ));
    }

    public function trialBalance(Request $request)
    {
        // تراز آزمایشی استاندارد: مانده حساب‌ها تا تاریخ «تا» (as-of).
        // اگر «از» هم داده شود، گردش دوره نمایش داده می‌شود (تراز گردش).
        $mode = (string) $request->get('mode', 'balance'); // balance|activity
        $from = resolve_request_date($request->get('from'));
        $to = resolve_request_date($request->get('to'), now()->toDateString());

        if ($mode === 'activity' && $from) {
            $trial = $this->accounting->trialBalance($from, $to);
        } else {
            $trial = $this->trialBalanceAsOf($to);
            $mode = 'balance';
            $from = null;
        }

        return view('accounting.trial', [
            'from' => $from,
            'to' => $to,
            'trial' => $trial,
            'mode' => $mode,
        ]);
    }

    public function receivables()
    {
        $account = Account::byCode(AccountingService::RECEIVABLE);
        $rows = [];

        if ($account) {
            $grouped = JournalLine::query()
                ->select('journal_entries.customer_id', DB::raw('SUM(journal_lines.debit) as d'), DB::raw('SUM(journal_lines.credit) as c'))
                ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
                ->whereNull('journal_entries.deleted_at')
                ->where('journal_lines.account_id', $account->id)
                ->whereNotNull('journal_entries.customer_id')
                ->groupBy('journal_entries.customer_id')
                ->get();

            $customers = Customer::whereIn('id', $grouped->pluck('customer_id'))->get()->keyBy('id');
            foreach ($grouped as $g) {
                $bal = (int) $g->d - (int) $g->c;
                if ($bal === 0) {
                    continue;
                }
                $rows[] = [
                    'customer' => $customers->get($g->customer_id),
                    'debit' => (int) $g->d,
                    'credit' => (int) $g->c,
                    'balance' => $bal,
                ];
            }
            usort($rows, fn ($a, $b) => $b['balance'] <=> $a['balance']);
        }

        // Operational نسیه list (delivered with remaining) — complements journal AR 1210.
        $creditTickets = Reception::query()
            ->with('customer')
            ->where('status', 'delivered')
            ->whereColumn('total_amount', '>', 'paid_amount')
            ->orderByDesc('delivered_at')
            ->limit(80)
            ->get()
            ->filter(fn (Reception $r) => $r->remainingAmount() > 0)
            ->values();

        return view('accounting.receivables', [
            'rows' => $rows,
            'total' => collect($rows)->sum('balance'),
            'creditTickets' => $creditTickets,
            'creditTotal' => $creditTickets->sum(fn (Reception $r) => $r->remainingAmount()),
        ]);
    }

    public function manualForm(Request $request)
    {
        $preCustomerId = $request->integer('customer_id') ?: null;
        $preReceptionId = $request->integer('reception_id') ?: null;
        $mode = (string) $request->get('mode', 'receipt'); // receipt|general

        $debtors = $this->debtorOptions();
        $openTickets = Reception::query()
            ->with('customer:id,name,phone')
            ->whereColumn('total_amount', '>', 'paid_amount')
            ->whereIn('status', ['delivered', 'ready', 'repairing', 'waiting_part', 'received', 'unrepairable'])
            ->orderByDesc('id')
            ->limit(200)
            ->get()
            ->filter(fn (Reception $r) => $r->remainingAmount() > 0)
            ->values()
            ->map(fn (Reception $r) => [
                'id' => $r->id,
                'customer_id' => $r->customer_id,
                'ticket_no' => $r->ticket_no,
                'remaining' => $r->remainingAmount(),
                'label' => $r->ticket_no.' — مانده '.number_format($r->remainingAmount()).' تومان',
            ]);

        $preCustomer = $preCustomerId ? Customer::find($preCustomerId) : null;
        $preBalance = $preCustomerId ? $this->accounting->customerReceivableBalance($preCustomerId) : 0;

        return view('accounting.manual', [
            'accounts' => Account::where('is_active', true)->orderBy('sort_order')->orderBy('code')->get(),
            'debtors' => $debtors,
            'openTickets' => $openTickets,
            'mode' => in_array($mode, ['receipt', 'general'], true) ? $mode : 'receipt',
            'preCustomerId' => $preCustomerId,
            'preReceptionId' => $preReceptionId,
            'preCustomer' => $preCustomer,
            'preBalance' => $preBalance,
            'methods' => Payment::METHODS,
        ]);
    }

    public function storeManual(Request $request)
    {
        merge_jalali_dates($request, ['entry_date']);

        $mode = (string) $request->input('mode', 'receipt');

        if ($mode === 'receipt') {
            return $this->storeDebtReceipt($request);
        }

        return $this->storeGeneralManual($request);
    }

    private function storeDebtReceipt(Request $request)
    {
        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'reception_id' => ['nullable', 'exists:receptions,id'],
            'amount' => ['required', 'integer', 'min:1'],
            'method' => ['required', 'in:cash,card,transfer'],
            'entry_date' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
        ], [
            'customer_id.required' => 'مشتری بدهکار را انتخاب کنید.',
            'amount.required' => 'مبلغ دریافتی را وارد کنید.',
            'amount.min' => 'مبلغ باید بزرگ‌تر از صفر باشد.',
        ]);

        $customer = Customer::findOrFail($data['customer_id']);
        $reception = ! empty($data['reception_id'])
            ? Reception::findOrFail($data['reception_id'])
            : null;

        $entry = DB::transaction(function () use ($data, $customer, $reception) {
            return $this->accounting->postDebtReceipt(
                $customer,
                (int) $data['amount'],
                (string) $data['method'],
                $reception,
                $data['entry_date'],
                $data['note'] ?? $data['description'] ?? null,
            );
        });

        return redirect()
            ->route('accounting.show', $entry)
            ->with('success', 'سند دریافت از بدهکار ثبت شد و از حساب مشتری کسر گردید.');
    }

    private function storeGeneralManual(Request $request)
    {
        $data = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'entry_date' => ['required', 'date'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'reception_id' => ['nullable', 'exists:receptions,id'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['required', 'exists:accounts,id'],
            'lines.*.debit' => ['nullable', 'integer', 'min:0'],
            'lines.*.credit' => ['nullable', 'integer', 'min:0'],
            'lines.*.memo' => ['nullable', 'string', 'max:200'],
        ]);

        $receivableId = Account::byCode(AccountingService::RECEIVABLE)?->id;
        $touchesAr = false;
        $payload = [];
        foreach ($data['lines'] as $line) {
            $acc = Account::findOrFail($line['account_id']);
            $debit = (int) ($line['debit'] ?? 0);
            $credit = (int) ($line['credit'] ?? 0);
            if ($debit <= 0 && $credit <= 0) {
                continue;
            }
            if ($receivableId && (int) $acc->id === (int) $receivableId) {
                $touchesAr = true;
            }
            $payload[] = [
                $acc->code,
                $debit,
                $credit,
                $line['memo'] ?? null,
            ];
        }

        if ($touchesAr && empty($data['customer_id'])) {
            throw ValidationException::withMessages([
                'customer_id' => 'برای ردیف حساب دریافتنی (۱۲۱۰) باید مشتری مشخص شود تا مانده بدهکار درست کم/زیاد شود.',
            ]);
        }

        $reception = ! empty($data['reception_id']) ? Reception::find($data['reception_id']) : null;
        if ($reception && ! empty($data['customer_id']) && (int) $reception->customer_id !== (int) $data['customer_id']) {
            throw ValidationException::withMessages([
                'reception_id' => 'قبض با مشتری انتخاب‌شده هم‌خوان نیست.',
            ]);
        }

        $entry = $this->accounting->createManual(
            $data['description'],
            $payload,
            $data['entry_date'],
            $data['customer_id'] ?? null,
            $reception
        );

        return redirect()->route('accounting.show', $entry)->with('success', 'سند دستی ثبت شد.');
    }

    public function rebuild()
    {
        $stats = $this->accounting->rebuildFromHistory();

        return back()->with('success', "بازسازی اسناد: درآمد {$stats['revenue']} / پرداخت {$stats['payments']} / قطعه {$stats['parts']}");
    }

    /** @return list<array{id:int,name:string,phone:?string,balance:int}> */
    private function debtorOptions(): array
    {
        $account = Account::byCode(AccountingService::RECEIVABLE);
        if (! $account) {
            return [];
        }

        $grouped = JournalLine::query()
            ->select('journal_entries.customer_id', DB::raw('SUM(journal_lines.debit) as d'), DB::raw('SUM(journal_lines.credit) as c'))
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->whereNull('journal_entries.deleted_at')
            ->where('journal_lines.account_id', $account->id)
            ->whereNotNull('journal_entries.customer_id')
            ->groupBy('journal_entries.customer_id')
            ->get();

        $customers = Customer::whereIn('id', $grouped->pluck('customer_id'))->get()->keyBy('id');
        $out = [];
        foreach ($grouped as $g) {
            $bal = (int) $g->d - (int) $g->c;
            if ($bal <= 0) {
                continue;
            }
            $c = $customers->get($g->customer_id);
            if (! $c) {
                continue;
            }
            $out[] = [
                'id' => $c->id,
                'name' => $c->name,
                'phone' => $c->phone,
                'balance' => $bal,
            ];
        }
        usort($out, fn ($a, $b) => $b['balance'] <=> $a['balance']);

        return $out;
    }

    private function trialBalanceAsOf(?string $asOf): array
    {
        $rows = [];
        $sumD = 0;
        $sumC = 0;
        foreach (Account::query()->where('is_active', true)->orderBy('sort_order')->orderBy('code')->get() as $account) {
            $b = $this->accounting->accountBalance($account, null, $asOf);
            if ($b['balance'] === 0 && $b['debit'] === 0 && $b['credit'] === 0) {
                continue;
            }
            // فرم تراز مانده: مانده بدهکار یا بستانکار در یک ستون
            $debitBal = $b['balance'] > 0 && $account->nature === 'debit' ? $b['balance']
                : ($b['balance'] < 0 && $account->nature === 'credit' ? abs($b['balance']) : 0);
            $creditBal = $b['balance'] > 0 && $account->nature === 'credit' ? $b['balance']
                : ($b['balance'] < 0 && $account->nature === 'debit' ? abs($b['balance']) : 0);

            // اگر nature درست است، مانده مثبت در ستون ماهیت می‌نشیند؛ برای مخالف علامت در ستون مقابل
            if ($account->nature === 'debit') {
                $debitBal = max(0, $b['balance']);
                $creditBal = max(0, -$b['balance']);
            } else {
                $creditBal = max(0, $b['balance']);
                $debitBal = max(0, -$b['balance']);
            }

            if ($debitBal === 0 && $creditBal === 0) {
                continue;
            }

            $rows[] = [
                'account' => $account,
                'debit' => $debitBal,
                'credit' => $creditBal,
                'balance' => $b['balance'],
            ];
            $sumD += $debitBal;
            $sumC += $creditBal;
        }

        return ['rows' => $rows, 'debit' => $sumD, 'credit' => $sumC];
    }

    private function customerBalanceAsOf(int $customerId, string $asOf): int
    {
        $account = Account::byCode(AccountingService::RECEIVABLE);
        if (! $account) {
            return 0;
        }
        $row = JournalLine::query()
            ->selectRaw('COALESCE(SUM(journal_lines.debit),0) as d, COALESCE(SUM(journal_lines.credit),0) as c')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->whereNull('journal_entries.deleted_at')
            ->where('journal_lines.account_id', $account->id)
            ->where('journal_entries.customer_id', $customerId)
            ->whereDate('journal_entries.entry_date', '<=', $asOf)
            ->first();

        return (int) ($row->d ?? 0) - (int) ($row->c ?? 0);
    }

    private function balanceAsOf(string $code, string $asOf): int
    {
        $account = Account::byCode($code);
        if (! $account) {
            return 0;
        }

        return (int) $this->accounting->accountBalance($account, null, $asOf)['balance'];
    }

    private function sumDebit(string $code, string $from, string $to): int
    {
        $account = Account::byCode($code);
        if (! $account) {
            return 0;
        }

        return $this->accounting->accountBalance($account, $from, $to)['debit'];
    }

    private function sumCredit(string $code, string $from, string $to): int
    {
        $account = Account::byCode($code);
        if (! $account) {
            return 0;
        }

        return $this->accounting->accountBalance($account, $from, $to)['credit'];
    }
}
