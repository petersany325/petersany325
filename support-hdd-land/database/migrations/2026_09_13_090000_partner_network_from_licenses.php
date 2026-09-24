<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            if (! Schema::hasColumn('partners', 'domain')) {
                $table->string('domain', 190)->nullable()->after('code')->index();
            }
            if (! Schema::hasColumn('partners', 'license_key')) {
                $table->string('license_key', 64)->nullable()->after('domain')->index();
            }
            if (! Schema::hasColumn('partners', 'source')) {
                $table->string('source', 30)->default('license')->after('license_key')->index();
            }
            if (! Schema::hasColumn('partners', 'last_synced_at')) {
                $table->timestamp('last_synced_at')->nullable()->after('is_active');
            }
        });

        Schema::table('receptions', function (Blueprint $table) {
            if (! Schema::hasColumn('receptions', 'partner_approval_status')) {
                $table->string('partner_approval_status', 30)->nullable()->after('partner_returned_at')->index();
            }
            if (! Schema::hasColumn('receptions', 'partner_network_ref')) {
                $table->string('partner_network_ref', 64)->nullable()->after('partner_approval_status')->unique();
            }
            if (! Schema::hasColumn('receptions', 'partner_reject_reason')) {
                $table->string('partner_reject_reason', 500)->nullable()->after('partner_network_ref');
            }
            if (! Schema::hasColumn('receptions', 'partner_payload')) {
                $table->json('partner_payload')->nullable()->after('partner_reject_reason');
            }
        });

        if (! Schema::hasTable('network_referrals')) {
            Schema::create('network_referrals', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('from_license_id')->nullable()->constrained('product_licenses')->nullOnDelete();
                $table->foreignId('to_license_id')->nullable()->constrained('product_licenses')->nullOnDelete();
                $table->string('from_domain', 190)->nullable()->index();
                $table->string('to_domain', 190)->nullable()->index();
                $table->string('from_shop_name', 190)->nullable();
                $table->string('to_shop_name', 190)->nullable();
                $table->string('origin_receipt_no', 60)->nullable()->index();
                $table->string('origin_ticket_no', 60)->nullable();
                $table->string('dest_receipt_no', 60)->nullable();
                $table->string('status', 30)->default('pending')->index(); // pending|awaiting_decision|accepted|rejected|cancelled
                $table->json('payload');
                $table->string('reject_reason', 500)->nullable();
                $table->timestamp('pulled_at')->nullable();
                $table->timestamp('decided_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('network_referrals');
        Schema::table('receptions', function (Blueprint $table) {
            foreach (['partner_approval_status', 'partner_network_ref', 'partner_reject_reason', 'partner_payload'] as $col) {
                if (Schema::hasColumn('receptions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
        Schema::table('partners', function (Blueprint $table) {
            foreach (['domain', 'license_key', 'source', 'last_synced_at'] as $col) {
                if (Schema::hasColumn('partners', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
