<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = Auth::user();

        return view('profile.edit', [
            'user' => $user,
            'needsPasswordCreate' => $user->needsInitialPassword(),
        ]);
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::user();
        $needsCreate = $user->needsInitialPassword();

        if ($needsCreate) {
            $data = $request->validate([
                'password' => ['required', 'string', 'min:6', 'confirmed'],
            ]);
        } else {
            $data = $request->validate([
                'current_password' => ['required', 'string'],
                'password' => ['required', 'string', 'min:6', 'confirmed'],
            ]);

            if (! Hash::check($data['current_password'], $user->password)) {
                return back()->withErrors(['current_password' => 'رمز فعلی اشتباه است.']);
            }
        }

        $user->update([
            'password' => $data['password'],
            'can_login_password' => true,
        ]);

        $message = $needsCreate
            ? 'رمز عبور با موفقیت ایجاد شد. از این پس می‌توانید با رمز هم وارد شوید.'
            : 'رمز عبور با موفقیت تغییر کرد.';

        return back()->with('success', $message);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($user->id)],
        ]);

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => User::normalizePhone($data['phone'] ?? null),
        ]);

        return back()->with('success', 'اطلاعات پروفایل ذخیره شد.');
    }
}
