<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->setMultiplier(11, '0.0500'); // Cloud Number Nine
        $this->setMultiplier(24, '0.1000'); // Cool Cooking
        $this->setMultiplier(44, '0.2500'); // Parking
    }

    public function down(): void
    {
        foreach ([11, 24, 44] as $gameId) {
            $this->setMultiplier($gameId, '0.0500');
        }
    }

    private function setMultiplier(int $gameId, string $multiplier): void
    {
        DB::table('minigame_rewards')
            ->where('game_id', $gameId)
            ->update([
                'coin_multiplier' => $multiplier,
                'updated_at' => now(),
            ]);
    }
};
