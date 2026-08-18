<?php

namespace App\Http\Controllers\Blog;

use App\Domain\Blog\Services\BlogCommentService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Blog\StoreBlogCommentRequest;
use App\Models\BlogPost;
use Illuminate\Http\RedirectResponse;

class BlogCommentController extends Controller
{
    public function __construct(private readonly BlogCommentService $service) {}

    public function store(StoreBlogCommentRequest $request, BlogPost $post): RedirectResponse
    {
        $this->service->create($post, $request->user(), $request->body());

        return back()->with('success', 'Komentarz został dodany.');
    }
}
