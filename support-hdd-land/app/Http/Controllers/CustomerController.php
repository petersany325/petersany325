<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\ReferralSource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q'));

        $customers = Customer::with('referralSource')
            ->withCount('receptions')
            ->when($q !== '', function ($query) use ($q) {
                $phone = User::normalizePhone($q) ?: $q;
                $query->where(function ($inner) use ($q, $phone) {
                    $inner->where('name', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%")
                        ->orWhere('national_code', 'like', "%{$q}%");
                    if ($phone !== $q) {
                        $inner->orWhere('phone', 'like', '%'.substr($phone, -10).'%');
                    }
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
        $customer->update($this->validated($request, $customer));

        return redirect()->route('customers.index')->with('success', 'اطلاعات مشتری به‌روزرسانی شد.');
    }

    public function show(Customer $customer)
    {
        $customer->load(['referralSource', 'receptions.technician']);

        return view('customers.show', compact('customer'));
    }

    public function destroy(Customer $customer)
    {
        if ($customer->receptions()->exists()) {
            return back()->withErrors([
                'customer' => 'این مشتری قبض دارد و قابل حذف نیست. ابتدا قبض‌ها را بررسی کنید یا فقط ویرایش کنید.',
            ]);
        }

        if (method_exists($customer, 'messages') && $customer->messages()->exists()) {
            $customer->messages()->delete();
        }

        $customer->delete();

        return redirect()->route('customers.index')->with('success', 'مشتری حذف شد.');
    }

    private function validated(Request $request, ?Customer $customer = null): array
    {
        $phone = User::normalizePhone((string) $request->input('phone', ''));
        $request->merge(['phone' => $phone]);

        $name = trim((string) $request->input('name', ''));
        $request->merge(['name' => $name]);

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('customers', 'name')->ignore($customer?->id),
            ],
            'phone' => [
                'required',
                'string',
                'max:20',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (! is_string($value) || strlen($value) < 10) {
                        $fail('شماره موبایل معتبر نیست.');
                    }
                },
                Rule::unique('customers', 'phone')->ignore($customer?->id),
            ],
            'national_code' => ['nullable', 'string', 'max:20'],
            'job' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:500'],
            'referral_source_id' => ['nullable', 'exists:referral_sources,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'name.unique' => 'نام مشتری تکراری است. مشتری دیگری با همین نام ثبت شده.',
            'phone.unique' => 'شماره موبایل تکراری است. مشتری دیگری با همین موبایل ثبت شده.',
        ]);

        // Also block alternate phone formats that resolve to the same number
        $dupPhone = Customer::findByPhone($data['phone']);
        if ($dupPhone && (! $customer || (int) $dupPhone->id !== (int) $customer->id)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'phone' => 'شماره موبایل تکراری است. مشتری دیگری با همین موبایل ثبت شده.',
            ]);
        }

        return $data;
    }
}
