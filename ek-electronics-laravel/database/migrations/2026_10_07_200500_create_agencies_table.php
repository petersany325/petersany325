<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agencies', function (Blueprint $table) {
            $table->id();
            $table->string('principal_name', 120);
            $table->string('principal_url', 255)->nullable();
            $table->string('territory', 120);
            $table->string('agent_name', 120)->default('EK Electronics');
            $table->string('title', 180);
            $table->string('slug', 191)->unique();
            $table->string('excerpt', 400);
            $table->longText('body');
            $table->text('coverage')->nullable();
            $table->string('website', 255)->nullable();
            $table->string('email', 191)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('logo_url', 255)->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        DB::table('agencies')->insert([
            'principal_name' => 'SeDiv',
            'principal_url' => 'https://www.hdd-land.com/',
            'territory' => 'South Africa',
            'agent_name' => 'EK Electronics',
            'title' => 'Authorized SeDiv representative',
            'slug' => 'sediv-south-africa',
            'excerpt' => 'EK Electronics is the SeDiv representative for South Africa — genuine HDD firmware and disk-imaging software, local licensing, and workshop support from Midrand.',
            'body' => <<<'TXT'
SeDiv is professional hard-disk firmware and disk-imaging software used by data-recovery laboratories. Licensed editions cover service work on families such as Western Digital, Seagate, Toshiba, Samsung, Fujitsu, Hitachi, and HGST.

EK Electronics is the SeDiv representative for South Africa. From our laboratory at Waterfall Business Park, Midrand, we are the local contact for licences, onboarding, and day-to-day questions from recovery engineers and HDD repair workshops.

South African customers deal with EK Electronics first. Quotations are prepared in ZAR, licences are matched to the drives you actually service, and support is available by phone, email, and WhatsApp during South African business hours. Where a case needs SeDiv’s own product engineers, we pass it on with a clear note so the detail is not lost.

This representation sits alongside the work we already do in Midrand: data recovery, certified refurbishment, and secure erasure. The same bench that handles failed drives can talk you through how SeDiv fits that workflow.
TXT,
            'coverage' => "Genuine SeDiv licence sales and renewals, quoted in ZAR\nHelp choosing SeDiv Professional, Hitachi ARM, and HGST imaging editions\nOnboarding for recovery engineers and repair workshops\nBusiness-hours support by phone, email, and WhatsApp\nCoordination with the EK Electronics Midrand laboratory",
            'website' => 'https://www.hdd-land.com/',
            'email' => 'info@ekelectronics.co.za',
            'phone' => '+27 10 500 2140',
            'logo_url' => null,
            'is_featured' => true,
            'is_active' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (! DB::table('menus')->where('url', '/agents')->exists()) {
            $sort = (int) DB::table('menus')->where('location', 'header')->max('sort_order') + 1;
            DB::table('menus')->insert([
                'parent_id' => null,
                'location' => 'header',
                'label' => 'Agents',
                'url' => '/agents',
                'hint' => null,
                'target' => '_self',
                'sort_order' => $sort,
                'is_active' => true,
                'is_highlighted' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('menus')->where('url', '/agents')->delete();
        Schema::dropIfExists('agencies');
    }
};
