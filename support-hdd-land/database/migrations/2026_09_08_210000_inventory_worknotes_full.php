<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reception_work_reports', function (Blueprint $table) {
            if (! Schema::hasColumn('reception_work_reports', 'visibility')) {
                $table->string('visibility', 20)->default('internal')->after('result_status')->index();
            }
        });

        if (! Schema::hasTable('part_categories')) {
            Schema::create('part_categories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('parent_id')->nullable()->constrained('part_categories')->nullOnDelete();
                $table->string('name');
                $table->string('item_type', 20)->default('repair')->index(); // shop|repair|labor
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('price_tiers')) {
            Schema::create('price_tiers', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code', 40)->nullable()->unique();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        Schema::table('parts', function (Blueprint $table) {
            if (! Schema::hasColumn('parts', 'category_id')) {
                $table->foreignId('category_id')->nullable()->after('warehouse_id')->constrained('part_categories')->nullOnDelete();
            }
            if (! Schema::hasColumn('parts', 'item_type')) {
                $table->string('item_type', 20)->default('repair')->after('category_id')->index();
            }
            if (! Schema::hasColumn('parts', 'tech_code')) {
                $table->string('tech_code', 80)->nullable()->after('code')->index();
            }
            if (! Schema::hasColumn('parts', 'barcode')) {
                $table->string('barcode', 80)->nullable()->after('tech_code')->index();
            }
            if (! Schema::hasColumn('parts', 'keywords')) {
                $table->string('keywords', 500)->nullable()->after('model');
            }
            if (! Schema::hasColumn('parts', 'description')) {
                $table->text('description')->nullable()->after('keywords');
            }
            if (! Schema::hasColumn('parts', 'discount_percent')) {
                $table->unsignedTinyInteger('discount_percent')->default(0)->after('sale_price');
            }
            if (! Schema::hasColumn('parts', 'sale_commission_percent')) {
                $table->unsignedTinyInteger('sale_commission_percent')->default(0)->after('discount_percent');
            }
            if (! Schema::hasColumn('parts', 'repair_commission_percent')) {
                $table->unsignedTinyInteger('repair_commission_percent')->default(0)->after('sale_commission_percent');
            }
            if (! Schema::hasColumn('parts', 'usage_count')) {
                $table->unsignedInteger('usage_count')->default(0)->after('min_stock');
            }
        });

        if (! Schema::hasTable('part_prices')) {
            Schema::create('part_prices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('part_id')->constrained()->cascadeOnDelete();
                $table->foreignId('price_tier_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('price')->default(0);
                $table->timestamps();
                $table->unique(['part_id', 'price_tier_id']);
            });
        }

        if (! Schema::hasTable('warehouse_transfers')) {
            Schema::create('warehouse_transfers', function (Blueprint $table) {
                $table->id();
                $table->string('doc_no', 40)->nullable()->index();
                $table->foreignId('from_warehouse_id')->constrained('warehouses')->cascadeOnDelete();
                $table->foreignId('to_warehouse_id')->constrained('warehouses')->cascadeOnDelete();
                $table->foreignId('part_id')->constrained()->cascadeOnDelete();
                $table->unsignedInteger('quantity');
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('note', 500)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('stocktakes')) {
            Schema::create('stocktakes', function (Blueprint $table) {
                $table->id();
                $table->string('doc_no', 40)->nullable()->index();
                $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
                $table->string('status', 20)->default('draft')->index(); // draft|posted
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('posted_at')->nullable();
                $table->string('note', 500)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('stocktake_lines')) {
            Schema::create('stocktake_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('stocktake_id')->constrained()->cascadeOnDelete();
                $table->foreignId('part_id')->constrained()->cascadeOnDelete();
                $table->integer('system_qty')->default(0);
                $table->integer('counted_qty')->default(0);
                $table->integer('diff_qty')->default(0);
                $table->string('note', 255)->nullable();
                $table->timestamps();
            });
        }

        Schema::table('stock_movements', function (Blueprint $table) {
            // extend doc types via app const; no schema change required
        });

        // Seed default price tiers if empty — done in app boot / controller first visit.
    }

    public function down(): void
    {
        Schema::dropIfExists('stocktake_lines');
        Schema::dropIfExists('stocktakes');
        Schema::dropIfExists('warehouse_transfers');
        Schema::dropIfExists('part_prices');

        Schema::table('parts', function (Blueprint $table) {
            foreach ([
                'category_id', 'item_type', 'tech_code', 'barcode', 'keywords', 'description',
                'discount_percent', 'sale_commission_percent', 'repair_commission_percent', 'usage_count',
            ] as $col) {
                if (Schema::hasColumn('parts', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::dropIfExists('price_tiers');
        Schema::dropIfExists('part_categories');

        Schema::table('reception_work_reports', function (Blueprint $table) {
            if (Schema::hasColumn('reception_work_reports', 'visibility')) {
                $table->dropColumn('visibility');
            }
        });
    }
};
