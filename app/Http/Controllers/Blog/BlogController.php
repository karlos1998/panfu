<?php

namespace App\Http\Controllers\Blog;

use App\Domain\Blog\Services\BlogService;
use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BlogController extends Controller
{
    public function __construct(private readonly BlogService $service) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Blog/Index', $this->service->index($request->string('category')->toString() ?: null));
    }

    public function show(BlogPost $post): Response
    {
        return Inertia::render('Blog/Show', $this->service->show($post));
    }
}
