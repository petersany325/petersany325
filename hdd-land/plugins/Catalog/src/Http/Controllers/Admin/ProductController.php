<?php

namespace Plugins\Catalog\src\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Plugins\Catalog\src\Models\Category;
use Plugins\Catalog\src\Models\Product;
use Plugins\Catalog\src\Support\ProductSlug;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $query = Product::query()->with('category.parent');

        if ($request->filled('q')) {
            $q = $request->string('q')->toString();
            $query->where(function ($b) use ($q) {
                $b->where('name', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%")
                    ->orWhere('brand', 'like', "%{$q}%");
            });
        }
        if ($request->filled('status') && Schema::hasColumn('products', 'status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('stock_status') && Schema::hasColumn('products', 'stock_status')) {
            $query->where('stock_status', $request->string('stock_status'));
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        $products = $query->latest()->paginate(20)->withQueryString();

        return view('catalog::admin.products-index', [
            'products' => $products,
            'categories' => Category::treeOptions(),
        ]);
    }

    public function create(Request $request): View
    {
        \Plugins\Catalog\Plugin::ensureProductCardColumns();

        $defaults = [
            'condition' => 'new',
            'is_active' => true,
            'status' => 'publish',
            'stock_status' => 'instock',
            'manage_stock' => true,
            'show_serial_on_card' => true,
            'has_warranty' => false,
            'requires_serial' => false,
        ];
        if ($request->boolean('with_serial') || $request->boolean('with_warranty')) {
            $defaults['has_warranty'] = true;
            $defaults['requires_serial'] = $request->boolean('with_serial');
        }

        return view('catalog::admin.products-form', [
            'product' => new Product($defaults),
            'categories' => Category::treeOptions(),
            'mediaLibrary' => $this->mediaLibrary(),
            'warrantyCompanies' => $this->warrantyCompanies(),
            'withSerialFocus' => $request->boolean('with_serial') || $request->boolean('with_warranty'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        \Plugins\Catalog\Plugin::ensureProductCardColumns();

        try {
            $data = $this->validated($request);
            $data = $this->handleMedia($request, $data);
            $product = Product::query()->create($data);
        } catch (UniqueConstraintViolationException $e) {
            return $this->handleSaveException($request, $e);
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->with('error', 'ذخیره محصول ناموفق بود: '.$e->getMessage());
        }

        if ($request->boolean('open_serials') || (! empty($data['requires_serial']) && ! empty($data['has_warranty']))) {
            return redirect()->to(url('/admin/products/'.$product->id.'/serials'))
                ->with('success', 'محصول ذخیره شد. حالا سریال‌ها را وارد کنید.');
        }

        return redirect()->route('admin.products.index')->with('success', 'محصول ذخیره شد.');
    }

    public function edit(Product $product): View
    {
        \Plugins\Catalog\Plugin::ensureProductCardColumns();

        return view('catalog::admin.products-form', [
            'product' => $product,
            'categories' => Category::treeOptions(),
            'mediaLibrary' => $this->mediaLibrary(),
            'warrantyCompanies' => $this->warrantyCompanies(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        \Plugins\Catalog\Plugin::ensureProductCardColumns();

        try {
            $data = $this->validated($request, $product);
            $data = $this->handleMedia($request, $data, $product);
            $product->update($data);
        } catch (UniqueConstraintViolationException $e) {
            return $this->handleSaveException($request, $e, $product);
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->with('error', 'به‌روزرسانی محصول ناموفق بود: '.$e->getMessage());
        }

        if ($request->boolean('open_serials') && ! empty($data['requires_serial']) && ! empty($data['has_warranty'])) {
            return redirect()->to(url('/admin/products/'.$product->id.'/serials'))
                ->with('success', 'محصول به‌روزرسانی شد. سریال‌ها را وارد کنید.');
        }

        return redirect()->route('admin.products.index')->with('success', 'محصول به‌روزرسانی شد.');
    }

    /** @return \Illuminate\Support\Collection<int, object> */
    protected function warrantyCompanies()
    {
        try {
            if (class_exists(\Plugins\AdminCore\src\Support\SerialSupport::class)) {
                \Plugins\AdminCore\src\Support\SerialSupport::ensureSchema();
            }
            if (Schema::hasTable('warranty_companies')) {
                return \Illuminate\Support\Facades\DB::table('warranty_companies')
                    ->where('is_active', 1)
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->get();
            }
        } catch (\Throwable) {
            //
        }

        return collect();
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return back()->with('success', 'محصول حذف شد.');
    }

    protected function handleSaveException(Request $request, UniqueConstraintViolationException $e, ?Product $product = null): RedirectResponse
    {
        $msg = $e->getMessage();
        if (str_contains($msg, 'slug') || str_contains($msg, 'products_slug_unique')) {
            return back()->withInput()->withErrors([
                'slug' => 'این آدرس (اسلاگ) قبلاً برای محصول دیگری ثبت شده است. در تب «تنظیمات پیشرفته» اسلاگ را تغییر دهید.',
            ]);
        }
        if (str_contains($msg, 'sku')) {
            return back()->withInput()->withErrors([
                'sku' => 'این کد کالا (SKU) قبلاً ثبت شده است.',
            ]);
        }

        report($e);

        return back()->withInput()->with('error', 'ذخیره محصول به‌دلیل تکراری بودن اطلاعات انجام نشد.');
    }

    protected function handleMedia(Request $request, array $data, ?Product $product = null): array
    {
        if ($request->boolean('clear_image') && $product?->image) {
            if (str_starts_with(ltrim((string) $product->image, '/'), 'products/')) {
                Storage::disk('public')->delete($product->image);
                $publicMirror = public_path('uploads/'.ltrim((string) $product->image, '/'));
                if (is_file($publicMirror)) {
                    @unlink($publicMirror);
                }
            }
            $data['image'] = null;
        }

        $lib = $this->safeLibraryImagePath((string) $request->input('image_library_path', ''));
        if ($lib !== '' && ! $request->hasFile('image')) {
            $data['image'] = $lib;
        }

        if ($request->hasFile('image')) {
            if ($product?->image) {
                Storage::disk('public')->delete($product->image);
            }
            $path = $request->file('image')->store('products', 'public');
            $optimizerFile = base_path('plugins/ImageOptimizer/src/Support/ImageOptimizer.php');
            if (! class_exists(\Plugins\ImageOptimizer\src\Support\ImageOptimizer::class) && is_file($optimizerFile)) {
                require_once $optimizerFile;
            }
            if (class_exists(\Plugins\ImageOptimizer\src\Support\ImageOptimizer::class)) {
                $optimized = \Plugins\ImageOptimizer\src\Support\ImageOptimizer::optimize(Storage::disk('public')->path($path), 80, 1920, 1920);
                $path = 'products/'.basename($optimized['path']);
            }
            $data['image'] = $path;
            $this->mirrorPublicUpload($path);
        }

        $gallery = $product?->gallery ?? [];
        if ($request->boolean('clear_gallery')) {
            foreach ($gallery as $path) {
                Storage::disk('public')->delete($path);
            }
            $gallery = [];
        }
        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $file) {
                if ($file && $file->isValid()) {
                    $gpath = $file->store('products/gallery', 'public');
                    $optimizerFile = base_path('plugins/ImageOptimizer/src/Support/ImageOptimizer.php');
                    if (! class_exists(\Plugins\ImageOptimizer\src\Support\ImageOptimizer::class) && is_file($optimizerFile)) {
                        require_once $optimizerFile;
                    }
                    if (class_exists(\Plugins\ImageOptimizer\src\Support\ImageOptimizer::class)) {
                        $optimized = \Plugins\ImageOptimizer\src\Support\ImageOptimizer::optimize(Storage::disk('public')->path($gpath), 80, 1920, 1920);
                        $gpath = 'products/gallery/'.basename($optimized['path']);
                    }
                    $gallery[] = $gpath;
                    $this->mirrorPublicUpload($gpath);
                }
            }
        }
        foreach ((array) $request->input('gallery_library_paths', []) as $gLib) {
            $gLib = $this->safeLibraryImagePath((string) $gLib);
            if ($gLib !== '' && ! in_array($gLib, $gallery, true)) {
                $gallery[] = $gLib;
            }
        }
        if (Schema::hasColumn('products', 'gallery')) {
            $data['gallery'] = array_values($gallery);
        }

        return $data;
    }

    protected function safeLibraryImagePath(string $path): string
    {
        $path = ltrim(str_replace('\\', '/', trim($path)), '/');
        if ($path === '' || str_contains($path, '..') || ! preg_match('/\.(jpe?g|png|webp|gif)$/i', $path)) {
            return '';
        }

        try {
            return Storage::disk('public')->exists($path) ? $path : '';
        } catch (\Throwable) {
            return '';
        }
    }

    protected function mirrorPublicUpload(string $storagePath): void
    {
        try {
            $full = Storage::disk('public')->path($storagePath);
            if (! is_file($full)) {
                return;
            }
            $dest = public_path('uploads/'.$storagePath);
            $dir = dirname($dest);
            if (! is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            @copy($full, $dest);
        } catch (\Throwable) {
            //
        }
    }

    /** @return list<array{path:string,url:string}> */
    protected function mediaLibrary(): array
    {
        $items = [];
        try {
            \Plugins\Catalog\src\Support\MediaLibrary::storageRoot();
            $root = \Plugins\Catalog\src\Support\MediaLibrary::rootRelative();
            foreach (Storage::disk('public')->allFiles($root) as $path) {
                if (! preg_match('/\.(jpe?g|png|webp|gif|svg)$/i', $path)) {
                    continue;
                }
                $items[] = [
                    'path' => $path,
                    'url' => is_file(public_path('uploads/'.$path))
                        ? asset('uploads/'.$path)
                        : asset('storage/'.$path),
                ];
            }
        } catch (\Throwable) {
            //
        }
        try {
            foreach (Storage::disk('public')->allFiles('products') as $path) {
                if (! preg_match('/\.(jpe?g|png|webp|gif)$/i', $path)) {
                    continue;
                }
                $items[] = ['path' => $path, 'url' => asset('storage/'.$path)];
            }
        } catch (\Throwable) {
            //
        }
        $seen = [];
        $out = [];
        foreach ($items as $it) {
            if (isset($seen[$it['path']])) {
                continue;
            }
            $seen[$it['path']] = true;
            $out[] = $it;
        }

        return array_slice($out, 0, 160);
    }

    protected function validated(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100'],
            'display_serial' => ['nullable', 'string', 'max:120'],
            'show_serial_on_card' => ['nullable'],
            'card_settings' => ['nullable', 'array'],
            'card_settings.*' => ['nullable'],
            'brand' => ['nullable', 'string', 'max:120'],
            'part_type' => ['nullable', 'string', 'max:50'],
            'condition' => ['nullable', 'string', 'max:30'],
            'warranty_type' => ['nullable', 'string', 'max:50'],
            'warranty_months' => ['nullable', 'integer', 'min:0', 'max:120'],
            'warranty_company' => ['nullable', 'string', 'max:160'],
            'has_warranty' => ['nullable'],
            'requires_serial' => ['nullable'],
            'capacity' => ['nullable', 'string', 'max:80'],
            'interface' => ['nullable', 'string', 'max:80'],
            'form_factor' => ['nullable', 'string', 'max:80'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'short_description' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'integer', 'min:0'],
            'compare_price' => ['nullable', 'integer', 'min:0'],
            'cost_price' => ['nullable', 'integer', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'status' => ['nullable', 'in:publish,draft,private'],
            'stock_status' => ['nullable', 'in:instock,outofstock,onbackorder'],
            'manage_stock' => ['nullable', 'boolean'],
            'low_stock_amount' => ['nullable', 'integer', 'min:0'],
            'menu_order' => ['nullable', 'integer'],
            'is_featured' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'max:4096'],
            'clear_image' => ['nullable', 'boolean'],
            'image_library_path' => ['nullable', 'string', 'max:500'],
            'gallery' => ['nullable', 'array'],
            'gallery.*' => ['nullable', 'image', 'max:4096'],
            'gallery_library_paths' => ['nullable', 'array'],
            'gallery_library_paths.*' => ['nullable', 'string', 'max:500'],
            'spec_keys' => ['nullable', 'array'],
            'spec_keys.*' => ['nullable', 'string', 'max:100'],
            'spec_values' => ['nullable', 'array'],
            'spec_values.*' => ['nullable', 'string', 'max:255'],
        ]);

        $status = $request->boolean('save_as_draft') ? 'draft' : ($data['status'] ?? 'publish');
        $data['status'] = $status;
        $data['is_active'] = $status === 'publish';
        $data['is_featured'] = $request->boolean('is_featured');
        $data['manage_stock'] = $request->boolean('manage_stock', true);
        $data['has_warranty'] = $request->boolean('has_warranty');
        $data['requires_serial'] = $data['has_warranty'] && $request->boolean('requires_serial');
        if (! $data['has_warranty']) {
            $data['warranty_type'] = 'none';
            $data['warranty_months'] = null;
            $data['warranty_company'] = null;
        } else {
            $data['warranty_company'] = trim((string) ($data['warranty_company'] ?? '')) ?: null;
            if ($data['warranty_company'] === null) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'warranty_company' => 'وقتی گارانتی فعال است، شرکت گارانتی را از لیست انتخاب کنید.',
                ]);
            }
            if (empty($data['warranty_type']) || $data['warranty_type'] === 'none') {
                $data['warranty_type'] = 'official';
            }
        }
        $cardDefaults = Product::defaultCardSettings();
        $cardIn = (array) $request->input('card_settings', []);
        $cardOut = [];
        foreach ($cardDefaults as $k => $_def) {
            $cardOut[$k] = ! empty($cardIn[$k]);
        }
        $data['card_settings'] = $cardOut;
        $data['show_serial_on_card'] = ! empty($cardOut['show_serial']);
        $data['stock_status'] = $data['stock_status'] ?? 'instock';
        $data['menu_order'] = (int) ($data['menu_order'] ?? 0);
        $slugSource = trim((string) ($data['slug'] ?? '')) ?: (string) $data['name'];
        $data['slug'] = ProductSlug::unique($slugSource, $product?->id);
        $data['condition'] = $data['condition'] ?: 'new';

        if ($data['manage_stock'] && (int) $data['stock'] <= 0 && $data['stock_status'] !== 'onbackorder') {
            $data['stock_status'] = 'outofstock';
        }

        $specs = [];
        foreach ($request->input('spec_keys', []) as $i => $key) {
            $key = trim((string) $key);
            $value = trim((string) ($request->input('spec_values')[$i] ?? ''));
            if ($key !== '' && $value !== '') {
                $specs[$key] = $value;
            }
        }
        $data['specs'] = $specs;
        unset($data['spec_keys'], $data['spec_values'], $data['gallery'], $data['image_library_path'], $data['gallery_library_paths'], $data['clear_image']);

        foreach (['status', 'stock_status', 'manage_stock', 'low_stock_amount', 'menu_order', 'gallery', 'display_serial', 'show_serial_on_card', 'card_settings', 'requires_serial', 'has_warranty', 'warranty_company', 'cost_price'] as $col) {
            if (! Schema::hasColumn('products', $col)) {
                unset($data[$col]);
            }
        }

        return $data;
    }
}
