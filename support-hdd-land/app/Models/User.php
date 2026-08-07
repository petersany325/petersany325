<?php

namespace App\Models;

use App\Support\Permissions;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name', 'email', 'phone', 'password', 'role', 'is_active',
    'permissions', 'can_login_otp', 'can_login_password',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'permissions' => 'array',
            'can_login_otp' => 'boolean',
            'can_login_password' => 'boolean',
        ];
    }

    public function technician(): HasOne
    {
        return $this->hasOne(Technician::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            'admin' => 'مدیر',
            'receptionist' => 'پذیرش',
            'technician' => 'تعمیرکار',
            'accountant' => 'حسابدار',
            'employee' => 'کارمند',
            default => $this->role,
        };
    }

    public function permissionList(): array
    {
        if ($this->isAdmin()) {
            return array_keys(Permissions::ALL);
        }

        if (is_array($this->permissions) && count($this->permissions)) {
            return $this->permissions;
        }

        return Permissions::defaultsForRole($this->role);
    }

    public function canAccess(string $permission): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return in_array($permission, $this->permissionList(), true);
    }

    public static function normalizePhone(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        $map = [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ];

        $phone = preg_replace('/\D+/', '', strtr($phone, $map)) ?? '';
        if ($phone === '') {
            return null;
        }

        if (str_starts_with($phone, '98') && strlen($phone) >= 12) {
            $phone = '0'.substr($phone, 2);
        }
        if (str_starts_with($phone, '9') && strlen($phone) === 10) {
            $phone = '0'.$phone;
        }

        return $phone;
    }
}
