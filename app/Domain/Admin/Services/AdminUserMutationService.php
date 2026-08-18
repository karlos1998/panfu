<?php

namespace App\Domain\Admin\Services;

use App\Domain\Admin\Data\AdminUserData;
use App\Domain\Admin\Data\InventoryData;
use App\Domain\Admin\Data\PlayerStateData;
use App\Domain\Admin\Data\UserRelationData;
use App\Enums\RelationType;
use App\Enums\UserRole;
use App\Models\Inventory;
use App\Models\PlayerState;
use App\Models\User;
use App\Models\UserRelation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminUserMutationService
{
    public function updateUser(User $administrator, User $user, AdminUserData $data): void
    {
        if ($administrator->is($user) && $data->role !== UserRole::Admin) {
            throw ValidationException::withMessages(['role' => 'Nie możesz odebrać sobie roli administratora.']);
        }

        DB::transaction(function () use ($user, $data): void {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);

            if ($lockedUser->role === UserRole::Admin && $data->role !== UserRole::Admin) {
                $adminCount = User::query()->where('role', UserRole::Admin)->count();
                if ($adminCount <= 1) {
                    throw ValidationException::withMessages(['role' => 'W systemie musi pozostać co najmniej jeden administrator.']);
                }
            }

            $attributes = [
                'name' => $data->name,
                'email' => $data->email,
                'role' => $data->role,
                'sex' => $data->sex,
                'coins' => $data->coins,
                'goldpanda' => $data->goldPanda ? 1 : 0,
                'sheriff' => $data->sheriff,
                'social_level' => $data->socialLevel,
                'social_score' => $data->socialScore,
                'current_gameserver' => $data->currentGameServer,
                'tour_finished' => $data->tourFinished,
                'birthday' => $data->birthday,
                'email_verified_at' => $data->emailVerified ? ($lockedUser->email_verified_at ?? now()) : null,
            ];

            if ($data->password !== null) {
                $attributes['password'] = $data->password;
                $attributes['remember_token'] = null;
                DB::table('sessions')->where('user_id', $lockedUser->id)->delete();
            }

            $lockedUser->forceFill($attributes)->save();
        });
    }

    public function addInventory(User $user, int $itemId, InventoryData $data): void
    {
        $user->inventoryEntries()->create($this->inventoryAttributes($data) + ['item_id' => $itemId]);
    }

    public function updateInventory(User $user, Inventory $inventory, InventoryData $data): void
    {
        $this->ensureOwnedBy($inventory->user_id, $user);
        $inventory->update($this->inventoryAttributes($data));
    }

    public function removeInventory(User $user, Inventory $inventory): void
    {
        $this->ensureOwnedBy($inventory->user_id, $user);
        $inventory->delete();
    }

    public function addState(User $user, PlayerStateData $data): void
    {
        $user->states()->updateOrCreate(
            ['category' => $data->category, 'name' => $data->name],
            ['value' => $data->value, 'last_changed' => now()->getTimestamp()],
        );
    }

    public function updateState(User $user, PlayerState $state, PlayerStateData $data): void
    {
        $this->ensureOwnedBy($state->user_id, $user);
        $state->update([
            'category' => $data->category,
            'name' => $data->name,
            'value' => $data->value,
            'last_changed' => now()->getTimestamp(),
        ]);
    }

    public function removeState(User $user, PlayerState $state): void
    {
        $this->ensureOwnedBy($state->user_id, $user);
        $state->delete();
    }

    public function addRelation(User $user, UserRelationData $data): void
    {
        DB::transaction(function () use ($user, $data): void {
            UserRelation::query()->updateOrCreate(
                ['player1' => $user->id, 'player2' => $data->relatedUserId],
                ['relation_type' => $data->type],
            );

            if ($data->type === RelationType::Friend) {
                UserRelation::query()->updateOrCreate(
                    ['player1' => $data->relatedUserId, 'player2' => $user->id],
                    ['relation_type' => RelationType::Friend],
                );
            } else {
                UserRelation::query()
                    ->where('player1', $data->relatedUserId)
                    ->where('player2', $user->id)
                    ->where('relation_type', RelationType::Friend)
                    ->delete();
            }
        });
    }

    public function removeRelation(User $user, UserRelation $relation): void
    {
        $this->ensureOwnedBy($relation->player1, $user);

        DB::transaction(function () use ($user, $relation): void {
            if ($relation->relation_type === RelationType::Friend) {
                UserRelation::query()
                    ->where('player1', $relation->player2)
                    ->where('player2', $user->id)
                    ->where('relation_type', RelationType::Friend)
                    ->delete();
            }

            $relation->delete();
        });
    }

    public function revokeSession(User $user, string $sessionId): void
    {
        DB::table('sessions')
            ->where('id', $sessionId)
            ->where('user_id', $user->id)
            ->delete();
    }

    /** @return array<string, int|bool> */
    private function inventoryAttributes(InventoryData $data): array
    {
        return [
            'active' => $data->active,
            'bought' => $data->bought,
            'x' => $data->x,
            'y' => $data->y,
            'rot' => $data->rotation,
            'room' => $data->room,
        ];
    }

    private function ensureOwnedBy(int $ownerId, User $user): void
    {
        abort_unless($ownerId === $user->id, 404);
    }
}
