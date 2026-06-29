<?php

namespace Tests\Feature\Panfu;

use App\Infrastructure\Panfu\Repositories\DatabasePlayerRepository;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabasePlayerRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_preserves_existing_player_progress_in_local_database(): void
    {
        $user = User::factory()->create([
            'email' => 'codex@example.test',
            'name' => 'CodexTester',
            'coins' => 4321,
            'social_level' => 12,
            'social_score' => 34,
            'ticket_id' => 'old-session',
        ]);

        (new DatabasePlayerRepository)->syncForFlashSession($user, 'fresh-session');

        $player = DB::table('users')
            ->where('email', 'codex@example.test')
            ->first();

        $this->assertSame(4321, (int) $player->coins);
        $this->assertSame(12, (int) $player->social_level);
        $this->assertSame(34, (int) $player->social_score);
        $this->assertSame('fresh-session', $player->ticket_id);
        $this->assertSame(1, (int) $player->current_gameserver);
        $this->assertSame(1, (int) $player->tour_finished);
    }

    public function test_sync_creates_local_starter_inventory(): void
    {
        $user = User::factory()->create();

        (new DatabasePlayerRepository)->syncForFlashSession($user, 'fresh-session');

        $this->assertDatabaseHas('inventories', [
            'user_id' => $user->id,
            'item_id' => 1001,
            'active' => true,
            'bought' => true,
        ]);
        $this->assertDatabaseHas('inventories', [
            'user_id' => $user->id,
            'item_id' => 100,
            'active' => true,
            'bought' => true,
        ]);
        $this->assertDatabaseHas('inventories', [
            'user_id' => $user->id,
            'item_id' => 103199,
            'active' => false,
            'bought' => true,
        ]);
    }
}
