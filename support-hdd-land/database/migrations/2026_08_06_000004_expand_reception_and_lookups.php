<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lookup_options', function (Blueprint $table) {
            $table->id();
            $table->string('group_key', 50)->index(); // admission_type, service_type, ...
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('receptions', function (Blueprint $table) {
            $table->string('receipt_no')->nullable()->after('ticket_no');
            $table->string('account_code')->nullable()->after('receipt_no');
            $table->string('admission_type')->nullable()->after('account_code');
            $table->string('service_type')->nullable()->after('admission_type');
            $table->string('repair_type')->nullable()->after('service_type');
            $table->string('delivered_by')->nullable()->after('repair_type');
            $table->string('referrer')->nullable()->after('delivered_by');
            $table->unsignedInteger('commission')->default(0)->after('referrer');
            $table->string('photo_path')->nullable()->after('commission');
            $table->text('appearance_notes')->nullable()->after('accessories');
            $table->string('hdd_capacity')->nullable()->after('appearance_notes');
            $table->boolean('warranty_return')->default(false)->after('hdd_capacity');
            $table->string('warranty_type')->nullable()->after('warranty_return');
            $table->string('card_number')->nullable()->after('warranty_type');
            $table->date('warranty_end_date')->nullable()->after('card_number');
            $table->unsignedBigInteger('pos_amount')->default(0)->after('deposit');
            $table->unsignedBigInteger('admission_fee')->default(0)->after('pos_amount');
            $table->unsignedBigInteger('estimated_cost')->default(0)->after('admission_fee');
            $table->date('estimated_delivery_at')->nullable()->after('estimated_cost');
            $table->date('next_visit_at')->nullable()->after('estimated_delivery_at');
            $table->string('payment_method')->nullable()->after('next_visit_at');
        });
    }

    public function down(): void
    {
        Schema::table('receptions', function (Blueprint $table) {
            $table->dropColumn([
                'receipt_no', 'account_code', 'admission_type', 'service_type', 'repair_type',
                'delivered_by', 'referrer', 'commission', 'photo_path', 'appearance_notes',
                'hdd_capacity', 'warranty_return', 'warranty_type', 'card_number', 'warranty_end_date',
                'pos_amount', 'admission_fee', 'estimated_cost', 'estimated_delivery_at',
                'next_visit_at', 'payment_method',
            ]);
        });
        Schema::dropIfExists('lookup_options');
    }
};
