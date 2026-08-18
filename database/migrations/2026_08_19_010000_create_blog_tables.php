<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('blog_posts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('blog_category_id')->constrained()->restrictOnDelete();
            $table->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('body');
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('blog_comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('blog_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('author_name');
            $table->string('body', 255);
            $table->timestamp('approved_at')->nullable()->index();
            $table->timestamps();
        });

        $now = now();
        DB::table('blog_categories')->insert([
            ['name' => 'Ogłoszenia', 'slug' => 'announcements', 'sort_order' => 10, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Społeczność', 'slug' => 'community', 'sort_order' => 20, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Konkursy', 'slug' => 'competitions', 'sort_order' => 30, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Poradniki', 'slug' => 'tutorials', 'sort_order' => 40, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Aktualizacje Panfu', 'slug' => 'updates', 'sort_order' => 50, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_comments');
        Schema::dropIfExists('blog_posts');
        Schema::dropIfExists('blog_categories');
    }
};
