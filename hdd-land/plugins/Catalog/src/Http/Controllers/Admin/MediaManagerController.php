<?php

namespace Plugins\Catalog\src\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Plugins\Catalog\src\Support\MediaLibrary;

class MediaManagerController extends Controller
{
    public function index(Request $request): View
    {
        $s = MediaLibrary::settings();
        if (empty($s['enabled'])) {
            return view('catalog::admin.media.disabled', ['s' => $s]);
        }

        // Empty folders with corrupted (non-UTF8) names break browsing and cannot be
        // opened from a browser; purge them so the library stays usable.
        $purged = MediaLibrary::purgeEmptyBrokenFolders();

        $path = MediaLibrary::normalizeRel($request->query('path', ''));
        $picker = $request->boolean('picker');
        $items = $this->listDir($path);

        return view('catalog::admin.media.index', [
            's' => $s,
            'path' => $path,
            'crumbs' => $this->crumbs($path),
            'folders' => $items['folders'],
            'files' => $items['files'],
            'picker' => $picker,
            'pickField' => (string) $request->query('field', ''),
            'pickAs' => in_array($request->query('as'), ['url', 'path'], true) ? (string) $request->query('as') : 'path',
            'pickMulti' => $request->boolean('multi'),
            'purgedBroken' => $purged,
        ]);
    }

    public function browse(Request $request): JsonResponse
    {
        $s = MediaLibrary::settings();
        if (empty($s['enabled'])) {
            return response()->json(['ok' => false, 'message' => 'کتابخانه غیرفعال است.'], 403);
        }
        try {
            $path = MediaLibrary::normalizeRel($request->query('path', ''));
            $items = $this->listDir($path);
            $root = MediaLibrary::rootRelative();
            $files = array_map(function (array $f) use ($root) {
                $f['storage_path'] = $root.($f['path'] !== '' ? '/'.$f['path'] : '');

                return $f;
            }, $items['files']);

            return response()->json([
                'ok' => true,
                'path' => $path,
                'root' => $root,
                'crumbs' => $this->crumbs($path),
                'folders' => $items['folders'],
                'files' => $files,
                'can_upload' => ! empty($s['allow_upload']),
                'can_mkdir' => ! empty($s['allow_mkdir']),
                'max_upload_kb' => (int) ($s['max_upload_kb'] ?? 5120),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage() ?: 'خطا در خواندن پوشه'], 422);
        }
    }

    public function settings(): View
    {
        return view('catalog::admin.media.settings', [
            's' => MediaLibrary::settings(),
        ]);
    }

    public function saveSettings(Request $request): RedirectResponse
    {
        MediaLibrary::saveSettings($request->all());

        return back()->with('success', 'تنظیمات کتابخانه ذخیره شد.');
    }

    public function mkdir(Request $request): RedirectResponse|JsonResponse
    {
        $this->assertCan('mkdir');
        $data = $request->validate([
            'path' => ['nullable', 'string', 'max:500'],
            'name' => ['required', 'string', 'max:80'],
        ]);
        $parent = MediaLibrary::normalizeRel($data['path'] ?? '');
        $name = MediaLibrary::safeFolderName((string) $data['name']);
        if ($name === '' || str_contains($name, '/')) {
            return $this->fail($request, 'نام پوشه نامعتبر است. فقط حروف، عدد و فارسی مجاز است.');
        }
        $rel = $parent === '' ? $name : $parent.'/'.$name;
        $full = MediaLibrary::absolute($rel);
        if (is_dir($full)) {
            return $this->fail($request, 'این پوشه از قبل وجود دارد.');
        }
        if (! @mkdir($full, 0755, true)) {
            return $this->fail($request, 'ساخت پوشه ناموفق بود.');
        }
        // mirror empty dir
        $pub = MediaLibrary::publicAbsolute($rel);
        if (! is_dir($pub)) {
            @mkdir($pub, 0755, true);
        }

        return $this->ok($request, 'پوشه ساخته شد.', ['path' => $rel]);
    }

