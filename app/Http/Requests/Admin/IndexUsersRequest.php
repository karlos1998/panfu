<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexUsersRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::enum(UserRole::class)],
            'status' => ['nullable', Rule::in(['goldpanda', 'sheriff', 'online'])],
            'sort' => ['nullable', Rule::in(['latest', 'oldest', 'name', 'coins'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /** @return array{search: string, role: string, status: string, sort: string} */
    public function filters(): array
    {
        return [
            'search' => (string) $this->validated('search', ''),
            'role' => (string) $this->validated('role', ''),
            'status' => (string) $this->validated('status', ''),
            'sort' => (string) $this->validated('sort', 'latest'),
        ];
    }
}
