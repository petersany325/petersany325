<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bank_accounts')) {
            Schema::create('bank_accounts', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('bank_name')->nullable();
                $table->string('account_number', 64)->nullable();
                $table->string('iban', 34)->nullable();
                $table->string('card_number', 32)->nullable();
                $table->string('account_type', 20)->default('transfer'); // cash|card|transfer
                $table->string('gl_code', 20)->nullable(); // 1110/1120/1130
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->string('note', 500)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('fixed_costs')) {
            Schema::create('fixed_costs', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('category', 80)->nullable();
                $table->unsignedBigInteger('amount')->default(0);
                $table->unsignedTinyInteger('day_of_month')->default(1); // 1-28
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->string('pay_method', 20)->default('cash'); // cash|card|transfer
                $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
                $table->string('expense_account_code', 20)->default('5310');
                $table->boolean('is_active')->default(true);
                $table->string('note', 500)->nullable();
                $table->string('last_posted_period', 7)->nullable(); // YYYY-MM
                $table->timestamp('last_posted_at')->nullable();
                $table->timestamps();
            });
        }

        $now = now();
        $exists = DB::table('accounts')->where('code', '5310')->exists();
        if (! $exists && Schema::hasTable('accounts')) {
            DB::table('accounts')->insert([
                'code' => '5310',
                'name' => 'هزینه‌های ثابت / عملیاتی',
                'type' => 'expense',
                'nature' => 'debit',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 110,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fixed_costs');
        Schema::dropIfExists('bank_accounts');
        if (Schema::hasTable('accounts')) {
            DB::table('accounts')->where('code', '5310')->where('is_system', true)->delete();
        }
    }
};
