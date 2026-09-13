<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('phone', 30)->nullable()->index();
            $table->string('shop_name', 160)->nullable();
            $table->string('code', 40)->nullable()->index();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('receptions', function (Blueprint $table) {
            $table->foreignId('partner_id')->nullable()->after('customer_id')->constrained('partners')->nullOnDelete();
            $table->string('partner_flow', 30)->nullable()->after('partner_id')->index(); // inbound|outbound|returned
            $table->string('partner_peer_receipt_no', 60)->nullable()->after('partner_flow');
            $table->string('partner_end_customer_note', 190)->nullable()->after('partner_peer_receipt_no');
            $table->foreignId('partner_referred_to_id')->nullable()->after('partner_end_customer_note')->constrained('partners')->nullOnDelete();
            $table->timestamp('partner_referred_at')->nullable()->after('partner_referred_to_id');
            $table->timestamp('partner_returned_at')->nullable()->after('partner_referred_at');
        });
    }

    public function down(): void
    {
        Schema::table('receptions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('partner_referred_to_id');
            $table->dropConstrainedForeignId('partner_id');
            $table->dropColumn([
                'partner_flow',
                'partner_peer_receipt_no',
                'partner_end_customer_note',
                'partner_referred_at',
                'partner_returned_at',
            ]);
        });
        Schema::dropIfExists('partners');
    }
};
