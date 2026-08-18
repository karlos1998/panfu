<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Blog\Services\AdminBlogService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveBlogPostRequest;
use App\Models\BlogPost;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BlogPostController extends Controller
{
    public function __construct(private readonly AdminBlogService $service) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Blog/Index', $this->service->index());
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Blog/Edit', $this->service->create());
    }

    public function store(SaveBlogPostRequest $request): RedirectResponse
    {
        $post = $this->service->store($request->payload(), $request->user());

        return redirect()->route('admin.blog.posts.edit', $post)->with('success', 'Wpis został utworzony.');
    }

    public function edit(BlogPost $post): Response
    {
        return Inertia::render('Admin/Blog/Edit', $this->service->edit($post));
    }

    public function update(SaveBlogPostRequest $request, BlogPost $post): RedirectResponse
    {
        $this->service->update($post, $request->payload());

        return redirect()->route('admin.blog.posts.edit', $post->refresh())->with('success', 'Wpis został zapisany.');
    }

    public function destroy(BlogPost $post): RedirectResponse
    {
        $this->service->delete($post);

        return redirect()->route('admin.blog.posts.index')->with('success', 'Wpis został usunięty.');
    }
}
