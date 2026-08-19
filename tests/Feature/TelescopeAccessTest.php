<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Providers\TelescopeServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class TelescopeAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_telescope_is_disabled_during_automated_tests(): void
    {
        $this->assertFalse((bool) config('telescope.enabled'));
        $this->assertFalse($this->app->providerIsLoaded(TelescopeServiceProvider::class));
        $this->get('/telescope')->assertNotFound();
    }

    public function test_only_administrators_pass_the_telescope_gate(): void
    {
        $provider = new class($this->app) extends TelescopeServiceProvider
        {
            public function defineGate(): void
            {
                $this->gate();
            }
        };
        $provider->defineGate();

        $regularUser = User::factory()->create();
        $administrator = User::factory()->create(['role' => UserRole::Admin]);

        $this->assertFalse(Gate::forUser($regularUser)->allows('viewTelescope'));
        $this->assertTrue(Gate::forUser($administrator)->allows('viewTelescope'));
    }
}
