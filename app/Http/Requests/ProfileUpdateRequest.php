<?php

namespace App\Http\Requests;

use App\Domain\Account\Data\AccountSettingsData;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password'],
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'gender' => ['required', 'in:boy,girl'],
            'new_password' => ['nullable', 'confirmed', Rules\Password::defaults()],
        ];
    }

    public function toData(): AccountSettingsData
    {
        /** @var array{name: string, email: string, gender: string, new_password?: string|null} $validated */
        $validated = $this->validated();
        $password = $validated['new_password'] ?? null;

        return new AccountSettingsData(
            name: $validated['name'],
            email: $validated['email'],
            sex: $validated['gender'] === 'girl',
            password: filled($password) ? $password : null,
        );
    }
}
