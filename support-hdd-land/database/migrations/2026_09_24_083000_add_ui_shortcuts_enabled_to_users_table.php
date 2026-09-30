<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'ui_shortcuts_enabled')) {
                $table->boolean('ui_shortcuts_enabled')->default(true)->after('ui_shortcuts');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'ui_shortcuts_enabled')) {
                $table->dropColumn('ui_shortcuts_enabled');
            }
        });
    }
};
