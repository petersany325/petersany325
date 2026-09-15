<?php

namespace Plugins\Catalog;

use App\Support\BasePlugin;
use App\Support\SchemaHealer;

class Plugin extends BasePlugin
{
    public function id(): string
    {
        return 'catalog';
    }

    public function name(): string
    {
        return 'کاتالوگ محصولات';
    }

    public function description(): string
    {
        return 'محصولات حرفه‌ای با دسته‌بندی چندسطحی، نوع قطعه، گارانتی و مشخصات فنی';
    }

    public function version(): string
    {
        return '1.4.1';
    }

    public function isCore(): bool
    {
        return true;
    }

    public function boot(): void
    {
        SchemaHealer::safeBoot();
        static::ensureProductCardColumns();
        static::ensureCategoryColumns();
        parent::boot();
    }

    public static function ensureProductCardColumns(): void
    {
        try {
            if (! \Illuminate\Support\Facades\Schema::hasTable('products')) {
                return;
            }
            if (! \Illuminate\Support\Facades\Schema::hasColumn('products', 'display_serial')) {
                \Illuminate\Support\Facades\Schema::table('products', function ($t) {
                    $t->string('display_serial', 120)->nullable()->after('sku');
                });
            }
            if (! \Illuminate\Support\Facades\Schema::hasColumn('products', 'show_serial_on_card')) {
                \Illuminate\Support\Facades\Schema::table('products', function ($t) {
                    $t->boolean('show_serial_on_card')->default(true);
                });
            }
            if (! \Illuminate\Support\Facades\Schema::hasColumn('products', 'card_settings')) {
                \Illuminate\Support\Facades\Schema::table('products', function ($t) {
                    $t->json('card_settings')->nullable();
                });
            }
            if (! \Illuminate\Support\Facades\Schema::hasColumn('products', 'requires_serial')) {
                \Illuminate\Support\Facades\Schema::table('products', function ($t) {
                    $t->boolean('requires_serial')->default(false);
                });
            }
            if (! \Illuminate\Support\Facades\Schema::hasColumn('products', 'has_warranty')) {
                \Illuminate\Support\Facades\Schema::table('products', function ($t) {
                    $t->boolean('has_warranty')->default(false);
                });
            }
            if (! \Illuminate\Support\Facades\Schema::hasColumn('products', 'warranty_company')) {
                \Illuminate\Support\Facades\Schema::table('products', function ($t) {
                    $t->string('warranty_company', 160)->nullable();
                });
            }
            if (! \Illuminate\Support\Facades\Schema::hasColumn('products', 'cost_price')) {
                \Illuminate\Support\Facades\Schema::table('products', function ($t) {
                    $t->unsignedBigInteger('cost_price')->nullable();
                });
            }
        } catch (\Throwable) {
            //
        }
    }

    public static function ensureCategoryColumns(): void
    {
        try {
            if (! \Illuminate\Support\Facades\Schema::hasTable('categories')) {
                return;
            }
            if (! \Illuminate\Support\Facades\Schema::hasColumn('categories', 'image')) {
                \Illuminate\Support\Facades\Schema::table('categories', function ($t) {
                    $t->string('image', 500)->nullable()->after('description');
                });
            }
            if (! \Illuminate\Support\Facades\Schema::hasColumn('categories', 'banner_image')) {
                \Illuminate\Support\Facades\Schema::table('categories', function ($t) {
                    $t->string('banner_image', 500)->nullable()->after('image');
                });
            }
            if (! \Illuminate\Support\Facades\Schema::hasColumn('categories', 'settings')) {
                \Illuminate\Support\Facades\Schema::table('categories', function ($t) {
                    $t->json('settings')->nullable()->after('sort_order');
                });
            }
        } catch (\Throwable) {
            //
        }
    }
}