<?php

namespace App\Http\Requests\Admin;

use App\Domain\Admin\Data\AdminUserData;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateAdminUserRequest extends FormRequest
{
    public function rules(): array
    {
        /** @var User $managedUser */
        $managedUser = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($managedUser)],
            'role' => ['required', Rule::enum(UserRole::class)],
            'sex' => ['required', 'boolean'],
            'coins' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'goldpanda' => ['required', 'boolean'],
            'sheriff' => ['required', 'boolean'],
            'social_level' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'social_score' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'current_gameserver' => ['nullable', 'integer', 'min:1', 'exists:gameservers,id'],
            'tour_finished' => ['required', 'boolean'],
            'birthday' => ['nullable', 'date', 'before_or_equal:today'],
            'email_verified' => ['required', 'boolean'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ];
    }

    public function toData(): AdminUserData
    {
        /** @var array<string, mixed> $data */
        $data = $this->validated();

        return new AdminUserData(
            name: (string) $data['name'],
            email: (string) $data['email'],
            role: UserRole::from((string) $data['role']),
            sex: (bool) $data['sex'],
            coins: (int) $data['coins'],
            goldPanda: (bool) $data['goldpanda'],
            sheriff: (bool) $data['sheriff'],
            socialLevel: (int) $data['social_level'],
            socialScore: (int) $data['social_score'],
            currentGameServer: isset($data['current_gameserver']) ? (int) $data['current_gameserver'] : null,
            tourFinished: (bool) $data['tour_finished'],
            birthday: isset($data['birthday']) ? (string) $data['birthday'] : null,
            emailVerified: (bool) $data['email_verified'],
            password: filled($data['password'] ?? null) ? (string) $data['password'] : null,
        );
    }
}
