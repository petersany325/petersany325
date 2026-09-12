<?php

namespace App\Http\Controllers;

use App\Services\AppUpdateService;
use App\Services\ReleaseChangeBoardService;
use Illuminate\Http\Request;

/**
 * Seller-only UI: stage/test selectable changes and publish ZIP + manifest for customers.
 */
class AppReleaseAdminController extends Controller
{
    public function __construct(
        private AppUpdateService $updates,
        private ReleaseChangeBoardService $board,
    ) {
    }

    public function index()
    {
        $isSeller = trim((string) config('license.key')) === '';
        if (! $isSeller) {
            return view('licenses.releases', [
                'manifest' => ['channel' => 'stable', 'latest' => null, 'releases' => []],
                'isSeller' => false,
                'currentApp' => $this->updates->installedVersion(),
                'boardItems' => [],
                'boardPublished' => [],
                'selectedCount' => 0,
                'untestedSelected' => 0,
                'suggestedVersion' => '1.0.0',
                'selectedChangelog' => '',
                'selectedFiles' => [],
            ]);
        }

        $manifestPath = storage_path('app/releases/manifest.json');
        $manifest = is_file($manifestPath)
            ? json_decode((string) file_get_contents($manifestPath), true)
            : ['channel' => 'stable', 'latest' => null, 'releases' => []];
        if (! is_array($manifest)) {
            $manifest = ['channel' => 'stable', 'latest' => null, 'releases' => []];
        }

        $board = $this->board->load();
        $selected = $this->board->selectedItems();
        $untestedSelected = collect($selected)->filter(fn ($i) => empty($i['tested']))->count();

        return view('licenses.releases', [
            'manifest' => $manifest,
            'isSeller' => true,
            'currentApp' => $this->updates->installedVersion(),
            'boardItems' => $board['items'],
            'boardPublished' => $board['published'],
            'selectedCount' => count($selected),
            'untestedSelected' => $untestedSelected,
            'suggestedVersion' => $this->board->suggestNextVersion($manifest['latest'] ?? null),
            'selectedChangelog' => implode("\n", $this->board->selectedChangelog()),
            'selectedFiles' => $this->board->selectedFiles(),
        ]);
    }

    public function storeChange(Request $request)
    {
        $this->assertSeller();

        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'summary' => ['nullable', 'string', 'max:500'],
            'files' => ['nullable', 'string', 'max:8000'],
            'test_url' => ['nullable', 'string', 'max:190'],
            'test_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $this->board->add($data);

        return redirect()
            ->route('licenses.releases', ['tab' => 'board'])
            ->with('success', 'تغییر به تابلو اضافه شد. بعد از تست انتخاب کنید و به انتشار مشتری بیفزایید.');
    }

    public function toggleTested(Request $request, string $change)
    {
        $this->assertSeller();
        $tested = $request->boolean('tested', true);
        if (! $this->board->update($change, ['tested' => $tested])) {
            return back()->with('error', 'آیتم پیدا نشد.');
        }

        return back()->with('success', $tested ? 'به‌عنوان تست‌شده علامت خورد.' : 'علامت تست برداشته شد.');
    }

    public function toggleSelected(Request $request, string $change)
    {
        $this->assertSeller();
        $selected = $request->boolean('selected', true);
        if (! $this->board->update($change, ['selected' => $selected])) {
            return back()->with('error', 'آیتم پیدا نشد.');
        }

        return back()->with('success', $selected ? 'به انتشار بعدی اضافه شد.' : 'از انتشار بعدی حذف شد.');
    }

    public function saveSelection(Request $request)
    {
        $this->assertSeller();
        $ids = $request->input('selected', []);
        if (! is_array($ids)) {
            $ids = [];
        }
        $this->board->setSelection(array_map('strval', $ids));

        return redirect()
            ->route('licenses.releases', ['tab' => 'publish'])
            ->with('success', 'انتخاب‌ها ذخیره شد. در مرحله انتشار، تغییرات انتخابی به آپدیت مشتری اضافه می‌شوند.');
    }

    public function markSelectedTested()
    {
        $this->assertSeller();
        $board = $this->board->load();
        $n = 0;
        foreach ($board['items'] as $i => $item) {
            if (! empty($item['selected']) && empty($item['tested'])) {
                $board['items'][$i]['tested'] = true;
                $board['items'][$i]['tested_at'] = now()->toIso8601String();
                $n++;
            }
        }
        $this->board->save($board);

        return redirect()
            ->route('licenses.releases', ['tab' => 'publish'])
            ->with('success', $n > 0 ? ($n.' مورد انتخابی به‌عنوان تست‌شده علامت خورد.') : 'مورد تست‌نشده‌ای در انتخاب‌ها نبود.');
    }