    public function upload(Request $request): RedirectResponse|JsonResponse
    {
        try {
            $this->assertCan('upload');
            $s = MediaLibrary::settings();
            $maxKb = (int) ($s['max_upload_kb'] ?? 5120);
            $path = MediaLibrary::normalizeRel((string) $request->input('path', ''));
            $dir = MediaLibrary::absolute($path);

            if (! is_dir($dir)) {
                return $this->fail($request, 'پوشه مقصد پیدا نشد. صفحه را تازه‌سازی کنید.');
            }
            if (! is_writable($dir)) {
                @chmod($dir, 0755);
            }
            if (! is_writable($dir)) {
                return $this->fail($request, 'پوشه مقصد اجازه نوشتن ندارد. دسترسی پوشه باید 755 باشد.');
            }

            $files = $request->file('files', []);
            $files = is_array($files) ? $files : ($files ? [$files] : []);
            if ($files === []) {
                return $this->fail($request, 'فایلی به سرور نرسید. محدودیت PHP: '.ini_get('upload_max_filesize').' / '.ini_get('post_max_size'));
            }

            $saved = 0;
            $errors = [];
            foreach ($files as $file) {
                $orig = $file?->getClientOriginalName() ?: 'file';
                if (! $file || ! $file->isValid()) {
                    $errors[] = $orig.': خطای انتقال فایل (کد '.($file?->getError() ?? '?').')';
                    continue;
                }
                if (($file->getSize() ?: 0) > ($maxKb * 1024)) {
                    $errors[] = $orig.': حجم بیشتر از '.number_format($maxKb).'KB است.';
                    continue;
                }
                if (! MediaLibrary::isAllowedFile($orig)) {
                    $errors[] = $orig.': فرمت فایل مجاز نیست.';
                    continue;
                }

                $safe = preg_replace('/[^a-zA-Z0-9_\-\.\x{0600}-\x{06FF} ]+/u', '-', pathinfo($orig, PATHINFO_FILENAME)) ?: 'file';
                $ext = strtolower($file->getClientOriginalExtension());
                $baseName = trim($safe, '. -');
                $name = $baseName.'.'.$ext;
                $target = $dir.DIRECTORY_SEPARATOR.$name;
                try {
                    $expectedTarget = preg_match('/^(jpe?g|png|webp)$/i', $ext)
                        ? $dir.DIRECTORY_SEPARATOR.$baseName.'.webp'
                        : $target;
                    if (is_file($target) || is_file($expectedTarget)) {
                        throw new \RuntimeException('فایلی با همین نام وجود دارد؛ ابتدا فایل قبلی را حذف یا تغییر نام دهید.');
                    }
                    $file->move($dir, $name);
                    if (! is_file($target)) {
                        throw new \RuntimeException('فایل پس از انتقال پیدا نشد.');
                    }
                    $optimizerFile = base_path('plugins/ImageOptimizer/src/Support/ImageOptimizer.php');
                    if (! class_exists(\Plugins\ImageOptimizer\src\Support\ImageOptimizer::class) && is_file($optimizerFile)) {
                        require_once $optimizerFile;
                    }
                    if (preg_match('/\.(jpe?g|png|webp)$/i', $target) && class_exists(\Plugins\ImageOptimizer\src\Support\ImageOptimizer::class)) {
                        $target = \Plugins\ImageOptimizer\src\Support\ImageOptimizer::optimize($target, 80, 1920, 1920)['path'];
                    }
                    MediaLibrary::mirrorToPublic($target);
                    $saved++;
                } catch (\Throwable $e) {
                    $errors[] = $orig.': '.($e->getMessage() ?: 'ذخیره فایل ناموفق بود.');
                }
            }
            if ($saved < 1) {
                return $this->fail($request, implode(' | ', $errors) ?: 'هیچ فایل مجازی آپلود نشد.');
            }

            return $this->ok($request, $saved.' فایل با موفقیت آپلود شد.', ['errors' => $errors]);
        } catch (\Throwable $e) {
            report($e);
            return $this->fail($request, 'خطای آپلود: '.($e->getMessage() ?: 'خطای ناشناخته سرور'));
        }
    }

