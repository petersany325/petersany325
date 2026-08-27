<?php

use App\Support\Permissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $newKeys = [
            'reports.operations',
            'reports.custody',
            'reports.payments',
            'reports.sms',
            'reports.messages',
        ];

        $users = DB::table('users')->select('id', 'role', 'permissions')->get();
        foreach ($users as $user) {
            if ($user->role === 'admin') {
                continue;
            }

            $defaults = Permissions::defaultsForRole((string) $user->role);
            $needed = array_values(array_intersect($defaults, $newKeys));
            if ($needed === []) {
                continue;
            }

            $perms = json_decode((string) $user->permissions, true);
            if (! is_array($perms) || $perms === []) {
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
        // no-op
    }
};
