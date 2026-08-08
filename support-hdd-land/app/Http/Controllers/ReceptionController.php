<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\FaultType;
use App\Models\LookupOption;
use App\Models\Part;
use App\Models\Payment;
use App\Models\PaymentReceipt;
use App\Models\Reception;
use App\Models\ReceptionPart;
use App\Models\ReferralSource;
use App\Models\SmsStatusRule;
use App\Models\StockMovement;
use App\Models\Technician;
use App\Services\AccountingService;
use App\Services\CostApprovalService;
use App\Services\ReceptionCustodyGate;
use App\Services\ReceptionLifecycleService;
use App\Services\ReceptionSettlementService;
use App\Services\SmsNotificationService;
use App\Services\TrashService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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
            // Light list payload — full report loads on demand when modal opens.
            $receptions = $this->searchQuery($q, $status)
                ->with(['customer:id,name,phone', 'technician:id,name'])
                ->latest('id')
                ->limit(40)
                ->get(['id', 'ticket_no', 'receipt_no', 'serial_number', 'product_name', 'status', 'customer_id', 'technician_id', 'created_at']);
        }

        return view('receptions.search', [
            'q' => $q,
            'status' => $status,
            'statuses' => Reception::availableStatuses(),
            'searched' => $searched,
            'receptions' => $receptions,
        ]);
    }

    public function reportPartial(Reception $reception)
    {
        $reception->load([
            'customer.referralSource',
            'technician',
            'faultType',
            'parts.part',
            'payments.receiver',
            'creator',
            'costStages',
            'statusLogs' => fn ($q) => $q->with('actor')->latest('id')->limit(25),
        ]);

        return view('receptions._report', ['reception' => $reception]);
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

        merge_jalali_dates($request, [
            'warranty_end_date', 'estimated_delivery_at', 'next_visit_at', 'received_at',
        ]);

        $data = $request->validate(array_merge($this->customerRules(), $this->deviceRules(), [
            'action' => ['nullable', 'in:save_close,save_continue,save_print'],
            'photo' => ['nullable', 'image', 'max:4096'],
        ]));

        $data['customer_phone'] = $this->normalizePhone((string) ($data['customer_phone'] ?? ''));
        $this->assertSerialAvailable($data['serial_number'] ?? null);

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

        $this->queueReceptionCreatedSms($reception);

        $action = $data['action'] ?? 'save_close';
        $flash = 'قبض پذیرش با موفقیت ثبت شد. پیامک در پس‌زمینه ارسال می‌شود.';
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
        merge_jalali_dates($request, [
            'received_at',
            'items.*.warranty_end_date',
            'items.*.estimated_delivery_at',
        ]);

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
        $this->assertBatchSerialsUnique($data['items'] ?? []);
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

        foreach ($receptions as $item) {
            $this->queueReceptionCreatedSms($item);
        }

        $flash = "{$count} قبض گروهی با کد {$batchCode} ثبت شد. پیامک‌ها در پس‌زمینه ارسال می‌شوند.";

        if ($action === 'save_print' && $first) {
            return redirect()->route('receptions.print', $first)->with('success', $flash);
        }

        return redirect()
            ->route('receptions.search', ['q' => $batchCode])
            ->with('success', $flash);
    }

    public function show(Reception $reception, SmsNotificationService $smsNotifications, ReceptionCustodyGate $gate)
    {
        $reception->load([
            'customer.referralSource', 'technician', 'custodyTechnician', 'faultType',
            'parts.part', 'payments.receiver', 'creator',
            'costApprovals' => fn ($q) => $q->latest('id')->limit(12),
            'costStages',
            'statusLogs' => fn ($q) => $q->with('actor')->latest('id')->limit(40),
            'handoffs' => fn ($q) => $q->with(['fromUser', 'toTechnician', 'toUser'])->latest('id')->limit(20),
            'workReports' => fn ($q) => $q->with(['user', 'technician'])->latest('id')->limit(10),
        ]);
        $rules = SmsStatusRule::activeOrdered();
        $previews = [];
        foreach ($rules as $rule) {
            if (! $rule instanceof SmsStatusRule) {
                continue;
            }
            $previews[$rule->status_key] = [
                'title' => $rule->title,
                'auto_send' => $rule->auto_send,
                'color' => $rule->color,
                'message' => $smsNotifications->preview($rule, $reception),
            ];
        }

        return view('receptions.show', array_merge($this->editLookups(), [
            'reception' => $reception,
            'statuses' => Reception::availableStatuses(),
            'smsRules' => $rules,
            'smsPreviews' => $previews,
            'smsMasterEnabled' => $smsNotifications->masterEnabled(),
            'costApprovals' => $reception->costApprovals,
            'costStages' => $reception->costStages,
            'statusLogs' => $reception->statusLogs,
            'stageDefs' => \App\Models\ReceptionCostStage::STAGES,
            'paymentMethods' => collect(Payment::METHODS)->except('zarinpal')->all(),
            'paymentTypes' => Payment::TYPES,
            'settlementModes' => ReceptionSettlementService::MODES,
            'parts' => $this->cachedActiveRows('parts_active_list_v2', Part::class, ['id', 'name', 'code', 'stock', 'sale_price']),
            'pendingHandoff' => $reception->handoffs->firstWhere('status', \App\Models\DeviceHandoff::STATUS_PENDING),
            'custodyChecklist' => $gate->checklist($reception),
            'workReports' => $reception->workReports,
        ]));
    }

    public function edit(Reception $reception)
    {
        $reception->load(['customer.referralSource', 'technician', 'faultType']);

        return view('receptions.edit', array_merge($this->editLookups(), [
            'reception' => $reception,
            'customer' => $reception->customer,
            'paymentMethods' => collect(Payment::METHODS)->except('zarinpal')->all(),
        ]));
    }

    public function update(Request $request, Reception $reception, SmsNotificationService $smsNotifications, ReceptionLifecycleService $lifecycle)
    {
        merge_jalali_dates($request, [
            'warranty_end_date', 'estimated_delivery_at', 'next_visit_at', 'received_at',
        ]);

        $data = $request->validate(array_merge($this->customerRules(), $this->deviceRules(), [
            'photo' => ['nullable', 'image', 'max:4096'],
            'final_fault' => ['nullable', 'string', 'max:5000'],
            'technician_notes' => ['nullable', 'string', 'max:5000'],
            'pickup_name' => ['nullable', 'string', 'max:120'],
            'pickup_phone' => ['nullable', 'string', 'max:20'],
            'send_sms' => ['nullable', 'boolean'],
            'sms_note' => ['nullable', 'string', 'max:300'],
        ]));

        $customer = $this->resolveCustomer($data);

        if (! empty($data['brand_model'])) {
            $converted = $this->toAsciiEnglish((string) $data['brand_model']);
            $data['brand_model'] = $converted !== null ? strtoupper($converted) : null;
        }
        foreach (['brand', 'model', 'serial_number'] as $asciiField) {
            if (! empty($data[$asciiField])) {
                $converted = $this->toAsciiEnglish((string) $data[$asciiField]);
                if ($asciiField === 'brand') {
                    $data[$asciiField] = $converted;
                } else {
                    $data[$asciiField] = $converted !== null ? strtoupper($converted) : null;
                }
            }
        }

        $this->assertSerialAvailable($data['serial_number'] ?? null, (int) $reception->id);

        $brandModel = trim((string) ($data['brand_model'] ?? trim(($data['brand'] ?? '').' '.($data['model'] ?? ''))));
        $productName = trim((string) ($data['product_name'] ?? '')) ?: ($brandModel !== '' ? $brandModel : $reception->product_name);

        $receivedAt = $reception->received_at;
        if (! empty($data['received_at'])) {
            $time = $data['received_time'] ?? ($reception->received_at?->format('H:i') ?: now()->format('H:i'));
            $receivedAt = \Illuminate\Support\Carbon::parse($data['received_at'].' '.$time);
        }

        if ($request->hasFile('photo')) {
            if ($reception->photo_path) {
                Storage::disk('public')->delete($reception->photo_path);
            }
            $reception->photo_path = $request->file('photo')->store('receptions', 'public');
        }

        $reception->fill([
            'customer_id' => $customer->id,
            'account_code' => $data['account_code'] ?? null,
            'admission_type' => $data['admission_type'] ?? null,
            'service_type' => $data['service_type'] ?? null,
            'repair_type' => $data['repair_type'] ?? null,
            'technician_id' => $data['technician_id'] ?? null,
            'fault_type_id' => $data['fault_type_id'] ?? null,
            'product_name' => $productName,
            'brand' => $data['brand'] ?? null,
            'model' => $data['model'] ?? null,
            'serial_number' => $data['serial_number'] ?? null,
            'delivered_by' => $data['delivered_by'] ?? $customer->name,
            'referrer' => $data['referrer'] ?? null,
            'commission' => (int) ($data['commission'] ?? 0),
            'accessories' => $data['accessories'] ?? null,
            'reported_fault' => $data['reported_fault'] ?? null,
            'appearance_notes' => $data['appearance_notes'] ?? null,
            'final_fault' => $data['final_fault'] ?? $reception->final_fault,
            'technician_notes' => $data['technician_notes'] ?? $reception->technician_notes,
            'hdd_capacity' => $data['hdd_capacity'] ?? null,
            'warranty_return' => $request->boolean('warranty_return'),
            'warranty_type' => $data['warranty_type'] ?? null,
            'card_number' => $data['card_number'] ?? null,
            'warranty_end_date' => $data['warranty_end_date'] ?? null,
            'deposit' => (int) ($data['deposit'] ?? $reception->deposit),
            'pos_amount' => (int) ($data['pos_amount'] ?? $reception->pos_amount),
            'admission_fee' => (int) ($data['admission_fee'] ?? $reception->admission_fee),
            'estimated_cost' => (int) ($data['estimated_cost'] ?? $reception->estimated_cost),
            'estimated_delivery_at' => $data['estimated_delivery_at'] ?? null,
            'next_visit_at' => $data['next_visit_at'] ?? null,
            'received_at' => $receivedAt,
            'payment_method' => $data['payment_method'] ?? $reception->payment_method,
            'pickup_name' => $data['pickup_name'] ?? $reception->pickup_name,
            'pickup_phone' => isset($data['pickup_phone'])
                ? $this->normalizePhone((string) $data['pickup_phone'])
                : $reception->pickup_phone,
        ])->save();

        $reception->recalculateTotals();
        try {
            app(AccountingService::class)->syncReceptionRevenue($reception->fresh());
        } catch (\Throwable) {
        }

        $lifecycle->log(
            $reception->fresh(),
            $reception->status,
            'ticket_edit',
            $reception->status,
            'ویرایش مشخصات قبض',
            null,
            ['edited_by' => Auth::id()]
        );

        $flash = 'قبض ذخیره شد.';
        if ($request->boolean('send_sms', true)) {
            $sms = $smsNotifications->sendOnTicketUpdated(
                $reception->fresh(['customer', 'faultType', 'technician']),
                $data['sms_note'] ?? null
            );
            if ($sms['ok'] ?? false) {
                $flash .= ' پیامک به مشتری ارسال شد.';
            } elseif (! ($sms['skipped'] ?? false)) {
                $flash .= ' پیامک ناموفق: '.($sms['message'] ?? '');
            }
        }

        return redirect()
            ->route('receptions.show', $reception)
            ->with('success', $flash);
    }

    public function destroy(Request $request, Reception $reception, TrashService $trash)
    {
        $data = $request->validate([
            'delete_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $ticket = $reception->ticket_no;
        $trash->softDeleteReception($reception, $data['delete_reason'] ?? null);

        return redirect()
            ->route('receptions.index')
            ->with('success', "قبض {$ticket} به سطل زباله منتقل شد. از منوی سطل زباله قابل بازیابی است.");
    }

    public function history(Reception $reception)
    {
        $reception->load([
            'customer', 'technician', 'custodyTechnician', 'faultType', 'creator',
            'parts.part',
            'statusLogs' => fn ($q) => $q->with('actor')->latest('id')->limit(100),
            'handoffs' => fn ($q) => $q->with(['fromUser', 'toTechnician', 'toUser'])->latest('id')->limit(50),
            'workReports' => fn ($q) => $q->with(['user', 'technician'])->latest('id')->limit(30),
            'payments.receiver',
        ]);

        return view('receptions.history', [
            'reception' => $reception,
            'statusLogs' => $reception->statusLogs,
            'workReports' => $reception->workReports,
            'handoffs' => $reception->handoffs,
        ]);
    }

    public function requestCostApproval(Request $request, Reception $reception, CostApprovalService $approvals, ReceptionCustodyGate $gate)
    {
        $data = $request->validate([
            'description' => ['nullable', 'string', 'max:1000'],
            'send_sms' => ['nullable', 'boolean'],
            'force' => ['nullable', 'boolean'],
        ]);

        $gate->assertCanSetCost($reception);

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

    public function updateStatus(Request $request, Reception $reception, SmsNotificationService $smsNotifications, ReceptionCustodyGate $gate)
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

        $newLabor = array_key_exists('labor_cost', $data) ? (int) ($data['labor_cost'] ?? 0) : (int) $reception->labor_cost;
        $costIncreasing = $newLabor > (int) $reception->labor_cost
            || ((int) ($data['discount'] ?? $reception->discount) !== (int) $reception->discount && $newLabor > 0);
        if ($costIncreasing || ($newLabor > 0 && ! $reception->hasCostSet())) {
            $gate->assertCanSetCost($reception);
        }

        if ($data['status'] === 'delivered') {
            throw ValidationException::withMessages([
                'status' => 'وضعیت «تحویل‌شده» فقط از پنل «تسویه و تحویل» بعد از ثبت تسویه و تأیید خروج کالا قابل اجراست.',
            ]);
        }

        // Soft assign of technician_id without handoff is blocked once custody workflow started
        // (main admin may manage repairs directly without the referral chain).
        if (! $gate->actorCanBypass()
            && ! empty($data['technician_id'])
            && (int) $data['technician_id'] !== (int) $reception->technician_id
            && ($reception->custody ?? 'front_desk') === 'front_desk'
            && ! $gate->hasAcceptedBenchHandoff($reception)) {
            throw ValidationException::withMessages([
                'technician_id' => 'برای سپردن دستگاه به تعمیرکار از «ارجاع به تعمیرکار» استفاده کنید تا تعمیرکار در کارتابل تأیید دریافت بزند.',
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
        $reception = $reception->fresh(['customer', 'faultType', 'technician', 'custodyTechnician']);

        if ((int) $reception->total_amount > 0 && ! $reception->cost_confirmed_at) {
            $reception->confirmCost();
            $reception->refresh();
        }

        app(\App\Services\ReceptionLifecycleService::class)->log(
            $reception,
            $data['status'],
            'status_change',
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
        $sendPrice = $request->boolean('send_price_sms');
        $newTotal = (int) $reception->total_amount;
        $priceJustSet = $newTotal > 0 && $prevTotal <= 0;
        $shouldPriceSms = $sendPrice || $priceJustSet;
        $statusKey = $data['status'];
        $receptionId = (int) $reception->id;

        if ($sendSms || $shouldPriceSms) {
            $msg .= ' پیامک در پس‌زمینه ارسال می‌شود.';
            dispatch(function () use ($receptionId, $statusKey, $sendSms, $shouldPriceSms) {
                try {
                    $row = Reception::query()->with(['customer', 'faultType', 'technician'])->find($receptionId);
                    if (! $row) {
                        return;
                    }
                    $sms = app(SmsNotificationService::class);
                    if ($sendSms) {
                        $sms->sendOnStatusChange($row, $statusKey, true);
                    }
                    if ($shouldPriceSms) {
                        $sms->sendOnPriceSet($row, true);
                    }
                } catch (\Throwable $e) {
                }
            })->afterResponse();
        }

        return back()->with('success', $msg);
    }

    public function addPart(Request $request, Reception $reception, SmsNotificationService $smsNotifications)
    {
        if (! $reception->canEditParts()) {
            return back()->withErrors(['part' => 'قبض تحویل‌شده قابل ویرایش قطعه نیست. ابتدا لغو تحویل بزنید.']);
        }

        merge_jalali_dates($request, ['used_at']);

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
                    'doc_no' => StockMovement::nextDocNo('OUT'),
                    'part_id' => $part->id,
                    'warehouse_id' => $part->warehouse_id,
                    'reception_id' => $reception->id,
                    'user_id' => Auth::id(),
                    'type' => 'out',
                    'doc_type' => 'consumption',
                    'quantity' => -1 * (int) $data['quantity'],
                    'unit_cost' => (int) $part->purchase_price,
                    'total_cost' => abs((int) $data['quantity']) * (int) $part->purchase_price,
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
            'auto_discount' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($data, $reception, $request) {
            $amount = (int) $data['amount'];
            if ($data['type'] === 'refund') {
                $amount = -1 * abs($amount);
            }

            $gross = $reception->grossCost();
            $paidBefore = (int) $reception->paid_amount;
            $explicitDiscount = array_key_exists('discount', $data) && $data['discount'] !== null
                ? (int) $data['discount']
                : null;

            // تخفیف خودکار فقط در تسویه نهایی؛ پرداخت جزئی/بیعانه باقیمانده را تخفیف نمی‌زند.
            if ($data['type'] === 'final' && $request->boolean('auto_discount', true) && $amount > 0) {
                $dueBeforeDiscount = max(0, $gross - $paidBefore);
                if ($amount < $dueBeforeDiscount) {
                    $auto = $dueBeforeDiscount - $amount;
                    $reason = trim((string) ($data['discount_reason'] ?? ''));
                    $reception->discount = $auto;
                    $reception->discount_reason = $reason !== ''
                        ? $reason
                        : ('تخفیف تسویه — دریافت '.number_format($amount).' از '.number_format($dueBeforeDiscount));
                    $reception->save();
                    $reception->recalculateTotals();
                } elseif ($explicitDiscount !== null) {
                    $reception->discount = $explicitDiscount;
                    if (array_key_exists('discount_reason', $data)) {
                        $reception->discount_reason = $data['discount_reason'];
                    }
                    $reception->save();
                    $reception->recalculateTotals();
                }
            } elseif ($explicitDiscount !== null && $data['type'] !== 'refund') {
                $reception->discount = $explicitDiscount;
                if (array_key_exists('discount_reason', $data)) {
                    $reception->discount_reason = $data['discount_reason'];
                }
                $reception->save();
                $reception->recalculateTotals();
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

            if ($reception->remainingAmount() === 0 && in_array($reception->status, ['ready', 'repairing', 'waiting_part', 'received', 'unrepairable'], true)) {
                $gate = app(ReceptionCustodyGate::class);
                $fresh = $reception->fresh(['custodyTechnician']);
                $block = $gate->deliveryBlockReason($fresh);
                // Never auto-deliver when exit OTP is required — must go through settle panel.
                if ($block || $fresh->needsExitOtp()) {
                    // پرداخت ثبت می‌شود؛ تحویل فقط وقتی گیت‌ها / کد خروج آزاد باشند
                } elseif ($data['type'] === 'final' || $request->boolean('auto_deliver')) {
                    $from = $reception->status;
                    $reception->update([
                        'status' => 'delivered',
                        'delivered_at' => now(),
                        'settlement_mode' => ReceptionSettlementService::MODE_PAID,
                        'settled_at' => now(),
                        'settlement_note' => $data['note'] ?? 'تسویه کامل',
                    ]);
                    app(\App\Services\ReceptionLifecycleService::class)->log(
                        $reception->fresh(),
                        'delivered',
                        'payment_auto_deliver',
                        $from,
                        'تحویل خودکار پس از تسویه کامل',
                        $payment->methodLabel()
                    );
                }
            }

            app(AccountingService::class)->postPayment($payment->fresh(['reception', 'customer']));
        });

        return back()->with('success', 'پرداخت ثبت شد.');
    }

    public function updatePayment(Request $request, Reception $reception, Payment $payment)
    {
        abort_unless((int) $payment->reception_id === (int) $reception->id, 404);

        $data = $request->validate([
            'type' => ['required', 'in:deposit,partial,final,refund'],
            'method' => ['required', 'in:cash,card,transfer'],
            'amount' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($data, $reception, $payment) {
            $amount = (int) $data['amount'];
            if ($data['type'] === 'refund') {
                $amount = -1 * abs($amount);
            }

            $payment->update([
                'type' => $data['type'],
                'method' => $data['method'],
                'amount' => $amount,
                'note' => $data['note'] ?? null,
            ]);

            $reception->recalculateTotals();
            app(AccountingService::class)->postPayment($payment->fresh(['reception', 'customer']));

            try {
                app(AccountingService::class)->syncReceptionRevenue($reception->fresh());
            } catch (\Throwable) {
            }
        });

        return back()->with('success', 'پرداخت ویرایش شد و مانده قبض به‌روز شد.');
    }

    public function destroyPayment(Reception $reception, Payment $payment)
    {
        abort_unless((int) $payment->reception_id === (int) $reception->id, 404);

        DB::transaction(function () use ($reception, $payment) {
            app(AccountingService::class)->voidPayment($payment);

            PaymentReceipt::query()
                ->where('payment_id', $payment->id)
                ->update(['payment_id' => null]);

            $payment->delete();
            $reception->recalculateTotals();

            try {
                app(AccountingService::class)->syncReceptionRevenue($reception->fresh());
            } catch (\Throwable) {
            }
        });

        return back()->with('success', 'پرداخت حذف شد و مانده قبض به‌روز شد.');
    }

    public function updateExitOtpRequired(Request $request, Reception $reception, \App\Services\DeliveryExitOtpService $exitOtp)
    {
        if ($reception->isDelivered()) {
            return back()->with('error', 'قبض تحویل‌شده قابل تغییر نیست.');
        }

        $data = $request->validate([
            'exit_otp_required' => ['nullable', 'boolean'],
        ]);

        $exitOtp->setRequired($reception, $request->boolean('exit_otp_required'));

        return back()->with(
            'success',
            $request->boolean('exit_otp_required')
                ? 'کد تأیید خروج برای این قبض فعال شد.'
                : 'کد تأیید خروج برای این قبض غیرفعال شد.'
        );
    }

    public function sendExitOtp(Request $request, Reception $reception, \App\Services\DeliveryExitOtpService $exitOtp)
    {
        $data = $request->validate([
            'pickup_phone' => ['nullable', 'string', 'max:20'],
        ]);

        if ($request->filled('pickup_phone')) {
            $reception->forceFill([
                'pickup_phone' => $this->normalizePhone((string) $data['pickup_phone']),
            ])->save();
        }

        $result = $exitOtp->send($reception->fresh(['customer']), $reception->pickup_phone);

        return back()->with($result['ok'] ? 'success' : 'error', $result['message'] ?? 'ارسال کد ناموفق بود.');
    }

    public function verifyExitOtp(Request $request, Reception $reception, \App\Services\DeliveryExitOtpService $exitOtp)
    {
        $data = $request->validate([
            'exit_otp_code' => ['required', 'string', 'max:12'],
        ]);

        $result = $exitOtp->verify($reception, (string) $data['exit_otp_code']);

        return back()->with($result['ok'] ? 'success' : 'error', $result['message'] ?? 'تأیید کد ناموفق بود.');
    }

    public function bypassExitOtp(Request $request, Reception $reception, \App\Services\DeliveryExitOtpService $exitOtp)
    {
        $data = $request->validate([
            'bypass_reason' => ['required', 'string', 'max:500'],
        ]);

        $result = $exitOtp->bypass($reception, (string) $data['bypass_reason']);

        return back()->with($result['ok'] ? 'success' : 'error', $result['message'] ?? 'عبور ناموفق بود.');
    }

    public function settleAndDeliver(Request $request, Reception $reception, ReceptionSettlementService $settlement, SmsNotificationService $smsNotifications)
    {
        if ($reception->isDelivered()) {
            return back()->with('error', 'این قبض قبلاً تحویل شده است.');
        }

        $data = $request->validate([
            'settlement_mode' => ['required', Rule::in(array_keys(ReceptionSettlementService::MODES))],
            'method' => ['nullable', Rule::in(array_keys(Payment::METHODS))],
            'amount' => ['nullable', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:500'],
            'pickup_name' => ['nullable', 'string', 'max:120'],
            'pickup_phone' => ['nullable', 'string', 'max:20'],
            'confirm_goods_exit' => ['accepted'],
            'accessories_exit_note' => ['nullable', 'string', 'max:500'],
            'send_sms' => ['nullable', 'boolean'],
        ], [
            'confirm_goods_exit.accepted' => 'برای تحویل، کلید «تأیید خروج دستگاه و قطعات همراه از کارگاه» را روشن کنید.',
            'note.required' => 'برای نسیه یا بخشش، توضیح تسویه الزامی است.',
        ]);
        if (in_array($data['settlement_mode'], [
            \App\Services\ReceptionSettlementService::MODE_CREDIT,
            \App\Services\ReceptionSettlementService::MODE_WAIVE,
        ], true)) {
            $request->validate([
                'note' => ['required', 'string', 'max:500'],
            ], [
                'note.required' => 'برای نسیه یا بخشش، توضیح تسویه الزامی است.',
            ]);
            $data['note'] = (string) $request->input('note');
        }
        $data['confirm_goods_exit'] = true;

        $result = $settlement->settleAndDeliver($reception->fresh(['customer', 'parts.part', 'custodyTechnician']), $data);

        if (! ($result['ok'] ?? false)) {
            return back()->with('error', $result['message'] ?? 'ثبت تسویه ناموفق بود.');
        }

        $msg = $result['message'];
        if ($request->boolean('send_sms')) {
            $fresh = $reception->fresh(['customer', 'faultType', 'technician']);
            $smsResult = $smsNotifications->sendOnStatusChange($fresh, 'delivered', true);
            if ($smsResult['ok'] ?? false) {
                $msg .= ' پیامک تحویل ارسال شد.';
            } elseif (! ($smsResult['skipped'] ?? false)) {
                $msg .= ' پیامک ناموفق: '.($smsResult['message'] ?? '');
            }
        }

        return back()->with('success', $msg);
    }

    /**
     * Post-delivery (or open credit) cash collection against AR 1210.
     */
    public function collectDebt(Request $request, Reception $reception, ReceptionSettlementService $settlement)
    {
        $data = $request->validate([
            'method' => ['required', Rule::in(['cash', 'card', 'transfer'])],
            'amount' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $result = $settlement->collectReceivable($reception->fresh(['customer']), $data);

        return back()->with($result['ok'] ? 'success' : 'error', $result['message'] ?? 'ثبت دریافت ناموفق بود.');
    }

    public function cancelDelivery(Request $request, Reception $reception, \App\Services\ReceptionLifecycleService $lifecycle)
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
            'restore_to' => ['nullable', 'in:repairing,ready,waiting_part,received'],
        ]);

        $reason = trim((string) ($data['reason'] ?? ''));
        if ($reason === '') {
            $reason = 'لغو تحویل از صفحه قبض';
        }

        $result = $lifecycle->cancelDelivery(
            $reception,
            $data['restore_to'] ?? 'repairing',
            $reason
        );

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function storeCostStage(Request $request, Reception $reception, ReceptionCustodyGate $gate)
    {
        if ($reception->isDelivered()) {
            return back()->withErrors(['stage' => 'قبض تحویل‌شده را نمی‌توان ویرایش کرد. ابتدا لغو تحویل بزنید.']);
        }

        $gate->assertCanSetCost($reception);

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

        return array_merge($this->editLookups(), [
            'customers' => Customer::query()->latest('id')->limit(80)->get(['id', 'name', 'phone']),
            'nextTicket' => Reception::nextTicketNo(),
            'nextReceipt' => Reception::nextReceiptNo(),
            'lastReception' => $lastReception,
        ]);
    }

    /** Lookups needed on show/edit without expensive next-receipt scans. */
    private function editLookups(): array
    {
        return [
            'technicians' => $this->cachedActiveRows('techs_active_v2', Technician::class, ['id', 'name']),
            'faultTypes' => $this->cachedActiveRows('faults_active_v2', FaultType::class, ['id', 'name']),
            'referralSources' => $this->cachedActiveRows('referrals_active_v2', ReferralSource::class, ['id', 'name']),
            'admissionTypes' => LookupOption::options('admission_type'),
            'serviceTypes' => LookupOption::options('service_type'),
            'repairTypes' => LookupOption::options('repair_type'),
            'warrantyTypes' => LookupOption::options('warranty_type'),
            'hddCapacities' => LookupOption::options('hdd_capacity'),
            'brandModels' => LookupOption::options('brand_model'),
        ];
    }

    /**
     * Cache lookup rows as plain arrays (database cache corrupts Eloquent models).
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $modelClass
     * @param  list<string>  $columns
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function cachedActiveRows(string $cacheKey, string $modelClass, array $columns, int $ttl = 60)
    {
        $rows = Cache::remember($cacheKey, $ttl, function () use ($modelClass, $columns) {
            return $modelClass::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get($columns)
                ->map(fn ($row) => $row->only($columns))
                ->all();
        });

        if (! is_array($rows)) {
            Cache::forget($cacheKey);
            $rows = $modelClass::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get($columns)
                ->map(fn ($row) => $row->only($columns))
                ->all();
        }

        return collect($rows)->map(function ($row) {
            if (is_object($row) && ! $row instanceof \Illuminate\Database\Eloquent\Model) {
                return $row;
            }
            if ($row instanceof \Illuminate\Database\Eloquent\Model) {
                return (object) $row->toArray();
            }

            return (object) (is_array($row) ? $row : []);
        })->filter(fn ($row) => isset($row->id))->values();
    }

    private function searchQuery(string $q, ?string $status = null)
    {
        $phone = $this->normalizePhone($q);
        $phoneTail = strlen($phone) >= 10 ? substr($phone, -10) : $phone;
        $qUpper = mb_strtoupper($q);

        return Reception::query()
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($q !== '', function ($query) use ($q, $qUpper, $phone, $phoneTail) {
                $query->where(function ($inner) use ($q, $qUpper, $phone, $phoneTail) {
                    // Prefer exact / prefix matches that can use indexes.
                    $inner->where('ticket_no', $q)
                        ->orWhere('ticket_no', 'like', $q.'%')
                        ->orWhere('receipt_no', $q)
                        ->orWhere('receipt_no', 'like', $q.'%')
                        ->orWhere('batch_code', $q)
                        ->orWhere('batch_code', 'like', $q.'%')
                        ->orWhere('serial_number', $q)
                        ->orWhere('serial_number', $qUpper)
                        ->orWhere('serial_number', 'like', $qUpper.'%')
                        ->orWhere('product_name', 'like', $q.'%')
                        ->orWhere('brand', 'like', $q.'%')
                        ->orWhere('model', 'like', $q.'%')
                        ->orWhereHas('customer', function ($c) use ($q, $phone, $phoneTail) {
                            $c->where('name', 'like', $q.'%')
                                ->orWhere('phone', $phone !== '' ? $phone : $q);

                            if ($phoneTail !== '' && strlen($phoneTail) >= 10) {
                                $c->orWhere('phone', 'like', '%'.$phoneTail);
                            } elseif (mb_strlen($q) >= 3) {
                                $c->orWhere('name', 'like', '%'.$q.'%')
                                    ->orWhere('phone', 'like', '%'.$q.'%');
                            }
                        });

                    // Fallback contains-search for short serial/ticket fragments.
                    if (mb_strlen($q) >= 4) {
                        $inner->orWhere('serial_number', 'like', '%'.$qUpper.'%')
                            ->orWhere('ticket_no', 'like', '%'.$q.'%')
                            ->orWhere('receipt_no', 'like', '%'.$q.'%');
                    }
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
            $converted = $this->toAsciiEnglish((string) $data['brand_model']);
            $data['brand_model'] = $converted !== null ? strtoupper($converted) : null;
        }
        if (! empty($data['brand'])) {
            $data['brand'] = $this->toAsciiEnglish((string) $data['brand']);
        }
        if (! empty($data['model'])) {
            $converted = $this->toAsciiEnglish((string) $data['model']);
            $data['model'] = $converted !== null ? strtoupper($converted) : null;
        }
        if (! empty($data['serial_number'])) {
            $converted = $this->toAsciiEnglish((string) $data['serial_number']);
            $data['serial_number'] = $converted !== null ? strtoupper($converted) : null;
        }

        $this->assertSerialAvailable($data['serial_number'] ?? null);

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
            'model' => (($m = $this->toAsciiEnglish((string) ($data['model'] ?? ($brandModel ?: '')))) !== null ? strtoupper($m) : null),
            'serial_number' => (($s = $this->toAsciiEnglish((string) ($data['serial_number'] ?? ''))) !== null ? strtoupper($s) : null),
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

    /**
     * Send create-SMS after the HTTP response so FPM workers are not blocked
     * by the SMS panel (up to ~20s) during peak reception traffic.
     */
    private function queueReceptionCreatedSms(Reception $reception): void
    {
        $id = (int) $reception->id;
        dispatch(function () use ($id) {
            try {
                $row = Reception::query()->with(['customer', 'faultType'])->find($id);
                if (! $row) {
                    return;
                }
                app(SmsNotificationService::class)->sendOnCreate($row);
            } catch (\Throwable $e) {
            }
        })->afterResponse();
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

    private function normalizeSerialNumber(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $converted = $this->toAsciiEnglish((string) $value);

        return $converted !== null ? strtoupper($converted) : null;
    }

    /**
     * One active ticket per device serial. Soft-deleted/cancelled tickets do not block reuse.
     */
    private function assertSerialAvailable(?string $serial, ?int $ignoreReceptionId = null, string $field = 'serial_number'): void
    {
        $serial = $this->normalizeSerialNumber($serial);
        if ($serial === null) {
            return;
        }

        $query = Reception::query()
            ->where('status', '!=', 'cancelled')
            ->whereRaw('UPPER(TRIM(serial_number)) = ?', [$serial]);

        if ($ignoreReceptionId) {
            $query->where('id', '!=', $ignoreReceptionId);
        }

        $existing = $query->orderByDesc('id')->first(['id', 'ticket_no', 'serial_number', 'status']);
        if (! $existing) {
            return;
        }

        throw ValidationException::withMessages([
            $field => 'این سریال قبلاً ثبت شده است (قبض '.$existing->ticket_no.'). هر سریال فقط یک قبض می‌تواند داشته باشد.',
        ]);
    }

    /** @param  array<int, array<string, mixed>>  $items */
    private function assertBatchSerialsUnique(array $items): void
    {
        $seen = [];
        foreach ($items as $index => $item) {
            $serial = $this->normalizeSerialNumber(isset($item['serial_number']) ? (string) $item['serial_number'] : null);
            if ($serial === null) {
                continue;
            }
            $field = 'items.'.$index.'.serial_number';
            if (isset($seen[$serial])) {
                throw ValidationException::withMessages([
                    $field => 'سریال تکراری در همین پذیرش گروهی است. هر سریال فقط یک قبض می‌تواند داشته باشد.',
                ]);
            }
            $seen[$serial] = $index;
            $this->assertSerialAvailable($serial, null, $field);
        }
    }

    private function toAsciiEnglish(string $value): ?string
    {
        // Windows Persian (ISIRI) keyboard → English QWERTY, then digit normalization.
        $faKeyboard = [
            'ض' => 'q', 'ص' => 'w', 'ث' => 'e', 'ق' => 'r', 'ف' => 't', 'غ' => 'y', 'ع' => 'u', 'ه' => 'i', 'خ' => 'o', 'ح' => 'p',
            'ج' => '[', 'چ' => ']',
            'ش' => 'a', 'س' => 's', 'ی' => 'd', 'ي' => 'd', 'ب' => 'f', 'ل' => 'g', 'ا' => 'h', 'ت' => 'j', 'ن' => 'k', 'م' => 'l',
            'ک' => ';', 'ك' => ';', 'گ' => "'",
            'ظ' => 'z', 'ط' => 'x', 'ز' => 'c', 'ر' => 'v', 'ذ' => 'b', 'د' => 'n', 'پ' => 'm', 'و' => ',',
            'ْ' => '`', 'ٓ' => '~', 'ٰ' => 'Q', 'ـ' => 'W', 'ژ' => 'C',
            'آ' => 'H', 'ة' => 'M', 'ء' => 'X', 'ئ' => 'S', 'ؤ' => 'A',
            '؟' => '?', '،' => ',', '؛' => ';',
        ];
        $digits = [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ];

        $text = trim($value);
        $out = '';
        $len = mb_strlen($text, 'UTF-8');
        for ($i = 0; $i < $len; $i++) {
            $ch = mb_substr($text, $i, 1, 'UTF-8');
            if (isset($faKeyboard[$ch])) {
                $out .= $faKeyboard[$ch];
            } elseif (isset($digits[$ch])) {
                $out .= $digits[$ch];
            } elseif (preg_match('/^[\x{0600}-\x{06FF}]$/u', $ch)) {
                // Unknown Arabic/Persian glyph: drop (serial/model must be Latin)
                continue;
            } else {
                $out .= $ch;
            }
        }

        // Keep printable ASCII for serial/model (letters, digits, punctuation)
        $text = preg_replace('/[^\x20-\x7E]+/u', '', $out) ?? '';
        $text = trim($text);

        return $text === '' ? null : $text;
    }
}
