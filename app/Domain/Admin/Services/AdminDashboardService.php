<?php

namespace App\Domain\Admin\Services;

use App\Enums\UserRole;
use App\Models\Inventory;
use App\Models\PlayerState;
use App\Models\User;
use App\Models\UserRelation;
use Illuminate\Support\Facades\DB;

class AdminDashboardService
{
    /** @return array<string, mixed> */
    public function dashboard(): array
    {
        $activeSince = now()->subMinutes((int) config('session.lifetime'))->getTimestamp();

        return [
            'metrics' => [
                'users' => User::query()->count(),
                'admins' => User::query()->where('role', UserRole::Admin)->count(),
                'goldPandas' => User::query()->where('goldpanda', '>', 0)->count(),
                'sheriffs' => User::query()->where('sheriff', true)->count(),
                'activeSessions' => DB::table('sessions')->where('last_activity', '>=', $activeSince)->count(),
                'inventoryItems' => Inventory::query()->count(),
                'states' => PlayerState::query()->count(),
                'relations' => UserRelation::query()->where('relation_type', '>', 0)->count(),
            ],
            'recentUsers' => User::query()
                ->latest()
                ->limit(6)
                ->get()
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role->value,
                    'createdAt' => $user->created_at?->toIso8601String(),
                ]),
        ];
    }
}
