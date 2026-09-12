<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('type', 20); // asset, liability, equity, income, expense
            $table->string('nature', 10)->default('debit'); // debit|credit
            $table->boolean('is_system')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->string('entry_no')->unique();
            $table->date('entry_date');
            $table->string('description');
            $table->string('source_type', 40)->nullable()->index(); // payment, reception_revenue, reception_part, manual, stock
            $table->unsignedBigInteger('source_id')->nullable()->index();
            $table->foreignId('reception_id')->nullable()->constrained('receptions')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('total_amount')->default(0);
            $table->timestamps();
            $table->unique(['source_type', 'source_id']);
        });

        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts')->restrictOnDelete();
            $table->unsignedBigInteger('debit')->default(0);
            $table->unsignedBigInteger('credit')->default(0);
            $table->string('memo')->nullable();
            $table->timestamps();
        });

        $now = now();
        $rows = [
            ['1110', 'صندوق نقد', 'asset', 'debit', 10],
            ['1120', 'کارتخوان / بانک', 'asset', 'debit', 20],
            ['1130', 'کارت‌به‌کارت', 'asset', 'debit', 30],
            ['1210', 'حساب‌های دریافتنی مشتریان', 'asset', 'debit', 40],
            ['1310', 'موجودی قطعات', 'asset', 'debit', 50],
            ['4110', 'درآمد خدمات تعمیر', 'income', 'credit', 60],
            ['4120', 'درآمد فروش قطعات', 'income', 'credit', 70],
            ['4130', 'درآمد حق پذیرش', 'income', 'credit', 80],
            ['5110', 'بهای تمام‌شده قطعات', 'expense', 'debit', 90],
            ['5210', 'تخفیف اعطایی', 'expense', 'debit', 100],
        ];

        foreach ($rows as [$code, $name, $type, $nature, $sort]) {
            DB::table('accounts')->insert([
                'code' => $code,
                'name' => $name,
                'type' => $type,
                'nature' => $nature,
                'is_system' => true,
                'is_active' => true,
                'sort_order' => $sort,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('accounts');
    }
};
