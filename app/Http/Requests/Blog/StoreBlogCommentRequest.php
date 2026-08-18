<?php

namespace App\Http\Requests\Blog;

use Illuminate\Foundation\Http\FormRequest;

class StoreBlogCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['body' => ['required', 'string', 'max:255']];
    }

    public function body(): string
    {
        return trim($this->string('body')->toString());
    }
}
