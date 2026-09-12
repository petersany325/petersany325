<?php

namespace Plugins\Accounting;

use App\Support\BasePlugin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Plugin extends BasePlugin
{
    public function id(): string
    {
        return 'accounting';
    }

    public function name(): string
    {
        return 'حسابداری و انبار';
    }

    public function description(): string
    {
        return 'فاکتور خرید/فروش با سریال، پیش‌فاکتور، سند دستی، بانک، انبار چندگانه، هزینه، حقوق و گزارش‌ها';
    }

    public function version(): string
    {
        return '1.1.0';
    }

    public function isCore(): bool
    {
        return true;
    }

    public function boot(): void
    {
        static::loadClasses();
        static::ensureSchema();
        static::seedDefaults();
        parent::boot();
    }

    /** Shared hosting without composer dump — load controllers/engine explicitly. */
    public static function loadClasses(): void
    {
        $base = __DIR__.DIRECTORY_SEPARATOR.'src';
        $files = [
            $base.'/Support/AccEngine.php',
            $base.'/Http/Controllers/Admin/HubController.php',
            $base.'/Http/Controllers/Staff/AccountingController.php',
            $base.'/Http/Controllers/Account/InvoiceController.php',
        ];
        foreach ($files as $f) {
            if (is_file($f)) {
                try {
                    require_once $f;
                } catch (\Throwable) {
                }
            }
        }
    }

    /** @return list<array{label:string,route:string,icon?:string,group?:string}> */
    public function adminMenu(): array
    {
        return [
            ['label' => 'میز حسابداری', 'route' => 'admin.accounting.hub', 'icon' => '◈', 'group' => 'accounting'],
            ['label' => 'اسناد مالی', 'route' => 'admin.accounting.docs', 'icon' => '▤', 'group' => 'accounting'],
            ['label' => 'انبارها', 'route' => 'admin.accounting.warehouses', 'icon' => '▣', 'group' => 'warehouse'],
            ['label' => 'حواله و رسید', 'route' => 'admin.accounting.stock', 'icon' => '⇄', 'group' => 'warehouse'],
            ['label' => 'بانک‌ها', 'route' => 'admin.accounting.banks', 'icon' => '₿', 'group' => 'finance'],
            ['label' => 'هزینه‌ها', 'route' => 'admin.accounting.expenses', 'icon' => '📉', 'group' => 'finance'],
            ['label' => 'حقوق و دستمزد', 'route' => 'admin.accounting.payroll', 'icon' => '👥', 'group' => 'hr'],
            ['label' => 'کمیسیون فروش', 'route' => 'admin.accounting.commissions', 'icon' => '%', 'group' => 'hr'],
            ['label' => 'تنظیمات حسابداری', 'route' => 'admin.accounting.settings', 'icon' => '⚙', 'group' => 'settings'],
            ['label' => 'گزارش‌ها', 'route' => 'admin.accounting.reports', 'icon' => '📊', 'group' => 'reports'],
        ];
    }

    public static function ensureSchema(): void
    {
        try {
            if (! Schema::hasTable('acc_warehouses')) {
                Schema::create('acc_warehouses', function ($t) {
                    $t->id();
                    $t->string('code', 40)->unique();
                    $t->string('name');
                    $t->string('city', 80)->nullable();
                    $t->string('address', 255)->nullable();
                    $t->boolean('is_default')->default(false);
                    $t->boolean('is_active')->default(true);
                    $t->timestamps();
                });
            }
            if (! Schema::hasTable('acc_banks')) {
                Schema::create('acc_banks', function ($t) {
                    $t->id();
                    $t->string('name');
                    $t->string('branch', 120)->nullable();
                    $t->string('account_no', 64)->nullable();
                    $t->string('iban', 34)->nullable();
                    $t->string('card_no', 32)->nullable();
                    $t->string('holder', 120)->nullable();
                    $t->string('currency', 8)->default('IRR');
                    $t->bigInteger('opening_balance')->default(0);
                    $t->boolean('is_active')->default(true);
                    $t->timestamps();
                });
            }
            if (! Schema::hasTable('acc_accounts')) {
                Schema::create('acc_accounts', function ($t) {
                    $t->id();
                    $t->string('code', 32)->unique();
                    $t->string('name');
                    $t->string('type', 32)->index();
                    $t->boolean('is_active')->default(true);
                    $t->timestamps();
                });
            }
            if (! Schema::hasTable('acc_expense_categories')) {
                Schema::create('acc_expense_categories', function ($t) {
                    $t->id();
                    $t->string('name');
                    $t->string('code', 32)->nullable();
                    $t->boolean('is_active')->default(true);
                    $t->timestamps();
                });
            }
            if (! Schema::hasTable('acc_documents')) {
                Schema::create('acc_documents', function ($t) {
                    $t->id();
                    $t->string('number', 40)->unique();
                    $t->string('type', 24)->index();
                    $t->string('status', 24)->default('draft')->index();
                    $t->date('doc_date')->nullable();
                    $t->unsignedBigInteger('party_user_id')->nullable()->index();
                    $t->string('party_name')->nullable();
                    $t->unsignedBigInteger('warehouse_id')->nullable()->index();
                    $t->unsignedBigInteger('warehouse_to_id')->nullable();
                    $t->unsignedBigInteger('bank_id')->nullable();
                    $t->unsignedBigInteger('staff_id')->nullable()->index();
                    $t->unsignedBigInteger('category_id')->nullable();
                    $t->unsignedBigInteger('related_id')->nullable();
                    $t->bigInteger('subtotal')->default(0);
                    $t->bigInteger('discount')->default(0);
                    $t->bigInteger('tax')->default(0);
                    $t->bigInteger('total')->default(0);
                    $t->decimal('commission_rate', 5, 2)->default(0);
                    $t->bigInteger('commission_amount')->default(0);
                    $t->string('payment_method', 40)->nullable();
                    $t->text('notes')->nullable();
                    $t->unsignedBigInteger('created_by')->nullable();
                    $t->timestamps();
                });
            }
            if (! Schema::hasTable('acc_document_lines')) {
                Schema::create('acc_document_lines', function ($t) {
                    $t->id();
                    $t->unsignedBigInteger('document_id')->index();
                    $t->unsignedBigInteger('product_id')->nullable()->index();
                    $t->string('title');
                    $t->string('sku', 80)->nullable();
                    $t->decimal('qty', 12, 3)->default(1);
                    $t->bigInteger('unit_price')->default(0);
                    $t->bigInteger('unit_cost')->default(0);
                    $t->bigInteger('line_total')->default(0);
                    $t->unsignedBigInteger('account_id')->nullable();
                    $t->string('side', 8)->nullable();
                    $t->timestamps();
                });
            }
            if (! Schema::hasTable('acc_document_serials')) {
                Schema::create('acc_document_serials', function ($t) {
                    $t->id();
                    $t->unsignedBigInteger('document_id')->index();
                    $t->unsignedBigInteger('line_id')->nullable()->index();
                    $t->unsignedBigInteger('product_id')->nullable()->index();
                    $t->string('serial', 120)->index();
                    $t->string('status', 24)->default('assigned');
                    $t->timestamps();
                });
            }
            if (! Schema::hasTable('acc_stock_balances')) {
                Schema::create('acc_stock_balances', function ($t) {
                    $t->id();
                    $t->unsignedBigInteger('warehouse_id')->index();
                    $t->unsignedBigInteger('product_id')->index();
                    $t->decimal('qty', 14, 3)->default(0);
                    $t->bigInteger('avg_cost')->default(0);
                    $t->timestamps();
                    $t->unique(['warehouse_id', 'product_id']);
                });
            }
            if (! Schema::hasTable('acc_payroll_runs')) {
                Schema::create('acc_payroll_runs', function ($t) {
                    $t->id();
                    $t->string('period', 7)->index();
                    $t->string('status', 24)->default('draft');
                    $t->bigInteger('total_base')->default(0);
                    $t->bigInteger('total_commission')->default(0);
                    $t->bigInteger('total_deduction')->default(0);
                    $t->bigInteger('total_net')->default(0);
                    $t->unsignedBigInteger('created_by')->nullable();
                    $t->timestamps();
                });
            }
            if (! Schema::hasTable('acc_payslips')) {
                Schema::create('acc_payslips', function ($t) {
                    $t->id();
                    $t->unsignedBigInteger('payroll_run_id')->index();
                    $t->unsignedBigInteger('staff_id')->index();
                    $t->string('staff_name');
                    $t->bigInteger('base_salary')->default(0);
                    $t->decimal('commission_rate', 5, 2)->default(0);
                    $t->bigInteger('commission_amount')->default(0);
                    $t->bigInteger('deduction')->default(0);
                    $t->bigInteger('net')->default(0);
                    $t->text('notes')->nullable();
                    $t->timestamps();
                });
            }
        } catch (\Throwable) {
        }
    }

    public static function seedDefaults(): void
    {
        try {
            if (Schema::hasTable('acc_warehouses') && DB::table('acc_warehouses')->count() === 0) {
                DB::table('acc_warehouses')->insert([
                    'code' => 'MAIN',
                    'name' => 'انبار اصلی',
                    'is_default' => true,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            if (Schema::hasTable('acc_accounts') && DB::table('acc_accounts')->count() === 0) {
                foreach ([
                    ['1110', 'صندوق', 'asset'],
                    ['1120', 'بانک', 'asset'],
                    ['1210', 'حساب‌های دریافتنی', 'asset'],
                    ['1310', 'موجودی کالا', 'asset'],
                    ['2110', 'حساب‌های پرداختنی', 'liability'],
                    ['3110', 'سرمایه', 'equity'],
                    ['4110', 'فروش کالا', 'income'],
                    ['5110', 'بهای تمام‌شده', 'expense'],
                    ['5210', 'هزینه‌های عملیاتی', 'expense'],
                    ['5220', 'حقوق و دستمزد', 'expense'],
                    ['5230', 'کمیسیون فروش', 'expense'],
                ] as [$code, $name, $type]) {
                    DB::table('acc_accounts')->insert([
                        'code' => $code, 'name' => $name, 'type' => $type, 'is_active' => true,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            }
            if (Schema::hasTable('acc_expense_categories') && DB::table('acc_expense_categories')->count() === 0) {
                foreach (['اجاره', 'حمل‌ونقل', 'تبلیغات', 'ملزومات', 'تعمیرات', 'سایر'] as $i => $name) {
                    DB::table('acc_expense_categories')->insert([
                        'name' => $name,
                        'code' => 'EX'.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        } catch (\Throwable) {
        }
    }
}
