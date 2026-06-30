<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('minigame_rewards', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('game_id')->unique();
            $table->decimal('coin_multiplier', 8, 4)->default('0.0500');
            $table->unsignedInteger('max_coins_per_round')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('minigame_rewards');
    }
};
