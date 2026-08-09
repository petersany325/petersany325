<?php

namespace Plugins\ThemeBuilder\src\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
            ->latest()
            ->take(24)
            ->get();

        $featured = $this->preferSellable($featured)->take(12)->values();

        if ($featured->count() < 4) {
            $extra = Product::query()
                ->published()
                ->latest()
                ->take(24)
                ->get();
            $seen = $featured->pluck('id')->all();
            $featured = $featured
                ->concat($this->preferSellable($extra)->reject(fn (Product $p) => in_array($p->id, $seen, true)))
                ->take(12)
                ->values();
        }

        $latest = $this->preferSellable(
            Product::query()->published()->latest()->take(24)->get()
        )->take(12)->values();

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

    /** @param  Collection<int, Product>  $items */
    protected function preferSellable(Collection $items): Collection
    {
        return $items
            ->sortByDesc(function (Product $p) {
                $score = 0;
                if (method_exists($p, 'inStock') && $p->inStock()) {
                    $score += 2;
                }
                if ((int) ($p->price ?? 0) > 0) {
                    $score += 1;
                }

                return $score;
            })
            ->values();
    }
}
