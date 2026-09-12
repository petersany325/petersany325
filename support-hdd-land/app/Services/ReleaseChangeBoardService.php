<?php

namespace App\Services;

use Illuminate\Support\Str;
use ZipArchive;

/**
 * Seller admin board: stage selectable changes, mark tested, publish into customer update.
 */
class ReleaseChangeBoardService
{
    public const FILE = 'releases/pending_changes.json';

    public function path(): string
    {
        return storage_path('app/'.self::FILE);
    }

    /** @return array{items:array<int,array>,published:array<int,array>} */
    public function load(): array
    {
        $path = $this->path();
        if (! is_file($path)) {
            $seed = $this->seedDefaults();
            $this->save($seed);

            return $seed;
        }

        $json = json_decode((string) file_get_contents($path), true);
        if (! is_array($json)) {
            return ['items' => [], 'published' => []];
        }

        return [
            'items' => array_values(is_array($json['items'] ?? null) ? $json['items'] : []),
            'published' => array_values(is_array($json['published'] ?? null) ? $json['published'] : []),
        ];
    }

    /** @param  array{items?:array,published?:array}  $data */
    public function save(array $data): void
    {
        $dir = dirname($this->path());
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $payload = [
            'items' => array_values($data['items'] ?? []),
            'published' => array_values($data['published'] ?? []),
            'updated_at' => now()->toIso8601String(),
        ];

        file_put_contents(
            $this->path(),
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
    }

    /**
     * @param  array{title:string,summary?:string,files?:array<int,string>,test_url?:string,test_notes?:string}  $input
     * @return array<string,mixed>
     */
    public function add(array $input): array
    {
        $board = $this->load();
        $item = [
            'id' => (string) Str::uuid(),
            'title' => trim((string) $input['title']),
            'summary' => trim((string) ($input['summary'] ?? '')),
            'files' => $this->normalizeFiles($input['files'] ?? []),
            'test_url' => trim((string) ($input['test_url'] ?? '')),
            'test_notes' => trim((string) ($input['test_notes'] ?? '')),
            'tested' => false,
            'selected' => true,
            'created_at' => now()->toIso8601String(),
            'tested_at' => null,
        ];
        array_unshift($board['items'], $item);
        $this->save($board);

        return $item;
    }

    public function find(string $id): ?array
    {
        foreach ($this->load()['items'] as $item) {
            if ((string) ($item['id'] ?? '') === $id) {
                return $item;
            }
        }

        return null;
    }

    /** @param  array<string,mixed>  $patch */
    public function update(string $id, array $patch): bool
    {
        $board = $this->load();
        $found = false;
        foreach ($board['items'] as $i => $item) {
            if ((string) ($item['id'] ?? '') !== $id) {
                continue;
            }
            $found = true;
            if (array_key_exists('title', $patch)) {
                $item['title'] = trim((string) $patch['title']);
            }
            if (array_key_exists('summary', $patch)) {
                $item['summary'] = trim((string) $patch['summary']);
            }
            if (array_key_exists('files', $patch)) {
                $item['files'] = $this->normalizeFiles($patch['files']);
            }
            if (array_key_exists('test_url', $patch)) {
                $item['test_url'] = trim((string) $patch['test_url']);
            }
            if (array_key_exists('test_notes', $patch)) {
                $item['test_notes'] = trim((string) $patch['test_notes']);
            }
            if (array_key_exists('tested', $patch)) {
                $item['tested'] = (bool) $patch['tested'];
                $item['tested_at'] = $item['tested'] ? now()->toIso8601String() : null;
            }
            if (array_key_exists('selected', $patch)) {
                $item['selected'] = (bool) $patch['selected'];
            }
            $board['items'][$i] = $item;
            break;
        }
        if ($found) {
            $this->save($board);
        }

        return $found;
    }

    public function delete(string $id): bool
    {
        $board = $this->load();
        $before = count($board['items']);
        $board['items'] = array_values(array_filter(
            $board['items'],
            fn ($item) => (string) ($item['id'] ?? '') !== $id
        ));
        if (count($board['items']) === $before) {
            return false;
        }
        $this->save($board);

        return true;
    }

    /** @param  array<int,string>  $ids */
    public function setSelection(array $ids): void
    {
        $want = array_fill_keys(array_map('strval', $ids), true);
        $board = $this->load();
        foreach ($board['items'] as $i => $item) {
            $board['items'][$i]['selected'] = isset($want[(string) ($item['id'] ?? '')]);
        }
        $this->save($board);
    }

    /** @return array<int,array> */
    public function selectedItems(): array
    {
        return array_values(array_filter(
            $this->load()['items'],
            fn ($item) => ! empty($item['selected'])
        ));
    }

    /** @return array<int,string> */
    public function selectedChangelog(): array
    {
        $lines = [];
        foreach ($this->selectedItems() as $item) {
            $title = trim((string) ($item['title'] ?? ''));
            if ($title === '') {
                continue;
            }
            $summary = trim((string) ($item['summary'] ?? ''));
            $lines[] = $summary !== '' ? ($title.' — '.$summary) : $title;
        }

        return $lines;
    }

    /** @return array<int,string> */
    public function selectedFiles(): array
    {
        $files = [];
        foreach ($this->selectedItems() as $item) {
            foreach ($this->normalizeFiles($item['files'] ?? []) as $rel) {
                $files[$rel] = true;
            }
        }

        return array_keys($files);
    }

    /**
     * Build a ZIP containing only selected change files (overlay package for customers).
     *
     * @return array{ok:bool,path?:string,file?:string,sha256?:string,count?:int,missing?:array<int,string>,message?:string}
     */
    public function buildSelectedZip(string $version): array
    {
        $files = $this->selectedFiles();
        if ($files === []) {
            return ['ok' => false, 'message' => 'هیچ فایلی در تغییرات انتخاب‌شده ثبت نشده است.'];
        }

        $root = base_path();
        $missing = [];
        $existing = [];
        foreach ($files as $rel) {
            $abs = $root.'/'.$rel;
            if (is_file($abs)) {
                $existing[] = $rel;
            } else {
                $missing[] = $rel;
            }
        }
        if ($existing === []) {
            return ['ok' => false, 'message' => 'هیچ‌کدام از فایل‌های انتخاب‌شده روی سرور پیدا نشد.', 'missing' => $missing];
        }

        $dir = storage_path('app/releases');
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $safeName = 'hddland-'.$version.'.zip';
        $out = $dir.'/'.$safeName;
        @unlink($out);

        $zip = new ZipArchive();
        if ($zip->open($out, ZipArchive::CREATE) !== true) {
            return ['ok' => false, 'message' => 'ساخت فایل ZIP ناموفق بود.'];
        }
        foreach ($existing as $rel) {
            $zip->addFile($root.'/'.$rel, $rel);
        }
        // Customer installer requires artisan + app/ at package root (findAppRoot).
        // Selective board zips must still satisfy that check so overlay can run.
        if (is_file($root.'/artisan')) {
            $zip->addFile($root.'/artisan', 'artisan');
        } else {
            $zip->addFromString('artisan', "#!/usr/bin/env php\n<?php\n// board overlay marker\n");
        }
        $hasAppFile = false;
        foreach ($existing as $rel) {
            if (str_starts_with(str_replace('\\', '/', $rel), 'app/')) {
                $hasAppFile = true;
                break;
            }
        }
        if (! $hasAppFile) {
            $zip->addFromString('app/.board_keep', "board\n");
        }
        // Always ship a tiny marker so apply knows source
        $zip->addFromString(
            'RELEASE_BOARD.txt',
            "version={$version}\nfiles=".count($existing)."\nbuilt=".now()->toIso8601String()."\n"
        );
        $zip->close();

        return [
            'ok' => true,
            'path' => $out,
            'file' => $safeName,
            'sha256' => hash_file('sha256', $out) ?: '',
            'count' => count($existing),
            'missing' => $missing,
        ];
    }

    /**
     * Move selected items into published archive after a successful customer release.
     *
     * @param  array<int,string>  $changelog
     */
    public function archiveSelected(string $version, array $changelog = []): void
    {
        $board = $this->load();
        $keep = [];
        $moved = [];
        foreach ($board['items'] as $item) {
            if (! empty($item['selected'])) {
                $item['published_version'] = $version;
                $item['published_at'] = now()->toIso8601String();
                $moved[] = $item;
            } else {
                $keep[] = $item;
            }
        }
        array_unshift($board['published'], [
            'version' => $version,
            'published_at' => now()->toIso8601String(),
            'changelog' => $changelog !== [] ? $changelog : array_map(fn ($i) => (string) ($i['title'] ?? ''), $moved),
            'items' => $moved,
        ]);
        $board['published'] = array_slice($board['published'], 0, 40);
        $board['items'] = $keep;
        $this->save($board);
    }

    /** Suggest next semver patch from current latest. */
    public function suggestNextVersion(?string $latest): string
    {
        $latest = trim((string) $latest);
        if ($latest === '' || ! preg_match('/^(\d+)\.(\d+)(?:\.(\d+))?$/', $latest, $m)) {
            return '1.0.0';
        }
        $major = (int) $m[1];
        $minor = (int) $m[2];
        $patch = isset($m[3]) ? ((int) $m[3] + 1) : 1;

        return $major.'.'.$minor.'.'.$patch;
    }

    /** @param  mixed  $files
     *  @return array<int,string>
     */
    private function normalizeFiles(mixed $files): array
    {
        if (is_string($files)) {
            $files = preg_split('/\r\n|\r|\n|,/', $files) ?: [];
        }
        if (! is_array($files)) {
            return [];
        }
        $out = [];
        foreach ($files as $f) {
            $rel = str_replace('\\', '/', trim((string) $f));
            $rel = ltrim($rel, '/');
            if ($rel === '' || str_contains($rel, '..')) {
                continue;
            }
            // Only allow app package paths
            if (! preg_match('#^(app|bootstrap|config|database|public|resources|routes|tools)/#', $rel)
                && ! in_array($rel, ['artisan', 'composer.json'], true)) {
                continue;
            }
            $out[$rel] = true;
        }

        return array_keys($out);
    }

    /** @return array{items:array<int,array>,published:array} */
    private function seedDefaults(): array
    {
        $now = now()->toIso8601String();

        return [
            'items' => [
                [
                    'id' => (string) Str::uuid(),
                    'title' => 'شماره قبض روی ردیف پذیرش گروهی',
                    'summary' => 'نمایش T-20N… جلوی هر کارت قبض گروهی برای یادداشت کارمند',
                    'files' => [
                        'resources/views/receptions/create.blade.php',
                        'public/js/app.js',
                        'public/css/app.css',
                        'resources/views/layouts/app.blade.php',
                    ],
                    'test_url' => '/receptions/create',
                    'test_notes' => 'پذیرش گروهی را باز کنید؛ روی هر ردیف شماره قبض دیده شود.',
                    'tested' => false,
                    'selected' => true,
                    'created_at' => $now,
                    'tested_at' => null,
                ],
                [
                    'id' => (string) Str::uuid(),
                    'title' => 'منوی تخصص، سود و حقوق تعمیرکار',
                    'summary' => 'صفحه /employees/pay برای درصد سود و حقوق ماهانه',
                    'files' => [
                        'app/Http/Controllers/EmployeeController.php',
                        'app/Http/Controllers/TechnicianController.php',
                        'app/Support/NavMenu.php',
                        'routes/web.php',
                        'resources/views/employees/pay.blade.php',
                        'resources/views/employees/index.blade.php',
                        'resources/views/employees/_form.blade.php',
                        'app/Models/Technician.php',
                        'database/migrations/2026_09_12_060000_add_monthly_salary_to_technicians_table.php',
                        'public/css/app.css',
                        'resources/views/layouts/app.blade.php',
                    ],
                    'test_url' => '/employees/pay',
                    'test_notes' => 'منوی کارمندان ← تخصص، سود و حقوق؛ ذخیره درصد و حقوق.',
                    'tested' => false,
                    'selected' => true,
                    'created_at' => $now,
                    'tested_at' => null,
                ],
                [
                    'id' => (string) Str::uuid(),
                    'title' => 'معافیت CSRF برای API آپدیت مشتری',
                    'summary' => 'license/updates/latest و download بدون نشست مرورگر کار کنند',
                    'files' => [
                        'bootstrap/app.php',
                    ],
                    'test_url' => '/licenses/releases',
                    'test_notes' => 'پس از انتشار، مشتری باید نسخه جدید را در ابزار آپدیت ببیند.',
                    'tested' => false,
                    'selected' => true,
                    'created_at' => $now,
                    'tested_at' => null,
                ],
            ],
            'published' => [],
        ];
    }
}
