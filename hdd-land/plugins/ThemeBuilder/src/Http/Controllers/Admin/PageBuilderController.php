<?php

namespace Plugins\ThemeBuilder\src\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Plugins\ThemeBuilder\src\PageBuilder;
use Plugins\ThemeBuilder\Plugin as ThemeBuilderPlugin;

class PageBuilderController extends Controller
{
    public function index(Request $request): View
    {
        ThemeBuilderPlugin::loadClasses();
        PageBuilder::ensureReady();
        $pages = PageBuilder::all();
        $id = $request->query('page');
        $path = $request->query('path');
        $current = null;
        if (is_numeric($id)) {
            $current = PageBuilder::findById((int) $id);
        }
        if ($current === null && is_string($path) && $path !== '') {
            $current = PageBuilder::ensurePage($path);
            $pages = PageBuilder::all();
        }
        if ($current === null && is_string($id) && $id !== '') {
            $current = PageBuilder::findByPath((string) $id) ?: PageBuilder::findBySlug((string) $id);
        }
        if ($current === null && $pages !== []) {
            $prefer = PageBuilder::findByPath('/computer-accounting/corporate')
                ?: PageBuilder::findByPath('/design/corporate');
            $current = $prefer ?: $pages[0];
        }

        return view('theme-builder::admin.builder', [
            'pages' => $pages,
            'current' => $current,
            'groups' => $this->groups($pages),
        ]);
    }

    public function save(Request $request): JsonResponse|RedirectResponse
    {
        ThemeBuilderPlugin::loadClasses();
        PageBuilder::ensureReady();
        $page = PageBuilder::save($request->all());
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['ok' => true, 'page' => $page, 'message' => 'صفحه ذخیره شد.']);
        }

        return redirect()
            ->to(url('/admin/page-builder?page='.$page['id']))
            ->with('success', 'صفحه ذخیره شد.');
    }

    public function upload(Request $request): JsonResponse
    {
        try {
            $request->validate(['file' => ['required', 'image', 'max:8192']]);
            $file = $request->file('file');
            $name = 'pg-'.uniqid('', true).'.'.strtolower($file->getClientOriginalExtension() ?: 'jpg');
            $dir = public_path('uploads/pages');
            if (! is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            if (! is_dir($dir) || ! is_writable($dir)) {
                return response()->json(['ok' => false, 'message' => 'مسیر uploads/pages قابل نوشتن نیست.'], 500);
            }
            $file->move($dir, $name);
            @chmod($dir.DIRECTORY_SEPARATOR.$name, 0644);
            $optimizer = base_path('plugins/ImageOptimizer/src/Support/ImageOptimizer.php');
            if (! class_exists(\Plugins\ImageOptimizer\src\Support\ImageOptimizer::class) && is_file($optimizer)) {
                require_once $optimizer;
            }
            if (class_exists(\Plugins\ImageOptimizer\src\Support\ImageOptimizer::class)) {
                $optimized = \Plugins\ImageOptimizer\src\Support\ImageOptimizer::optimize($dir.DIRECTORY_SEPARATOR.$name, 80, 1920, 1920);
                $name = basename($optimized['path']);
            }

            return response()->json([
                'ok' => true,
                'file' => [
                    'path' => 'uploads/pages/'.$name,
                    'url' => asset('uploads/pages/'.$name),
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

    /**
     * @param  list<array<string, mixed>>  $pages
     * @return array<string, list<array<string, mixed>>>
     */
    protected function groups(array $pages): array
    {
        $order = ['صفحه اول', 'طراحی سایت', 'هوش مصنوعی', 'حسابداری وب', 'حسابداری کامپیوتر', 'تعمیرگاه', 'فروش سایت', 'سایت'];
        $groups = [];
        foreach ($pages as $page) {
            $group = trim((string) ($page['menu_group'] ?? 'سایت')) ?: 'سایت';
            $groups[$group][] = $page;
        }
        $sorted = [];
        foreach ($order as $name) {
            if (isset($groups[$name])) {
                $sorted[$name] = $groups[$name];
                unset($groups[$name]);
            }
        }

        return $sorted + $groups;
    }
}
