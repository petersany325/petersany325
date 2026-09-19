<?php

namespace App\Http\Controllers;

use App\Models\Technician;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class TechnicianController extends Controller
{
    public function index()
    {
        $technicians = Technician::with('user')->orderBy('name')->paginate(20);

        return view('technicians.index', compact('technicians'));
    }

    public function create()
    {
        return view('technicians.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:20'],
            'specialty' => ['nullable', 'string', 'max:120'],
            'commission_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'create_login' => ['nullable', 'boolean'],
            'email' => ['nullable', 'required_if:create_login,1', 'email', 'unique:users,email'],
            'password' => ['nullable', 'required_if:create_login,1', 'string', 'min:6'],
        ]);

        $userId = null;
        if ($request->boolean('create_login')) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => Hash::make($data['password']),
                'role' => 'technician',
                'is_active' => true,
            ]);
            $userId = $user->id;
        }

        Technician::create([
            'user_id' => $userId,
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'specialty' => $data['specialty'] ?? null,
            'commission_percent' => (int) ($data['commission_percent'] ?? 0),
            'is_active' => true,
        ]);

        return redirect()->route('technicians.index')->with('success', 'تعمیرکار ثبت شد.');
    }

    public function edit(Technician $technician)
    {
        return view('technicians.edit', compact('technician'));
    }

    public function update(Request $request, Technician $technician)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:20'],
            'specialty' => ['nullable', 'string', 'max:120'],
            'commission_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $technician->update([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'specialty' => $data['specialty'] ?? null,
            'commission_percent' => (int) ($data['commission_percent'] ?? 0),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('technicians.index')->with('success', 'اطلاعات تعمیرکار به‌روزرسانی شد.');
    }
}
