<?php

namespace Plugins\ThemeBuilder\src\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Plugins\Catalog\src\Models\Category;
use Plugins\Catalog\src\Models\Product;
use Plugins\ThemeBuilder\src\Models\BuilderPage;
use Plugins\ThemeBuilder\src\PageBuilder;

class PageController extends Controller
{
    public function show(Request $request, string $slug): View
    {
        PageBuilder::ensureSchema();
        $page = BuilderPage::query()->where('slug', $slug)->firstOrFail();

        if (! $page->isPublished() && ! ($request->user() && $request->user()->isAdmin())) {
            abort(404);
        }

        return view('theme-builder::storefront.render', $this->viewData($page));
    }

    /** @return array<string, mixed> */
    public function homepageData(): array
    {
        PageBuilder::ensureSchema();
        $page = PageBuilder::ensureHomepage();

        return $this->viewData($page);
    }

    /** @return array<string, mixed> */
    protected function viewData(BuilderPage $page): array
    {
        $featured = Product::query()
            ->published()
            ->where('is_featured', true)
            ->where('price', '>', 0)
            ->latest()
            ->take(24)
            ->get()
            ->filter(fn (Product $p) => $p->inStock())
            ->take(12)
            ->values();

        if ($featured->isEmpty()) {
            $featured = Product::query()
                ->published()
                ->where('price', '>', 0)
                ->latest()
                ->take(24)
                ->get()
                ->filter(fn (Product $p) => $p->inStock())
                ->take(8)
                ->values();
        }

        $latest = Product::query()
            ->published()
            ->where('price', '>', 0)
            ->latest()
            ->take(24)
            ->get()
            ->filter(fn (Product $p) => $p->inStock())
            ->take(12)
            ->values();

        $categories = Category::query()
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->with('activeChildren')
            ->orderBy('sort_order')
            ->take(12)
            ->get();

        return [
            'page' => $page,
            'widgets' => $page->widgets(),
            'featured' => $featured,
            'latest' => $latest,
            'categories' => $categories,
        ];
    }
}
