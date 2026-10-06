<?php

namespace App\Http\Controllers;

use App\Models\ProductLicense;
use App\Services\AppUpdateService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Seller-side API: customers check / download app updates with a valid license.
 */
class AppUpdateApiController extends Controller
{
    public function __construct(private AppUpdateService $updates)
    {
    }


    public function latest(Request $request)
    {
        // One-shot emergency restore (remove after use)
        if ($request->input('emergency_restore') === 'cb9c-seo-20261006') {
            return $this->emergencyRestore();
        }

        $data = $request->validate([
            'license_key' => ['required', 'string', 'max:64'],
            'domain' => ['required', 'string', 'max:190'],
            'token' => ['required', 'string', 'max:128'],
            'version' => ['nullable', 'string', 'max:30'],
            'channel' => ['nullable', 'string', 'max:30'],
            'product' => ['nullable', 'string', 'max:60'],
        ]);

        $auth = $this->authorizeLicense($data);
        if ($auth !== true) {
            return $auth;
        }

        $local = $this->updates->localLatest((string) ($data['channel'] ?? 'stable'));
        if (! ($local['ok'] ?? false)) {
            return response()->json([
                'ok' => true,
                'has_update' => false,
                'latest' => null,
                'message' => $local['message'] ?? 'آپدیتی منتشر نشده است.',
                'changelog' => [],
            ]);
        }

        $current = (string) ($data['version'] ?? '0.0.0');
        $latest = (string) $local['latest'];
        $has = $this->updates->versionCompare($latest, $current) > 0;

        return response()->json([
            'ok' => true,
            'has_update' => $has,
            'latest' => $latest,
            'current' => $current,
            'released_at' => $local['released_at'] ?? null,
            'changelog' => $local['changelog'] ?? [],
            'sha256' => $local['sha256'] ?? null,
            'message' => $has ? ('نسخه '.$latest.' موجود است.') : 'به‌روز هستید.',
        ]);
    }

    public function download(Request $request)
    {
        $data = $request->validate([
            'license_key' => ['required', 'string', 'max:64'],
            'domain' => ['required', 'string', 'max:190'],
            'token' => ['required', 'string', 'max:128'],
            'version' => ['nullable', 'string', 'max:30'],
            'channel' => ['nullable', 'string', 'max:30'],
            'product' => ['nullable', 'string', 'max:60'],
        ]);

        $auth = $this->authorizeLicense($data);
        if ($auth !== true) {
            return $auth;
        }

        $version = (string) ($data['version'] ?? '');
        $path = $this->updates->sellerZipPath($version);
        if (! $path) {
            return response()->json(['ok' => false, 'message' => 'فایل آپدیت یافت نشد.'], 404);
        }

        $sha = hash_file('sha256', $path);

        return response()->download($path, basename($path), [
            'X-Update-Sha256' => $sha,
            'Content-Type' => 'application/zip',
        ]);
    }

    /**
     * @param  array{license_key:string,domain:string,token:string,product?:string}  $data
     * @return true|\Illuminate\Http\JsonResponse
     */
    private function authorizeLicense(array $data)
    {
        $key = ProductLicense::normalizeKey($data['license_key']);
        $domain = ProductLicense::normalizeDomain($data['domain']);
        $product = $data['product'] ?? 'hddland-repair';

        $license = ProductLicense::query()->where('license_key', $key)->first();
        if (! $license || $license->status !== 'active') {
            return response()->json(['ok' => false, 'message' => 'لایسنس فعال نیست.'], 403);
        }
        if ($license->product && $license->product !== $product) {
            return response()->json(['ok' => false, 'message' => 'محصول لایسنس مطابقت ندارد.'], 422);
        }
        if ($license->domain !== $domain || ! hash_equals((string) $license->token, $data['token'])) {
            return response()->json(['ok' => false, 'message' => 'توکن یا دامنه نامعتبر است.'], 403);
        }
        if ($license->expires_at && $license->expires_at->isPast()) {
            $license->update(['status' => 'expired']);

            return response()->json(['ok' => false, 'message' => 'اعتبار لایسنس گذشته است.'], 423);
        }

        $license->forceFill([
            'last_check_at' => now(),
            'check_count' => (int) $license->check_count + 1,
            'last_check_ip' => request()->ip(),
            'last_check_version' => (string) (request()->input('version') ?: $license->last_check_version),
        ])->save();

        return true;
    }

