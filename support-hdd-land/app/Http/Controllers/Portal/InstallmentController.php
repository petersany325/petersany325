<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\InstallmentItem;
use App\Models\InstallmentPlan;
use App\Services\InstallmentService;
use App\Support\BankTransferSettings;
use App\Support\PaymentGateways;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InstallmentController extends Controller
{
    public function index(Request $request)
    {
        $customer = $this->customer($request);
        $plans = InstallmentPlan::query()
            ->with(['items', 'reception'])
            ->where('customer_id', $customer->id)
            ->where('status', '!=', 'cancelled')
            ->latest('id')
            ->get();

        foreach ($plans as $plan) {
            foreach ($plan->items as $item) {
                $item->refreshStatus();
            }
            $plan->refreshStatus();
        }

        return view('portal.installments.index', compact('customer', 'plans'));
    }

    public function show(Request $request, InstallmentPlan $plan)
    {
        $customer = $this->customer($request);
        abort_unless((int) $plan->customer_id === (int) $customer->id, 404);
        abort_if($plan->status === 'cancelled', 404);

        $plan->load(['items.payments', 'reception', 'checks']);
        foreach ($plan->items as $item) {
            $item->refreshStatus();
        }
        $plan->refreshStatus();

        $bankTransfer = BankTransferSettings::all();
        $zarinpalReady = PaymentGateways::zarinpal()['configured']
            && $plan->reception
            && $plan->reception->remainingAmount() > 0;

        return view('portal.installments.show', compact('customer', 'plan', 'bankTransfer', 'zarinpalReady'));
    }

    public function pay(Request $request, InstallmentItem $item, InstallmentService $installments)
    {
        $customer = $this->customer($request);
        $item->loadMissing('plan');
        abort_unless($item->plan && (int) $item->plan->customer_id === (int) $customer->id, 404);
        abort_if($item->plan->status === 'cancelled' || $item->status === 'cancelled', 404);

        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'method' => ['required', Rule::in(['transfer', 'card'])],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $result = $installments->collect($item, [
                'amount' => (int) $data['amount'],
                'method' => $data['method'],
                'note' => trim((string) ($data['note'] ?? '')) !== ''
                    ? $data['note']
                    : 'پرداخت قسط از کارتابل مشتری',
            ]);
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }

        return redirect()
            ->route('portal.installments.show', $item->installment_plan_id)
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    private function customer(Request $request): Customer
    {
        /** @var Customer $customer */
        $customer = $request->attributes->get('portalCustomer');

        return $customer;
    }
}
