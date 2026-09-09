<?php

namespace Plugins\Catalog\src\Support;

use Illuminate\Support\Str;
use Plugins\Catalog\src\Models\Product;

class ProductSlug
{
  public static function baseFrom(string $nameOrSlug): string
  {
    $slug = Str::slug($nameOrSlug);

    return $slug !== '' ? $slug : 'product-'.Str::lower(Str::random(6));
  }

  public static function unique(string $nameOrSlug, ?int $ignoreId = null): string
  {
    $base = static::baseFrom($nameOrSlug);
    $slug = $base;
    $n = 2;

    while (static::taken($slug, $ignoreId)) {
      $slug = $base.'-'.$n;
      $n++;
      if ($n > 500) {
        return $base.'-'.Str::lower(Str::random(4));
      }
    }

    return $slug;
  }

  protected static function taken(string $slug, ?int $ignoreId): bool
  {
    $query = Product::query()->where('slug', $slug);
    if ($ignoreId) {
      $query->where('id', '!=', $ignoreId);
    }

    return $query->exists();
  }
}
