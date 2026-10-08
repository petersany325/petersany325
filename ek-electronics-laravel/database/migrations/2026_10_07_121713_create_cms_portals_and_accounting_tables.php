<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 32)->default('customer')->after('is_admin');
            $table->string('phone', 40)->nullable()->after('email');
            $table->boolean('is_active')->default(true)->after('role');
        });

        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('location', 40)->default('header'); // header|footer|footer_shop|footer_services
            $table->string('label', 120);
            $table->string('url', 255);
            $table->string('target', 20)->default('_self');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title', 180);
            $table->string('slug', 191)->unique();
            $table->string('status', 20)->default('published'); // draft|published
            $table->string('meta_title', 180)->nullable();
            $table->text('meta_description')->nullable();
            $table->boolean('show_in_menu')->default(false);
            $table->timestamps();
        });

        Schema::create('page_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40); // hero|heading|text|image|cta|html|faq|products
            $table->string('heading', 180)->nullable();
            $table->longText('body')->nullable();
            $table->string('image_path', 255)->nullable();
            $table->string('button_label', 80)->nullable();
            $table->string('button_url', 255)->nullable();
            $table->json('settings')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('number', 191)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 120);
            $table->string('email', 191);
            $table->string('phone', 40)->nullable();
            $table->string('subject', 180);
            $table->string('department', 40)->default('support'); // support|sales|recovery|accounts
            $table->string('priority', 20)->default('normal'); // low|normal|high|urgent
            $table->string('status', 20)->default('open'); // open|pending|answered|closed
            $table->timestamp('last_reply_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ticket_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('author_name', 120)->nullable();
            $table->boolean('is_staff')->default(false);
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->date('spent_on');
            $table->string('category', 80); // rent|salaries|courier|stock|whatsapp|utilities|other
            $table->string('description', 255);
            $table->decimal('amount', 12, 2);
            $table->string('vendor', 120)->nullable();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('ticket_replies');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('page_blocks');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('menus');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'phone', 'is_active']);
        });
    }
};
