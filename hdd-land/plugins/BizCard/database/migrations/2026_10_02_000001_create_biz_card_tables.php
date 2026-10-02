<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('biz_card_club_members')) {
            Schema::create('biz_card_club_members', function (Blueprint $table) {
                $table->id();
                $table->string('name', 120)->nullable();
                $table->string('phone', 20)->index();
                $table->string('status', 20)->default('pending')->index();
                $table->string('token', 64)->nullable()->unique();
                $table->timestamp('requested_at')->nullable();
                $table->timestamp('confirmed_at')->nullable();
                $table->string('source', 40)->default('card');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('biz_card_sms_logs')) {
            Schema::create('biz_card_sms_logs', function (Blueprint $table) {
                $table->id();
                $table->string('phone', 20)->index();
                $table->string('type', 30)->default('custom')->index();
                $table->text('message')->nullable();
                $table->string('status', 20)->default('queued');
                $table->string('provider', 40)->nullable();
                $table->text('response')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('biz_card_sms_logs');
        Schema::dropIfExists('biz_card_club_members');
    }
};
