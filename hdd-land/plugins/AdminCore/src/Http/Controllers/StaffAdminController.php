<?php

namespace Plugins\AdminCore\src\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Plugins\AdminCore\src\Support\StaffActivity;
use Plugins\StaffHR\Plugin as StaffPlugin;
use Plugins\StaffHR\src\Support\StaffAcl;
use Plugins\StaffHR\src\Support\StaffReports;

/**
 * مدیریت کارمندان داخل AdminCore — روی هاست همیشه با AdminCore آپلود می‌شود.
 */
class StaffAdminController extends Controller
{
    public function index(Request $request): View
    {
        $this->bootStaff();
        $staff = Schema::hasTable('staff_members')
            ? DB::table('staff_members')->orderByDesc('id')->limit(200)->get()
            : collect();
        $days = max(1, min(90, (int) $request->input('days', 30)));
        $leaderboard = StaffReports::staffLeaderboard($days);
        $today = StaffReports::summarize(null, now()->startOfDay(), now()->endOfDay());
        $range = StaffReports::summarize(null, now()->subDays($days)->startOfDay());
        $activityCounts = StaffActivity::actionCounts(null, $days);

        return view('admin-core::staff.index', [
            's' => StaffPlugin::settings(),
            'staff' => $staff,
            'roles' => $this->parseRoles(StaffPlugin::settings()),
            'permissionLabels' => StaffAcl::permissionLabels(),
            'rolePresets' => StaffAcl::rolePresets(),
            'leaderboard' => $leaderboard,
            'today' => $today,
            'range' => $range,
            'days' => $days,
            'activityCounts' => $activityCounts,
            'loginUrl' => StaffPlugin::loginUrl(),
        ]);
    }

    public function create(): View
    {
        $this->bootStaff();

        return view('admin-core::staff.form', [
            'member' => null,
            's' => StaffPlugin::settings(),
            'roles' => $this->parseRoles(StaffPlugin::settings()),
            'permissionLabels' => StaffAcl::permissionLabels(),
            'rolePresets' => StaffAcl::rolePresets(),
            'loginUrl' => StaffPlugin::loginUrl(),
        ]);
    }

    public function edit(int $id): View
    {
        $this->bootStaff();
        $member = DB::table('staff_members')->where('id', $id)->first();
        if (! $member) {
            abort(404);
        }

        return view('admin-core::staff.form', [
            'member' => $member,
            's' => StaffPlugin::settings(),
            'roles' => $this->parseRoles(StaffPlugin::settings()),
            'permissionLabels' => StaffAcl::permissionLabels(),
            'rolePresets' => StaffAcl::rolePresets(),
            'loginUrl' => StaffPlugin::loginUrl(),
        ]);
    }

