<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SaveBlogPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        $postId = $this->route('post')?->id;

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash:ascii', Rule::unique('blog_posts')->ignore($postId)],
            'blog_category_id' => ['required', 'integer', 'exists:blog_categories,id'],
            'body' => ['required', 'string'],
            'published_at' => ['nullable', 'date'],
        ];
    }

    public function payload(): array
    {
        $title = trim($this->string('title')->toString());

        return [
            'title' => $title,
            'slug' => $this->filled('slug') ? Str::slug($this->string('slug')->toString()) : Str::slug($title),
            'blog_category_id' => $this->integer('blog_category_id'),
            'body' => trim($this->string('body')->toString()),
            'published_at' => $this->date('published_at'),
        ];
    }
}
