<?php

namespace Plugins\Catalog\src\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Plugins\Catalog\src\Models\Category;
use Plugins\Catalog\src\Models\Product;

class CatalogController extends Controller
{
    private function schemaCapabilities(): array
    {
        return Cache::remember('catalog_schema_capabilities_v2', now()->addDay(), fn () => [
            'brand' => Schema::hasColumn('products', 'brand'),
            'display_serial' => Schema::hasColumn('products', 'display_serial'),
            'part_type' => Schema::hasColumn('products', 'part_type'),
            'warranty_type' => Schema::hasColumn('products', 'warranty_type'),
            'serials' => Schema::hasTable('product_serials'),
            'serial_visibility' => Schema::hasTable('product_serials') && Schema::hasColumn('product_serials', 'show_in_sales'),
        ]);
    }

    public function home(): View
    {
        if (class_exists(\Plugins\ThemeBuilder\src\Http\Controllers\Storefront\PageController::class)) {
            $data = app(\Plugins\ThemeBuilder\src\Http\Controllers\Storefront\PageController::class)->homepageData();

            return view('storefront.home', $data);
        }

        $featured = Product::query()->published()->where('is_featured', true)->latest()->take(8)->get();
        $latest = Product::query()->published()->latest()->take(12)->get();
        $categories = Category::query()
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->with(['activeChildren'])
            ->orderBy('sort_order')
            ->take(8)
            ->get();

        return view('storefront.home', [
            'featured' => $featured,
            'latest' => $latest,
            'categories' => $categories,
            'page' => null,
            'widgets' => [],
        ]);
    }

    public function index(Request $request): View
    {
        $caps = $this->schemaCapabilities();
        $query = Product::query()->published()->with('category');

        if ($request->filled('q')) {
            $q = $request->string('q')->toString();
            $query->where(function ($builder) use ($q, $caps) {
                $builder->where('name', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%")
                    ->orWhere('short_description', 'like', "%{$q}%");
                if ($caps['brand']) {
                    $builder->orWhere('brand', 'like', "%{$q}%");
                }
                if ($caps['display_serial']) {
                    $builder->orWhere('display_serial', 'like', "%{$q}%");
                }
            });
        }

        if ($request->filled('category')) {
            $cat = Category::query()->where('slug', $request->string('category'))->first();
            if ($cat) {
                $ids = collect([$cat->id])->merge($cat->children()->pluck('id'));
                $partFromCat = $this->partTypeForCategorySlug((string) $cat->slug);
                $query->where(function ($q) use ($ids, $partFromCat, $caps) {
                    $q->whereIn('category_id', $ids);
                    // Keep menu/category chips aligned even if a product only has part_type set.
                    if ($caps['part_type'] && $partFromCat !== null) {
                        $q->orWhere('part_type', $partFromCat);
                    }
                });
            }
        }

        if ($request->filled('part_type') && $caps['part_type']) {
            $part = strtolower(trim($request->string('part_type')->toString()));
            $query->where('part_type', $part);
        }

        if ($request->filled('brand') && $caps['brand']) {
            $query->where('brand', $request->string('brand'));
        }

        if ($request->filled('warranty') && $caps['warranty_type']) {
            if ($request->string('warranty')->toString() === 'yes') {
                $query->whereNotNull('warranty_type')->where('warranty_type', '!=', '')->where('warranty_type', '!=', 'none');
            } elseif ($request->string('warranty')->toString() === 'no') {
                $query->where(function ($b) {
                    $b->whereNull('warranty_type')->orWhere('warranty_type', '')->orWhere('warranty_type', 'none');
                });
            }
        }
        $products = $query->latest()->paginate(12)->withQueryString();
        $categories = Category::query()->where('is_active', true)->whereNull('parent_id')->with('activeChildren')->orderBy('sort_order')->get();
        $brands = collect();
        if ($caps['brand']) {
            $brands = Product::query()->published()->whereNotNull('brand')->where('brand', '!=', '')->distinct()->orderBy('brand')->pluck('brand');
        }

        return view('catalog::storefront.index', compact('products', 'categories', 'brands'));
    }

    public function show(string $slug): View
    {
        $caps = $this->schemaCapabilities();
        $product = Product::query()->published()->where('slug', $slug)->with('category.parent')->firstOrFail();
        $related = Product::query()
            ->published()
            ->where('id', '!=', $product->id)
            ->when($product->category_id, fn ($q) => $q->where('category_id', $product->category_id))
            ->take(4)
            ->get();

        $availableSerials = collect();
        if (! empty($product->requires_serial) && $caps['serials']) {
            $availableSerials = \Illuminate\Support\Facades\DB::table('product_serials')
                ->where('product_id', $product->id)
                ->where('status', 'available')
                ->when($caps['serial_visibility'], fn ($q) => $q->where('show_in_sales', 1))
                ->orderBy('serial')
                ->limit(200)
                ->get(['id', 'serial', 'warranty_company', 'company_warranty_months']);
        }

        return view('catalog::storefront.show', compact('product', 'related', 'availableSerials'));
    }

    public function category(string $slug): View
    {
        $caps = $this->schemaCapabilities();
        $category = Category::query()->where('slug', $slug)->where('is_active', true)->with('activeChildren')->firstOrFail();
        $ids = collect([$category->id])->merge($category->activeChildren->pluck('id'));
        $partFromCat = $this->partTypeForCategorySlug((string) $category->slug);

        $products = Product::query()
            ->published()
            ->with('category')
            ->where(function ($q) use ($ids, $partFromCat, $caps) {
                $q->whereIn('category_id', $ids);
                if ($caps['part_type'] && $partFromCat !== null) {
                    $q->orWhere('part_type', $partFromCat);
                }
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $categories = Category::query()->where('is_active', true)->whereNull('parent_id')->with('activeChildren')->orderBy('sort_order')->get();
        $brands = collect();
        if ($caps['brand']) {
            $brands = Product::query()->published()->whereNotNull('brand')->where('brand', '!=', '')->distinct()->orderBy('brand')->pluck('brand');
        }

        return view('catalog::storefront.index', compact('products', 'categories', 'category', 'brands'));
    }

    /** Map storefront category slugs used in menus to product part_type values. */
    protected function partTypeForCategorySlug(string $slug): ?string
    {
        $slug = strtolower(trim($slug));

        return match ($slug) {
            'ssd' => 'ssd',
            'nvme' => 'nvme',
            'ram' => 'ram',
            'hdd', 'hard-disk', 'hard-akstrnal' => 'hdd',
            default => null,
        };
    }
}