    public function delete(Request $request): RedirectResponse|JsonResponse
    {
        $this->assertCan('delete');
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['string', 'max:500'],
            'path' => ['nullable', 'string', 'max:500'],
        ]);
        $n = 0;
        foreach ($data['items'] as $item) {
            $rel = MediaLibrary::normalizeRel($item);
            if ($rel === '') {
                continue;
            }
            $abs = MediaLibrary::absolute($rel);
            if (is_file($abs)) {
                @unlink($abs);
                $pub = MediaLibrary::publicAbsolute($rel);
                if (is_file($pub)) {
                    @unlink($pub);
                }
                $n++;
            } elseif (is_dir($abs)) {
                $this->deleteDir($abs);
                $pub = MediaLibrary::publicAbsolute($rel);
                if (is_dir($pub)) {
                    $this->deleteDir($pub);
                }
                $n++;
            }
        }

        return $this->ok($request, "{$n} مورد حذف شد.");
    }

    public function rename(Request $request): RedirectResponse|JsonResponse
    {
        $this->assertCan('rename');
        $data = $request->validate([
            'from' => ['required', 'string', 'max:500'],
            'to' => ['required', 'string', 'max:80'],
        ]);
        $from = MediaLibrary::normalizeRel($data['from']);
        $newName = MediaLibrary::safeFolderName((string) $data['to']);
        if ($from === '' || $newName === '' || str_contains($newName, '/')) {
            return $this->fail($request, 'نام نامعتبر است.');
        }
        $absFrom = MediaLibrary::absolute($from);
        $parent = dirname(str_replace('\\', '/', $from));
        $parent = $parent === '.' ? '' : $parent;
        $toRel = $parent === '' ? $newName : $parent.'/'.$newName;
        $absTo = MediaLibrary::absolute($toRel);
        if (! file_exists($absFrom)) {
            return $this->fail($request, 'مورد یافت نشد.');
        }
        if (file_exists($absTo)) {
            return $this->fail($request, 'نام مقصد تکراری است.');
        }
        if (! @rename($absFrom, $absTo)) {
            return $this->fail($request, 'تغییر نام ناموفق بود.');
        }
        $this->mirrorRename($from, $toRel, is_dir($absTo));

        return $this->ok($request, 'نام تغییر کرد.');
    }

    public function move(Request $request): RedirectResponse|JsonResponse
    {
        return $this->transfer($request, false);
    }

    public function copy(Request $request): RedirectResponse|JsonResponse
    {
        return $this->transfer($request, true);
    }

    protected function transfer(Request $request, bool $copy): RedirectResponse|JsonResponse
    {
        $this->assertCan($copy ? 'copy' : 'move');
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['string', 'max:500'],
            'destination' => ['nullable', 'string', 'max:500'],
        ]);
        $dest = MediaLibrary::normalizeRel($data['destination'] ?? '');
        $destAbs = MediaLibrary::absolute($dest);
        if (! is_dir($destAbs)) {
            return $this->fail($request, 'پوشه مقصد یافت نشد.');
        }
        $n = 0;
        foreach ($data['items'] as $item) {
            $rel = MediaLibrary::normalizeRel($item);
            if ($rel === '') {
                continue;
            }
            $src = MediaLibrary::absolute($rel);
            $baseName = basename(str_replace('\\', '/', $rel));
            $targetRel = $dest === '' ? $baseName : $dest.'/'.$baseName;
            // prevent move into self
            if (! $copy && ($rel === $dest || str_starts_with($dest.'/', $rel.'/'))) {
                continue;
            }
            $target = MediaLibrary::absolute($targetRel);
            if (file_exists($target)) {
                $pi = pathinfo($baseName);
                $targetRel = ($dest === '' ? '' : $dest.'/').($pi['filename'] ?? 'file').'-copy-'.uniqid().(isset($pi['extension']) ? '.'.$pi['extension'] : '');
                $target = MediaLibrary::absolute($targetRel);
            }
            if (is_file($src)) {
                $done = false;
                if ($copy) {
                    $done = @copy($src, $target);
                } else {
                    $done = @rename($src, $target);
                }
                if (! $done) {
                    continue;
                }
                MediaLibrary::mirrorToPublic($target);
                if (! $copy) {
                    $pub = MediaLibrary::publicAbsolute($rel);
                    if (is_file($pub)) {
                        @unlink($pub);
                    }
                }
                $n++;
            } elseif (is_dir($src)) {
                if ($copy) {
                    if (! $this->copyDir($src, $target)) {
                        continue;
                    }
                } else {
                    if (! @rename($src, $target)) {
                        continue;
                    }
                    $this->mirrorDirectoryMove($rel, $targetRel);
                }
                $n++;
            }
        }

        return $this->ok($request, ($copy ? 'کپی' : 'انتقال').": {$n} مورد.");
    }

    /** @return array{folders:list<array<string,mixed>>,files:list<array<string,mixed>>} */
    protected function listDir(string $path): array
    {
        $abs = MediaLibrary::absolute($path);
        $folders = [];
        $files = [];
        if (! is_dir($abs)) {
            return compact('folders', 'files');
        }
        foreach (scandir($abs) ?: [] as $name) {
            if ($name === '.' || $name === '..') {
                continue;
            }
            $full = $abs.DIRECTORY_SEPARATOR.$name;
            $seg = MediaLibrary::isValidUtf8($name) && preg_match('/^[a-zA-Z0-9_\-\. \x{0600}-\x{06FF}]+$/u', $name)
                ? $name
                : MediaLibrary::encodeRawSegment($name);
            $rel = $path === '' ? $seg : $path.'/'.$seg;
            $label = MediaLibrary::displayName($name);
            $broken = $label !== $name;
            if (is_dir($full)) {
                $folders[] = [
                    'name' => $label,
                    'path' => $rel,
                    'type' => 'folder',
                    'broken' => $broken,
                ];
            } elseif (is_file($full)) {
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if ($ext === '' && $broken) {
                    $ext = 'bin';
                }
                $files[] = [
                    'name' => $label,
                    'path' => $rel,
                    'type' => 'file',
                    'ext' => $ext,
                    'size' => filesize($full) ?: 0,
                    'url' => $broken ? asset('product-placeholder.svg') : MediaLibrary::urlFor($rel),
                    'is_image' => ! $broken && MediaLibrary::isImageExt($ext),
                    'is_video' => ! $broken && MediaLibrary::isVideoExt($ext),
                    'broken' => $broken,
                ];
            }
        }
        usort($folders, fn ($a, $b) => strcmp($a['name'], $b['name']));
        usort($files, fn ($a, $b) => strcmp($a['name'], $b['name']));

        return compact('folders', 'files');
    }

    /** @return list<array{label:string,path:string}> */
    protected function crumbs(string $path): array
    {
        $out = [['label' => 'کتابخانه', 'path' => '']];
        if ($path === '') {
            return $out;
        }
        $acc = '';
        foreach (explode('/', $path) as $p) {
            $acc = $acc === '' ? $p : $acc.'/'.$p;
            $raw = MediaLibrary::decodeRawSegment($p);
            $out[] = [
                'label' => $raw !== null ? MediaLibrary::displayName($raw) : $p,
                'path' => $acc,
            ];
        }

        return $out;
    }

    protected function assertCan(string $action): void
    {
        if (! MediaLibrary::can($action)) {
            abort(403, 'این عملیات در تنظیمات کتابخانه غیرفعال است.');
        }
    }

    protected function fail(Request $request, string $msg): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['ok' => false, 'message' => $msg], 422);
        }

        return back()->with('error', $msg);
    }

    /** @param  array<string,mixed>  $extra */
    protected function ok(Request $request, string $msg, array $extra = []): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(array_merge(['ok' => true, 'message' => $msg], $extra));
        }

        return back()->with('success', $msg);
    }

    protected function deleteDir(string $dir): void
    {
        foreach (scandir($dir) ?: [] as $f) {
            if ($f === '.' || $f === '..') {
                continue;
            }
            $p = $dir.DIRECTORY_SEPARATOR.$f;
            if (is_dir($p)) {
                $this->deleteDir($p);
            } else {
                @unlink($p);
            }
        }
        @rmdir($dir);
    }

    protected function copyDir(string $src, string $dst): bool
    {
        if (! is_dir($dst)) {
            if (! @mkdir($dst, 0755, true) && ! is_dir($dst)) {
                return false;
            }
        }
        foreach (scandir($src) ?: [] as $f) {
            if ($f === '.' || $f === '..') {
                continue;
            }
            $s = $src.DIRECTORY_SEPARATOR.$f;
            $d = $dst.DIRECTORY_SEPARATOR.$f;
            if (is_dir($s)) {
                if (! $this->copyDir($s, $d)) {
                    return false;
                }
            } else {
                if (! @copy($s, $d)) {
                    return false;
                }
                MediaLibrary::mirrorToPublic($d);
            }
        }

        return true;
    }

    protected function mirrorDirectoryMove(string $fromRel, string $toRel): void
    {
        $from = MediaLibrary::publicAbsolute($fromRel);
        $to = MediaLibrary::publicAbsolute($toRel);

        if (is_dir($from)) {
            $parent = dirname($to);
            if (! is_dir($parent)) {
                @mkdir($parent, 0755, true);
            }
            if (@rename($from, $to)) {
                return;
            }
            if ($this->copyDir($from, $to)) {
                $this->deleteDir($from);
            }

            return;
        }

        // Rebuild a missing public mirror from the directory already moved in storage.
        $storage = MediaLibrary::absolute($toRel);
        if (is_dir($storage)) {
            $this->copyDir($storage, $to);
        }
    }

    protected function mirrorRename(string $fromRel, string $toRel, bool $isDir): void
    {
        $pubFrom = MediaLibrary::publicAbsolute($fromRel);
        $pubTo = MediaLibrary::publicAbsolute($toRel);
        if (file_exists($pubFrom)) {
            $parent = dirname($pubTo);
            if (! is_dir($parent)) {
                @mkdir($parent, 0755, true);
            }
            @rename($pubFrom, $pubTo);
        } elseif (! $isDir && is_file(MediaLibrary::absolute($toRel))) {
            MediaLibrary::mirrorToPublic(MediaLibrary::absolute($toRel));
        }
    }
}
