<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\FaultType;
use App\Models\LookupOption;
use App\Models\Part;
use App\Models\Payment;
use App\Models\Reception;
use App\Models\ReceptionPart;
use App\Models\ReferralSource;
use App\Models\SmsLog;
use App\Models\SmsStatusRule;
use App\Models\StockMovement;
use App\Models\Technician;
use App\Services\AccountingService;
use App\Services\CostApprovalService;
use App\Services\SmsNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ReceptionController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status');
        $q = trim((string) $request->get('q'));

        $receptions = $this->searchQuery($q, $status)
            ->with(['customer', 'technician', 'faultType'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('receptions.index', [
            'receptions' => $receptions,
            'statuses' => Reception::availableStatuses(),
            'status' => $status,
            'q' => $q,
        ]);
    }

    public function search(Request $request)
    {
        $q = trim((string) $request->get('q'));
        $status = $request->get('status');
        $searched = $request->has('q');

        $receptions = collect();
        if ($searched && $q !== '') {
            $receptions = $this->searchQuery($q, $status)
                ->with([
                    'customer.referralSource',
                    'technician',
                    'faultType',
                    'parts.part',
                    'payments.receiver',
                    'creator',
                    'costStages',
                    'statusLogs' => fn ($q) => $q->with('actor')->latest('id')->limit(25),
                ])
                ->latest()
                ->limit(50)
                ->get();
        }

        return view('receptions.search', [
            'q' => $q,
            'status' => $status,
            'statuses' => Reception::availableStatuses(),
            'searched' => $searched,
            'receptions' => $receptions,
        ]);
    }

    public function create()
    {
        return view('receptions.create', $this->formData());
    }

    public function lookupPhone(Request $request)
    {
        $raw = (string) $request->query('phone', '');
        $phone = $this->normalizePhone($raw);

        if (strlen($phone) < 10) {
            return response()->json(['found' => false, 'phone' => $phone]);
        }

        $customer = $this->findCustomerByPhone($phone);

        if (! $customer) {
            return response()->json(['found' => false, 'phone' => $phone]);
        }

        return response()->json([
            'found' => true,
            'phone' => $phone,
            'customer' => $this->customerPayload($customer),
        ]);
    }

    public function ensureCustomer(Request $request)
    {
        $data = $request->validate([
            'customer_id' => ['nullable', 'exists:customers,id'],
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'national_code' => ['nullable', 'string', 'max:20'],
            'job' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:500'],
            'referral_source_id' => ['nullable', 'exists:referral_sources,id'],
        ]);

        $data['customer_phone'] = $this->normalizePhone((string) $data['customer_phone']);
        if (strlen($data['customer_phone']) < 10) {
            throw ValidationException::withMessages([
                'customer_phone' => 'شماره موبایل معتبر نیست.',
            ]);
        }

        $customer = null;
        if (! empty($data['customer_id'])) {
            $customer = Customer::find($data['customer_id']);
        }
        if (! $customer) {
            $customer = $this->findCustomerByPhone($data['customer_phone']);
        }

        $payload = [
            'name' => $data['customer_name'],
            'phone' => $data['customer_phone'],
            'national_code' => $data['national_code'] ?? null,
            'job' => $data['job'] ?? null,
            'address' => $data['address'] ?? null,
            'referral_source_id' => $data['referral_source_id'] ?? null,
        ];

        if ($customer) {
            $customer->update($payload);
            $created = false;
        } else {
            $customer = Customer::create($payload);
            $created = true;
        }

        return response()->json([
            'ok' => true,
            'created' => $created,
            'customer' => $this->customerPayload($customer),
        ]);
    }

    public function store(Request $request)
    {
        if ($request->input('intake_mode') === 'group') {
            return $this->storeBatch($request);
        }

        $data = $request->validate(array_merge($this->customerRules(), $this->deviceRules(), [
            'action' => ['nullable', 'in:save_close,save_continue,save_print'],
            'photo' => ['nullable', 'image', 'max:4096'],
        ]));

        $data['customer_phone'] = $this->normalizePhone((string) ($data['customer_phone'] ?? ''));

        $reception = DB::transaction(function () use ($data, $request) {
            $customer = $this->resolveCustomer($data);
            $photoPath = null;
            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('receptions', 'public');
            }

            return $this->createReceptionRecord($customer, $data, $request, [
                'photo_path' => $photoPath,
                'batch_code' => null,
            ]);
        });

        $smsNote = $this->notifyReceptionCreated($reception);

        $action = $data['action'] ?? 'save_close';
        $flash = 'قبض پذیرش با موفقیت ثبت شد.'.($smsNote ? ' '.$smsNote : '');
        if ($action === 'save_print') {
            return redirect()->route('receptions.print', $reception)->with('success', $flash);
        }
        if ($action === 'save_continue') {
            return redirect()->route('receptions.create')->with('success', $flash.' می‌توانید پذیرش بعدی را وارد کنید.');
        }

        return redirect()->route('receptions.show', $reception)->with('success', $flash);
    }

    public function storeBatch(Request $request)
    {
        $data = $request->validate(array_merge($this->customerRules(), [
            'admission_type' => ['nullable', 'string', 'max:80'],
            'received_at' => ['nullable', 'date'],
            'received_time' => ['nullable', 'date_format:H:i'],
            'delivered_by' => ['nullable', 'string', 'max:120'],
            'referrer' => ['nullable', 'string', 'max:120'],
            'payment_method' => ['nullable', 'in:cash,card,transfer'],
            'action' => ['nullable', 'in:save_close,save_continue,save_print'],
            'items' => ['required', 'array', 'min:2'],
            'items.*.serial_number' => ['nullable', 'string', 'max:120'],
            'items.*.brand_model' => ['nullable', 'string', 'max:160'],
            'items.*.brand' => ['nullable', 'string', 'max:80'],
            'items.*.model' => ['nullable', 'string', 'max:120'],
            'items.*.service_type' => ['nullable', 'string', 'max:120'],
            'items.*.repair_type' => ['nullable', 'string', 'max:120'],
            'items.*.technician_id' => ['nullable', 'exists:technicians,id'],
            'items.*.fault_type_id' => ['nullable', 'exists:fault_types,id'],
            'items.*.hdd_capacity' => ['nullable', 'string', 'max:80'],
            'items.*.reported_fault' => ['nullable', 'string', 'max:5000'],
            'items.*.accessories' => ['nullable', 'string', 'max:2000'],
            'items.*.appearance_notes' => ['nullable', 'string', 'max:5000'],
            'items.*.warranty_return' => ['nullable', 'boolean'],
            'items.*.warranty_type' => ['nullable', 'string', 'max:120'],
            'items.*.card_number' => ['nullable', 'string', 'max:80'],
            'items.*.warranty_end_date' => ['nullable', 'date'],
            'items.*.deposit' => ['nullable', 'integer', 'min:0'],
            'items.*.pos_amount' => ['nullable', 'integer', 'min:0'],
            'items.*.admission_fee' => ['nullable', 'integer', 'min:0'],
            'items.*.estimated_cost' => ['nullable', 'integer', 'min:0'],
            'items.*.estimated_delivery_at' => ['nullable', 'date'],
            'items.*.commission' => ['nullable', 'integer', 'min:0'],
        ]));

        $data['customer_phone'] = $this->normalizePhone((string) ($data['customer_phone'] ?? ''));
        $batchCode = 'BATCH-'.now()->format('ymdHis').'-'.random_int(100, 999);

        $receptions = DB::transaction(function () use ($data, $request, $batchCode) {
            $customer = $this->resolveCustomer($data);
            $created = [];

            foreach ($data['items'] as $index => $item) {
                $row = array_merge($item, [
                    'admission_type' => $data['admission_type'] ?? ($item['admission_type'] ?? null),
                    'received_at' => $data['received_at'] ?? null,
                    'received_time' => $data['received_time'] ?? null,
                    'delivered_by' => $data['delivered_by'] ?? ($customer->name ?? null),
                    'referrer' => $data['referrer'] ?? null,
                    'payment_method' => $data['payment_method'] ?? 'cash',
                    'account_code' => null,
                ]);

                $fakeRequest = new Request($row);
                $fakeRequest->merge([
                    'warranty_return' => ! empty($item['warranty_return']) ? 1 : 0,
                ]);

                $created[] = $this->createReceptionRecord($customer, $row, $fakeRequest, [
                    'photo_path' => null,
                    'batch_code' => $batchCode,
                ]);
            }

            return collect($created);
        });

        $count = $receptions->count();
        $first = $receptions->first();
        $action = $data['action'] ?? 'save_close';

        $smsNote = '';
        if ($first) {
            $smsNote = $this->notifyReceptionCreated($first);
            // for remaining tickets in batch, also send if configured
            foreach ($receptions->slice(1) as $item) {
                $this->notifyReceptionCreated($item);
            }
        }

        $flash = "{$count} قبض گروهی با کد {$batchCode} ثبت شد.".($smsNote ? ' '.$smsNote : '');

        if ($action === 'save_print' && $first) {
            return redirect()->route('receptions.print', $first)->with('success', $flash);
        }

        return redirect()
            ->route('receptions.search', ['q' => $batchCode])
            ->with('success', $flash);
    }

    public function show(Reception $reception, SmsNotificationService $smsNotifications)
    {
        $reception->load([
            'customer.referralSource', 'technician', 'custodyTechnician', 'faultType',
            'parts.part', 'payments.receiver', 'creator',
            'costApprovals' => fn ($q) => $q->latest('id')->limit(12),
            'costStages',
            'statusLogs' => fn ($q) => $q->with('actor')->latest('id')->limit(40),
            'handoffs' => fn ($q) => $q->with(['fromUser', 'toTechnician', 'toUser'])->latest('id')->limit(20),
        ]);
        $rules = SmsStatusRule::activeOrdered();
        $previews = [];
        foreach ($rules as $rule) {
            $previews[$rule->status_key] = [
                'title' => $rule->title,
                'auto_send' => $rule->auto_send,
                'color' => $rule->color,
                'message' => $smsNotifications->preview($rule, $reception),
            ];
        }

        return view('receptions.show', array_merge($this->formData(), [
            'reception' => $reception,
            'statuses' => Reception::availableStatuses(),
            'smsRules' => $rules,
            'smsPreviews' => $previews,
            'smsMasterEnabled' => $smsNotifications->masterEnabled(),
            'smsLogs' => SmsLog::query()->where('reception_id', $reception->id)->latest()->limit(15)->get(),
            'costApprovals' => $reception->costApprovals,
            'costStages' => $reception->costStages,
            'statusLogs' => $reception->statusLogs,
            'stageDefs' => \App\Models\ReceptionCostStage::STAGES,
            'paymentMethods' => collect(Payment::METHODS)->except('zarinpal')->all(),
            'paymentTypes' => Payment::TYPES,
            'parts' => Part::where('is_active', true)->orderBy('name')->get(),
            'pendingHandoff' => $reception->handoffs->firstWhere('status', \App\Models\DeviceHandoff::STATUS_PENDING),
        ]));
    }

    public function requestCostApproval(Request $request, Reception $reception, CostApprovalService $approvals)
    {
        $data = $request->validate([
            'description' => ['nullable', 'string', 'max:1000'],
            'send_sms' => ['nullable', 'boolean'],
            'force' => ['nullable', 'boolean'],
        ]);

        $result = $approvals->requestAndSend(
            $reception->fresh(['customer', 'parts', 'faultType', 'technician']),
            $data['description'] ?? null,
            $request->boolean('send_sms', true),
            $request->boolean('force') || \App\Support\CostApprovalSettings::receptionRequiresApproval($reception)
        );

        if (! ($result['ok'] ?? false)) {
            return back()->withErrors(['cost_approval' => $result['message'] ?? 'ارسال لینک تأیید ناموفق بود.']);
        }

        return back()->with('success', $result['message'] ?? 'لینک تأیید هزینه ارسال شد.');
    }

    public function updateStatus(Request $request, Reception $reception, SmsNotificationService $smsNotifications)
    {
        $statusKeys = array_keys(Reception::availableStatuses());
        $data = $request->validate([
            'status' => ['required', Rule::in($statusKeys)],
            'technician_id' => ['nullable', 'exists:technicians,id'],
            'fault_type_id' => ['nullable', 'exists:fault_types,id'],
            'final_fault' => ['nullable', 'string', 'max:1000'],
            'technician_notes' => ['nullable', 'string', 'max:2000'],
            'labor_cost' => ['nullable', 'integer', 'min:0'],
            'discount' => ['nullable', 'integer', 'min:0'],
            'discount_reason' => ['nullable', 'string', 'max:255'],
            'send_sms' => ['nullable', 'boolean'],
            'send_price_sms' => ['nullable', 'boolean'],
            'force_without_cost' => ['nullable', 'boolean'],
        ]);

        if ($reception->isDelivered() && $data['status'] !== 'delivered') {
            throw ValidationException::withMessages([
                'status' => 'قبض تحویل‌شده را نمی‌توان مستقیم تغییر وضعیت داد. ابتدا «لغو تحویل» بزنید تا دستگاه به چرخه تعمیر برگردد.',
            ]);
        }

        $prevTotal = (int) $reception->total_amount;
        $fromStatus = $reception->status;

        $reception->fill([
            'status' => $data['status'],
            'technician_id' => $data['technician_id'] ?? $reception->technician_id,
            'fault_type_id' => $data['fault_type_id'] ?? $reception->fault_type_id,
            'final_fault' => $data['final_fault'] ?? $reception->final_fault,
            'technician_notes' => $data['technician_notes'] ?? $reception->technician_notes,
            'labor_cost' => $data['labor_cost'] ?? $reception->labor_cost,
            'discount' => $data['discount'] ?? $reception->discount,
            'discount_reason' => array_key_exists('discount_reason', $data)
                ? $data['discount_reason']
                : $reception->discount_reason,
        ]);

        $reception->save();
        $reception->recalculateTotals();
        $reception = $reception->fresh(['customer', 'faultType', 'technician']);

        if ((int) $reception->total_amount > 0 && ! $reception->cost_confirmed_at) {
            $reception->confirmCost();
            $reception->refresh();
        }

        if ($data['status'] === 'delivered') {
            if (! $reception->hasCostSet() && ! $request->boolean('force_without_cost')) {
                throw ValidationException::withMessages([
                    'status' => 'قبل از تحویل، هزینه قبض را مشخص و ثبت کنید. اگر عمداً بدون هزینه تحویل می‌دهید، گزینه تایید را بزنید.',
                ]);
            }
            if (! $reception->delivered_at) {
                $reception->delivered_at = now();
                $reception->save();
            }
        }

        app(\App\Services\ReceptionLifecycleService::class)->log(
            $reception,
            $data['status'],
            $data['status'] === 'delivered' ? 'delivery' : 'status_change',
            $fromStatus,
            null,
            $data['technician_notes'] ?? null
        );

        $msg = 'وضعیت قبض به‌روزرسانی شد.';

        try {
            app(AccountingService::class)->syncReceptionRevenue($reception);
        } catch (\Throwable $e) {
            // حسابداری نباید مانع ثبت وضعیت شود
        }

        $sendSms = $request->boolean('send_sms');
        $smsResult = $smsNotifications->sendOnStatusChange($reception, $data['status'], $sendSms);
        if ($sendSms) {
            if ($smsResult['ok'] ?? false) {
                $msg .= ' پیامک وضعیت ارسال شد.';
            } elseif (! ($smsResult['skipped'] ?? false)) {
                $msg .= ' پیامک وضعیت ناموفق: '.($smsResult['message'] ?? '');
            }
        }

        // پیامک مبلغ: وقتی مبلغ تازه مشخص شد یا کاربر تیک ارسال مبلغ را زد
        $newTotal = (int) $reception->total_amount;
        $priceJustSet = $newTotal > 0 && $prevTotal <= 0;
        if ($request->boolean('send_price_sms') || $priceJustSet) {
            $priceResult = $smsNotifications->sendOnPriceSet($reception, $request->boolean('send_price_sms') || $priceJustSet);
            if ($priceResult && ($priceResult['ok'] ?? false)) {
                $msg .= ' پیامک مبلغ برای مشتری ارسال شد.';
            }
        }

        return back()->with('success', $msg);
    }

    public function addPart(Request $request, Reception $reception, SmsNotificationService $smsNotifications)
    {
        if (! $reception->canEditParts()) {
            return back()->withErrors(['part' => 'قبض تحویل‌شده قابل ویرایش قطعه نیست. ابتدا لغو تحویل بزنید.']);
        }

        $data = $request->validate([
            'part_id' => ['nullable', 'exists:parts,id'],
            'part_name' => ['required_without:part_id', 'nullable', 'string', 'max:120'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_price' => ['nullable', 'integer', 'min:0'],
            'used_at' => ['nullable', 'date'],
            'send_price_sms' => ['nullable', 'boolean'],
        ]);

        $hadCost = $reception->hasCostSet();

        DB::transaction(function () use ($data, $reception) {
            $part = null;
            $name = $data['part_name'] ?? null;
            $unitPrice = (int) ($data['unit_price'] ?? 0);

            if (! empty($data['part_id'])) {
                $part = Part::lockForUpdate()->findOrFail($data['part_id']);
                $name = $part->name;
                if ($unitPrice <= 0) {
                    $unitPrice = (int) $part->sale_price;
                }

                if ($part->stock < $data['quantity']) {
                    throw ValidationException::withMessages([
                        'quantity' => 'موجودی قطعه کافی نیست.',
                    ]);
                }

                $part->stock -= $data['quantity'];
                $part->save();

                StockMovement::create([
                    'part_id' => $part->id,
                    'reception_id' => $reception->id,
                    'user_id' => Auth::id(),
                    'type' => 'out',
                    'quantity' => -1 * (int) $data['quantity'],
                    'stock_after' => $part->stock,
                    'note' => 'مصرف در قبض '.$reception->ticket_no,
                ]);
            }

            ReceptionPart::create([
                'reception_id' => $reception->id,
                'part_id' => $part?->id,
                'part_name' => $name,
                'quantity' => $data['quantity'],
                'unit_price' => $unitPrice,
                'total_price' => $unitPrice * (int) $data['quantity'],
                'used_at' => $data['used_at'] ?? now()->toDateString(),
            ]);

            $reception->recalculateTotals();
        });

        $reception = $reception->fresh(['customer', 'parts']);
        $msg = 'قطعه روی قبض ثبت شد.';

        try {
            $acc = app(AccountingService::class);
            $acc->syncReceptionRevenue($reception);
            $rp = $reception->parts()->latest('id')->first();
            if ($rp) {
                $acc->postReceptionPart($rp);
            }
        } catch (\Throwable $e) {
        }

        if ($reception->hasCostSet() && ! $reception->cost_confirmed_at) {
            $reception->confirmCost();
            $reception->refresh();
        }

        $priceJustSet = ! $hadCost && $reception->hasCostSet();
        if ($request->boolean('send_price_sms') || $priceJustSet) {
            $priceResult = $smsNotifications->sendOnPriceSet(
                $reception,
                $request->boolean('send_price_sms') || $priceJustSet
            );
            if ($priceResult && ($priceResult['ok'] ?? false)) {
                $msg .= ' پیامک مبلغ برای مشتری ارسال شد.';
            }
        }

        return back()->with('success', $msg);
    }

    public function addPayment(Request $request, Reception $reception)
    {
        $data = $request->validate([
            'type' => ['required', 'in:deposit,partial,final,refund'],
            'method' => ['required', 'in:cash,card,transfer'],
            'amount' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:500'],
            'discount' => ['nullable', 'integer', 'min:0'],
            'discount_reason' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($data, $reception) {
            if (array_key_exists('discount', $data) && $data['discount'] !== null) {
                $reception->discount = (int) $data['discount'];
                $reception->discount_reason = $data['discount_reason'] ?? $reception->discount_reason;
                $reception->save();
                $reception->recalculateTotals();
            }

            $amount = (int) $data['amount'];
            if ($data['type'] === 'refund') {
                $amount = -1 * abs($amount);
            }

            $payment = Payment::create([
                'reception_id' => $reception->id,
                'customer_id' => $reception->customer_id,
                'received_by' => Auth::id(),
                'type' => $data['type'],
                'method' => $data['method'],
                'amount' => $amount,
                'note' => $data['note'] ?? null,
                'paid_at' => now(),
            ]);

            $reception->recalculateTotals();

            if ($data['type'] === 'final' || $reception->remainingAmount() === 0) {
                if ($reception->status === 'ready') {
                    $from = $reception->status;
                    $reception->update([
                        'status' => 'delivered',
                        'delivered_at' => now(),
                    ]);
                    app(\App\Services\ReceptionLifecycleService::class)->log(
                        $reception->fresh(),
                        'delivered',
                        'payment_auto_deliver',
                        $from,
                        'تحویل خودکار پس از تسویه',
                        $payment->methodLabel()
                    );
                }
            }

            app(AccountingService::class)->postPayment($payment->fresh(['reception', 'customer']));
        });

        return back()->with('success', 'پرداخت ثبت شد.');
    }

    public function cancelDelivery(Request $request, Reception $reception, \App\Services\ReceptionLifecycleService $lifecycle)
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
            'restore_to' => ['nullable', 'in:repairing,ready,waiting_part,received'],
        ]);

        $result = $lifecycle->cancelDelivery(
            $reception,
            $data['restore_to'] ?? 'repairing',
            $data['reason'] ?? null
        );

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function storeCostStage(Request $request, Reception $reception)
    {
        if ($reception->isDelivered()) {
            return back()->withErrors(['stage' => 'قبض تحویل‌شده را نمی‌توان ویرایش کرد. ابتدا لغو تحویل بزنید.']);
        }

        $keys = array_keys(\App\Models\ReceptionCostStage::STAGES);
        $data = $request->validate([
            'stage_key' => ['required', Rule::in($keys)],
            'amount' => ['required', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', Rule::in(array_keys(\App\Models\ReceptionCostStage::STATUSES))],
            'custom_label' => ['nullable', 'string', 'max:120'],
        ]);

        $def = \App\Models\ReceptionCostStage::STAGES[$data['stage_key']];
        $label = $data['stage_key'] === 'custom' && ! empty($data['custom_label'])
            ? $data['custom_label']
            : $def['label'];

        $stage = \App\Models\ReceptionCostStage::create([
            'reception_id' => $reception->id,
            'stage_key' => $data['stage_key'],
            'stage_label' => $label,
            'amount' => (int) $data['amount'],
            'status' => $data['status'] ?? 'waived',
            'sort_order' => $def['sort'],
            'note' => $data['note'] ?? null,
            'created_by' => Auth::id(),
            'approved_at' => in_array(($data['status'] ?? 'waived'), ['approved', 'waived'], true) ? now() : null,
        ]);

        $reception->recalculateTotals();
        if ($reception->hasCostSet() && ! $reception->cost_confirmed_at) {
            $reception->confirmCost();
        }

        app(\App\Services\ReceptionLifecycleService::class)->log(
            $reception->fresh(),
            $reception->status,
            'cost_stage',
            $reception->status,
            'ثبت هزینه: '.$stage->stage_label,
            number_format((int) $stage->amount).' تومان'
        );

        $msg = 'مرحله هزینه «'.$stage->stage_label.'» ثبت شد.';

        if (($data['status'] ?? '') === 'pending_approval' && (int) $stage->amount > 0) {
            $approvalResult = app(\App\Services\CostApprovalService::class)->requestAndSend(
                $reception->fresh(['customer', 'parts', 'faultType', 'technician', 'costStages']),
                'تأیید هزینه مرحله: '.$stage->stage_label.($stage->note ? ' — '.$stage->note : ''),
                true,
                true,
                $stage
            );
            $msg .= ' '.($approvalResult['message'] ?? '');
        }

        return back()->with('success', $msg);
    }

    public function destroyCostStage(Reception $reception, \App\Models\ReceptionCostStage $stage)
    {
        abort_unless((int) $stage->reception_id === (int) $reception->id, 404);
        if ($reception->isDelivered()) {
            return back()->withErrors(['stage' => 'قبض تحویل‌شده قابل ویرایش نیست.']);
        }

        $label = $stage->stage_label;
        $stage->delete();
        $reception->recalculateTotals();

        return back()->with('success', 'مرحله هزینه «'.$label.'» حذف شد.');
    }

    public function print(Reception $reception)
    {
        $reception->load(['customer', 'technician', 'faultType', 'parts', 'payments', 'costStages']);

        $invoice = [
            'shop_name' => \App\Models\AppSetting::getValue('invoice_shop_name', 'سرزمین هارد'),
            'phones' => \App\Models\AppSetting::getValue('invoice_phones', ''),
            'address' => \App\Models\AppSetting::getValue('invoice_address', ''),
            'footer' => \App\Models\AppSetting::getValue('invoice_footer', 'مدیریت تعمیرکاران — قبض پذیرش'),
            'terms' => \App\Models\AppSetting::getValue('invoice_terms', ''),
            'auto_print' => \App\Models\AppSetting::getValue('invoice_auto_print', '0') === '1',
            'show_logo' => \App\Models\AppSetting::getValue('invoice_show_logo', '1') !== '0',
            'font_size' => (int) \App\Models\AppSetting::getValue('invoice_font_size', '11'),
            'page_size' => \App\Models\AppSetting::getValue('invoice_page_size', 'A4'),
            'margin_mm' => (int) \App\Models\AppSetting::getValue('invoice_margin_mm', '10'),
            'show_deposit' => \App\Models\AppSetting::getValue('invoice_show_deposit', '1') !== '0',
            'show_estimated_cost' => \App\Models\AppSetting::getValue('invoice_show_estimated_cost', '1') !== '0',
            'show_accessories' => \App\Models\AppSetting::getValue('invoice_show_accessories', '1') !== '0',
            'show_appearance' => \App\Models\AppSetting::getValue('invoice_show_appearance', '1') !== '0',
            'show_technician' => \App\Models\AppSetting::getValue('invoice_show_technician', '1') !== '0',
            'show_warranty' => \App\Models\AppSetting::getValue('invoice_show_warranty', '1') !== '0',
            'show_parts' => \App\Models\AppSetting::getValue('invoice_show_parts', '1') !== '0',
            'show_payments' => \App\Models\AppSetting::getValue('invoice_show_payments', '1') !== '0',
            'show_fault' => \App\Models\AppSetting::getValue('invoice_show_fault', '1') !== '0',
            'show_serial' => \App\Models\AppSetting::getValue('invoice_show_serial', '1') !== '0',
        ];

        $payLinks = \App\Support\PaymentGateways::showOnInvoice()
            ? \App\Support\PaymentGateways::active()
            : [];

        return view('receptions.print', compact('reception', 'invoice', 'payLinks'));
    }

    private function formData(): array
    {
        $lastReception = Reception::query()
            ->orderByDesc('id')
            ->first(['id', 'ticket_no', 'receipt_no', 'serial_number']);

        return [
            'customers' => Customer::latest()->limit(80)->get(),
            'technicians' => Technician::where('is_active', true)->orderBy('name')->get(),
            'faultTypes' => FaultType::where('is_active', true)->orderBy('name')->get(),
            'referralSources' => ReferralSource::where('is_active', true)->orderBy('name')->get(),
            'admissionTypes' => LookupOption::options('admission_type'),
            'serviceTypes' => LookupOption::options('service_type'),
            'repairTypes' => LookupOption::options('repair_type'),
            'warrantyTypes' => LookupOption::options('warranty_type'),
            'hddCapacities' => LookupOption::options('hdd_capacity'),
            'brandModels' => LookupOption::options('brand_model'),
            'nextTicket' => Reception::nextTicketNo(),
            'nextReceipt' => Reception::nextReceiptNo(),
            'lastReception' => $lastReception,
        ];
    }

    private function searchQuery(string $q, ?string $status = null)
    {
        $phone = $this->normalizePhone($q);
        $phoneTail = strlen($phone) >= 10 ? substr($phone, -10) : $phone;

        return Reception::query()
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($q !== '', function ($query) use ($q, $phone, $phoneTail) {
                $query->where(function ($inner) use ($q, $phone, $phoneTail) {
                    $inner->where('ticket_no', 'like', "%{$q}%")
                        ->orWhere('receipt_no', 'like', "%{$q}%")
                        ->orWhere('batch_code', 'like', "%{$q}%")
                        ->orWhere('serial_number', 'like', "%{$q}%")
                        ->orWhere('product_name', 'like', "%{$q}%")
                        ->orWhere('brand', 'like', "%{$q}%")
                        ->orWhere('model', 'like', "%{$q}%")
                        ->orWhereHas('customer', function ($c) use ($q, $phone, $phoneTail) {
                            $c->where('name', 'like', "%{$q}%")
                                ->orWhere('phone', 'like', "%{$q}%");

                            if ($phone !== '') {
                                $c->orWhere('phone', $phone)
                                    ->orWhere('phone', 'like', '%'.$phoneTail.'%');
                            }
                        });
                });
            });
    }

    private function customerRules(): array
    {
        return [
            'customer_id' => ['nullable', 'exists:customers,id'],
            'customer_name' => ['required_without:customer_id', 'nullable', 'string', 'max:120'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'national_code' => ['nullable', 'string', 'max:20'],
            'job' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:500'],
            'referral_source_id' => ['nullable', 'exists:referral_sources,id'],
        ];
    }

    private function deviceRules(): array
    {
        return [
            'account_code' => ['nullable', 'string', 'max:50'],
            'admission_type' => ['nullable', 'string', 'max:80'],
            'service_type' => ['nullable', 'string', 'max:120'],
            'repair_type' => ['nullable', 'string', 'max:120'],
            'technician_id' => ['nullable', 'exists:technicians,id'],
            'fault_type_id' => ['nullable', 'exists:fault_types,id'],
            'product_name' => ['nullable', 'string', 'max:120'],
            'brand' => ['nullable', 'string', 'max:80'],
            'model' => ['nullable', 'string', 'max:120'],
            'brand_model' => ['nullable', 'string', 'max:160'],
            'serial_number' => ['nullable', 'string', 'max:120'],
            'delivered_by' => ['nullable', 'string', 'max:120'],
            'referrer' => ['nullable', 'string', 'max:120'],
            'commission' => ['nullable', 'integer', 'min:0'],
            'accessories' => ['nullable', 'string', 'max:2000'],
            'reported_fault' => ['nullable', 'string', 'max:5000'],
            'appearance_notes' => ['nullable', 'string', 'max:5000'],
            'hdd_capacity' => ['nullable', 'string', 'max:80'],
            'warranty_return' => ['nullable', 'boolean'],
            'warranty_type' => ['nullable', 'string', 'max:120'],
            'card_number' => ['nullable', 'string', 'max:80'],
            'warranty_end_date' => ['nullable', 'date'],
            'deposit' => ['nullable', 'integer', 'min:0'],
            'pos_amount' => ['nullable', 'integer', 'min:0'],
            'admission_fee' => ['nullable', 'integer', 'min:0'],
            'estimated_cost' => ['nullable', 'integer', 'min:0'],
            'estimated_delivery_at' => ['nullable', 'date'],
            'next_visit_at' => ['nullable', 'date'],
            'received_at' => ['nullable', 'date'],
            'received_time' => ['nullable', 'date_format:H:i'],
            'payment_method' => ['nullable', 'in:cash,card,transfer'],
        ];
    }

    private function resolveCustomer(array $data): Customer
    {
        $customerId = $data['customer_id'] ?? null;
        $phone = $data['customer_phone'] ?? '';

        $payload = [
            'name' => $data['customer_name'] ?? null,
            'phone' => $phone,
            'national_code' => $data['national_code'] ?? null,
            'job' => $data['job'] ?? null,
            'address' => $data['address'] ?? null,
            'referral_source_id' => $data['referral_source_id'] ?? null,
        ];

        if ($customerId) {
            $customer = Customer::findOrFail($customerId);
            $updates = array_filter($payload, fn ($v) => $v !== null && $v !== '');
            if ($updates) {
                $customer->update($updates);
            }

            return $customer->fresh();
        }

        $existing = $this->findCustomerByPhone($phone);
        if ($existing) {
            $updates = array_filter($payload, fn ($v) => $v !== null && $v !== '');
            if ($updates) {
                $existing->update($updates);
            }

            return $existing->fresh();
        }

        return Customer::create([
            'name' => $data['customer_name'],
            'phone' => $phone,
            'national_code' => $data['national_code'] ?? null,
            'job' => $data['job'] ?? null,
            'address' => $data['address'] ?? null,
            'referral_source_id' => $data['referral_source_id'] ?? null,
        ]);
    }

    private function findCustomerByPhone(string $phone): ?Customer
    {
        if ($phone === '') {
            return null;
        }

        return Customer::query()
            ->where(function ($q) use ($phone) {
                $q->where('phone', $phone)
                    ->orWhere('phone', ltrim($phone, '0'))
                    ->orWhere('phone', '0'.ltrim($phone, '0'));

                $tail = substr($phone, -10);
                if ($tail !== '') {
                    $q->orWhere('phone', 'like', '%'.$tail);
                }
            })
            ->orderByDesc('id')
            ->first();
    }

    private function customerPayload(Customer $customer): array
    {
        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'phone' => $customer->phone,
            'national_code' => $customer->national_code,
            'job' => $customer->job,
            'address' => $customer->address,
            'referral_source_id' => $customer->referral_source_id,
            'visits' => $customer->receptions()->count(),
        ];
    }

    private function createReceptionRecord(Customer $customer, array $data, Request $request, array $extra = []): Reception
    {
        if (! empty($data['brand_model'])) {
            $data['brand_model'] = $this->toAsciiEnglish((string) $data['brand_model']);
        }
        if (! empty($data['brand'])) {
            $data['brand'] = $this->toAsciiEnglish((string) $data['brand']);
        }
        if (! empty($data['model'])) {
            $data['model'] = $this->toAsciiEnglish((string) $data['model']);
        }
        if (! empty($data['serial_number'])) {
            $data['serial_number'] = $this->toAsciiEnglish((string) $data['serial_number']);
        }

        $brandModel = trim((string) ($data['brand_model'] ?? trim(($data['brand'] ?? '').' '.($data['model'] ?? ''))));
        $productName = trim((string) ($data['product_name'] ?? '')) ?: ($brandModel !== '' ? $brandModel : 'دستگاه تعمیری');

        $receivedAt = now();
        if (! empty($data['received_at'])) {
            $time = $data['received_time'] ?? now()->format('H:i');
            $receivedAt = \Illuminate\Support\Carbon::parse($data['received_at'].' '.$time);
        }

        $deposit = (int) ($data['deposit'] ?? 0);

        $reception = Reception::create([
            'ticket_no' => Reception::nextTicketNo(),
            'receipt_no' => Reception::nextReceiptNo(),
            'batch_code' => $extra['batch_code'] ?? null,
            'account_code' => $data['account_code'] ?? null,
            'admission_type' => $data['admission_type'] ?? null,
            'service_type' => $data['service_type'] ?? null,
            'repair_type' => $data['repair_type'] ?? null,
            'customer_id' => $customer->id,
            'technician_id' => $data['technician_id'] ?? null,
            'fault_type_id' => $data['fault_type_id'] ?? null,
            'created_by' => Auth::id(),
            'product_name' => $productName,
            'brand' => $data['brand'] ?? null,
            'model' => $this->toAsciiEnglish((string) ($data['model'] ?? ($brandModel ?: ''))),
            'serial_number' => $this->toAsciiEnglish((string) ($data['serial_number'] ?? '')),
            'delivered_by' => $data['delivered_by'] ?? $customer->name,
            'referrer' => $data['referrer'] ?? null,
            'commission' => (int) ($data['commission'] ?? 0),
            'photo_path' => $extra['photo_path'] ?? null,
            'accessories' => $data['accessories'] ?? null,
            'appearance_notes' => $data['appearance_notes'] ?? null,
            'reported_fault' => $data['reported_fault'] ?? null,
            'hdd_capacity' => $data['hdd_capacity'] ?? null,
            'warranty_return' => $request->boolean('warranty_return'),
            'warranty_type' => $data['warranty_type'] ?? null,
            'card_number' => $data['card_number'] ?? null,
            'warranty_end_date' => $data['warranty_end_date'] ?? null,
            'status' => 'received',
            'deposit' => $deposit,
            'pos_amount' => (int) ($data['pos_amount'] ?? 0),
            'admission_fee' => (int) ($data['admission_fee'] ?? 0),
            'estimated_cost' => (int) ($data['estimated_cost'] ?? 0),
            'payment_method' => $data['payment_method'] ?? 'cash',
            'paid_amount' => $deposit,
            'estimated_delivery_at' => $data['estimated_delivery_at'] ?? null,
            'next_visit_at' => $data['next_visit_at'] ?? null,
            'received_at' => $receivedAt,
        ]);

        $reception->recalculateTotals();

        if ($deposit > 0) {
            $payment = Payment::create([
                'reception_id' => $reception->id,
                'customer_id' => $customer->id,
                'received_by' => Auth::id(),
                'type' => 'deposit',
                'method' => $data['payment_method'] ?? 'cash',
                'amount' => $deposit,
                'note' => 'بیعانه هنگام پذیرش',
                'paid_at' => now(),
            ]);
            $reception->recalculateTotals();
        }

        try {
            $acc = app(AccountingService::class);
            $acc->syncReceptionRevenue($reception->fresh());
            if (! empty($payment)) {
                $acc->postPayment($payment->fresh(['reception', 'customer']));
            }
        } catch (\Throwable $e) {
        }

        return $reception;
    }

    private function notifyReceptionCreated(Reception $reception): string
    {
        try {
            $sms = app(SmsNotificationService::class);
            $result = $sms->sendOnCreate($reception->fresh(['customer', 'faultType']));
            if (! $result) {
                return '';
            }
            if ($result['ok'] ?? false) {
                return 'پیامک پذیرش برای مشتری ارسال شد.';
            }
            if ($result['skipped'] ?? false) {
                return '';
            }

            return 'پیامک پذیرش ارسال نشد: '.($result['message'] ?? '');
        } catch (\Throwable $e) {
            return 'خطا در ارسال پیامک پذیرش.';
        }
    }

    private function normalizePhone(string $value): string
    {
        $map = [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ];
        $digits = preg_replace('/\D+/', '', strtr($value, $map)) ?? '';

        if (str_starts_with($digits, '98') && strlen($digits) >= 12) {
            $digits = '0'.substr($digits, 2);
        }
        if (strlen($digits) === 10 && str_starts_with($digits, '9')) {
            $digits = '0'.$digits;
        }

        return $digits;
    }

    private function toAsciiEnglish(string $value): ?string
    {
        $map = [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            'ك' => 'ک', 'ي' => 'ی',
        ];
        $text = strtr(trim($value), $map);
        // Keep printable ASCII for serial/model (letters, digits, punctuation)
        $text = preg_replace('/[^\x20-\x7E]+/u', '', $text) ?? '';
        $text = trim($text);

        return $text === '' ? null : $text;
    }
}
