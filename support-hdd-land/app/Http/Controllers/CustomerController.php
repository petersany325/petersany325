<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\ReferralSource;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q'));

        $customers = Customer::with('referralSource')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%")
                        ->orWhere('national_code', 'like', "%{$q}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('customers.index', compact('customers', 'q'));
    }

    public function create()
    {
        return view('customers.create', [
            'referralSources' => ReferralSource::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        Customer::create($data);

        return redirect()->route('customers.index')->with('success', 'مشتری با موفقیت ثبت شد.');
    }

    public function edit(Customer $customer)
    {
        return view('customers.edit', [
            'customer' => $customer,
            'referralSources' => ReferralSource::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Customer $customer)
    {
        $customer->update($this->validated($request));

        return redirect()->route('customers.index')->with('success', 'اطلاعات مشتری به‌روزرسانی شد.');
    }

    public function show(Customer $customer)
    {
        $customer->load(['referralSource', 'receptions.technician']);

        return view('customers.show', compact('customer'));
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:20'],
            'national_code' => ['nullable', 'string', 'max:20'],
            'job' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:500'],
            'referral_source_id' => ['nullable', 'exists:referral_sources,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
