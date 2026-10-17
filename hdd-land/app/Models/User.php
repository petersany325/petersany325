<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name', 'last_name', 'username', 'email', 'password', 'phone', 'role', 'is_admin',
    'national_id', 'address', 'city', 'province', 'postal_code', 'birth_date',
    'bank_name', 'bank_card', 'bank_iban', 'bank_account_holder',
    'phone_verified_at', 'two_factor_enabled', 'two_factor_secret', 'two_factor_method',
    'terms_accepted_at', 'email_verified_at',
])]
#[Hidden(['password', 'remember_token', 'two_factor_secret'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'birth_date' => 'date',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'two_factor_enabled' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin || $this->role === 'admin';
    }

    public function isStaff(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }
        if ($this->role === 'staff') {
            return $this->staffMember() !== null;
        }

        return $this->staffMember() !== null;
    }

    public function staffMember(): ?object
    {
        static $cache = [];
        $id = (int) $this->id;
        if (array_key_exists($id, $cache)) {
            return $cache[$id];
        }
        try {
            if (! \Illuminate\Support\Facades\Schema::hasTable('staff_members')) {
                return $cache[$id] = null;
            }
            \Plugins\StaffHR\Plugin::ensureSchema();
            $row = \Illuminate\Support\Facades\DB::table('staff_members')->where('user_id', $id)->first();

            return $cache[$id] = $row ?: null;
        } catch (\Throwable) {
            return $cache[$id] = null;
        }
    }

    public function hasStaffPermission(string $permission): bool
    {
        if ($this->isAdmin()) {
            return true;
        }
        $staff = $this->staffMember();
        if (! $staff) {
            return false;
        }

        return \Plugins\StaffHR\src\Support\StaffAcl::hasPermission($staff, $permission);
    }

    public function needsTwoFactor(): bool
    {
        if ($this->isAdmin() || $this->isStaff()) {
            return false;
        }
        $force = ! empty(\Plugins\AuthCustomers\Plugin::settings()['force_2fa']);

        return $force || (bool) $this->two_factor_enabled;
    }

    public function twoFactorLabel(): string
    {
        return match ($this->two_factor_method) {
            'sms' => 'پیامک',
            'email' => 'ایمیل',
            'authenticator' => 'Authenticator',
            default => 'غیرفعال',
        };
    }
}
