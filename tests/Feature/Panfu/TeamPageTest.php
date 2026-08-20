<?php

namespace Tests\Feature\Panfu;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TeamPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_page_groups_users_by_role_and_sheriff_status(): void
    {
        User::factory()->create([
            'name' => 'AdminPanda',
            'role' => UserRole::Admin,
            'current_gameserver' => 1,
        ]);
        User::factory()->create([
            'name' => 'ModeratorPanda',
            'role' => UserRole::Moderator,
        ]);
        User::factory()->create([
            'name' => 'SheriffPanda',
            'sheriff' => true,
        ]);
        User::factory()->create(['name' => 'RegularPanda']);

        $this->withHeaders(['Accept-Language' => 'pl-PL,pl;q=0.9'])
            ->get('/team')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Panfu/Team')
                ->where('meta.title', 'Zespół Panfu - Panfu.me')
                ->where('groups.0.key', 'administrators')
                ->where('groups.0.members.0.name', 'AdminPanda')
                ->where('groups.0.members.0.roleLabel', 'Administrator')
                ->where('groups.0.members.0.online', true)
                ->where('groups.0.members.0.avatar.url', '/playercard?user=AdminPanda')
                ->where('groups.1.key', 'moderators')
                ->where('groups.1.members.0.name', 'ModeratorPanda')
                ->where('groups.2.key', 'sheriffs')
                ->where('groups.2.members.0.name', 'SheriffPanda')
                ->has('groups.0.members', 1)
                ->has('groups.1.members', 1)
                ->has('groups.2.members', 1)
                ->etc());
    }

    public function test_team_page_renders_empty_groups(): void
    {
        $this->withHeaders(['Accept-Language' => 'pl-PL,pl;q=0.9'])
            ->get('/team')->assertInertia(fn (Assert $page) => $page
            ->has('groups', 3)
            ->has('groups.0.members', 0)
            ->has('groups.1.members', 0)
            ->has('groups.2.members', 0)
            ->where('groups.2.emptyMessage', 'Tu jeszcze nic nie ma.')
            ->etc());
    }
}
