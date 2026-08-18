<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBlogManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_and_delete_blog_post(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = BlogCategory::query()->where('slug', 'community')->firstOrFail();

        $this->actingAs($admin)->post('/admin/blog/posts', [
            'title' => 'Spotkanie pand',
            'slug' => '',
            'blog_category_id' => $category->id,
            'body' => '# Zapraszamy',
            'published_at' => '2026-08-19 12:00:00',
        ])->assertRedirect();

        $post = BlogPost::query()->sole();
        $this->assertSame('spotkanie-pand', $post->slug);
        $this->assertSame($admin->id, $post->author_id);

        $this->actingAs($admin)->patch("/admin/blog/posts/{$post->slug}", [
            'title' => 'Spotkanie pand już jutro',
            'slug' => 'spotkanie-pand-jutro',
            'blog_category_id' => $category->id,
            'body' => 'Nowa treść',
            'published_at' => null,
        ])->assertRedirect();

        $post->refresh();
        $this->assertSame('spotkanie-pand-jutro', $post->slug);
        $this->assertNull($post->published_at);

        $this->actingAs($admin)->delete("/admin/blog/posts/{$post->slug}")->assertRedirect('/admin/blog');
        $this->assertDatabaseMissing('blog_posts', ['id' => $post->id]);
    }

    public function test_regular_user_cannot_manage_blog(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin/blog')->assertForbidden();
    }
}
