<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexPlayerHomesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['furnished', 'placed', 'empty'])],
            'sort' => ['nullable', Rule::in(['latest', 'name', 'furniture'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /** @return array{search: string, status: string, sort: string} */
    public function filters(): array
    {
        return [
            'search' => (string) $this->validated('search', ''),
            'status' => (string) $this->validated('status', ''),
            'sort' => (string) $this->validated('sort', 'latest'),
        ];
    }
}
