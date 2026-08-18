<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Blog\Services\AdminBlogService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveBlogCategoryRequest;
use App\Models\BlogCategory;
use Illuminate\Http\RedirectResponse;

class BlogCategoryController extends Controller
{
    public function __construct(private readonly AdminBlogService $service) {}

    public function store(SaveBlogCategoryRequest $request): RedirectResponse
    {
        $this->service->storeCategory($request->payload());

        return back()->with('success', 'Kategoria została dodana.');
    }

    public function update(SaveBlogCategoryRequest $request, BlogCategory $category): RedirectResponse
    {
        $this->service->updateCategory($category, $request->payload());

        return back()->with('success', 'Kategoria została zapisana.');
    }

    public function destroy(BlogCategory $category): RedirectResponse
    {
        $this->service->deleteCategory($category);

        return back()->with('success', 'Kategoria została usunięta.');
    }
}
