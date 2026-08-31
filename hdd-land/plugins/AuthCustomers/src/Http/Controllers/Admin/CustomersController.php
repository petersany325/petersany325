<?php

namespace Plugins\AuthCustomers\src\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Plugins\AuthCustomers\Plugin;
use Plugins\AuthCustomers\src\Services\SmsGateway;

class CustomersController extends Controller
{
    public function index(Request $request): View
    {
        Plugin::ensureSchema();
        $q = User::query()
            ->where(function ($w) {
                $w->where('is_admin', false)->orWhereNull('is_admin');
            })
            ->where(function ($w) {
                $w->whereNull('role')->orWhere('role', '<>', 'admin');
            })
            ->latest();

        if ($search = trim((string) $request->query('q', ''))) {
            $q->where(function ($w) use ($search) {
                $w->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $customers = $q->paginate(20)->withQueryString();
        $balances = [];
        try {
            \Plugins\AuthCustomers\src\Support\WalletSupport::ensureSchema();
            foreach ($customers as $c) {
                $balances[$c->id] = \Plugins\AuthCustomers\src\Services\WalletService::balance((int) $c->id);
            }
        } catch (\Throwable) {
            //
        }

        return view('auth-customers::admin.customers', compact('customers', 'search', 'balances'));
    }

    public function edit(User $user): View|RedirectResponse
    {
        if ($guard = $this->guardCustomer($user)) {
            return $guard;
        }
        Plugin::ensureSchema();
        $settings = Plugin::settings();
        $provinces = [];
        $citiesMap = [];
        $banks = [];
        try {
            $provinces = \Plugins\AuthCustomers\src\Support\IranLocations::provinces();
            $citiesMap = \Plugins\AuthCustomers\src\Support\IranLocations::map();
        } catch (\Throwable) {
            //
        }
        try {
            $banks = \Plugins\AuthCustomers\src\Support\IranBanks::names();
        } catch (\Throwable) {
            //
        }
        $balance = 0;
        try {
            \Plugins\AuthCustomers\src\Support\WalletSupport::ensureSchema();
            $balance = \Plugins\AuthCustomers\src\Services\WalletService::balance((int) $user->id);
        } catch (\Throwable) {
            //
        }

        return view('auth-customers::admin.customer-edit', compact('user', 'settings', 'provinces', 'citiesMap', 'banks', 'balance'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        if ($guard = $this->guardCustomer($user)) {
            return $guard;
        }
        Plugin::ensureSchema();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'national_id' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'province' => ['nullable', 'string', 'max:80'],
            'city' => ['nullable', 'string', 'max:120'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'birth_date' => ['nullable', 'date'],
            'bank_name' => ['nullable', 'string', 'max:80'],
            'bank_card' => ['nullable', 'string', 'max:24'],
            'bank_iban' => ['nullable', 'string', 'max:34'],
            'bank_account_holder' => ['nullable', 'string', 'max:160'],
            'password' => ['nullable', 'string', 'min:8', 'max:100'],
            'phone_verified' => ['nullable', 'boolean'],
            'two_factor_enabled' => ['nullable', 'boolean'],
            'two_factor_method' => ['nullable', 'in:none,sms,email,authenticator'],
        ], [
            'name.required' => 'نام الزامی است.',
            'email.required' => 'ایمیل الزامی است.',
            'email.unique' => 'این ایمیل قبلاً ثبت شده است.',
            'password.min' => 'رمز عبور حداقل ۸ کاراکتر باشد.',
        ]);

        if (! empty($data['province'])) {
            try {
                if (! \Plugins\AuthCustomers\src\Support\IranLocations::isValidProvince($data['province'])) {
                    return back()->withErrors(['province' => 'استان نامعتبر است.'])->withInput();
                }
                if (! empty($data['city'])
                    && ! \Plugins\AuthCustomers\src\Support\IranLocations::isValidCity($data['province'], $data['city'])) {
                    return back()->withErrors(['city' => 'شهر با استان مطابقت ندارد.'])->withInput();
                }
            } catch (\Throwable) {
                //
            }
        }

        $payload = [
            'name' => trim((string) $data['name']),
            'email' => trim((string) $data['email']),
            'national_id' => trim((string) ($data['national_id'] ?? '')) ?: null,
            'address' => trim((string) ($data['address'] ?? '')) ?: null,
            'province' => trim((string) ($data['province'] ?? '')) ?: null,
            'city' => trim((string) ($data['city'] ?? '')) ?: null,
            'postal_code' => trim((string) ($data['postal_code'] ?? '')) ?: null,
            'birth_date' => $data['birth_date'] ?? null,
            'bank_name' => trim((string) ($data['bank_name'] ?? '')) ?: null,
            'bank_account_holder' => trim((string) ($data['bank_account_holder'] ?? '')) ?: null,
        ];

        $phoneRaw = trim((string) ($data['phone'] ?? ''));
        if ($phoneRaw !== '') {
            try {
                $phone = SmsGateway::normalizePhone($phoneRaw);
            } catch (\Throwable) {
                $phone = $phoneRaw;
            }
            if ($phone !== $user->phone) {
                $payload['phone'] = $phone;
                if (! $request->boolean('phone_verified')) {
                    $payload['phone_verified_at'] = null;
                }
            } else {
                $payload['phone'] = $phone;
            }
        } else {
            $payload['phone'] = null;
            $payload['phone_verified_at'] = null;
        }

        if ($request->boolean('phone_verified') && ! empty($payload['phone'] ?? $user->phone)) {
            $payload['phone_verified_at'] = $user->phone_verified_at ?: now();
        } elseif (! $request->boolean('phone_verified')) {
            $payload['phone_verified_at'] = null;
        }

        $card = preg_replace('/\D+/', '', (string) ($data['bank_card'] ?? ''));
        $payload['bank_card'] = $card !== '' ? $card : null;

        $iban = strtoupper(preg_replace('/\s+/', '', (string) ($data['bank_iban'] ?? '')));
        if ($iban !== '') {
            if (! str_starts_with($iban, 'IR')) {
                $iban = 'IR'.$iban;
            }
            $payload['bank_iban'] = $iban;
        } else {
            $payload['bank_iban'] = null;
        }

        if (! empty($data['password'])) {
            $payload['password'] = (string) $data['password'];
        }

        $enable2fa = $request->boolean('two_factor_enabled');
        $method = (string) ($data['two_factor_method'] ?? 'none');
        if ($enable2fa) {
            if ($method === '' || $method === 'none') {
                $method = 'sms';
            }
            $payload['two_factor_enabled'] = true;
            $payload['two_factor_method'] = $method;
            if ($method === 'authenticator' && empty($user->two_factor_secret)) {
                try {
                    $payload['two_factor_secret'] = \Plugins\AuthCustomers\src\Services\TotpAuthenticator::generateSecret();
                } catch (\Throwable) {
                    //
                }
            }
        } else {
            $payload['two_factor_enabled'] = false;
            $payload['two_factor_method'] = 'none';
            $payload['two_factor_secret'] = null;
        }

        $user->fill($payload)->save();

        return redirect('/admin/customers')
            ->with('success', 'مشتری «'.$user->name.'» ویرایش شد.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($guard = $this->guardCustomer($user)) {
            return $guard;
        }
        if ((int) Auth::id() === (int) $user->id) {
            return back()->with('error', 'نمی‌توانید حساب خودتان را حذف کنید.');
        }

        $name = (string) $user->name;
        $id = (int) $user->id;

        try {
            DB::transaction(function () use ($user, $id) {
                // Best-effort cleanup of related customer data
                foreach ([
                    'wallet_transactions' => 'user_id',
                    'wallets' => 'user_id',
                    'tickets' => 'user_id',
                    'ticket_messages' => 'user_id',
                    'customer_preorders' => 'user_id',
                    'orders' => 'user_id',
                ] as $table => $col) {
                    try {
                        if (Schema::hasTable($table) && Schema::hasColumn($table, $col)) {
                            DB::table($table)->where($col, $id)->delete();
                        }
                    } catch (\Throwable) {
                        //
                    }
                }
                $user->delete();
            });
        } catch (\Throwable $e) {
            return back()->with('error', 'حذف مشتری انجام نشد: '.$e->getMessage());
        }

        return redirect('/admin/customers')->with('success', 'مشتری «'.$name.'» حذف شد.');
    }

    public function toggle2fa(User $user): RedirectResponse
    {
        if ($user->isAdmin()) {
            return back()->with('error', 'برای ادمین از این بخش تغییر ندهید.');
        }
        Plugin::ensureSchema();
        if ($user->two_factor_enabled) {
            $user->update([
                'two_factor_enabled' => false,
                'two_factor_method' => 'none',
                'two_factor_secret' => null,
            ]);

            return back()->with('success', '۲FA برای '.$user->name.' خاموش شد.');
        }

        $s = Plugin::settings();
        $method = $s['default_2fa_method'] ?? 'email';
        if ($method === 'sms' && empty($s['enable_sms_otp'])) {
            $method = 'email';
        }
        if ($method === 'email' && empty($s['enable_email_otp'])) {
            $method = ! empty($s['enable_authenticator']) ? 'authenticator' : 'sms';
        }
        if ($method === 'authenticator' && empty($s['enable_authenticator'])) {
            $method = ! empty($s['enable_email_otp']) ? 'email' : 'sms';
        }
        $user->update([
            'two_factor_enabled' => true,
            'two_factor_method' => $method,
            'two_factor_secret' => $method === 'authenticator'
                ? \Plugins\AuthCustomers\src\Services\TotpAuthenticator::generateSecret()
                : null,
        ]);

        return back()->with('success', '۲FA برای '.$user->name.' فعال شد ('.$method.').');
    }

    protected function guardCustomer(User $user): ?RedirectResponse
    {
        if ($user->isAdmin()) {
            return redirect('/admin/customers')->with('error', 'این حساب ادمین است و از این بخش قابل مدیریت نیست.');
        }

        return null;
    }
}