    public function saveSettings(Request $request): RedirectResponse
    {
        $this->bootStaff();
        StaffPlugin::saveSettings($request->all());
        StaffActivity::log('staff_settings_save', auth()->id());

        return back()->with('success', 'تنظیمات کارمندان ذخیره شد.');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->bootStaff();
        $s = StaffPlugin::settings();
        $data = $this->validated($request, true);
        $data['role'] = $data['role'] ?: 'custom';
        $user = $this->upsertUser($data, null);
        $perms = $this->resolvePermissions($request, (string) $data['role']);

        $id = DB::table('staff_members')->insertGetId([
            'user_id' => $user->id,
            'name' => $data['name'],
            'email' => $data['email'] ?? $user->email,
            'phone' => $user->phone ?: ($data['phone'] ?? null),
            'role' => $data['role'],
            'department' => $data['department'] ?? null,
            'base_salary' => (int) ($data['base_salary'] ?? 0),
            'commission_rate' => (float) ($data['commission_rate'] ?? $s['default_commission_rate'] ?? 0),
            'permissions' => json_encode($perms, JSON_UNESCAPED_UNICODE),
            'can_see_profit' => ! empty($data['can_see_profit']),
            'hired_at' => $data['hired_at'] ?? now()->toDateString(),
            'notes' => $data['notes'] ?? null,
            'is_active' => ! empty($data['is_active']),
            'referral_code' => \Plugins\AdminCore\src\Support\StaffReferral::generateUniqueCode($data['name']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        StaffActivity::log('staff_create', auth()->id(), [
            'staff_id' => $id,
            'name' => $data['name'],
            'role' => $data['role'],
        ]);

        return redirect()->to(url('/admin/staff'))
            ->with('success', 'کارمند ساخته شد. لینک امن ورود: '.StaffPlugin::loginUrl());
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $this->bootStaff();
        $member = DB::table('staff_members')->where('id', $id)->first();
        if (! $member) {
            return back()->with('error', 'کارمند یافت نشد.');
        }
        $data = $this->validated($request, false);
        $data['role'] = $data['role'] ?: 'custom';
        $user = $this->upsertUser($data, $member->user_id ? (int) $member->user_id : null);
        $perms = $this->resolvePermissions($request, (string) $data['role']);

        DB::table('staff_members')->where('id', $id)->update([
            'user_id' => $user->id,
            'name' => $data['name'],
            'email' => $data['email'] ?? $user->email,
            'phone' => $user->phone ?: ($data['phone'] ?? null),
            'role' => $data['role'],
            'department' => $data['department'] ?? null,
            'base_salary' => (int) ($data['base_salary'] ?? 0),
            'commission_rate' => (float) ($data['commission_rate'] ?? 0),
            'permissions' => json_encode($perms, JSON_UNESCAPED_UNICODE),
            'can_see_profit' => ! empty($data['can_see_profit']),
            'notes' => $data['notes'] ?? null,
            'is_active' => ! empty($data['is_active']),
            'updated_at' => now(),
        ]);

        // اگر کد معرف خالی است، بساز
        if (Schema::hasColumn('staff_members', 'referral_code')) {
            $code = DB::table('staff_members')->where('id', $id)->value('referral_code');
            if (! $code) {
                DB::table('staff_members')->where('id', $id)->update([
                    'referral_code' => \Plugins\AdminCore\src\Support\StaffReferral::generateUniqueCode($data['name']),
                    'updated_at' => now(),
                ]);
            }
        }

        StaffActivity::log('staff_update', auth()->id(), ['staff_id' => $id, 'name' => $data['name']]);

        return redirect()->to(url('/admin/staff'))->with('success', 'کارمند به‌روزرسانی شد.');
    }

    public function regenerateCode(int $id): RedirectResponse
    {
        $this->bootStaff();
        $member = DB::table('staff_members')->where('id', $id)->first();
        if (! $member) {
            return back()->with('error', 'کارمند یافت نشد.');
        }
        $code = \Plugins\AdminCore\src\Support\StaffReferral::generateUniqueCode($member->name ?? 'STF');
        DB::table('staff_members')->where('id', $id)->update([
            'referral_code' => $code,
            'updated_at' => now(),
        ]);
        StaffActivity::log('referral_code_regen', auth()->id(), ['staff_id' => $id, 'code' => $code]);

        return back()->with('success', 'کد معرف جدید: '.$code);
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->bootStaff();
        $member = DB::table('staff_members')->where('id', $id)->first();
        if ($member) {
            DB::table('staff_members')->where('id', $id)->delete();
            if ($member->user_id) {
                $u = User::query()->find($member->user_id);
                if ($u && ! $u->isAdmin() && $u->role === 'staff') {
                    $u->role = 'customer';
                    $u->save();
                }
            }
            StaffActivity::log('staff_delete', auth()->id(), ['staff_id' => $id, 'name' => $member->name]);
        }

        return back()->with('success', 'کارمند حذف شد.');
    }

    public function reports(Request $request): View
    {
        $this->bootStaff();
        $days = max(1, min(365, (int) $request->input('days', 30)));
        $staffId = (int) $request->input('staff_id', 0);
        $userId = null;
        if ($staffId) {
            $userId = (int) (DB::table('staff_members')->where('id', $staffId)->value('user_id') ?: 0) ?: null;
        }
        $from = now()->subDays($days)->startOfDay();

        return view('admin-core::staff.reports', [
            'days' => $days,
            'staffId' => $staffId,
            'staffList' => Schema::hasTable('staff_members') ? DB::table('staff_members')->orderBy('name')->get() : collect(),
            'summary' => StaffReports::summarize($userId, $from),
            'byDay' => StaffReports::byDay($userId, $days),
            'leaderboard' => StaffReports::staffLeaderboard($days),
            'activity' => StaffActivity::recent($userId, 150, $days),
            'activityCounts' => StaffActivity::actionCounts($userId, $days),
            'loginUrl' => StaffPlugin::loginUrl(),
            'chart' => StaffReports::monthlyGrowthChart(max(3, min(12, (int) ceil($days / 30)))),
            'chartMonths' => max(3, min(12, (int) ceil($days / 30) ?: 6)),
        ]);
    }

    public function toggle(int $id): RedirectResponse
    {
        $this->bootStaff();
        $member = DB::table('staff_members')->where('id', $id)->first();
        if (! $member) {
            return back()->with('error', 'یافت نشد.');
        }
        $next = empty($member->is_active) ? 1 : 0;
        DB::table('staff_members')->where('id', $id)->update([
            'is_active' => $next,
            'updated_at' => now(),
        ]);
        StaffActivity::log($next ? 'staff_activate' : 'staff_deactivate', auth()->id(), ['staff_id' => $id]);

        return back()->with('success', $next ? 'کارمند فعال شد.' : 'کارمند غیرفعال شد.');
    }

    public function activity(Request $request): View
    {
        $this->bootStaff();
        $days = max(1, min(90, (int) $request->input('days', 14)));
        $staffId = (int) $request->input('staff_id', 0);
        $userId = null;
        if ($staffId) {
            $userId = (int) (DB::table('staff_members')->where('id', $staffId)->value('user_id') ?: 0) ?: null;
        }

        return view('admin-core::staff.activity', [
            'days' => $days,
            'staffId' => $staffId,
            'staffList' => Schema::hasTable('staff_members') ? DB::table('staff_members')->orderBy('name')->get() : collect(),
            'logs' => StaffActivity::recent($userId, 300, $days),
            'counts' => StaffActivity::actionCounts($userId, $days),
            'names' => $this->staffNames(),
        ]);
    }

    protected function bootStaff(): void
    {
        $pluginFile = base_path('plugins/StaffHR/Plugin.php');
        if (is_file($pluginFile)) {
            require_once $pluginFile;
        }
        if (class_exists(StaffPlugin::class)) {
            StaffPlugin::loadClasses();
            StaffPlugin::ensureSchema();
            StaffPlugin::ensureCommerceColumns();
        }
        StaffActivity::ensureSchema();
        \Plugins\AdminCore\src\Support\StaffReferral::ensureSchema();
    }

    /** @return array<int,string> */
    protected function staffNames(): array
    {
        if (! Schema::hasTable('staff_members')) {
            return [];
        }
        $out = [];
        foreach (DB::table('staff_members')->get(['user_id', 'name']) as $s) {
            if ($s->user_id) {
                $out[(int) $s->user_id] = $s->name;
            }
        }

        return $out;
    }

    /** @return array<string,mixed> */
    protected function validated(Request $request, bool $creating): array
    {
        $s = StaffPlugin::settings();

        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => [$creating ? 'required' : 'nullable', 'email', 'max:190'],
            'phone' => ['required', 'string', 'max:30'],
            'password' => [$creating ? 'required' : 'nullable', 'string', 'min:6', 'max:100'],
            'role' => ['nullable', 'string', 'max:40'],
            'department' => ['nullable', 'string', 'max:80'],
            'base_salary' => ['nullable', 'integer', 'min:0'],
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:50'],
            'hired_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable'],
            'can_see_profit' => ['nullable'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'max:40'],
        ]);
    }

