<?php

namespace App\Domain\Blog\Services;

use App\Models\BlogCategory;
use App\Models\BlogComment;
use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AdminBlogService
{
    public function index(): array
    {
        return [
            'posts' => BlogPost::query()->with('category')->withCount('comments')->latest()->paginate(20)->through(fn (BlogPost $post) => $this->post($post)),
            'categories' => $this->categories(),
            'comments' => BlogComment::query()->with(['post', 'user'])->latest()->limit(30)->get()->map(fn (BlogComment $comment) => [
                'id' => $comment->id,
                'authorName' => $comment->user?->name ?? $comment->author_name,
                'body' => $comment->body,
                'postTitle' => $comment->post->title,
                'createdAt' => $comment->created_at->format('Y-m-d H:i'),
            ]),
        ];
    }

    public function create(): array
    {
        return ['post' => null, 'categories' => $this->categories()];
    }

    public function edit(BlogPost $post): array
    {
        $post->load('category');

        return ['post' => $this->post($post, true), 'categories' => $this->categories()];
    }

    public function store(array $data, User $author): BlogPost
    {
        return BlogPost::query()->create($data + ['author_id' => $author->id]);
    }

    public function update(BlogPost $post, array $data): void
    {
        $post->update($data);
    }

    public function delete(BlogPost $post): void
    {
        $post->delete();
    }

    public function storeCategory(array $data): void
    {
        BlogCategory::query()->create($data);
    }

    public function updateCategory(BlogCategory $category, array $data): void
    {
        $category->update($data);
    }

    public function deleteCategory(BlogCategory $category): void
    {
        if ($category->posts()->exists()) {
            throw ValidationException::withMessages(['category' => 'Nie można usunąć kategorii zawierającej wpisy.']);
        }
        $category->delete();
    }

    public function deleteComment(BlogComment $comment): void
    {
        $comment->delete();
    }

    private function categories(): array
    {
        return BlogCategory::query()->withCount('posts')->orderBy('sort_order')->get()->map(fn (BlogCategory $category) => [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'sortOrder' => $category->sort_order,
            'isActive' => $category->is_active,
            'postsCount' => $category->posts_count,
        ])->all();
    }

    private function post(BlogPost $post, bool $withBody = false): array
    {
        return [
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'categoryId' => $post->blog_category_id,
            'categoryName' => $post->category->name,
            'body' => $withBody ? $post->body : null,
            'publishedAt' => $post->published_at?->format('Y-m-d\TH:i'),
            'commentsCount' => (int) ($post->comments_count ?? 0),
            'url' => route('admin.blog.posts.edit', $post),
        ];
    }
}
