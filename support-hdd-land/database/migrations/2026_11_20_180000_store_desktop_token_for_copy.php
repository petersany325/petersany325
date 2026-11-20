<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('desktop_devices') || Schema::hasColumn('desktop_devices', 'token_encrypted')) {
            return;
        }

        Schema::table('desktop_devices', function (Blueprint $table) {
            $table->text('token_encrypted')->nullable()->after('token_hash');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('desktop_devices') && Schema::hasColumn('desktop_devices', 'token_encrypted')) {
            Schema::table('desktop_devices', function (Blueprint $table) {
                $table->dropColumn('token_encrypted');
            });
        }
    }
};
