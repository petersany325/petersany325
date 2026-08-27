<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (! User::query()->where('is_admin', true)->exists()) {
            User::query()->create([
                'name' => 'مدیر سایت',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'is_admin' => true,
            ]);
        }

        $this->call(CmsSeeder::class);
        $this->call(MenuSeeder::class);
    }
}
