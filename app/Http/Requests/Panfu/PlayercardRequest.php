<?php

namespace App\Http\Requests\Panfu;

use Illuminate\Foundation\Http\FormRequest;

class PlayercardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'user' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function username(): ?string
    {
        $username = $this->string('user')->trim()->toString();

        return $username !== '' ? $username : null;
    }
}
