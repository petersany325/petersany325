<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Soft-delete (recycle bin) for receptions and journal entries.
 * Standard: delete → trash; restore or force-delete permanently.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('receptions', 'deleted_at')) {
            Schema::table('receptions', function (Blueprint $table) {
                $table->softDeletes();
                $table->foreignId('deleted_by')->nullable()->after('deleted_at')->constrained('users')->nullOnDelete();
                $table->string('delete_reason')->nullable()->after('deleted_by');
            });
        }

        if (! Schema::hasColumn('journal_entries', 'deleted_at')) {
            Schema::table('journal_entries', function (Blueprint $table) {
                $table->softDeletes();
                $table->foreignId('deleted_by')->nullable()->after('deleted_at')->constrained('users')->nullOnDelete();
                $table->string('delete_reason')->nullable()->after('deleted_by');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('receptions', 'deleted_at')) {
            Schema::table('receptions', function (Blueprint $table) {
                $table->dropConstrainedForeignId('deleted_by');
                $table->dropColumn(['deleted_at', 'delete_reason']);
            });
        }
        if (Schema::hasColumn('journal_entries', 'deleted_at')) {
            Schema::table('journal_entries', function (Blueprint $table) {
                $table->dropConstrainedForeignId('deleted_by');
                $table->dropColumn(['deleted_at', 'delete_reason']);
            });
        }
    }
};
