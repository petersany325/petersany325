<?php

use App\Models\User;
use App\Support\Permissions;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        User::query()->each(function (User $user) {
            $perms = is_array($user->permissions) ? $user->permissions : [];
            if (in_array('partners', $perms, true)) {
                return;
            }
            // Grant to roles that already have handoffs/receptions by default.
            $role = (string) ($user->role ?? '');
            $defaults = Permissions::defaultsForRole($role);
            if (in_array('partners', $defaults, true) || in_array('handoffs', $perms, true) || $user->isAdmin()) {
                $perms[] = 'partners';
                $user->permissions = array_values(array_unique($perms));
                $user->save();
            }
        });
    }

    public function down(): void
    {
        // keep permission grants
    }
};
