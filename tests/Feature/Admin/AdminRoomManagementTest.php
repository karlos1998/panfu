<?php

namespace Tests\Feature\Admin;

use App\Models\Inventory;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminRoomManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('inertia.testing.ensure_pages_exist', false);
        $this->admin = User::factory()->admin()->create();
    }

    public function test_regular_users_cannot_access_room_administration(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.rooms.homes.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.rooms.public.index'))->assertForbidden();
    }

    public function test_admin_can_filter_the_paginated_player_home_list(): void
    {
        $furnished = User::factory()->create(['name' => 'UrządzonaPanda']);
        User::factory()->create(['name' => 'PustaPanda']);
        $furniture = Item::query()->create(['id' => 104568, 'name' => 'Różowy stolik', 'type' => 13, 'premium' => false]);
        Inventory::query()->create([
            'user_id' => $furnished->id,
            'item_id' => $furniture->id,
            'active' => true,
            'bought' => true,
            'x' => 259,
            'y' => 226,
            'rot' => 1,
            'room' => 0,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.rooms.homes.index', ['status' => 'placed', 'search' => 'Urządzona']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Rooms/Homes/Index')
                ->where('filters.status', 'placed')
                ->has('homes.data', 1)
                ->where('homes.data.0.userId', $furnished->id)
                ->where('homes.data.0.furnitureCount', 1)
                ->where('homes.data.0.placedFurnitureCount', 1));
    }

    public function test_player_home_detail_exposes_background_furniture_and_coordinates(): void
    {
        $user = User::factory()->create();
        $background = Item::query()->create(['id' => 100, 'name' => 'Domek na drzewie', 'type' => 0, 'premium' => false]);
        $furniture = Item::query()->create(['id' => 104568, 'name' => 'Różowy stolik', 'type' => 13, 'premium' => false]);
        Inventory::query()->create(['user_id' => $user->id, 'item_id' => $background->id, 'active' => true, 'bought' => true]);
        Inventory::query()->create([
            'user_id' => $user->id,
            'item_id' => $furniture->id,
            'active' => true,
            'bought' => true,
            'x' => 259,
            'y' => 226,
            'rot' => 1,
            'room' => 2,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.rooms.homes.show', $user))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Rooms/Homes/Show')
                ->where('home.user.id', $user->id)
                ->where('home.activeBackground.itemId', 100)
                ->where('home.activeBackground.swfUrl', '/vendor/openpanfu/rooms/home/assets/backgrounds/treehouse.swf')
                ->has('home.furniture', 1)
                ->where('home.furniture.0.itemId', 104568)
                ->where('home.furniture.0.x', 259)
                ->where('home.furniture.0.y', 226)
                ->where('home.furniture.0.room', 2)
                ->where('client.stageWidth', 772));
    }

    public function test_admin_can_browse_the_public_room_catalog(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.rooms.public.index', ['search' => 'town']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Rooms/Public/Index')
                ->has('rooms.data', 2)
                ->where('rooms.data.0.id', 'town')
                ->where('rooms.data.0.number', 4)
                ->where('rooms.data.0.assetExists', true));
    }

    public function test_public_room_debugger_contains_real_swf_layers_and_configuration(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.rooms.public.show', 'town'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Rooms/Public/Show')
                ->where('room.id', 'town')
                ->where('room.roomSwfUrl', '/vendor/openpanfu/rooms/town/assets/room.swf')
                ->has('room.spawns', 7)
                ->has('room.hotspots', 11)
                ->where('room.hotspots.3.id', 'door1')
                ->where('room.hotspots.3.target', '1')
                ->where('room.hotspots.3.x', 165)
                ->where('room.hotspots.3.y', 271)
                ->where('room.hotspots.3.radius', 30)
                ->where('room.hotspots.3.destination.id', 'icecreamparlor')
                ->where('room.debug.walkAreaCharacterId', 101)
                ->has('room.debug.walkAreaFrames', 1)
                ->where('room.debug.walkAreaFrames.0.url', '/vendor/panfu-admin/room-debug/town/walkarea-1.svg')
                ->where('room.debug.walkAreaFrames.0.transform.a', 1.000061)
                ->has('room.assets')
                ->has('room.elements'));
    }

    public function test_unknown_public_room_returns_not_found(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.rooms.public.show', 'not-a-room'))
            ->assertNotFound();
    }
}
