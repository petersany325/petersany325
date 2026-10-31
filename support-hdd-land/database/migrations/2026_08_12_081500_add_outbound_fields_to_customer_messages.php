<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_messages', function (Blueprint $table) {
            if (! Schema::hasColumn('customer_messages', 'direction')) {
                $table->string('direction', 16)->default('inbound')->after('priority'); // inbound|outbound
            }
            if (! Schema::hasColumn('customer_messages', 'remote_part_preorder_id')) {
                $table->foreignId('remote_part_preorder_id')->nullable()->after('reception_id')
                    ->constrained('remote_part_preorders')->nullOnDelete();
            }
            if (! Schema::hasColumn('customer_messages', 'customer_read_at')) {
                $table->timestamp('customer_read_at')->nullable()->after('staff_read_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customer_messages', function (Blueprint $table) {
            if (Schema::hasColumn('customer_messages', 'remote_part_preorder_id')) {
                $table->dropConstrainedForeignId('remote_part_preorder_id');
            }
            if (Schema::hasColumn('customer_messages', 'direction')) {
                $table->dropColumn('direction');
            }
            if (Schema::hasColumn('customer_messages', 'customer_read_at')) {
                $table->dropColumn('customer_read_at');
            }
        });
    }
};
