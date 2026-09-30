<?php

use App\Models\User;
use App\Support\Permissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Open SMS status access for technician accounts so repair-stage
 * customer notifications stay configurable from their staff shell.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $extra = ['sms.statuses', 'reports.sms'];

        User::query()
            ->where('role', 'technician')
            ->orderBy('id')
            ->each(function (User $user) use ($extra) {
                $current = is_array($user->permissions) && count($user->permissions)
                    ? $user->permissions
                    : Permissions::defaultsForRole('technician');
                $merged = array_values(array_unique(array_merge($current, $extra)));
                sort($merged);
                $user->forceFill(['permissions' => $merged])->save();
            });
    }

    public function down(): void
    {
        // Keep ACL grants — additive permission change.
    }
};
