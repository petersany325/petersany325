<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * White-label seeder: only a placeholder admin.
 * The web installer uses seedFresh() instead and does not call this for customer installs.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'مدیر',
                'phone' => '09120000000',
                'password' => 'password',
                'role' => 'admin',
                'permissions' => \App\Support\Permissions::defaultsForRole('admin'),
                'can_login_otp' => true,
                'can_login_password' => true,
                'is_active' => true,
            ]
        );
    }
}
