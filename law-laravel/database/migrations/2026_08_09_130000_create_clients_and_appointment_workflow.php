<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (! Schema::hasColumn('appointments', 'viewed_at')) {
                $table->timestamp('viewed_at')->nullable()->after('status');
            }
            if (! Schema::hasColumn('appointments', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('viewed_at');
            }
            if (! Schema::hasColumn('appointments', 'admin_note')) {
                $table->text('admin_note')->nullable()->after('notes');
            }
        });

        if (! Schema::hasTable('clients')) {
            Schema::create('clients', function (Blueprint $table) {
                $table->id();
                $table->string('first_name');
                $table->string('last_name');
                $table->string('father_name')->nullable();
                $table->string('national_code', 10)->nullable()->index();
                $table->string('id_number')->nullable();
                $table->string('phone');
                $table->string('mobile')->nullable();
                $table->string('email')->nullable();
                $table->text('address')->nullable();
                $table->string('case_type')->nullable(); // حقوقی، کیفری، خانواده، تجاری، ...
                $table->string('subject'); // موضوع وکالت
                $table->string('opponent')->nullable(); // طرف دعوا
                $table->string('referrer')->nullable(); // معرف
                $table->text('description')->nullable(); // شرح اظهارات / توضیحات
                $table->unsignedBigInteger('fee_agreed')->nullable(); // مبلغ توافقی حق‌الوکاله (ریال)
                $table->unsignedBigInteger('fee_paid')->nullable(); // مبلغ طی‌شده / پرداخت‌شده
                $table->string('fee_method')->nullable(); // نقدی، کارت، چک، ...
                $table->date('contract_date')->nullable();
                $table->string('contract_no')->nullable();
                $table->string('status')->default('draft'); // draft, confirmed, active, closed
                $table->timestamp('confirmed_at')->nullable();
                $table->text('admin_note')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');

        Schema::table('appointments', function (Blueprint $table) {
            foreach (['viewed_at', 'archived_at', 'admin_note'] as $col) {
                if (Schema::hasColumn('appointments', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
