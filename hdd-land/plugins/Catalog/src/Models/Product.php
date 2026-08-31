<?php

namespace Plugins\Catalog\src\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Plugins\Catalog\src\Support\ProductSlug;

class Product extends Model
{
    public const STATUSES = [
        'publish' => 'منتشر شده',
        'draft' => 'پیش‌نویس',
        'private' => 'خصوصی',
    ];

    public const STOCK_STATUSES = [
        'instock' => 'موجود',
        'outofstock' => 'ناموجود',
        'onbackorder' => 'پیش‌سفارش',
    ];

    public const PART_TYPES = [
        'hdd' => 'هارد دیسک (HDD)',
        'ssd' => 'اس‌اس‌دی (SSD)',
        'nvme' => 'NVMe',
        'ram' => 'رم (RAM)',
        'motherboard' => 'مادربورد',
        'cpu' => 'پردازنده',
        'gpu' => 'کارت گرافیک',
        'psu' => 'منبع تغذیه',
        'case' => 'کیس',
        'cooler' => 'خنک‌کننده',
        'accessory' => 'لوازم جانبی',
        'other' => 'سایر',
    ];

    public const WARRANTY_TYPES = [
        'official' => 'گارانتی اصلی شرکتی',
        'seller' => 'گارانتی فروشگاه',
        'replacement' => 'گارانتی تعویض',
        'international' => 'گارانتی بین‌المللی',
        'none' => 'بدون گارانتی',
    ];

    public const CONDITIONS = [
        'new' => 'نو',
        'stock' => 'استوک',
        'refurb' => 'ریفر/بازسازی‌شده',
        'used' => 'کارکرده',
    ];

