<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\InstallmentCheck;
use App\Models\InstallmentItem;
use App\Models\InstallmentPlan;
use App\Models\Reception;
use App\Services\InstallmentService;
use App\Support\InstallmentSettings;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InstallmentPlanController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');
        $customerId = (int) $request->query('customer_id', 0);

        $plans = InstallmentPlan::query()
            ->with(['customer', 'items'])
            ->when($customerId > 0, fn ($qq) => $qq->where('customer_id', $customerId))
            ->when($status !== '', fn ($qq) => $qq->where('status', $status))
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('title', 'like', '%'.$q.'%')
                        ->orWhere('notes', 'like', '%'.$q.'%')
                        ->orWhere('guarantor_name', 'like', '%'.$q.'%')
                        ->orWhere('guarantor_phone', 'like', '%'.$q.'%')
                        ->orWhereHas('customer', function ($c) use ($q) {
                            $c->where('name', 'like', '%'.$q.'%')
                                ->orWhere('phone', 'like', '%'.$q.'%')
                                ->orWhere('alias', 'like', '%'.$q.'%');
                        });
                });
            })
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('installments.index', compact('plans', 'q', 'status', 'customerId'));
    }

    public function create(Request $request)
    {
        $customerId = (int) $request->query('customer_id', 0);
        $customer = $customerId > 0 ? Customer::find($customerId) : null;
        $receptions = $customer
            ? Reception::query()
                ->where('customer_id', $customer->id)
                ->where('status', '!=', 'cancelled')
                ->latest('id')
                ->limit(40)
                ->get()
            : collect();

        return view('installments.create', [
            'customer' => $customer,
            'receptions' => $receptions,
            'customers' => Customer::query()->orderByDesc('id')->limit(30)->get(),
        ]);
    }

    public function store(Request $request, InstallmentService $service)
    {
        merge_jalali_dates($request, ['start_date', 'items.*.due_date']);

        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'reception_id' => ['nullable', 'exists:receptions,id'],
            'title' => ['nullable', 'string', 'max:200'],
            'total_amount' => ['nullable', 'integer', 'min:0'],
            'down_payment' => ['nullable', 'integer', 'min:0'],
            'installment_count' => ['nullable', 'integer', 'min:1', 'max:120'],
            'interval_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'schedule_mode' => ['required', Rule::in(['auto', 'manual'])],
            'start_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'guarantor_name' => ['nullable', 'string', 'max:120'],
            'guarantor_phone' => ['nullable', 'string', 'max:20'],
            'guarantor_national_code' => ['nullable', 'string', 'max:20'],
            'guarantor_address' => ['nullable', 'string', 'max:255'],
            'guarantor_notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['nullable', 'array'],
            'items.*.due_date' => ['nullable', 'date'],
            'items.*.amount' => ['nullable', 'integer', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
        ]);

        if (($data['schedule_mode'] ?? '') === 'auto' && (int) ($data['total_amount'] ?? 0) < 1) {
            return back()->withInput()->withErrors(['total_amount' => 'برای زمان‌بندی خودکار، مبلغ کل را وارد کنید.']);
        }

        $manual = null;
        if ($data['schedule_mode'] === 'manual') {
            $manual = [];
            foreach ($data['items'] ?? [] as $row) {
                if (! empty($row['due_date']) && (int) ($row['amount'] ?? 0) > 0) {
                    $manual[] = [
                        'due_date' => $row['due_date'],
                        'amount' => (int) $row['amount'],
                        'notes' => $row['notes'] ?? null,
                    ];
                }
            }
        }

        $plan = $service->createPlan($data, $manual);

        return redirect()->route('installments.show', $plan)->with('success', 'طرح اقساط ثبت شد.');
    }

    public function show(InstallmentPlan $installment)
    {
        $installment->load([
            'customer',
            'reception',
            'items.payments',
            'checks',
            'payments.receiver',
            'creator',
        ]);

        foreach ($installment->items as $item) {
            $item->refreshStatus();
        }
        $installment->refreshStatus();
        $installment->load('items');

        return view('installments.show', [
            'plan' => $installment,
            'methods' => \App\Models\InstallmentPayment::METHODS,
            'checkStatuses' => InstallmentCheck::STATUSES,
            'sms' => InstallmentSettings::all(),
        ]);
    }

    public function collect(Request $request, InstallmentItem $item, InstallmentService $service)
    {
        merge_jalali_dates($request, ['check.due_date']);

        $data = $request->validate([
            'method' => ['required', Rule::in(array_keys(\App\Models\InstallmentPayment::METHODS))],
            'amount' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:500'],
            'item_notes' => ['nullable', 'string', 'max:1000'],
            'check.check_number' => ['nullable', 'string', 'max:64'],
            'check.bank_name' => ['nullable', 'string', 'max:120'],
            'check.branch' => ['nullable', 'string', 'max:120'],
            'check.account_no' => ['nullable', 'string', 'max:64'],
            'check.amount' => ['nullable', 'integer', 'min:1'],
            'check.due_date' => ['nullable', 'date'],
            'check.holder_name' => ['nullable', 'string', 'max:120'],
            'check.notes' => ['nullable', 'string', 'max:500'],
            'check.is_custody' => ['nullable', 'boolean'],
        ]);

        $payload = [
            'method' => $data['method'],
            'amount' => (int) $data['amount'],
            'note' => $data['note'] ?? null,
            'item_notes' => $data['item_notes'] ?? null,
            'check' => $data['check'] ?? [],
        ];
        if (array_key_exists('is_custody', $payload['check'])) {
            $payload['check']['is_custody'] = $request->boolean('check.is_custody');
        }

        $result = $service->collect($item, $payload);

        return redirect()
            ->route('installments.show', $item->installment_plan_id)
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function updateItemNotes(Request $request, InstallmentItem $item)
    {
        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $item->forceFill(['notes' => $data['notes'] ?? null])->save();

        return back()->with('success', 'یادداشت قسط ذخیره شد.');
    }

    public function updatePlanNotes(Request $request, InstallmentPlan $installment)
    {
        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:4000'],
            'title' => ['nullable', 'string', 'max:200'],
            'guarantor_name' => ['nullable', 'string', 'max:120'],
            'guarantor_phone' => ['nullable', 'string', 'max:20'],
            'guarantor_national_code' => ['nullable', 'string', 'max:20'],
            'guarantor_address' => ['nullable', 'string', 'max:255'],
            'guarantor_notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $installment->fill($data)->save();

        return back()->with('success', 'اطلاعات طرح ذخیره شد.');
    }

    public function storeCheck(Request $request, InstallmentPlan $installment, InstallmentService $service)
    {
        merge_jalali_dates($request, ['due_date']);
        $data = $request->validate([
            'installment_item_id' => ['nullable', 'exists:installment_items,id'],
            'check_number' => ['nullable', 'string', 'max:64'],
            'bank_name' => ['nullable', 'string', 'max:120'],
            'branch' => ['nullable', 'string', 'max:120'],
            'account_no' => ['nullable', 'string', 'max:64'],
            'amount' => ['required', 'integer', 'min:1'],
            'due_date' => ['nullable', 'date'],
            'holder_name' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(array_keys(InstallmentCheck::STATUSES))],
            'is_custody' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $item = null;
        if (! empty($data['installment_item_id'])) {
            $item = InstallmentItem::query()
                ->where('installment_plan_id', $installment->id)
                ->findOrFail($data['installment_item_id']);
        }

        $data['is_custody'] = $request->boolean('is_custody', true);
        $service->storeCheck($installment, $item, $data, (int) $data['amount']);

        return back()->with('success', 'چک امانی ثبت شد.');
    }

    public function updateCheck(Request $request, InstallmentCheck $check)
    {
        merge_jalali_dates($request, ['due_date']);
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(InstallmentCheck::STATUSES))],
            'notes' => ['nullable', 'string', 'max:1000'],
            'due_date' => ['nullable', 'date'],
        ]);
        $check->fill($data)->save();

        return back()->with('success', 'وضعیت چک به‌روز شد.');
    }

    public function cancel(Request $request, InstallmentPlan $installment, InstallmentService $service)
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);
        $service->cancelPlan($installment, $data['reason'] ?? null);

        return back()->with('success', 'طرح اقساط لغو شد.');
    }
}
