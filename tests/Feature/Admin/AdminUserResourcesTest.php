<?php

namespace Tests\Feature\Admin;

use App\Enums\RelationType;
use App\Models\Inventory;
use App\Models\Item;
use App\Models\PlayerState;
use App\Models\User;
use App\Models\UserRelation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminUserResourcesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->user = User::factory()->create();
        $this->actingAs($this->admin);
    }

    public function test_admin_can_add_update_and_remove_inventory_items(): void
    {
        $item = Item::query()->create(['name' => 'Korona', 'type' => 1, 'price' => 100, 'z' => 1, 'premium' => true]);

        $this->post(route('admin.users.inventory.store', $this->user), [
            'item_id' => $item->id,
            ...$this->inventoryPayload(),
        ])->assertRedirect();

        $inventory = Inventory::query()->whereBelongsTo($this->user)->firstOrFail();
        $this->assertTrue($inventory->active);

        $this->patch(route('admin.users.inventory.update', [$this->user, $inventory]), $this->inventoryPayload([
            'active' => false,
            'room' => 4,
        ]))->assertRedirect();

        $this->assertDatabaseHas('inventories', ['id' => $inventory->id, 'active' => false, 'room' => 4]);

        $this->delete(route('admin.users.inventory.destroy', [$this->user, $inventory]))->assertRedirect();
        $this->assertDatabaseMissing('inventories', ['id' => $inventory->id]);
    }

    public function test_admin_cannot_mutate_another_users_nested_resource_through_a_different_user_route(): void
    {
        $otherUser = User::factory()->create();
        $item = Item::query()->create(['name' => 'Kapelusz', 'type' => 1, 'price' => 10, 'z' => 1, 'premium' => false]);
        $inventory = Inventory::query()->create([
            'user_id' => $otherUser->id,
            'item_id' => $item->id,
            ...$this->inventoryPayload(),
        ]);

        $this->delete(route('admin.users.inventory.destroy', [$this->user, $inventory]))
            ->assertNotFound();

        $this->assertDatabaseHas('inventories', ['id' => $inventory->id]);
    }

    public function test_admin_can_manage_player_states(): void
    {
        $this->post(route('admin.users.states.store', $this->user), [
            'category' => 12,
            'name' => 7,
            'value' => 3,
        ])->assertRedirect();

        $state = PlayerState::query()->whereBelongsTo($this->user)->firstOrFail();

        $this->patch(route('admin.users.states.update', [$this->user, $state]), [
            'category' => 12,
            'name' => 7,
            'value' => 9,
        ])->assertRedirect();

        $this->assertDatabaseHas('states', ['id' => $state->id, 'value' => 9]);

        $this->delete(route('admin.users.states.destroy', [$this->user, $state]))->assertRedirect();
        $this->assertDatabaseMissing('states', ['id' => $state->id]);
    }

    public function test_friend_management_keeps_the_game_relation_bidirectional(): void
    {
        $friend = User::factory()->create();

        $this->post(route('admin.users.relations.store', $this->user), [
            'related_user_id' => $friend->id,
            'type' => RelationType::Friend->value,
        ])->assertRedirect();

        $this->assertDatabaseHas('relations', ['player1' => $this->user->id, 'player2' => $friend->id, 'relation_type' => 1]);
        $this->assertDatabaseHas('relations', ['player1' => $friend->id, 'player2' => $this->user->id, 'relation_type' => 1]);

        $relation = UserRelation::query()->where('player1', $this->user->id)->firstOrFail();
        $this->delete(route('admin.users.relations.destroy', [$this->user, $relation]))->assertRedirect();

        $this->assertDatabaseMissing('relations', ['player1' => $this->user->id, 'player2' => $friend->id, 'relation_type' => 1]);
        $this->assertDatabaseMissing('relations', ['player1' => $friend->id, 'player2' => $this->user->id, 'relation_type' => 1]);
    }

    public function test_admin_can_revoke_only_the_managed_users_session(): void
    {
        $this->insertSession('managed-session', $this->user);
        $this->insertSession('admin-session', $this->admin);

        $this->delete(route('admin.users.sessions.destroy', [$this->user, 'managed-session']))
            ->assertRedirect();

        $this->assertDatabaseMissing('sessions', ['id' => 'managed-session']);
        $this->assertDatabaseHas('sessions', ['id' => 'admin-session']);
    }

    /** @param array<string, mixed> $overrides */
    private function inventoryPayload(array $overrides = []): array
    {
        return array_merge([
            'active' => true,
            'bought' => true,
            'x' => 10,
            'y' => 20,
            'rot' => 0,
            'room' => 1,
        ], $overrides);
    }

    private function insertSession(string $id, User $user): void
    {
        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => '',
            'last_activity' => now()->getTimestamp(),
        ]);
    }
}
