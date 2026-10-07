<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        $portal = request('portal', 'customer');
        if ($portal === 'staff' && ! Setting::bool('staff_login_enabled', true)) {
            return redirect()->route('login')->with('error', 'Staff login is disabled.');
        }
        if ($portal !== 'staff' && ! Setting::bool('customer_login_enabled', true)) {
            return redirect()->route('home')->with('error', 'Customer login is disabled.');
        }

        return view('auth.login', [
            'customerRegisterEnabled' => Setting::bool('customer_register_enabled', true),
            'staffLoginEnabled' => Setting::bool('staff_login_enabled', true),
        ]);
    }

    public function showRegister(): View|RedirectResponse
    {
        if (! Setting::bool('customer_register_enabled', true)) {
            return redirect()->route('login')->with('error', 'Customer registration is disabled.');
        }

        return view('auth.register');
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'portal' => ['nullable', 'in:customer,staff'],
        ]);

        if (! Auth::attempt(['email' => $data['email'], 'password' => $data['password']], $request->boolean('remember'))) {
            return back()->withInput()->with('error', 'Invalid email or password.');
        }

        $request->session()->regenerate();
        $user = Auth::user();
        if (! $user->isActiveUser()) {
            Auth::logout();

            return back()->with('error', 'This account is disabled.');
        }

        $portal = $data['portal'] ?? 'customer';
        if ($portal === 'staff') {
            if (! Setting::bool('staff_login_enabled', true) || ! Setting::bool('staff_portal_enabled', true)) {
                Auth::logout();

                return back()->with('error', 'Staff login is disabled.');
            }
            if (! $user->isStaff()) {
                Auth::logout();

                return back()->with('error', 'Staff access only.');
            }

            return redirect()->intended(route('staff.dashboard'));
        }

        if (! Setting::bool('customer_login_enabled', true) && ! $user->isStaff()) {
            Auth::logout();

            return back()->with('error', 'Customer login is disabled.');
        }

        if ($user->isAdmin()) {
            return redirect()->intended('/admin');
        }
        if ($user->isStaff()) {
            return redirect()->intended(route('staff.dashboard'));
        }
        if (! Setting::bool('customer_portal_enabled', true)) {
            return redirect()->intended(route('home'));
        }

        return redirect()->intended(route('account.dashboard'));
    }

    public function register(Request $request): RedirectResponse
    {
        if (! Setting::bool('customer_register_enabled', true)) {
            return redirect()->route('login')->with('error', 'Customer registration is disabled.');
        }

        $phoneRules = Setting::bool('require_phone_on_register', false)
            ? ['required', 'string', 'max:40']
            : ['nullable', 'string', 'max:40'];

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:191', 'unique:users,email'],
            'phone' => $phoneRules,
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'role' => 'customer',
            'is_admin' => false,
            'is_active' => true,
        ]);

        Auth::login($user);

        return redirect()->route('account.dashboard')->with(
            'success',
            Setting::getValue('welcome_message', 'Welcome to EK Electronics.')
        );
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