    public function destroyChange(string $change)
    {
        $this->assertSeller();
        if (! $this->board->delete($change)) {
            return back()->with('error', 'آیتم پیدا نشد.');
        }

        return back()->with('success', 'آیتم از تابلو حذف شد.');
    }

    public function store(Request $request)
    {
        $this->assertSeller();

        $data = $request->validate([
            'version' => ['required', 'string', 'max:30', 'regex:/^\d+\.\d+(\.\d+)?$/'],
            'changelog' => ['nullable', 'string', 'max:4000'],
            'set_latest' => ['nullable'],
            'source' => ['nullable', 'in:board,upload'],
            'require_tested' => ['nullable'],
            'zip' => ['nullable', 'file', 'mimes:zip', 'max:512000'],
        ], [
            'version.regex' => 'نسخه باید شبیه 1.2.0 باشد.',
        ]);

        $source = (string) ($data['source'] ?? 'board');
        $selected = $this->board->selectedItems();

        if ($source === 'board') {
            if ($selected === []) {
                return back()->withInput()->with('error', 'حداقل یک تغییر را از تابلو انتخاب کنید.');
            }
            if ($request->boolean('require_tested')) {
                $untested = collect($selected)->filter(fn ($i) => empty($i['tested']))->pluck('title')->all();
                if ($untested !== []) {
                    return back()->withInput()->with(
                        'error',
                        'این موارد هنوز تست نشده‌اند: '.implode('، ', $untested).' — یا از تابلو «علامت تست شد» بزنید، یا تیک «فقط موارد تست‌شده» را بردارید.'
                    );
                }
            }
        }

        $changelogText = trim((string) ($data['changelog'] ?? ''));
        if ($changelogText === '' && $selected !== []) {
            $changelogText = implode("\n", $this->board->selectedChangelog());
        }
        $lines = preg_split('/\r\n|\r|\n/', $changelogText) ?: [];
        $changelog = array_values(array_filter(array_map('trim', $lines), fn ($l) => $l !== ''));

        $dir = storage_path('app/releases');
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $version = $data['version'];
        $safeName = 'hddland-'.$version.'.zip';
        $path = $dir.'/'.$safeName;
        $sha = '';

        if ($source === 'board') {
            $built = $this->board->buildSelectedZip($version);
            if (! ($built['ok'] ?? false)) {
                return back()->withInput()->with('error', $built['message'] ?? 'ساخت ZIP از تغییرات انتخابی ناموفق بود.');
            }
            $safeName = (string) $built['file'];
            $path = (string) $built['path'];
            $sha = (string) $built['sha256'];
            if (! empty($built['missing'])) {
                session()->flash('warning', 'برخی فایل‌ها روی دیسک نبودند و از ZIP حذف شدند: '.implode('، ', $built['missing']));
            }
        } else {
            if (! $request->hasFile('zip')) {
                return back()->withInput()->with('error', 'برای انتشار دستی، فایل ZIP الزامی است.');
            }
            $request->file('zip')->move($dir, $safeName);
            $path = $dir.'/'.$safeName;
            $sha = hash_file('sha256', $path) ?: '';
        }

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
            'source' => $source,
            'change_ids' => array_values(array_map(fn ($i) => (string) ($i['id'] ?? ''), $selected)),
        ]);

        $setLatest = $request->boolean('set_latest', true);
        $manifest = [
            'channel' => (string) ($manifest['channel'] ?? 'stable'),
            'latest' => $setLatest ? $version : (string) ($manifest['latest'] ?? $version),
            'product' => (string) config('updates.product', 'hddland-repair'),
            'releases' => $releases,
            'updated_at' => now()->toIso8601String(),
        ];

        file_put_contents($manifestPath, json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        if ($source === 'board' && $selected !== []) {
            $this->board->archiveSelected($version, $changelog);
        }

        return redirect()
            ->route('licenses.releases', ['tab' => 'history'])
            ->with('success', 'آپدیت نسخه '.$version.' برای مشتریان منتشر شد. در پنل مشتری از «آپدیت نرم‌افزار» دیده می‌شود.');
    }

    private function assertSeller(): void
    {
        if (trim((string) config('license.key')) !== '') {
            abort(403, 'انتشار آپدیت فقط روی سرور فروشنده مجاز است.');
        }
    }
}
