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

    public function index(Request $request)
    {
        $from = $request->get('from', now()->startOfMonth()->toDateString());
        $to = $request->get('to', now()->toDateString());

        $cash = $this->sumAccount(AccountingService::CASH, $from, $to);
        $card = $this->sumAccount(AccountingService::CARD, $from, $to);
        $transfer = $this->sumAccount(AccountingService::TRANSFER, $from, $to);
        $receivable = $this->balanceCode(AccountingService::RECEIVABLE);
        $incomeService = $this->sumCredit(AccountingService::INC_SERVICE, $from, $to);
        $incomeParts = $this->sumCredit(AccountingService::INC_PARTS, $from, $to);
        $cogs = $this->sumDebit(AccountingService::COGS, $from, $to);

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
            'receivable' => $receivable,
            'incomeService' => $incomeService,
            'incomeParts' => $incomeParts,
            'incomeTotal' => $incomeService + $incomeParts + $this->sumCredit(AccountingService::INC_ADMISSION, $from, $to),
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
        $from = $request->get('from', now()->startOfMonth()->toDateString());
        $to = $request->get('to', now()->toDateString());
        $q = trim((string) $request->get('q', ''));

        $entries = JournalEntry::with(['customer', 'reception', 'lines.account'])
            ->whereDate('entry_date', '>=', $from)
            ->whereDate('entry_date', '<=', $to)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('entry_no', 'like', "%{$q}%")
                        ->orWhere('description', 'like', "%{$q}%")
                        ->orWhereHas('reception', fn ($r) => $r->where('ticket_no', 'like', "%{$q}%"));
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
        $from = $request->get('from', now()->startOfMonth()->toDateString());
        $to = $request->get('to', now()->toDateString());
        $account = Account::byCode($code) ?: Account::query()->orderBy('code')->firstOrFail();

        $lines = JournalLine::with(['entry.reception', 'entry.customer'])
            ->where('account_id', $account->id)
            ->whereHas('entry', fn ($e) => $e->whereDate('entry_date', '>=', $from)->whereDate('entry_date', '<=', $to))
            ->orderBy('id')
            ->get();

        $running = 0;
        $rows = [];
        foreach ($lines as $line) {
            $delta = $account->nature === 'credit'
                ? ((int) $line->credit - (int) $line->debit)
                : ((int) $line->debit - (int) $line->credit);
            $running += $delta;
            $rows[] = ['line' => $line, 'balance' => $running];
        }

        $accounts = Account::orderBy('sort_order')->orderBy('code')->get();

        return view('accounting.ledger', compact('account', 'accounts', 'rows', 'from', 'to', 'running'));
    }

    public function trialBalance(Request $request)
    {
        $from = $request->get('from');
        $to = $request->get('to', now()->toDateString());
        $trial = $this->accounting->trialBalance($from ?: null, $to);

        return view('accounting.trial', [
            'from' => $from,
            'to' => $to,
            'trial' => $trial,
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

        return view('accounting.receivables', [
            'rows' => $rows,
            'total' => collect($rows)->sum('balance'),
        ]);
    }

    public function manualForm()
    {
        return view('accounting.manual', [
            'accounts' => Account::where('is_active', true)->orderBy('sort_order')->orderBy('code')->get(),
        ]);
    }

    public function storeManual(Request $request)
    {
        $data = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'entry_date' => ['required', 'date'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['required', 'exists:accounts,id'],
            'lines.*.debit' => ['nullable', 'integer', 'min:0'],
            'lines.*.credit' => ['nullable', 'integer', 'min:0'],
            'lines.*.memo' => ['nullable', 'string', 'max:200'],
        ]);

        $payload = [];
        foreach ($data['lines'] as $line) {
            $acc = Account::findOrFail($line['account_id']);
            $payload[] = [
                $acc->code,
                (int) ($line['debit'] ?? 0),
                (int) ($line['credit'] ?? 0),
                $line['memo'] ?? null,
            ];
        }

        $entry = $this->accounting->createManual($data['description'], $payload, $data['entry_date']);

        return redirect()->route('accounting.show', $entry)->with('success', 'سند دستی ثبت شد.');
    }

    public function rebuild()
    {
        $stats = $this->accounting->rebuildFromHistory();

        return back()->with('success', "بازسازی اسناد: درآمد {$stats['revenue']} / پرداخت {$stats['payments']} / قطعه {$stats['parts']}");
    }

    private function sumAccount(string $code, string $from, string $to): int
    {
        $account = Account::byCode($code);
        if (! $account) {
            return 0;
        }
        $b = $this->accounting->accountBalance($account, $from, $to);

        return max(0, $b['balance']);
    }

    private function balanceCode(string $code): int
    {
        $account = Account::byCode($code);
        if (! $account) {
            return 0;
        }

        return $this->accounting->accountBalance($account)['balance'];
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
