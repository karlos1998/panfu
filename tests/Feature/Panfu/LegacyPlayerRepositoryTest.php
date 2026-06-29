<?php

namespace Tests\Feature\Panfu;

use App\Infrastructure\Panfu\Repositories\MySqlLegacyPlayerRepository;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LegacyPlayerRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_preserves_existing_player_progress(): void
    {
        config([
            'database.connections.legacy_openpanfu' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ],
        ]);
        DB::purge('legacy_openpanfu');

        Schema::connection('legacy_openpanfu')->create('gameservers', function ($table) {
            $table->integer('id')->primary();
            $table->string('name');
            $table->integer('player_count');
            $table->string('url');
            $table->integer('port');
            $table->integer('goldpanda');
            $table->string('secret_key')->nullable();
            $table->timestamps();
        });

        Schema::connection('legacy_openpanfu')->create('users', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('sex');
            $table->integer('coins')->nullable();
            $table->integer('goldpanda')->default(1);
            $table->boolean('sheriff')->default(false);
            $table->integer('social_level')->default(1);
            $table->integer('social_score')->nullable();
            $table->integer('current_gameserver')->nullable();
            $table->boolean('tour_finished')->default(false);
            $table->string('ticket_id')->nullable();
            $table->timestamps();
        });

        $user = User::factory()->create([
            'email' => 'codex@example.test',
            'name' => 'CodexTester',
        ]);

        DB::connection('legacy_openpanfu')->table('users')->insert([
            'name' => 'CodexTester',
            'email' => 'codex@example.test',
            'password' => 'old-session',
            'sex' => 0,
            'coins' => 4321,
            'goldpanda' => 1,
            'sheriff' => 0,
            'social_level' => 12,
            'social_score' => 34,
            'current_gameserver' => 1,
            'tour_finished' => 1,
            'ticket_id' => 'old-session',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        (new MySqlLegacyPlayerRepository)->sync($user, 'fresh-session');

        $legacyUser = DB::connection('legacy_openpanfu')
            ->table('users')
            ->where('email', 'codex@example.test')
            ->first();

        $this->assertSame(4321, (int) $legacyUser->coins);
        $this->assertSame(12, (int) $legacyUser->social_level);
        $this->assertSame(34, (int) $legacyUser->social_score);
        $this->assertSame('fresh-session', $legacyUser->ticket_id);
    }
}
