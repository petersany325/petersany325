<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Link orphan technician «محمد رضانژاد» to primary admin so کارتابل ارجاع works,
 * and ensure that admin account is named/role-aligned.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('technicians')) {
            return;
        }

        $tech = DB::table('technicians')
            ->where(function ($q) {
                $q->where('name', 'محمد رضانژاد')
                    ->orWhere('name', 'like', '%محمد%رضانژاد%');
            })
            ->orderBy('id')
            ->first();

        if (! $tech) {
            $tech = DB::table('technicians')
                ->where('name', 'like', '%رضانژاد%')
                ->whereNull('user_id')
                ->orderByDesc('id')
                ->first();
        }

        if (! $tech) {
            return;
        }

        $admin = DB::table('users')->where('role', 'admin')->where('is_active', 1)->orderBy('id')->first();
        if (! $admin) {
            return;
        }

        // Detach any other technician currently linked to this admin (keep one identity).
        DB::table('technicians')
            ->where('user_id', $admin->id)
            ->where('id', '!=', $tech->id)
            ->update(['user_id' => null]);

        // Link محمد رضانژاد technician → admin user.
        DB::table('technicians')->where('id', $tech->id)->update([
            'user_id' => $admin->id,
            'is_active' => 1,
            'name' => 'محمد رضانژاد',
        ]);

        // Admin display name = محمد رضانژاد (same person in cartable).
        DB::table('users')->where('id', $admin->id)->update([
            'name' => 'محمد رضانژاد',
            'role' => 'admin',
            'is_active' => 1,
        ]);

        // Open tickets already under this technician stay; ensure technician_id matches custody when with him.
        if (Schema::hasTable('receptions')) {
            DB::table('receptions')
                ->where('custody_technician_id', $tech->id)
                ->whereNotIn('status', ['delivered', 'cancelled'])
                ->where(function ($q) use ($tech) {
                    $q->whereNull('technician_id')->orWhere('technician_id', '!=', $tech->id);
                })
                ->update(['technician_id' => $tech->id]);
        }
    }

    public function down(): void
    {
        // irreversible identity merge — no-op
    }
};
