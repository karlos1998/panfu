<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SaveBlogCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:100', 'alpha_dash:ascii', Rule::unique('blog_categories')->ignore($this->route('category')?->id)],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function payload(): array
    {
        $name = trim($this->string('name')->toString());

        return [
            'name' => $name,
            'slug' => Str::slug($this->filled('slug') ? $this->string('slug')->toString() : $name),
            'sort_order' => $this->integer('sort_order'),
            'is_active' => $this->boolean('is_active'),
        ];
    }
}
