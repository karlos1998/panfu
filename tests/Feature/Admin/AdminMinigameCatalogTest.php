<?php

namespace Tests\Feature\Admin;

use App\Models\MinigameReward;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminMinigameCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('inertia.testing.ensure_pages_exist', false);
    }

    public function test_regular_users_cannot_access_minigame_catalog(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.minigames.index'))
            ->assertForbidden();
    }

    public function test_admin_can_view_minigames_and_their_reward_settings(): void
    {
        MinigameReward::query()->create([
            'game_id' => 12,
            'coin_multiplier' => '0.3333',
            'max_coins_per_round' => null,
            'enabled' => true,
        ]);
        MinigameReward::query()->create([
            'game_id' => 44,
            'coin_multiplier' => '0.2500',
            'max_coins_per_round' => 75,
            'enabled' => false,
        ]);
        MinigameReward::query()->create([
            'game_id' => 52,
            'coin_multiplier' => '0.0500',
            'max_coins_per_round' => null,
            'enabled' => true,
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.minigames.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Minigames/Index')
                ->has('minigames', 3)
                ->where('minigames.0.id', 12)
                ->where('minigames.0.name', 'Cast Away')
                ->where('minigames.0.type', 'single')
                ->where('minigames.0.enabled', true)
                ->where('minigames.0.coinMultiplier', '0.3333')
                ->where('minigames.0.maxCoinsPerRound', null)
                ->where('minigames.0.thumbnailUrl', '/vendor/panfu-admin/minigames/game12.webp')
                ->has('minigames.0.rooms', 1)
                ->where('minigames.0.rooms.0.id', 'castle')
                ->where('minigames.0.rooms.0.number', 13)
                ->where('minigames.0.rooms.0.label', 'Castle')
                ->where('minigames.1.id', 44)
                ->where('minigames.1.name', 'Parking')
                ->where('minigames.1.enabled', false)
                ->where('minigames.1.maxCoinsPerRound', 75)
                ->where('minigames.1.rooms.0.id', 'giftshop')
                ->where('minigames.2.id', 52)
                ->has('minigames.2.rooms', 2)
                ->where('minigames.2.rooms.0.id', 'laboratory')
                ->where('minigames.2.rooms.1.id', 'evroncastle_road')
                ->where('metrics.total', 3)
                ->where('metrics.enabled', 2)
                ->where('metrics.customMultiplier', 2));
    }
}
