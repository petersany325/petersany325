<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('interns') && ! Schema::hasColumn('interns', 'user_id')) {
            Schema::table('interns', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
                $table->index(['user_id']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('interns') && Schema::hasColumn('interns', 'user_id')) {
            Schema::table('interns', function (Blueprint $table) {
                $table->dropConstrainedForeignId('user_id');
            });
        }
    }
};
