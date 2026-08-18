<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Blog\Services\AdminBlogService;
use App\Http\Controllers\Controller;
use App\Models\BlogComment;
use Illuminate\Http\RedirectResponse;

class BlogCommentController extends Controller
{
    public function __construct(private readonly AdminBlogService $service) {}

    public function destroy(BlogComment $comment): RedirectResponse
    {
        $this->service->deleteComment($comment);

        return back()->with('success', 'Komentarz został usunięty.');
    }
}
