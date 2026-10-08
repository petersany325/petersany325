<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('id')->constrained('menus')->nullOnDelete();
            $table->string('hint', 180)->nullable()->after('url');
            $table->boolean('is_highlighted')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
            $table->dropColumn(['hint', 'is_highlighted']);
        });
    }
};
