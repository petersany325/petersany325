<?php

use App\Models\User;
use App\Support\Permissions;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        User::query()->orderBy('id')->each(function (User $user): void {
            if ($user->isAdmin() || $user->isIntern()) {
                return;
            }

            $perms = $user->permissions;
            if (! is_array($perms) || $perms === []) {
                // Empty permissions already fall back to role defaults (include dashboard).
                return;
            }

            $changed = false;
            if (! in_array('dashboard', $perms, true)) {
                array_unshift($perms, 'dashboard');
                $changed = true;
            }
            if (! in_array('profile', $perms, true)) {
                $perms[] = 'profile';
                $changed = true;
            }

            // Keep known permissions only; drop stale keys.
            $allowed = array_keys(Permissions::ALL);
            $filtered = array_values(array_unique(array_values(array_filter(
                $perms,
                fn ($p) => in_array($p, $allowed, true)
            ))));
            if ($filtered !== array_values($perms)) {
                $perms = $filtered;
                $changed = true;
            }

            if ($changed) {
                $user->permissions = array_values(array_unique($perms));
                $user->save();
            }
        });
    }

    public function down(): void
    {
        //
    }
};
