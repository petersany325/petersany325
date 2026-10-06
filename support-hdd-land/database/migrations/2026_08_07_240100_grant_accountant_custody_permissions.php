<?php

use App\Models\User;
use App\Support\Permissions;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        User::query()->where('role', 'accountant')->each(function (User $user) {
            $current = is_array($user->permissions) ? $user->permissions : [];
            $merged = array_values(array_unique(array_merge($current, Permissions::defaultsForRole('accountant'))));
            $user->forceFill(['permissions' => $merged])->save();
        });
    }

    public function down(): void
    {
        // non-destructive
    }
};
