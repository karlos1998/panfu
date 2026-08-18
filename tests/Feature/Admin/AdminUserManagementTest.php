<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('inertia.testing.ensure_pages_exist', false);
    }

    public function test_only_admins_can_open_the_admin_panel(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.dashboard'))
            ->assertForbidden();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard')
                ->has('metrics')
                ->has('recentUsers'));
    }

    public function test_admin_can_search_and_filter_the_paginated_user_list(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['name' => 'SzukanaPanda', 'email' => 'panda@example.test', 'goldpanda' => 1]);
        User::factory()->create(['name' => 'InnaPanda', 'email' => 'inna@example.test', 'goldpanda' => 0]);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['search' => 'Szukana', 'status' => 'goldpanda']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Users/Index')
                ->where('filters.search', 'Szukana')
                ->where('filters.status', 'goldpanda')
                ->has('users.data', 1)
                ->where('users.data.0.name', 'SzukanaPanda'));
    }

    public function test_admin_can_open_a_complete_user_detail_view(): void
    {
        $admin = User::factory()->admin()->create();
        DB::table('gameservers')->insert(['id' => 7, 'name' => 'Pandama']);
        $user = User::factory()->create(['current_gameserver' => 7]);

        $this->actingAs($admin)
            ->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Users/Show')
                ->where('managedUser.id', $user->id)
                ->where('managedUser.currentGameServerName', 'Pandama')
                ->hasAll(['inventory', 'states', 'relations', 'sessions', 'options.roles', 'options.items', 'options.users']));
    }

    public function test_admin_can_update_all_managed_account_fields(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['current_gameserver' => 7]);

        $this->actingAs($admin)
            ->patch(route('admin.users.update', $user), $this->validUserPayload([
                'name' => 'KapitanPanda',
                'email' => 'kapitan@example.test',
                'role' => UserRole::Admin->value,
                'coins' => 98765,
                'goldpanda' => true,
                'sheriff' => true,
                'social_level' => 42,
                'social_score' => 123456,
                'current_gameserver' => 99,
                'tour_finished' => false,
                'email_verified' => false,
            ]))
            ->assertRedirect()
            ->assertSessionHas('success');

        $user->refresh();
        $this->assertSame('KapitanPanda', $user->name);
        $this->assertSame('kapitan@example.test', $user->email);
        $this->assertSame(UserRole::Admin, $user->role);
        $this->assertSame(98765, $user->coins);
        $this->assertTrue($user->sheriff);
        $this->assertSame(42, $user->social_level);
        $this->assertSame(7, $user->current_gameserver);
        $this->assertFalse($user->tour_finished);
        $this->assertNull($user->email_verified_at);
    }

    public function test_admin_cannot_demote_themselves_or_the_last_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->patch(route('admin.users.update', $admin), $this->validUserPayload([
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => UserRole::User->value,
            ]))
            ->assertSessionHasErrors('role');

        $this->assertSame(UserRole::Admin, $admin->refresh()->role);
    }

    /** @param array<string, mixed> $overrides */
    private function validUserPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Panda',
            'email' => 'user@example.test',
            'role' => UserRole::User->value,
            'sex' => false,
            'coins' => 1000,
            'goldpanda' => false,
            'sheriff' => false,
            'social_level' => 1,
            'social_score' => 0,
            'tour_finished' => true,
            'birthday' => null,
            'email_verified' => true,
            'password' => null,
            'password_confirmation' => null,
        ], $overrides);
    }
}