    protected $fillable = [
        'category_id', 'name', 'slug', 'sku', 'brand', 'part_type', 'condition',
        'warranty_type', 'warranty_months', 'capacity', 'interface', 'form_factor',
        'specs', 'short_description', 'description', 'price', 'compare_price', 'cost_price',
        'stock', 'stock_status', 'manage_stock', 'low_stock_amount',
        'image', 'gallery', 'status', 'menu_order', 'is_active', 'is_featured',
        'display_serial', 'show_serial_on_card', 'card_settings', 'requires_serial',
        'has_warranty', 'warranty_company',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'manage_stock' => 'boolean',
            'price' => 'integer',
            'compare_price' => 'integer',
            'cost_price' => 'integer',
            'stock' => 'integer',
            'low_stock_amount' => 'integer',
            'warranty_months' => 'integer',
            'menu_order' => 'integer',
            'specs' => 'array',
            'gallery' => 'array',
            'show_serial_on_card' => 'boolean',
            'card_settings' => 'array',
            'requires_serial' => 'boolean',
            'has_warranty' => 'boolean',
        ];
    }

    /** @return array<string,bool> */
    public static function defaultCardSettings(): array
    {
        return [
            'show_short_desc' => true,
            'show_serial' => true,
            'show_warranty' => true,
            'show_stock' => true,
            'show_meta' => true,
            'show_condition' => true,
            'show_add_cart' => true,
            'show_buy_now' => true,
            'show_preorder' => true,
        ];
    }

    /** @return array<string,bool> */
    public function cardSettings(): array
    {
        return array_merge(static::defaultCardSettings(), is_array($this->card_settings) ? $this->card_settings : []);
    }

    protected static function booted(): void
    {
        static::saving(function (Product $product) {
            if (blank($product->slug)) {
                $product->slug = ProductSlug::unique((string) $product->name, $product->exists ? (int) $product->id : null);
            }

            if (Schema::hasColumn('products', 'status')) {
                $status = $product->status ?: 'publish';
                $product->status = $status;
                $product->is_active = $status === 'publish';
            }

            if (Schema::hasColumn('products', 'manage_stock') && $product->manage_stock) {
                if ((int) $product->stock <= 0) {
                    $product->stock_status = 'outofstock';
                } elseif ($product->stock_status === 'outofstock') {
                    $product->stock_status = 'instock';
                }
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        if (Schema::hasColumn('products', 'status')) {
            return $query->where('status', 'publish')->where('is_active', true);
        }

        return $query->where('is_active', true);
    }

    public function formattedPrice(): string
    {
        return number_format($this->price).' تومان';
    }

    public function onSale(): bool
    {
        return $this->compare_price && $this->compare_price > $this->price;
    }

    public function discountPercent(): ?int
    {
        if (! $this->onSale()) {
            return null;
        }

        return (int) round((($this->compare_price - $this->price) / $this->compare_price) * 100);
    }

    public function inStock(): bool
    {
        $status = $this->stock_status ?: 'instock';
        if ($status === 'outofstock') {
            return false;
        }
        if ($this->manage_stock ?? true) {
            return $this->stock > 0 || $status === 'onbackorder';
        }

        return $status !== 'outofstock';
    }

    public function imageUrl(): string
    {
        if ($this->image) {
            $path = ltrim(str_replace('\\', '/', (string) $this->image), '/');
            if (is_file(public_path('uploads/'.$path))) {
                return asset('uploads/'.$path);
            }

            return asset('storage/'.$path);
        }

        return asset('product-placeholder.svg');
    }

    /** @return list<string> */
    public function galleryUrls(): array
    {
        $urls = [$this->imageUrl()];
        foreach ($this->gallery ?? [] as $path) {
            $path = ltrim(str_replace('\\', '/', (string) $path), '/');
            if ($path === '') {
                continue;
            }
            if (is_file(public_path('uploads/'.$path))) {
                $urls[] = asset('uploads/'.$path);
            } else {
                $urls[] = asset('storage/'.$path);
            }
        }

        return array_values(array_unique($urls));
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status ?? 'publish'] ?? ($this->status ?: '—');
    }

    public function stockStatusLabel(): string
    {
        return self::STOCK_STATUSES[$this->stock_status ?? 'instock'] ?? ($this->stock_status ?: '—');
    }

    public function partTypeLabel(): string
    {
        return self::PART_TYPES[$this->part_type] ?? ($this->part_type ?: '—');
    }

    public function warrantyLabel(): string
    {
        $type = self::WARRANTY_TYPES[$this->warranty_type] ?? ($this->warranty_type ?: '—');
        if ($this->warranty_months) {
            return $type.' — '.$this->warranty_months.' ماه';
        }

        return $type;
    }

    public function conditionLabel(): string
    {
        return self::CONDITIONS[$this->condition] ?? ($this->condition ?: '—');
    }

    public function hasWarranty(): bool
    {
        $type = (string) ($this->warranty_type ?? '');

        return $type !== '' && $type !== 'none';
    }

    public function warrantyBadgeText(): string
    {
        if (! $this->hasWarranty()) {
            return 'فاقد گارانتی';
        }

        return $this->warrantyLabel();
    }

    public function displaySerialText(): ?string
    {
        // Only show an explicit marketing/display serial — never leak warehouse stock SNs.
        $serial = trim((string) ($this->display_serial ?? ''));

        return $serial !== '' ? $serial : null;
    }

    /** @return \Illuminate\Support\Collection<int, object> */
    public function availableSerialRows(int $limit = 100)
    {
        try {
            if (! \Illuminate\Support\Facades\Schema::hasTable('product_serials') || ! $this->id) {
                return collect();
            }
            $q = \Illuminate\Support\Facades\DB::table('product_serials')
                ->where('product_id', $this->id)
                ->where('status', 'available');
            if (\Illuminate\Support\Facades\Schema::hasColumn('product_serials', 'show_in_sales')) {
                $q->where('show_in_sales', 1);
            }

            return $q->orderBy('serial')->limit($limit)->get(['id', 'serial', 'warranty_company', 'company_warranty_months', 'has_company_warranty']);
        } catch (\Throwable) {
            return collect();
        }
    }

    public function availableSerialCount(): int
    {
        try {
            if (! \Illuminate\Support\Facades\Schema::hasTable('product_serials') || ! $this->id) {
                return 0;
            }

            return (int) \Illuminate\Support\Facades\DB::table('product_serials')
                ->where('product_id', $this->id)
                ->where('status', 'available')
                ->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    public function allowsPreorder(): bool
    {
        return ($this->stock_status ?? '') === 'onbackorder' || ! $this->inStock();
    }

    /** @return array<string, string> */
    public function specRows(): array
    {
        $rows = [
            'برند' => $this->brand,
            'نوع قطعه' => $this->part_type ? $this->partTypeLabel() : null,
            'وضعیت کالا' => $this->condition ? $this->conditionLabel() : null,
            'گارانتی' => ($this->warranty_type || $this->warranty_months) ? $this->warrantyLabel() : null,
            'ظرفیت' => $this->capacity,
            'رابط' => $this->interface,
            'فرم‌فکتور' => $this->form_factor,
            'کد کالا' => $this->sku,
            'وضعیت انبار' => $this->stockStatusLabel(),
        ];

        foreach (($this->specs ?? []) as $key => $value) {
            if ($key !== '' && $value !== '' && $value !== null) {
                $rows[(string) $key] = (string) $value;
            }
        }

        return array_filter($rows, fn ($v) => filled($v));
    }
}
