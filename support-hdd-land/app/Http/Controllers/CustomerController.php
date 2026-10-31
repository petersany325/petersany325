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
        $filter = (string) $request->get('filter', '');

        $customers = Customer::with('referralSource')
            ->withCount('receptions')
            ->when($filter === 'blacklist', fn ($query) => $query->where('is_blacklisted', true))
            ->when($filter === 'debt', function ($query) {
                $query->whereHas('receptions', function ($r) {
                    $r->where('status', '!=', 'cancelled')
                        ->whereRaw('COALESCE(total_amount,0) > COALESCE(paid_amount,0)');
                });
            })
            ->when($q !== '', function ($query) use ($q) {
                $phone = User::normalizePhone($q) ?: $q;
                $query->where(function ($inner) use ($q, $phone) {
                    $inner->where('name', 'like', "%{$q}%")
                        ->orWhere('alias', 'like', "%{$q}%")
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

        return view('customers.index', compact('customers', 'q', 'filter'));
    }

    /** Live name/phone suggest for reception, delivery, blacklist, etc. */
    public function suggest(Request $request)
    {
        $user = $request->user();
        abort_unless(
            $user && ($user->canAccess('customers') || $user->canAccess('receptions') || $user->canAccess('handoffs')),
            403
        );

        $q = trim((string) $request->query('q', ''));
        $q = strtr($q, [
            'ي' => 'ی', 'ك' => 'ک',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
        $filter = (string) $request->query('filter', '');

        if (mb_strlen($q) < 1) {
            return response()->json(['ok' => true, 'q' => $q, 'count' => 0, 'customers' => []]);
        }

        $digits = preg_replace('/\D+/', '', $q) ?? '';
        $rows = Customer::query()
            ->withCount('receptions')
            ->when($filter === 'blacklist', fn ($query) => $query->where('is_blacklisted', true))
            ->where(function ($query) use ($q, $digits) {
                $query->where('name', 'like', '%'.$q.'%')
                    ->orWhere('alias', 'like', '%'.$q.'%');
                if (strlen($digits) >= 3) {
                    $query->orWhere('phone', 'like', '%'.$digits.'%');
                }
                if (mb_strlen($q) >= 3) {
                    $query->orWhere('national_code', 'like', '%'.$q.'%');
                }
            })
            ->orderByDesc('id')
            ->limit(25)
            ->get();

        return response()->json([
            'ok' => true,
            'q' => $q,
            'count' => $rows->count(),
            'customers' => $rows->map(fn (Customer $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'display_name' => $c->displayName(),
                'alias' => $c->alias,
                'phone' => $c->phone,
                'job' => $c->job,
                'is_blacklisted' => (bool) $c->is_blacklisted,
                'visits' => (int) ($c->receptions_count ?? 0),
            ])->values(),
        ]);
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

    public function toggleBlacklist(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'blacklist_reason' => ['nullable', 'string', 'max:500'],
        ]);

        if ($customer->is_blacklisted) {
            $customer->update([
                'is_blacklisted' => false,
                'blacklist_reason' => null,
                'blacklisted_at' => null,
            ]);

            return back()->with('success', 'مشتری از لیست سیاه خارج شد.');
        }

        $customer->update([
            'is_blacklisted' => true,
            'blacklist_reason' => $data['blacklist_reason'] ?: 'بدحساب / مسدود توسط پذیرش',
            'blacklisted_at' => now(),
        ]);

        return back()->with('success', 'مشتری به لیست سیاه اضافه شد. پذیرش جدید برای این موبایل مسدود است.');
    }

    public function destroy(Customer $customer)
    {
        $receiptCount = $customer->receptions()->count();
        $preorderCount = \Illuminate\Support\Facades\Schema::hasTable('remote_part_preorders')
            ? (int) $customer->remotePartPreorders()->count()
            : 0;

        // Soft-delete only — never forceDelete. Historical receipts, messages and
        // remote-part preorders (with photos in storage/) must remain intact.
        $customer->forceFill([
            'name' => mb_substr($customer->name, 0, 100).' «حذف‌شده»',
            'phone' => 'del'.$customer->id.'_'.preg_replace('/\D+/', '', (string) $customer->phone),
        ])->save();

        $customer->delete();

        $message = 'مشتری از فهرست حذف شد.';
        if ($receiptCount > 0) {
            $message .= " {$receiptCount} قبض قبلی در سیستم باقی ماند.";
        }
        if ($preorderCount > 0) {
            $message .= " {$preorderCount} پیش‌سفارش قطعه و عکس‌ها محفوظ ماند.";
        }

        return redirect()->route('customers.index')->with('success', $message);
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
                Rule::unique('customers', 'name')->whereNull('deleted_at')->ignore($customer?->id),
            ],
            'alias' => ['nullable', 'string', 'max:120'],
            'gender' => ['nullable', 'in:male,female,other'],
            'phone' => [
                'required',
                'string',
                'max:20',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (! is_string($value) || strlen($value) < 10) {
                        $fail('شماره موبایل معتبر نیست.');
                    }
                },
                Rule::unique('customers', 'phone')->whereNull('deleted_at')->ignore($customer?->id),
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
