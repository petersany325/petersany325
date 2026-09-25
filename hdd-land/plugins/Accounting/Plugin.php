<?php

namespace Plugins\Accounting;

use App\Support\BasePlugin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Plugins\Accounting\src\Support\AccChart;
use Plugins\Accounting\src\Support\AccCommerce;
use Plugins\Accounting\src\Support\AccRoutes;

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
        return 'یک حقیقت فروش: سفارش فروشگاه، فاکتور، اقساط و موجودی کالا روی یک سند حسابداری';
    }

    public function version(): string
    {
        return '1.4.3';
    }

    public function isCore(): bool
    {
        return true;
    }

    public function boot(): void
    {
        try {
            static::loadClasses();
        } catch (\Throwable) {
        }
        try {
            static::ensureSchema();
        } catch (\Throwable) {
        }
        try {
            static::seedDefaults();
        } catch (\Throwable) {
        }
        try {
            AccCommerce::boot();
        } catch (\Throwable) {
        }
        try {
            AccRoutes::registerCustomer();
        } catch (\Throwable) {
        }
        try {
            parent::boot();
        } catch (\Throwable) {
        }
        try {
            AccRoutes::registerAdminFallbacks();
        } catch (\Throwable) {
        }
    }

    /** Shared hosting without composer dump — load controllers/engine explicitly. */
    public static function loadClasses(): void
    {
        $base = __DIR__.DIRECTORY_SEPARATOR.'src';
        $files = [
            $base.'/Support/AccMath.php',
            $base.'/Support/AccChart.php',
            $base.'/Support/AccJournal.php',
            $base.'/Support/AccCommerce.php',
            $base.'/Support/AccRoutes.php',
            $base.'/Support/AccSafe.php',
            $base.'/Support/AccountingLedger.php',
            $base.'/Support/AccEngine.php',
            $base.'/Http/Controllers/Admin/HubController.php',
            $base.'/Http/Controllers/Admin/ChartController.php',
            $base.'/Http/Controllers/Admin/StaffController.php',
            $base.'/Http/Controllers/Admin/ReportController.php',
            $base.'/Http/Controllers/Admin/CheckController.php',
            $base.'/Http/Controllers/Admin/InstallmentController.php',
            $base.'/Http/Controllers/Staff/AccountingController.php',
            $base.'/Http/Controllers/Account/InvoiceController.php',
            $base.'/Http/Controllers/Account/InstallmentController.php',
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

    /** @return list<array{label:string,href:string,icon?:string,group?:string}> */
    public function adminMenu(): array
    {
        $h = static fn (string $path) => '/admin/accounting'.($path === '' ? '' : '/'.$path);

        return [
            ['label' => 'داشبورد حسابداری', 'href' => $h(''), 'icon' => '◈', 'group' => 'accounting'],
            ['label' => 'کدینگ حساب‌ها', 'href' => $h('chart'), 'icon' => '☰', 'group' => 'accounting'],
            ['label' => 'فاکتور و پیش‌فاکتور', 'href' => $h('docs'), 'icon' => '▤', 'group' => 'accounting'],
            ['label' => 'فاکتور فروش جدید', 'href' => $h('docs/create').'?type=sale', 'icon' => '＋', 'group' => 'accounting'],
            ['label' => 'پیش‌فاکتور جدید', 'href' => $h('docs/create').'?type=proforma', 'icon' => '＋', 'group' => 'accounting'],
            ['label' => 'فاکتور خرید', 'href' => $h('docs/create').'?type=purchase', 'icon' => '＋', 'group' => 'accounting'],
            ['label' => 'ثبت سند دستی', 'href' => $h('docs/create').'?type=voucher', 'icon' => '✎', 'group' => 'accounting'],
            ['label' => 'تعریف کالا و سریال', 'href' => $h('goods'), 'icon' => '▦', 'group' => 'warehouse'],
            ['label' => 'انبارها', 'href' => $h('warehouses'), 'icon' => '▣', 'group' => 'warehouse'],
            ['label' => 'حواله و رسید', 'href' => $h('stock'), 'icon' => '⇄', 'group' => 'warehouse'],
            ['label' => 'بانک‌ها', 'href' => $h('banks'), 'icon' => '₿', 'group' => 'finance'],
            ['label' => 'هزینه‌ها', 'href' => $h('expenses'), 'icon' => '📉', 'group' => 'finance'],
            ['label' => 'کارمند و ویزیتور', 'href' => $h('staff'), 'icon' => '👤', 'group' => 'hr'],
            ['label' => 'حقوق و دستمزد', 'href' => $h('payroll'), 'icon' => '👥', 'group' => 'hr'],
            ['label' => 'دسته چک', 'href' => $h('checkbooks'), 'icon' => '▤', 'group' => 'finance'],
            ['label' => 'چک دریافتی از مشتری', 'href' => $h('checks/received'), 'icon' => '▭', 'group' => 'finance'],
            ['label' => 'چک خرج‌شده', 'href' => $h('checks/spent'), 'icon' => '↗', 'group' => 'finance'],
            ['label' => 'اخطار سررسید چک', 'href' => $h('checks/alerts'), 'icon' => '⚠', 'group' => 'finance'],
            ['label' => 'همه چک‌ها', 'href' => $h('checks'), 'icon' => '▭', 'group' => 'finance'],
            ['label' => 'اقساط مشتریان', 'href' => $h('installments'), 'icon' => '◫', 'group' => 'finance'],
            ['label' => 'مرکز گزارش‌ها', 'href' => $h('reports'), 'icon' => '📊', 'group' => 'reports'],
            ['label' => 'گزارش خرید و فروش', 'href' => $h('reports/sales'), 'icon' => '📈', 'group' => 'reports'],
            ['label' => 'تراز آزمایشی', 'href' => $h('reports/trial'), 'icon' => '⚖', 'group' => 'reports'],
            ['label' => 'سود و زیان', 'href' => $h('reports/income'), 'icon' => '📈', 'group' => 'reports'],
            ['label' => 'ترازنامه', 'href' => $h('reports/balance'), 'icon' => '▤', 'group' => 'reports'],
            ['label' => 'تطبیق موجودی سایت', 'href' => $h('reports/shop-stock'), 'icon' => '▦', 'group' => 'reports'],
            ['label' => 'تنظیمات حسابداری', 'href' => $h('settings'), 'icon' => '⚙', 'group' => 'settings'],
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
                    $t->unsignedBigInteger('parent_id')->nullable()->index();
                    $t->string('level', 16)->nullable()->index();
                    $t->string('nature', 16)->nullable();
                    $t->boolean('is_postable')->default(false);
                    $t->boolean('is_system')->default(false);
                    $t->unsignedInteger('sort')->default(0);
                    $t->boolean('is_active')->default(true);
                    $t->timestamps();
                });
            }
            static::ensureColumn('acc_accounts', 'parent_id', function ($t) {
                $t->unsignedBigInteger('parent_id')->nullable()->index();
            });
            static::ensureColumn('acc_accounts', 'level', function ($t) {
                $t->string('level', 16)->nullable()->index();
            });
            static::ensureColumn('acc_accounts', 'nature', function ($t) {
                $t->string('nature', 16)->nullable();
            });
            static::ensureColumn('acc_accounts', 'is_postable', function ($t) {
                $t->boolean('is_postable')->default(false);
            });
            static::ensureColumn('acc_accounts', 'is_system', function ($t) {
                $t->boolean('is_system')->default(false);
            });
            static::ensureColumn('acc_accounts', 'sort', function ($t) {
                $t->unsignedInteger('sort')->default(0);
            });
            if (! Schema::hasTable('acc_settings')) {
                Schema::create('acc_settings', function ($t) {
                    $t->id();
                    $t->string('k', 64)->unique();
                    $t->string('v', 64)->nullable();
                    $t->timestamps();
                });
            }
            if (! Schema::hasTable('acc_journal_entries')) {
                Schema::create('acc_journal_entries', function ($t) {
                    $t->id();
                    $t->string('number', 40)->unique();
                    $t->date('entry_date')->nullable()->index();
                    $t->string('source', 40)->index();
                    $t->unsignedBigInteger('source_id')->nullable()->index();
                    $t->unsignedBigInteger('document_id')->nullable()->index();
                    $t->string('description', 255)->nullable();
                    $t->string('status', 16)->default('posted')->index();
                    $t->unsignedBigInteger('created_by')->nullable();
                    $t->timestamps();
                });
            }
            if (! Schema::hasTable('acc_journal_lines')) {
                Schema::create('acc_journal_lines', function ($t) {
                    $t->id();
                    $t->unsignedBigInteger('entry_id')->index();
                    $t->unsignedBigInteger('account_id')->index();
                    $t->string('account_code', 32)->nullable()->index();
                    $t->bigInteger('debit')->default(0);
                    $t->bigInteger('credit')->default(0);
                    $t->string('memo', 255)->nullable();
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
            if (! Schema::hasTable('acc_checks')) {
                Schema::create('acc_checks', function ($t) {
                    $t->id();
                    $t->string('number', 64)->index();
                    $t->string('direction', 16)->index(); // receivable | payable
                    $t->string('status', 24)->default('pending')->index(); // pending, received, paid, returned, delivered, bounced, cancelled
                    $t->string('bank_name', 120)->nullable();
                    $t->string('branch', 120)->nullable();
                    $t->string('account_no', 64)->nullable();
                    $t->string('sayad', 64)->nullable();
                    $t->string('party_name')->nullable();
                    $t->unsignedBigInteger('party_user_id')->nullable()->index();
                    $t->unsignedBigInteger('bank_id')->nullable();
                    $t->unsignedBigInteger('document_id')->nullable()->index();
                    $t->unsignedBigInteger('staff_id')->nullable();
                    $t->bigInteger('amount')->default(0);
                    $t->date('issue_date')->nullable();
                    $t->date('due_date')->nullable()->index();
                    $t->date('clear_date')->nullable();
                    $t->text('notes')->nullable();
                    $t->unsignedBigInteger('created_by')->nullable();
                    $t->timestamps();
                });
            }
            if (! Schema::hasTable('acc_installment_requests')) {
                Schema::create('acc_installment_requests', function ($t) {
                    $t->id();
                    $t->string('number', 40)->unique();
                    $t->unsignedBigInteger('user_id')->nullable()->index();
                    $t->string('customer_name');
                    $t->string('customer_mobile', 30)->nullable();
                    $t->string('customer_national_id', 20)->nullable();
                    $t->string('product_title');
                    $t->unsignedBigInteger('product_id')->nullable();
                    $t->bigInteger('product_price')->default(0);
                    $t->bigInteger('down_payment')->default(0);
                    $t->unsignedSmallInteger('months')->default(3);
                    $t->bigInteger('monthly_amount')->default(0);
                    $t->bigInteger('total_amount')->default(0);
                    $t->string('status', 24)->default('pending')->index(); // pending, reviewing, approved, rejected, active, completed, cancelled
                    $t->unsignedBigInteger('ticket_id')->nullable()->index();
                    $t->unsignedBigInteger('approved_by')->nullable();
                    $t->text('customer_note')->nullable();
                    $t->text('admin_note')->nullable();
                    $t->timestamps();
                });
            }
            if (! Schema::hasTable('acc_installment_schedules')) {
                Schema::create('acc_installment_schedules', function ($t) {
                    $t->id();
                    $t->unsignedBigInteger('request_id')->index();
                    $t->unsignedSmallInteger('installment_no');
                    $t->date('due_date')->nullable();
                    $t->bigInteger('amount')->default(0);
                    $t->string('status', 24)->default('pending'); // pending, paid, overdue, waived
                    $t->date('paid_at')->nullable();
                    $t->bigInteger('paid_amount')->default(0);
                    $t->text('notes')->nullable();
                    $t->timestamps();
                });
            }
            static::ensureColumn('acc_documents', 'order_id', function ($t) {
                $t->unsignedBigInteger('order_id')->nullable()->index();
            });
            static::ensureColumn('acc_documents', 'source', function ($t) {
                $t->string('source', 24)->nullable()->index();
            });
            static::ensureColumn('acc_installment_requests', 'document_id', function ($t) {
                $t->unsignedBigInteger('document_id')->nullable()->index();
            });
            static::ensureColumn('acc_installment_requests', 'order_id', function ($t) {
                $t->unsignedBigInteger('order_id')->nullable()->index();
            });
            static::ensureColumn('acc_document_lines', 'unit', function ($t) {
                $t->string('unit', 24)->nullable();
            });
            static::ensureColumn('acc_document_lines', 'vat_rate', function ($t) {
                $t->decimal('vat_rate', 6, 2)->default(0);
            });
            static::ensureColumn('acc_document_lines', 'discount_rate', function ($t) {
                $t->decimal('discount_rate', 6, 2)->default(0);
            });
            static::ensureColumn('acc_document_lines', 'tafsil', function ($t) {
                $t->string('tafsil', 160)->nullable();
            });
            static::ensureColumn('acc_checks', 'checkbook_id', function ($t) {
                $t->unsignedBigInteger('checkbook_id')->nullable()->index();
            });
            static::ensureColumn('acc_checks', 'endorsed_to', function ($t) {
                $t->string('endorsed_to', 190)->nullable();
            });
            static::ensureColumn('acc_checks', 'alert_days', function ($t) {
                $t->unsignedSmallInteger('alert_days')->nullable();
            });
            if (! Schema::hasTable('acc_checkbooks')) {
                Schema::create('acc_checkbooks', function ($t) {
                    $t->id();
                    $t->string('owner_type', 16)->default('company')->index(); // company | person
                    $t->string('owner_name');
                    $t->string('bank_name', 120)->nullable();
                    $t->unsignedBigInteger('bank_id')->nullable();
                    $t->string('series_from', 40)->nullable();
                    $t->string('series_to', 40)->nullable();
                    $t->unsignedInteger('leaf_count')->default(0);
                    $t->unsignedInteger('used_count')->default(0);
                    $t->unsignedSmallInteger('alert_days')->default(3);
                    $t->boolean('is_active')->default(true);
                    $t->text('notes')->nullable();
                    $t->timestamps();
                });
            }
            if (! Schema::hasTable('acc_party_alerts')) {
                Schema::create('acc_party_alerts', function ($t) {
                    $t->id();
                    $t->string('party_name')->nullable();
                    $t->unsignedBigInteger('party_user_id')->nullable()->index();
                    $t->string('owner_type', 16)->default('person'); // company | person
                    $t->unsignedSmallInteger('alert_days')->default(3);
                    $t->timestamps();
                });
            }
            if (Schema::hasTable('staff_members')) {
                static::ensureColumn('staff_members', 'kind', function ($t) {
                    $t->string('kind', 16)->default('employee')->index();
                });
                static::ensureColumn('staff_members', 'profit_rate', function ($t) {
                    $t->decimal('profit_rate', 5, 2)->default(0);
                });
                static::ensureColumn('staff_members', 'check_alert_days', function ($t) {
                    $t->unsignedSmallInteger('check_alert_days')->default(3);
                });
            }
            if (Schema::hasTable('products')) {
                static::ensureColumn('products', 'unit', function ($t) {
                    $t->string('unit', 24)->nullable();
                });
                static::ensureColumn('products', 'vat_rate', function ($t) {
                    $t->decimal('vat_rate', 6, 2)->default(0);
                });
                static::ensureColumn('products', 'min_qty', function ($t) {
                    $t->decimal('min_qty', 12, 3)->default(0);
                });
                if (! Schema::hasColumn('products', 'barcode')) {
                    static::ensureColumn('products', 'barcode', function ($t) {
                        $t->string('barcode', 64)->nullable()->index();
                    });
                }
            }
        } catch (\Throwable) {
        }
    }

    /** @param  callable(\Illuminate\Database\Schema\Blueprint):void  $add */
    protected static function ensureColumn(string $table, string $column, callable $add): void
    {
        if (Schema::hasTable($table) && ! Schema::hasColumn($table, $column)) {
            Schema::table($table, $add);
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
            if (Schema::hasTable('acc_accounts')) {
                try {
                    AccChart::seed();
                } catch (\Throwable) {
                }
            }
            if (Schema::hasTable('acc_settings') && ! DB::table('acc_settings')->where('k', 'check_alert_days')->exists()) {
                DB::table('acc_settings')->insert([
                    'k' => 'check_alert_days',
                    'v' => '3',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
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
