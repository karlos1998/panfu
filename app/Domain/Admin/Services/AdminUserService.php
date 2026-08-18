<?php

namespace App\Domain\Admin\Services;

use App\Enums\RelationType;
use App\Enums\UserRole;
use App\Models\Inventory;
use App\Models\Item;
use App\Models\PlayerState;
use App\Models\User;
use App\Models\UserRelation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AdminUserService
{
    /**
     * @param  array{search: string, role: string, status: string, sort: string}  $filters
     * @return array<string, mixed>
     */
    public function paginatedUsers(array $filters): array
    {
        $query = User::query()
            ->withCount(['inventoryEntries', 'states', 'outgoingRelations']);

        $this->applyFilters($query, $filters);
        $this->applySort($query, $filters['sort']);

        /** @var LengthAwarePaginator<int, User> $users */
        $users = $query->paginate(20)->withQueryString();

        return [
            'users' => $users->through(fn (User $user) => $this->summarizeUser($user)),
            'filters' => $filters,
            'roles' => collect(UserRole::cases())->map(fn (UserRole $role) => [
                'value' => $role->value,
                'label' => $role->label(),
            ]),
        ];
    }

    /** @return array<string, mixed> */
    public function userDetails(User $user, ?string $currentSessionId = null): array
    {
        $inventory = $user->inventoryEntries()
            ->with('item')
            ->latest()
            ->get()
            ->map(fn (Inventory $entry) => [
                'id' => $entry->id,
                'itemId' => $entry->item_id,
                'name' => $entry->item?->name ?? "Przedmiot #{$entry->item_id}",
                'type' => $entry->item?->type,
                'premium' => $entry->item?->premium ?? false,
                'active' => $entry->active,
                'bought' => $entry->bought,
                'x' => $entry->x,
                'y' => $entry->y,
                'rotation' => $entry->rot,
                'room' => $entry->room,
            ]);

        $states = $user->states()
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->map(fn (PlayerState $state) => [
                'id' => $state->id,
                'category' => $state->category,
                'name' => $state->name,
                'value' => $state->value,
                'lastChanged' => $state->last_changed,
            ]);

        $relations = $user->outgoingRelations()
            ->where('relation_type', '>', RelationType::None->value)
            ->with('relatedUser')
            ->latest()
            ->get()
            ->map(fn (UserRelation $relation) => [
                'id' => $relation->id,
                'userId' => $relation->player2,
                'name' => $relation->relatedUser?->name ?? "Panda #{$relation->player2}",
                'email' => $relation->relatedUser?->email,
                'type' => $relation->relation_type->value,
                'typeLabel' => $relation->relation_type->label(),
            ]);

        return [
            'managedUser' => $this->profile($user),
            'inventory' => $inventory,
            'states' => $states,
            'relations' => $relations,
            'sessions' => $this->sessionsFor($user, $currentSessionId),
            'options' => [
                'roles' => collect(UserRole::cases())->map(fn (UserRole $role) => ['value' => $role->value, 'label' => $role->label()]),
                'relationTypes' => collect([RelationType::Friend, RelationType::Blocked])->map(fn (RelationType $type) => ['value' => $type->value, 'label' => $type->label()]),
                'items' => Item::query()->orderBy('type')->orderBy('name')->get(['id', 'name', 'type', 'premium']),
                'users' => User::query()->whereKeyNot($user->id)->orderBy('name')->get(['id', 'name', 'email']),
                'gameServers' => DB::table('gameservers')->orderBy('name')->get(['id', 'name']),
            ],
        ];
    }

    /**
     * @param  Builder<User>  $query
     * @param  array{search: string, role: string, status: string, sort: string}  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        $query
            ->when($filters['search'] !== '', function (Builder $query) use ($filters): void {
                $search = $filters['search'];
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->when(is_numeric($search), fn (Builder $query) => $query->orWhereKey((int) $search));
                });
            })
            ->when($filters['role'] !== '', fn (Builder $query) => $query->where('role', $filters['role']))
            ->when($filters['status'] === 'goldpanda', fn (Builder $query) => $query->where('goldpanda', '>', 0))
            ->when($filters['status'] === 'sheriff', fn (Builder $query) => $query->where('sheriff', true))
            ->when($filters['status'] === 'online', fn (Builder $query) => $query->whereNotNull('current_gameserver')->where('current_gameserver', '>', 0));
    }

    /** @param Builder<User> $query */
    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'oldest' => $query->oldest(),
            'name' => $query->orderBy('name'),
            'coins' => $query->orderByDesc('coins'),
            default => $query->latest(),
        };
    }

    /** @return array<string, mixed> */
    private function summarizeUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role->value,
            'roleLabel' => $user->role->label(),
            'coins' => $user->coins ?? 0,
            'goldPanda' => $user->goldpanda > 0,
            'sheriff' => $user->sheriff,
            'online' => ($user->current_gameserver ?? 0) > 0,
            'socialLevel' => $user->social_level,
            'inventoryCount' => $user->inventory_entries_count,
            'statesCount' => $user->states_count,
            'relationsCount' => $user->outgoing_relations_count,
            'lastLogin' => $user->last_login?->toDateString(),
            'createdAt' => $user->created_at?->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    private function profile(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role->value,
            'sex' => $user->sex,
            'coins' => $user->coins ?? 0,
            'goldpanda' => $user->goldpanda > 0,
            'sheriff' => $user->sheriff,
            'socialLevel' => $user->social_level,
            'socialScore' => $user->social_score ?? 0,
            'currentGameServer' => $user->current_gameserver,
            'tourFinished' => $user->tour_finished,
            'birthday' => $user->birthday?->toDateString(),
            'lastLogin' => $user->last_login?->toDateString(),
            'emailVerified' => $user->email_verified_at !== null,
            'createdAt' => $user->created_at?->toIso8601String(),
            'updatedAt' => $user->updated_at?->toIso8601String(),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function sessionsFor(User $user, ?string $currentSessionId): array
    {
        $activeSince = now()->subMinutes((int) config('session.lifetime'))->getTimestamp();

        return DB::table('sessions')
            ->where('user_id', $user->id)
            ->orderByDesc('last_activity')
            ->get()
            ->map(fn (object $session) => [
                'id' => $session->id,
                'ipAddress' => $session->ip_address,
                'userAgent' => $session->user_agent,
                'lastActivity' => date(DATE_ATOM, $session->last_activity),
                'active' => $session->last_activity >= $activeSince,
                'current' => hash_equals((string) $session->id, (string) $currentSessionId),
            ])
            ->all();
    }
}
