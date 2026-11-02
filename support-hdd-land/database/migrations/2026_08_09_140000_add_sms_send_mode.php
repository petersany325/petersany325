<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_status_rules', function (Blueprint $table) {
            $table->string('send_mode', 20)->default('always')->after('auto_send');
        });

        // Preserve existing yes/no behavior.
        DB::table('sms_status_rules')->where('auto_send', 1)->update(['send_mode' => 'always']);
        DB::table('sms_status_rules')->where('auto_send', 0)->update(['send_mode' => 'never']);
    }

    public function down(): void
    {
        Schema::table('sms_status_rules', function (Blueprint $table) {
            $table->dropColumn('send_mode');
        });
    }
};
