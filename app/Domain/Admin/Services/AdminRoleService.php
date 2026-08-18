<?php

namespace App\Domain\Admin\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class AdminRoleService
{
    public function promote(string $identifier): User
    {
        $user = User::query()
            ->where(function (Builder $query) use ($identifier): void {
                $query->where('email', $identifier)
                    ->orWhere('name', $identifier)
                    ->when(ctype_digit($identifier), fn (Builder $query) => $query->orWhereKey((int) $identifier));
            })
            ->firstOrFail();

        $user->forceFill(['role' => UserRole::Admin])->save();

        return $user;
    }
}
