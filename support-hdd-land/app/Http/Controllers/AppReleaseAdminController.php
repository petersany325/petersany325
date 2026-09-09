<?php

namespace App\Http\Controllers;

use App\Services\AppUpdateService;
use Illuminate\Http\Request;

/**
 * Seller-only UI: publish a ZIP + manifest entry for customer live updates.
 */
class AppReleaseAdminController extends Controller
{
    public function __construct(private AppUpdateService $updates)
    {
    }

    public function index()
    {
        $manifestPath = storage_path('app/releases/manifest.json');
        $manifest = is_file($manifestPath)
            ? json_decode((string) file_get_contents($manifestPath), true)
            : ['channel' => 'stable', 'latest' => null, 'releases' => []];

        return view('licenses.releases', [
            'manifest' => is_array($manifest) ? $manifest : ['channel' => 'stable', 'latest' => null, 'releases' => []],
            'isSeller' => trim((string) config('license.key')) === '',
            'currentApp' => $this->updates->installedVersion(),
        ]);
    }

    public function store(Request $request)
    {
        if (trim((string) config('license.key')) !== '') {
            return back()->with('error', 'انتشار آپدیت فقط روی سرور فروشنده (بدون LICENSE_KEY) مجاز است.');
        }

        $data = $request->validate([
            'version' => ['required', 'string', 'max:30', 'regex:/^\d+\.\d+(\.\d+)?$/'],
            'changelog' => ['nullable', 'string', 'max:4000'],
            'set_latest' => ['nullable'],
            'zip' => ['required', 'file', 'mimes:zip', 'max:512000'],
        ], [
            'version.regex' => 'نسخه باید شبیه 1.2.0 باشد.',
            'zip.required' => 'فایل ZIP آپدیت الزامی است.',
        ]);

        $dir = storage_path('app/releases');
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $version = $data['version'];
        $safeName = 'hddland-'.$version.'.zip';
        $request->file('zip')->move($dir, $safeName);
        $path = $dir.'/'.$safeName;
        $sha = hash_file('sha256', $path);

        $lines = preg_split('/\r\n|\r|\n/', (string) ($data['changelog'] ?? '')) ?: [];
        $changelog = array_values(array_filter(array_map('trim', $lines), fn ($l) => $l !== ''));

        $manifestPath = $dir.'/manifest.json';
        $manifest = is_file($manifestPath)
            ? json_decode((string) file_get_contents($manifestPath), true)
            : [];
        if (! is_array($manifest)) {
            $manifest = [];
        }

        $releases = is_array($manifest['releases'] ?? null) ? $manifest['releases'] : [];
        $releases = array_values(array_filter($releases, fn ($r) => (string) ($r['version'] ?? '') !== $version));
        array_unshift($releases, [
            'version' => $version,
            'released_at' => now()->toDateString(),
            'min_php' => '8.2',
            'changelog' => $changelog,
            'file' => $safeName,
            'sha256' => $sha,
        ]);

        $setLatest = $request->boolean('set_latest', true);
        $manifest = [
            'channel' => (string) ($manifest['channel'] ?? 'stable'),
            'latest' => $setLatest ? $version : (string) ($manifest['latest'] ?? $version),
            'releases' => $releases,
            'updated_at' => now()->toIso8601String(),
        ];

        file_put_contents($manifestPath, json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        return back()->with('success', 'آپدیت نسخه '.$version.' منتشر شد. مشتریان با لایسنس معتبر می‌توانند آن را ببینند و نصب کنند.');
    }
}
