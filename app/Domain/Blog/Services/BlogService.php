<?php

namespace App\Domain\Blog\Services;

use App\Models\BlogCategory;
use App\Models\BlogComment;
use App\Models\BlogPost;
use App\Models\User;

class BlogService
{
    public function __construct(
        private readonly MarkdownRenderer $markdown,
        private readonly PandaAvatarService $avatars,
    ) {}

    public function index(?string $category): array
    {
        $posts = BlogPost::query()
            ->published()
            ->with('category')
            ->withCount(['comments' => fn ($query) => $query->whereNotNull('approved_at')])
            ->when($category, fn ($query) => $query->whereHas('category', fn ($categories) => $categories->where('slug', $category)))
            ->latest('published_at')
            ->paginate(5)
            ->withQueryString();

        return [
            'categories' => $this->categories(),
            'activeCategory' => $category,
            'posts' => $posts->through(fn (BlogPost $post) => $this->post($post)),
            ...$this->sidebar(),
        ];
    }

    public function show(BlogPost $post): array
    {
        abort_unless($post->published_at && $post->published_at->isPast(), 404);
        $post->load('category')->loadCount(['comments' => fn ($query) => $query->whereNotNull('approved_at')]);
        $comments = $post->comments()->whereNotNull('approved_at')->with($this->userRelations())->oldest()->get();

        return [
            'post' => $this->post($post),
            'comments' => $comments->map(fn (BlogComment $comment) => $this->comment($comment)),
            'previous' => $this->adjacent($post, '<'),
            'next' => $this->adjacent($post, '>'),
        ];
    }

    private function categories(): array
    {
        return BlogCategory::query()->where('is_active', true)->orderBy('sort_order')->get(['name', 'slug'])
            ->map(fn (BlogCategory $category) => ['name' => $category->name, 'slug' => $category->slug])->all();
    }

    private function sidebar(): array
    {
        $topUsers = User::query()
            ->withCount(['blogComments as comments_count' => fn ($query) => $query->whereNotNull('approved_at')])
            ->with($this->inventoryRelations())
            ->whereHas('blogComments', fn ($query) => $query->whereNotNull('approved_at'))
            ->orderByDesc('comments_count')->limit(3)->get();

        $latest = BlogComment::query()->whereNotNull('approved_at')->with(['post', ...$this->userRelations()])->latest()->limit(6)->get();

        return [
            'topCommenters' => $topUsers->map(fn (User $user) => [
                'name' => $user->name,
                'commentsCount' => (int) $user->comments_count,
                'avatar' => $this->avatars->forUser($user),
            ]),
            'latestComments' => $latest->map(fn (BlogComment $comment) => [
                ...$this->comment($comment),
                'post' => ['title' => $comment->post->title, 'url' => route('blog.show', $comment->post, absolute: false)],
            ]),
        ];
    }

    private function post(BlogPost $post): array
    {
        return [
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'url' => route('blog.show', $post, absolute: false),
            'bodyHtml' => $this->markdown->render($post->body),
            'publishedAt' => $post->published_at?->toIso8601String(),
            'publishedLabel' => $post->published_at?->locale('pl')->diffForHumans(),
            'category' => ['name' => $post->category->name, 'slug' => $post->category->slug],
            'commentsCount' => (int) $post->comments_count,
        ];
    }

    private function comment(BlogComment $comment): array
    {
        return [
            'id' => $comment->id,
            'authorName' => $comment->user?->name ?? $comment->author_name,
            'body' => $comment->body,
            'createdLabel' => $comment->created_at->locale('pl')->diffForHumans(),
            'avatar' => $this->avatars->forUser($comment->user),
        ];
    }

    private function adjacent(BlogPost $post, string $operator): ?array
    {
        $query = BlogPost::query()->published()->where('published_at', $operator, $post->published_at);
        $candidate = $operator === '<' ? $query->latest('published_at')->first() : $query->oldest('published_at')->first();

        return $candidate ? ['title' => $candidate->title, 'url' => route('blog.show', $candidate, absolute: false)] : null;
    }

    private function inventoryRelations(): array
    {
        return ['inventoryEntries' => fn ($query) => $query->where('active', true)->with('item')];
    }

    private function userRelations(): array
    {
        return ['user' => fn ($query) => $query->with($this->inventoryRelations())];
    }
}
