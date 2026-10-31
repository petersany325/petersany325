<?php

use App\Support\Permissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $users = DB::table('users')->select('id', 'role', 'permissions')->get();
        foreach ($users as $user) {
            if ($user->role === 'admin') {
                continue;
            }

            $defaults = Permissions::defaultsForRole((string) $user->role);
            $needed = array_values(array_intersect($defaults, ['handoffs', 'notifications']));
            if ($needed === []) {
                continue;
            }

            $perms = json_decode((string) $user->permissions, true);
            if (! is_array($perms) || $perms === []) {
                // empty permissions already fall back to role defaults
                continue;
            }

            $merged = array_values(array_unique(array_merge($perms, $needed)));
            if ($merged === $perms) {
                continue;
            }

            DB::table('users')->where('id', $user->id)->update([
                'permissions' => json_encode($merged, JSON_UNESCAPED_UNICODE),
            ]);
        }
    }

    public function down(): void
    {
        // no-op: do not strip permissions once granted
    }
};
