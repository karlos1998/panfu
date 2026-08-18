<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexPublicRoomsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['allowed', 'disabled', 'collision', 'vehicle', 'missing'])],
            'sort' => ['nullable', Rule::in(['number', 'name'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /** @return array{search: string, status: string, sort: string} */
    public function filters(): array
    {
        return [
            'search' => (string) $this->validated('search', ''),
            'status' => (string) $this->validated('status', ''),
            'sort' => (string) $this->validated('sort', 'number'),
        ];
    }
}
