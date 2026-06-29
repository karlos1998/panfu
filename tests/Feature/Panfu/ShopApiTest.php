<?php

namespace Tests\Feature\Panfu;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_not_load_shop_catalogue(): void
    {
        $this->getJson('/api/shop')->assertUnauthorized();
    }

    public function test_authenticated_users_can_load_local_shop_catalogue(): void
    {
        config([
            'panfu.shop.default_coins' => 1000,
        ]);

        $user = User::factory()->create(['coins' => 2465]);

        $this
            ->actingAs($user)
            ->getJson('/api/shop')
            ->assertOk()
            ->assertJsonPath('coins', 2465)
            ->assertJsonStructure([
                'coins',
                'items' => [
                    'clothes' => [
                        'items',
                        'subcategories' => [
                            'head',
                            'upperbody',
                        ],
                    ],
                    'furniture' => [
                        'items',
                        'subcategories' => [
                            'floor',
                            'wall',
                        ],
                    ],
                ],
            ]);
    }
}
