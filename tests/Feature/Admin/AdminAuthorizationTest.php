<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth', 'admin'])
            ->get('/admin-authorization-test', fn () => response()->noContent());
    }

    public function test_new_users_receive_the_user_role(): void
    {
        $user = User::factory()->create();

        $this->assertSame(UserRole::User, $user->role);
        $this->assertFalse($user->isAdmin());
    }

    public function test_regular_users_cannot_access_admin_routes(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin-authorization-test')
            ->assertForbidden();
    }

    public function test_admins_can_access_admin_routes(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue($admin->isAdmin());

        $this->actingAs($admin)
            ->get('/admin-authorization-test')
            ->assertNoContent();
    }
}
