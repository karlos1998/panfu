<?php

namespace Tests\Feature\Blog;

use App\Models\BlogCategory;
use App\Models\BlogComment;
use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BlogTest extends TestCase
{
    use RefreshDatabase;

    public function test_blog_lists_published_posts_and_renders_safe_markdown(): void
    {
        $author = User::factory()->create();
        $category = BlogCategory::query()->where('slug', 'announcements')->firstOrFail();
        BlogPost::query()->create([
            'author_id' => $author->id,
            'blog_category_id' => $category->id,
            'title' => 'Nowości w Panfu',
            'slug' => 'nowosci-w-panfu',
            'body' => "## Witajcie\n\n**Pandy!** <script>alert('xss')</script>",
            'published_at' => now()->subMinute(),
        ]);

        $this->get('/blog')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Blog/Index')
            ->where('posts.data.0.title', 'Nowości w Panfu')
            ->where('posts.data.0.category.slug', 'announcements')
            ->where('posts.data.0.bodyHtml', fn (string $html) => str_contains($html, '<h2>Witajcie</h2>') && ! str_contains($html, '<script>'))
        );
    }

    public function test_drafts_are_not_publicly_visible(): void
    {
        $post = $this->createPost(null);

        $this->get('/blog')->assertInertia(fn (Assert $page) => $page->has('posts.data', 0));
        $this->get("/blog/{$post->slug}")->assertNotFound();
    }

    public function test_authenticated_user_can_comment_and_guest_cannot(): void
    {
        $post = $this->createPost(now()->subMinute());
        $user = User::factory()->create();

        $this->post("/blog/{$post->slug}/comments", ['body' => 'Komentarz'])->assertRedirect('/login');
        $this->actingAs($user)->post("/blog/{$post->slug}/comments", ['body' => 'Świetny wpis!'])->assertRedirect();

        $this->assertDatabaseHas('blog_comments', [
            'blog_post_id' => $post->id,
            'user_id' => $user->id,
            'author_name' => $user->name,
            'body' => 'Świetny wpis!',
        ]);
    }

    public function test_comment_is_limited_to_255_characters(): void
    {
        $post = $this->createPost(now()->subMinute());

        $this->actingAs(User::factory()->create())
            ->post("/blog/{$post->slug}/comments", ['body' => str_repeat('x', 256)])
            ->assertSessionHasErrors('body');
    }

    public function test_blog_exposes_a_shared_playercard_url_for_comment_authors(): void
    {
        $post = $this->createPost(now()->subMinute());
        $user = User::factory()->create(['name' => 'AwatarowaPanda']);
        BlogComment::query()->create([
            'blog_post_id' => $post->id,
            'user_id' => $user->id,
            'author_name' => $user->name,
            'body' => 'Komentarz z awatarem',
            'approved_at' => now(),
        ]);

        $this->get("/blog/{$post->slug}")->assertInertia(fn (Assert $page) => $page
            ->where('comments.0.avatar.url', '/playercard?user=AwatarowaPanda')
        );
    }

    private function createPost(mixed $publishedAt): BlogPost
    {
        $category = BlogCategory::query()->where('slug', 'tutorials')->firstOrFail();

        return BlogPost::query()->create([
            'author_id' => User::factory()->create()->id,
            'blog_category_id' => $category->id,
            'title' => 'Przewodnik',
            'slug' => 'przewodnik',
            'body' => 'Treść',
            'published_at' => $publishedAt,
        ]);
    }
}
