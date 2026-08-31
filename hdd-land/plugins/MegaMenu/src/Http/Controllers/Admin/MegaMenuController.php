<?php

namespace Plugins\MegaMenu\src\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Plugins\Catalog\src\Models\Category;
use Plugins\MegaMenu\Plugin as MegaMenuPlugin;
use Plugins\MegaMenu\src\Models\MegaMenuItem;

class MegaMenuController extends Controller
{
    public function index(Request $request): View
    {
        MegaMenuPlugin::ensureSchema();
        MegaMenuPlugin::seedDefaultsIfEmpty();

        $tree = MegaMenuItem::query()
            ->with(['children.children.children', 'category'])
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $flat = MegaMenuItem::query()
            ->orderByRaw('CASE WHEN parent_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->keyBy('id');

        $edit = null;
        if ($request->filled('edit')) {
            $edit = MegaMenuItem::query()->find($request->integer('edit'));
        }

        return view('mega-menu::admin.index', [
            'tree' => $tree,
            'flat' => $flat,
            'edit' => $edit,
            'treeJson' => $this->serializeTree($tree),
            'categories' => class_exists(Category::class) ? Category::treeOptions() : collect(),
            'animations' => MegaMenuPlugin::animations(),
            'effects' => MegaMenuPlugin::effects(),
            'types' => MegaMenuPlugin::types(),
            'fonts' => MegaMenuPlugin::fonts(),
            'iconPresets' => MegaMenuPlugin::iconPresets(),
            'openModes' => MegaMenuPlugin::openModes(),
            'panelAligns' => MegaMenuPlugin::panelAligns(),
            'media' => $this->listMedia(),
            'uploadUrl' => route('admin.mega-menu.upload'),
            'settings' => MegaMenuPlugin::settings(),
        ]);
    }

    public function saveSettings(Request $request): RedirectResponse|JsonResponse
    {
        try {
            MegaMenuPlugin::ensureSchema();
            MegaMenuPlugin::saveSettings($request->all());
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['ok' => true, 'message' => 'تنظیمات اصلی مگامنو ذخیره شد.']);
            }

            return back()->with('success', 'تنظیمات اصلی مگامنو ذخیره شد.');
        } catch (\Throwable $e) {
            report($e);
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['ok' => false, 'message' => 'ذخیره تنظیمات ناموفق: '.$e->getMessage()], 500);
            }

            return back()->with('error', 'ذخیره تنظیمات ناموفق: '.$e->getMessage());
        }
    }

    public function upload(Request $request): JsonResponse
    {
        try {
            $request->validate(['file' => ['required', 'image', 'max:5120']]);
            $file = $request->file('file');
            $name = 'mm-'.uniqid('', true).'.'.strtolower($file->getClientOriginalExtension() ?: 'jpg');
            $dir = public_path('uploads/menu');
            if (! is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            if (! is_dir($dir) || ! is_writable($dir)) {
                return response()->json([
                    'ok' => false,
                    'message' => 'مسیر ذخیره منو قابل نوشتن نیست: uploads/menu',
                ], 500);
            }
            $file->move($dir, $name);
            @chmod($dir.DIRECTORY_SEPARATOR.$name, 0644);
            // اگر docroot روی public_html باشد، uploads ریشه را هم همگام کن
            $alt = base_path('uploads/menu');
            if (! is_dir($alt) && is_dir(base_path('uploads'))) {
                @symlink($dir, $alt);
            } elseif (is_dir(dirname($alt)) && ! is_dir($alt) && ! is_link($alt)) {
                @mkdir($alt, 0755, true);
                @copy($dir.DIRECTORY_SEPARATOR.$name, $alt.DIRECTORY_SEPARATOR.$name);
            }
            $optimizerFile = base_path('plugins/ImageOptimizer/src/Support/ImageOptimizer.php');
            if (! class_exists(\Plugins\ImageOptimizer\src\Support\ImageOptimizer::class) && is_file($optimizerFile)) {
                require_once $optimizerFile;
            }
            if (class_exists(\Plugins\ImageOptimizer\src\Support\ImageOptimizer::class)) {
                $optimized = \Plugins\ImageOptimizer\src\Support\ImageOptimizer::optimize($dir.DIRECTORY_SEPARATOR.$name, 80, 1920, 1920);
                $name = basename($optimized['path']);
            }
            $url = asset('uploads/menu/'.$name);

            return response()->json([
                'ok' => true,
                'file' => [
                    'path' => 'uploads/menu/'.$name,
                    'url' => $url,
                    'name' => $name,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'ok' => false,
                'message' => collect($e->errors())->flatten()->first() ?: 'فایل نامعتبر است.',
            ], 422);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        MegaMenuPlugin::ensureSchema();
        $data = $this->validated($request);

        if (! array_key_exists('sort_order', $request->all()) || $request->input('sort_order') === null) {
            $q = MegaMenuItem::query()->where('parent_id', $data['parent_id']);
            $data['sort_order'] = (int) $q->max('sort_order') + 1;
        }

        $item = MegaMenuItem::query()->create($data);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'message' => 'آیتم منو اضافه شد.',
                'item' => $this->serializeItem($item->fresh(['category', 'children'])),
            ]);
        }

        return back()->with('success', 'آیتم منو اضافه شد.');
    }

    public function update(Request $request, MegaMenuItem $menuItem): JsonResponse|RedirectResponse
    {
        try {
            MegaMenuPlugin::ensureSchema();
            $data = $this->validated($request);

            if (! $request->exists('parent_id')) {
                unset($data['parent_id']);
            } elseif (! empty($data['parent_id']) && (int) $data['parent_id'] === (int) $menuItem->id) {
                return $this->fail($request, 'آیتم نمی‌تواند والد خودش باشد.');
            }

            if (! empty($data['parent_id'])) {
                $walk = MegaMenuItem::query()->find($data['parent_id']);
                while ($walk) {
                    if ((int) $walk->id === (int) $menuItem->id) {
                        return $this->fail($request, 'نمی‌توان زیرمجموعه را به‌عنوان والد انتخاب کرد.');
                    }
                    $walk = $walk->parent;
                }
            }

            $menuItem->update($data);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'ok' => true,
                    'message' => 'آیتم منو به‌روزرسانی شد.',
                    'item' => $this->serializeItem($menuItem->fresh(['category', 'children'])),
                ]);
            }

            return redirect()->route('admin.mega-menu.index', ['edit' => $menuItem->id])
                ->with('success', 'آیتم منو به‌روزرسانی شد.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return $this->fail($request, 'ذخیره منو ناموفق: '.$e->getMessage());
        }
    }

    public function destroy(Request $request, MegaMenuItem $menuItem): JsonResponse|RedirectResponse
    {
        $this->deleteRecursive($menuItem);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['ok' => true, 'message' => 'آیتم حذف شد.']);
        }

        return back()->with('success', 'آیتم حذف شد.');
    }

    public function reorder(Request $request): JsonResponse
    {
        MegaMenuPlugin::ensureSchema();

        $data = $request->validate([
            'tree' => ['required', 'array'],
            'tree.*.id' => ['required', 'integer'],
            'tree.*.children' => ['nullable', 'array'],
        ]);

        $this->applyTreeOrder($data['tree'], null);

        return response()->json(['ok' => true, 'message' => 'ترتیب منو ذخیره شد.']);
    }

    public function toggle(Request $request, MegaMenuItem $menuItem): JsonResponse
    {
        $menuItem->is_active = ! $menuItem->is_active;
        $menuItem->save();

        return response()->json([
            'ok' => true,
            'is_active' => $menuItem->is_active,
            'message' => $menuItem->is_active ? 'فعال شد.' : 'غیرفعال شد.',
        ]);
    }

    protected function fail(Request $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['ok' => false, 'message' => $message], 422);
        }

        return back()->with('error', $message);
    }

    protected function deleteRecursive(MegaMenuItem $item): void
    {
        foreach ($item->children as $child) {
            $this->deleteRecursive($child);
        }
        $item->delete();
    }

    /** @param  array<int, array{id:int, children?:array}>  $nodes */
    protected function applyTreeOrder(array $nodes, ?int $parentId, int $depth = 0): void
    {
        if ($depth > 5) {
            return;
        }

        foreach (array_values($nodes) as $index => $node) {
            $id = (int) ($node['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            if ($parentId !== null && $id === $parentId) {
                continue;
            }

            MegaMenuItem::query()->where('id', $id)->update([
                'parent_id' => $parentId,
                'sort_order' => $index + 1,
            ]);

            $children = $node['children'] ?? [];
            if (is_array($children) && $children !== []) {
                $this->applyTreeOrder($children, $id, $depth + 1);
            }
        }
    }

    /** @param  \Illuminate\Support\Collection<int, MegaMenuItem>  $items */
    protected function serializeTree($items): array
    {
        return $items->map(fn (MegaMenuItem $item) => $this->serializeItem($item))->values()->all();
    }

    protected function serializeItem(MegaMenuItem $item): array
    {
        return [
            'id' => $item->id,
            'parent_id' => $item->parent_id,
            'title' => $item->title,
            'type' => $item->type,
            'url' => $item->url,
            'category_id' => $item->category_id,
            'badge' => $item->badge,
            'icon' => $item->icon,
            'columns' => $item->columns,
            'html' => $item->html,
            'is_mega' => (bool) $item->is_mega,
            'open_in_new' => (bool) $item->open_in_new,
            'is_active' => (bool) $item->is_active,
            'sort_order' => $item->sort_order,
            'image_url' => $item->image_url,
            'bg_image_url' => $item->bg_image_url,
            'description' => $item->description,
            'animation' => $item->animation ?: 'fade',
            'effect' => $item->effect ?: 'shadow',
            'panel_width' => $item->panel_width ?: 'wide',
            'show_search' => (bool) $item->show_search,
            'search_placeholder' => $item->search_placeholder,
            'is_tabbed' => (bool) $item->is_tabbed,
            'tab_label' => $item->tab_label,
            'form_type' => $item->form_type ?: 'none',
            'form_html' => $item->form_html,
            'accent_color' => $item->accent_color ?: '#e23d12',
            'css_class' => $item->css_class,
            'icon_image_url' => $item->icon_image_url,
            'font_family' => $item->font_family ?: '',
            'title_color' => $item->title_color ?: '',
            'link_color' => $item->link_color ?: '',
            'hover_color' => $item->hover_color ?: '',
            'text_color' => $item->text_color ?: '',
            'panel_bg_color' => $item->panel_bg_color ?: '',
            'panel_radius' => (int) ($item->panel_radius ?: 18),
            'icon_size' => (int) ($item->icon_size ?: 18),
            'open_mode' => $item->open_mode ?: 'hover',
            'panel_align' => $item->panel_align ?: 'right',
            'children' => $item->relationLoaded('children')
                ? $item->children->map(fn (MegaMenuItem $c) => $this->serializeItem($c))->values()->all()
                : [],
        ];
    }

    /** @return \Illuminate\Support\Collection<int, array{path:string,url:string,name:string}> */
    protected function listMedia()
    {
        $items = collect();
        $roots = ['uploads/menu', 'uploads/theme', 'uploads/media'];
        foreach ($roots as $rel) {
            $dir = public_path($rel);
            if (! is_dir($dir)) {
                continue;
            }
            try {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
                );
                foreach ($iterator as $file) {
                    if (! $file->isFile()) {
                        continue;
                    }
                    $name = $file->getFilename();
                    if (! preg_match('/\.(jpe?g|png|webp|gif|svg|mp4|webm|mov)$/i', $name)) {
                        continue;
                    }
                    $full = $file->getPathname();
                    $sub = ltrim(str_replace('\\', '/', substr($full, strlen($dir))), '/');
                    $path = $rel.($sub !== '' ? '/'.$sub : '');
                    $items->push([
                        'path' => $path,
                        'url' => asset($path),
                        'name' => $name,
                    ]);
                }
            } catch (\Throwable) {
                //
            }
        }

        return $items->unique('url')->take(120)->values();
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'type' => ['required', 'in:link,category,column,html,heading,promo,search,tab,form'],
            'url' => ['nullable', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer'],
            'category_id' => ['nullable', 'integer'],
            'badge' => ['nullable', 'string', 'max:40'],
            'icon' => ['nullable', 'string', 'max:80'],
            'columns' => ['nullable', 'integer', 'min:1', 'max:6'],
            'html' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
            'is_mega' => ['nullable', 'boolean'],
            'open_in_new' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'image_url' => ['nullable', 'string', 'max:500'],
            'bg_image_url' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:255'],
            'animation' => ['nullable', 'string', 'max:30'],
            'effect' => ['nullable', 'string', 'max:30'],
            'panel_width' => ['nullable', 'in:normal,wide,full'],
            'show_search' => ['nullable', 'boolean'],
            'search_placeholder' => ['nullable', 'string', 'max:120'],
            'is_tabbed' => ['nullable', 'boolean'],
            'tab_label' => ['nullable', 'string', 'max:80'],
            'form_type' => ['nullable', 'in:none,search,newsletter,login,custom'],
            'form_html' => ['nullable', 'string'],
            'accent_color' => ['nullable', 'string', 'max:20'],
            'css_class' => ['nullable', 'string', 'max:80'],
            'icon_image_url' => ['nullable', 'string', 'max:500'],
            'font_family' => ['nullable', 'string', 'max:80'],
            'title_color' => ['nullable', 'string', 'max:20'],
            'link_color' => ['nullable', 'string', 'max:20'],
            'hover_color' => ['nullable', 'string', 'max:20'],
            'text_color' => ['nullable', 'string', 'max:20'],
            'panel_bg_color' => ['nullable', 'string', 'max:20'],
            'panel_radius' => ['nullable', 'integer', 'min:0', 'max:40'],
            'icon_size' => ['nullable', 'integer', 'min:12', 'max:48'],
            'open_mode' => ['nullable', 'in:hover,click'],
            'panel_align' => ['nullable', 'in:right,left,center'],
        ]);

        $data['parent_id'] = array_key_exists('parent_id', $data)
            ? (! empty($data['parent_id']) ? (int) $data['parent_id'] : null)
            : null;
        if (! $request->exists('parent_id')) {
            unset($data['parent_id']);
        }
        $data['category_id'] = ! empty($data['category_id']) ? (int) $data['category_id'] : null;
        $data['columns'] = max(1, min(6, (int) ($data['columns'] ?? 3)));
        if (array_key_exists('sort_order', $data) && $data['sort_order'] !== null) {
            $data['sort_order'] = (int) $data['sort_order'];
        } else {
            unset($data['sort_order']);
        }
        $data['is_mega'] = $request->boolean('is_mega');
        $data['open_in_new'] = $request->boolean('open_in_new');
        $data['is_active'] = $request->boolean('is_active', true);
        $data['show_search'] = $request->boolean('show_search');
        $data['is_tabbed'] = $request->boolean('is_tabbed');
        $data['animation'] = $data['animation'] ?? 'fade';
        $data['effect'] = $data['effect'] ?? 'shadow';
        $data['panel_width'] = $data['panel_width'] ?? 'wide';
        $data['form_type'] = $data['form_type'] ?? 'none';
        $data['open_mode'] = $data['open_mode'] ?? 'hover';
        $data['panel_align'] = $data['panel_align'] ?? 'right';
        $data['panel_radius'] = max(0, min(40, (int) ($data['panel_radius'] ?? 18)));
        $data['icon_size'] = max(12, min(48, (int) ($data['icon_size'] ?? 18)));

        return $data;
    }
}
