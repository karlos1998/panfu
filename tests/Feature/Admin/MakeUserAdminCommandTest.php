<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MakeUserAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_role_can_be_bootstrapped_by_email(): void
    {
        $user = User::factory()->create(['email' => 'future-admin@example.test']);

        $this->artisan('user:make-admin', ['user' => $user->email])
            ->expectsOutputToContain('ma teraz rolę administratora')
            ->assertSuccessful();

        $this->assertSame(UserRole::Admin, $user->refresh()->role);
    }

    public function test_command_fails_for_an_unknown_user(): void
    {
        $this->artisan('user:make-admin', ['user' => 'missing@example.test'])
            ->expectsOutput('Nie znaleziono wskazanego użytkownika.')
            ->assertFailed();
    }
}
