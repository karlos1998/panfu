<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_route_redirects_to_account_settings(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->get('/profile')
            ->assertRedirect('/account/settings');
    }

    public function test_account_settings_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->get('/account/settings')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Panfu/AccountSettings')
                ->where('account.name', $user->name)
                ->where('account.email', $user->email)
                ->where('account.gender', $user->sex ? 'girl' : 'boy')
                ->etc());
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/account/settings', [
                'current_password' => 'password',
                'name' => 'Test User',
                'email' => 'test@example.com',
                'gender' => 'girl',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/account/settings');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertTrue((bool) $user->sex);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/account/settings', [
                'current_password' => 'password',
                'name' => 'Test User',
                'email' => $user->email,
                'gender' => 'boy',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/account/settings');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_account_password_can_be_updated_from_settings(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/account/settings', [
                'current_password' => 'password',
                'name' => $user->name,
                'email' => $user->email,
                'gender' => 'boy',
                'new_password' => 'new-password',
                'new_password_confirmation' => 'new-password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/account/settings');

        $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}
