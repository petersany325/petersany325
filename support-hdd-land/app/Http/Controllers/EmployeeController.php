<?php

namespace App\Http\Controllers;

use App\Models\Technician;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    public function index()
    {
        $employees = User::query()->orderBy('name')->paginate(20);
        $all = User::query()->get(['id', 'is_active', 'can_login_otp', 'can_login_password']);

        return view('employees.index', [
            'employees' => $employees,
            'stats' => [
                'total' => $all->count(),
                'active' => $all->where('is_active', true)->count(),
                'otp' => $all->where('can_login_otp', true)->count(),
                'password' => $all->where('can_login_password', true)->count(),
            ],
        ]);
    }

    public function create()
    {
        return view('employees.create', [
            'permissions' => Permissions::ALL,
            'defaults' => Permissions::defaultsForRole('receptionist'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'] ?: null,
            'phone' => User::normalizePhone($data['phone']),
            'password' => $data['password'] ?: str()->random(12),
            'role' => $data['role'],
            'permissions' => $data['role'] === 'admin'
                ? array_keys(Permissions::ALL)
                : ($data['permissions'] ?? Permissions::defaultsForRole($data['role'])),
            'can_login_otp' => $request->boolean('can_login_otp'),
            'can_login_password' => $request->boolean('can_login_password'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        if ($data['role'] === 'technician') {
            Technician::firstOrCreate(
                ['user_id' => $user->id],
                ['name' => $user->name, 'phone' => $user->phone, 'is_active' => true]
            );
        }

        return redirect()->route('employees.index')->with('success', 'کارمند ثبت شد.');
    }

    public function edit(User $employee)
    {
        return view('employees.edit', [
            'employee' => $employee,
            'permissions' => Permissions::ALL,
            'selected' => $employee->permissionList(),
        ]);
    }

    public function update(Request $request, User $employee)
    {
        $data = $this->validated($request, $employee);

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'] ?: null,
            'phone' => User::normalizePhone($data['phone']),
            'role' => $data['role'],
            'permissions' => $employee->isAdmin() ? array_keys(Permissions::ALL) : ($data['permissions'] ?? []),
            'can_login_otp' => $request->boolean('can_login_otp'),
            'can_login_password' => $request->boolean('can_login_password'),
            'is_active' => $request->boolean('is_active', true),
        ];

        if (! empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        $employee->update($payload);

        return redirect()->route('employees.index')->with('success', 'اطلاعات کارمند به‌روزرسانی شد.');
    }

    private function validated(Request $request, ?User $employee = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', Rule::unique('users', 'email')->ignore($employee?->id)],
            'phone' => ['required', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($employee?->id)],
            'role' => ['required', Rule::in(['admin', 'receptionist', 'technician', 'accountant', 'employee'])],
            'password' => [$employee ? 'nullable' : 'nullable', 'string', 'min:6'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(array_keys(Permissions::ALL))],
        ]);

        return $data;
    }
}
