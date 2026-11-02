<?php

use App\Models\AppSetting;
use App\Support\Permissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $defaults = [
            ['name' => 'پذیرش مشتری', 'hint' => 'مراجعه حضوری مشتری', 'mark' => 'م', 'sort_order' => 10, 'ask_quantity' => 1],
            ['name' => 'پاسخ تلفن', 'hint' => 'تماس ورودی / خروجی', 'mark' => 'ت', 'sort_order' => 20, 'ask_quantity' => 1],
            ['name' => 'نظافت', 'hint' => 'نظافت دفتر و محیط کار', 'mark' => 'ن', 'sort_order' => 30, 'ask_quantity' => 0],
            ['name' => 'پیگیری قبض', 'hint' => 'پیگیری وضعیت دستگاه مشتریان', 'mark' => 'پ', 'sort_order' => 40, 'ask_quantity' => 1],
            ['name' => 'هماهنگی تعمیر', 'hint' => 'هماهنگی با تعمیرکار / کارگاه', 'mark' => 'ه', 'sort_order' => 50, 'ask_quantity' => 0],
            ['name' => 'انبار / خرید', 'hint' => 'قطعه، موجودی، خرید', 'mark' => 'ا', 'sort_order' => 60, 'ask_quantity' => 0],
            ['name' => 'سایر', 'hint' => 'رویداد متفرقه روزانه', 'mark' => 'س', 'sort_order' => 90, 'ask_quantity' => 0],
        ];

        $now = now();
        foreach ($defaults as $row) {
            $exists = DB::table('daily_log_categories')->where('name', $row['name'])->exists();
            if ($exists) {
                continue;
            }
            DB::table('daily_log_categories')->insert([
                'name' => $row['name'],
                'hint' => $row['hint'],
                'mark' => $row['mark'],
                'sort_order' => $row['sort_order'],
                'ask_quantity' => $row['ask_quantity'],
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        AppSetting::setValue('daily_log_allow_past_days', '7');
        AppSetting::setValue('daily_log_require_note', '0');
        AppSetting::setValue('daily_log_show_quantity', '1');

        $newKeys = ['daily_logs', 'daily_logs.manage'];
        $users = DB::table('users')->select('id', 'role', 'permissions')->get();
        foreach ($users as $user) {
            if ($user->role === 'admin') {
                continue;
            }

            $defaultsRole = Permissions::defaultsForRole((string) $user->role);
            $needed = array_values(array_intersect($defaultsRole, $newKeys));
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
        // keep data
    }
};