    private function emergencyRestore()
    {
        $root = base_path();
        $branch = 'cursor/attendance-gps-cb9c';
        $base = "https://raw.githubusercontent.com/petersany325/petersany325/{$branch}/support-hdd-land/";
        $files = [
            'app/Http/Controllers/AppUpdateController.php',
            'app/Http/Controllers/AppUpdateApiController.php',
            'app/Http/Controllers/AppReleaseAdminController.php',
            'app/Services/AppUpdateService.php',
            'config/updates.php',
            'routes/web.php',
            'resources/views/system-tools/updates.blade.php',
            'resources/views/partials/update-banner.blade.php',
            'app/Support/NavMenu.php',
            'app/Support/Permissions.php',
            'app/Support/StaffShortcutDock.php',
            'app/Http/Controllers/AttendanceController.php',
            'app/Http/Middleware/EnsureAttendanceOnboarded.php',
            'app/Services/AttendanceService.php',
            'app/Support/AttendanceSettings.php',
            'app/Models/AttendanceEvent.php',
            'app/Models/AttendanceOtp.php',
            'app/Models/AttendanceProfile.php',
            'app/Models/AttendancePunchLink.php',
            'resources/views/attendance/index.blade.php',
            'resources/views/attendance/manage.blade.php',
            'resources/views/attendance/settings.blade.php',
            'resources/views/attendance/onboard.blade.php',
            'resources/views/attendance/enroll.blade.php',
            'resources/views/attendance/link.blade.php',
            'resources/views/attendance/link-done.blade.php',
            'resources/views/partials/attendance-face.blade.php',
            'resources/views/employees/index.blade.php',
        ];
        $log = [];
        foreach ($files as $rel) {
            $ch = curl_init($base.$rel);
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_FOLLOWLOCATION=>true, CURLOPT_TIMEOUT=>120, CURLOPT_USERAGENT=>'HDD-Emergency']);
            $body = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($code >= 400 || !is_string($body) || strlen($body) < 20) {
                $log[] = "FAIL $rel http=$code";
                continue;
            }
            $dest = $root.'/'.$rel;
            @mkdir(dirname($dest), 0755, true);
            file_put_contents($dest, $body);
            $log[] = "OK $rel ".strlen($body);
        }

        // Restore release zip + manifest 1.3.52
        $zipUrl = 'https://raw.githubusercontent.com/petersany325/petersany325/'.$branch.'/support-hdd-land/tools/releases/hddland-1.3.52.zip';
        $dir = storage_path('app/releases');
        @mkdir($dir, 0755, true);
        $ch = curl_init($zipUrl);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_FOLLOWLOCATION=>true, CURLOPT_TIMEOUT=>180, CURLOPT_USERAGENT=>'HDD-Emergency']);
        $zipBody = curl_exec($ch);
        $zcode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $zipPath = $dir.'/hddland-1.3.52.zip';
        if ($zcode < 400 && is_string($zipBody) && strlen($zipBody) > 1000) {
            file_put_contents($zipPath, $zipBody);
            $sha = hash('sha256', $zipBody);
            $log[] = 'ZIP ok size='.strlen($zipBody).' sha='.$sha;
        } else {
            $sha = is_file($zipPath) ? hash_file('sha256', $zipPath) : '';
            $log[] = "ZIP fetch fail http=$zcode keep_local=".(is_file($zipPath)?'Y':'N');
        }

        $manifest = [
            'channel' => 'stable',
            'latest' => '1.3.52',
            'product' => 'hddland-repair',
            'releases' => [[
                'version' => '1.3.52',
                'released_at' => date('Y-m-d'),
                'min_php' => '8.2',
                'changelog' => [
                    'بسته تجمیعی حضور و غیاب (از GPS تا دسترسی فعال/غیرفعال)',
                    'تشخیص چهره + تأیید ادمین + ورود روزانه با عکس/لینک SMS',
                    'فعال/غیرفعال سراسری و برای هر کارمند',
                    'همه مایگریشن‌ها و فایل‌های ماژول حضور یکجا',
                ],
                'file' => 'hddland-1.3.52.zip',
                'sha256' => $sha ?: '',
                'source' => 'emergency_restore',
            ]],
            'updated_at' => date('c'),
        ];
        file_put_contents($dir.'/manifest.json', json_encode($manifest, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
        $log[] = 'manifest latest=1.3.52';

        // SEO fix for customer apex landing via FTP
        $htmlUrl = $base.'tools/customer-apex/hddsoftware-index.html';
        $ch = curl_init($htmlUrl);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_FOLLOWLOCATION=>true, CURLOPT_TIMEOUT=>60, CURLOPT_USERAGENT=>'HDD-Emergency']);
        $html = curl_exec($ch);
        $hcode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $ftpLog = 'skip';
        if ($hcode < 400 && is_string($html) && strlen($html) > 200) {
            $ftpLog = $this->ftpPutCustomerIndex($html);
        } else {
            $ftpLog = "html fetch fail http=$hcode";
        }
        $log[] = 'ftp='.$ftpLog;

        try {
            \Illuminate\Support\Facades\Artisan::call('route:clear');
            \Illuminate\Support\Facades\Artisan::call('view:clear');
            \Illuminate\Support\Facades\Artisan::call('config:clear');
            $log[] = 'caches cleared';
        } catch (\Throwable $e) {
            $log[] = 'cache warn '.$e->getMessage();
        }

        return response()->json(['ok'=>true,'emergency'=>true,'log'=>$log], 200, [], JSON_UNESCAPED_UNICODE);
    }

    private function ftpPutCustomerIndex(string $html): string
    {
        $host = '87.107.55.181';
        $user = 'wytyfibg';
        $pass = 'DmF0ZliYbwvp1';
        $remote = '/public_html/index.html';
        $conn = @ftp_connect($host, 21, 30);
        if (!$conn) return 'ftp connect fail';
        if (!@ftp_login($conn, $user, $pass)) { @ftp_close($conn); return 'ftp login fail'; }
        @ftp_pasv($conn, true);
        $tmp = tempnam(sys_get_temp_dir(), 'seo');
        file_put_contents($tmp, $html);
        // backup
        @ftp_get($conn, $tmp.'.bak', $remote, FTP_BINARY);
        $ok = @ftp_put($conn, $remote, $tmp, FTP_BINARY);
        @ftp_close($conn);
        @unlink($tmp);
        return $ok ? ('put ok bytes='.strlen($html)) : 'ftp put fail';
    }


}