    /** @param  array<string,mixed>  $data */
    protected function upsertUser(array $data, ?int $userId): User
    {
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $phone = trim((string) ($data['phone'] ?? ''));

        $user = $userId ? User::query()->find($userId) : null;
        if (! $user && $email !== '') {
            $user = User::query()->where('email', $email)->first();
        }
        if (! $user && $phone !== '') {
            $user = User::query()->where('phone', $phone)->first();
        }
        if (! $user) {
            $user = new User;
            $user->email = $email !== '' ? $email : ('staff'.Str::lower(Str::random(8)).'@local.staff');
        }
        if ($user->isAdmin()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'email' => 'این کاربر مدیر سیستم است و نمی‌تواند به عنوان کارمند ثبت شود.',
            ]);
        }

        $user->name = $data['name'];
        if ($email !== '') {
            $user->email = $email;
        }
        if ($phone !== '') {
            try {
                $user->phone = \Plugins\AuthCustomers\src\Services\SmsGateway::normalizePhone($phone);
            } catch (\Throwable) {
                $user->phone = $phone;
            }
        }
        $user->role = 'staff';
        $user->is_admin = false;
        if (! empty($data['password'])) {
            $user->password = $data['password'];
        } elseif (! $user->exists) {
            $user->password = Str::random(12);
        }
        $user->save();

        return $user;
    }

    /** @return list<string> */
    protected function resolvePermissions(Request $request, string $role): array
    {
        $fromForm = StaffAcl::normalizePermissions($request->input('permissions', []));
        if ($fromForm) {
            return $fromForm;
        }
        if ($role === '' || $role === 'custom') {
            return [];
        }

        return StaffAcl::permissionsForRole($role);
    }

    /** @param  array<string,mixed>  $s @return array<string,string> */
    protected function parseRoles(array $s): array
    {
        $out = [];
        foreach (preg_split('/\r\n|\r|\n/', (string) ($s['roles'] ?? '')) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || ! str_contains($line, '|')) {
                continue;
            }
            [$k, $lab] = array_map('trim', explode('|', $line, 2));
            if ($k !== '') {
                $out[$k] = $lab !== '' ? $lab : $k;
            }
        }
        foreach (StaffAcl::rolePresets() as $k => $meta) {
            $out[$k] = $out[$k] ?? $meta['label'];
        }

        return $out ?: ['seller' => 'فروشنده'];
    }
}
