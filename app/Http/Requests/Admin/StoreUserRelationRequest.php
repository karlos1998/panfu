<?php

namespace App\Http\Requests\Admin;

use App\Domain\Admin\Data\UserRelationData;
use App\Enums\RelationType;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRelationRequest extends FormRequest
{
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->route('user');

        return [
            'related_user_id' => ['required', 'integer', 'different:user_id', Rule::exists(User::class, 'id')->whereNot('id', $user->id)],
            'type' => ['required', Rule::enum(RelationType::class), Rule::notIn([RelationType::None->value])],
        ];
    }

    public function toData(): UserRelationData
    {
        return new UserRelationData(
            relatedUserId: (int) $this->validated('related_user_id'),
            type: RelationType::from((int) $this->validated('type')),
        );
    }
}
