<?php

namespace Plugins\WarrantyDesk;

use App\Support\BasePlugin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

class Plugin extends BasePlugin
{
    public const SETTINGS_KEY = 'warranty_register_page';

    protected static bool $booted = false;

    public function id(): string
    {
        return 'warranty-desk';
    }

    public function name(): string
    {
        return 'ثبت گارانتی سازمانی';
    }

    public function description(): string
    {
        return 'صفحه ثبت درخواست پوشش گارانتی برای فروشگاه، شرکت و سازمان + کارتابل ادمین';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function isCore(): bool
    {
        return true;
    }

    public function boot(): void
    {
        if (self::$booted) {
            return;
        }
        self::$booted = true;
        static::loadClasses();
        static::registerViews();
        static::ensureSchema();
        static::remapMenuLinks();
        parent::boot();
    }

    /** Safe from MegaMenu / blades when plugin discovery is late or missing. */
    public static function ensureBooted(): void
    {
        try {
            (new static())->boot();
        } catch (\Throwable) {
        }
    }

    public static function loadClasses(): void
    {
        $base = __DIR__.DIRECTORY_SEPARATOR.'src';
        foreach ([
            $base.'/Support/PageCopy.php',
            $base.'/Support/SmsHook.php',
            $base.'/Models/WarrantyRequest.php',
            $base.'/Http/Controllers/StorefrontController.php',
            $base.'/Http/Controllers/Admin/DeskController.php',
        ] as $file) {
            if (is_file($file)) {
                try {
                    require_once $file;
                } catch (\Throwable) {
                }
            }
        }
    }

    public static function registerViews(): void
    {
        $views = __DIR__.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'views';
        if (! is_dir($views)) {
            return;
        }
        View::addNamespace('warranty-desk', $views);
        View::addNamespace('warrantydesk', $views);
    }

    public static function ensureSchema(): void
    {
        try {
            if (! Schema::hasTable('warranty_coverage_requests')) {
                Schema::create('warranty_coverage_requests', function ($t) {
                    $t->id();
                    $t->string('public_code', 24)->unique();
                    $t->unsignedBigInteger('user_id')->nullable()->index();
                    $t->string('applicant_type', 24)->default('shop')->index();
                    $t->string('org_name', 190);
                    $t->string('contact_name', 120);
                    $t->string('mobile', 30)->index();
                    $t->string('phone', 30)->nullable();
                    $t->string('city', 80)->nullable();
                    $t->string('product_kind', 120)->nullable();
                    $t->string('brand_model', 190)->nullable();
                    $t->unsignedInteger('qty')->default(1);
                    $t->text('serials')->nullable();
                    $t->text('notes')->nullable();
                    $t->string('status', 24)->default('submitted')->index();
                    $t->unsignedBigInteger('quote_amount')->nullable();
                    $t->unsignedSmallInteger('quote_months')->nullable();
                    $t->string('package_name', 160)->nullable();
                    $t->text('admin_note')->nullable();
                    $t->text('sms_log')->nullable();
                    $t->unsignedBigInteger('reviewed_by')->nullable();
                    $t->timestamp('reviewed_at')->nullable();
                    $t->timestamps();
                });
            }
        } catch (\Throwable) {
        }
    }

    /** Connect «ثبت گارانتی» menu rows to the dedicated page. */
    public static function remapMenuLinks(): void
    {
        try {
            if (! Schema::hasTable('mega_menu_items')) {
                return;
            }

            DB::table('mega_menu_items')
                ->where(function ($q) {
                    $q->where('title', 'ثبت گارانتی')
                        ->orWhere('title', 'like', '%ثبت گارانتی%');
                })
                ->where(function ($q) {
                    $q->whereNull('url')
                        ->orWhere('url', '')
                        ->orWhere('url', '#')
                        ->orWhere('url', '/serial-check')
                        ->orWhere('url', 'serial-check')
                        ->orWhere('url', '/serial-check/');
                })
                ->update(['url' => '/warranty-register', 'updated_at' => now()]);

            $parent = DB::table('mega_menu_items')
                ->whereNull('parent_id')
                ->where(function ($q) {
                    $q->where('title', 'گارانتی')
                        ->orWhere('title', 'like', 'گارانتی %');
                })
                ->orderBy('id')
                ->first();

            if (! $parent) {
                return;
            }

            $child = DB::table('mega_menu_items')
                ->where('parent_id', $parent->id)
                ->where(function ($q) {
                    $q->where('title', 'ثبت گارانتی')
                        ->orWhere('url', '/warranty-register');
                })
                ->first();

            $now = now();
            if (! $child) {
                $maxSort = (int) DB::table('mega_menu_items')->where('parent_id', $parent->id)->max('sort_order');
                DB::table('mega_menu_items')->insert([
                    'parent_id' => $parent->id,
                    'title' => 'ثبت گارانتی',
                    'type' => 'link',
                    'url' => '/warranty-register',
                    'description' => 'درخواست پوشش گارانتی برای فروشگاه و سازمان',
                    'is_mega' => false,
                    'is_active' => true,
                    'sort_order' => $maxSort + 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } elseif (trim((string) ($child->url ?? '')) !== '/warranty-register') {
                DB::table('mega_menu_items')->where('id', $child->id)->update([
                    'title' => 'ثبت گارانتی',
                    'url' => '/warranty-register',
                    'is_active' => true,
                    'updated_at' => $now,
                ]);
            }
        } catch (\Throwable) {
        }
    }
}
