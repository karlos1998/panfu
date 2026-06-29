<?php

namespace App\Http\Requests\Auth;

use App\Domain\Auth\Data\RegisterUserData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules;

class RegisterUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'gender' => ['required', 'in:boy,girl'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ];
    }

    public function toData(): RegisterUserData
    {
        /** @var array{name: string, email: string, gender: string, password: string} $validated */
        $validated = $this->validated();

        return new RegisterUserData(
            name: $validated['name'],
            email: $validated['email'],
            password: $validated['password'],
            sex: $validated['gender'] === 'girl',
        );
    }
}
