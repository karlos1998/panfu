<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->setMultiplier('0.3333');
    }

    public function down(): void
    {
        $this->setMultiplier('0.0500');
    }

    private function setMultiplier(string $multiplier): void
    {
        DB::table('minigame_rewards')
            ->where('game_id', 12)
            ->update([
                'coin_multiplier' => $multiplier,
                'updated_at' => now(),
            ]);
    }
};
