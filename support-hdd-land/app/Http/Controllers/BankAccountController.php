<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BankAccountController extends Controller
{
    public function index()
    {
        $banks = BankAccount::query()
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('accounting.banks', compact('banks'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        DB::transaction(function () use ($data) {
            $bank = BankAccount::create($data);
            if (! empty($data['is_default'])) {
                $bank->makeDefault();
            }
        });

        return back()->with('success', 'حساب بانکی ثبت شد.');
    }

    public function update(Request $request, BankAccount $bank)
    {
        $data = $this->validated($request, $bank->id);
        DB::transaction(function () use ($data, $bank) {
            $bank->update($data);
            if (! empty($data['is_default'])) {
                $bank->makeDefault();
            }
        });

        return back()->with('success', 'حساب بانکی به‌روزرسانی شد.');
    }

    public function destroy(BankAccount $bank)
    {
        if ($bank->fixedCosts()->exists()) {
            return back()->withErrors(['bank' => 'این حساب به هزینه ثابت وصل است؛ ابتدا هزینه را تغییر دهید.']);
        }
        $bank->delete();

        return back()->with('success', 'حساب بانکی حذف شد.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'bank_name' => ['nullable', 'string', 'max:120'],
            'account_number' => ['nullable', 'string', 'max:64'],
            'iban' => ['nullable', 'string', 'max:34'],
            'card_number' => ['nullable', 'string', 'max:32'],
            'account_type' => ['required', Rule::in(['cash', 'card', 'transfer'])],
            'gl_code' => ['nullable', Rule::in(['1110', '1120', '1130'])],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'note' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_default'] = $request->boolean('is_default', false);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        if (empty($data['gl_code'])) {
            $data['gl_code'] = match ($data['account_type']) {
                'cash' => '1110',
                'card' => '1120',
                default => '1130',
            };
        }

        return $data;
    }
}
