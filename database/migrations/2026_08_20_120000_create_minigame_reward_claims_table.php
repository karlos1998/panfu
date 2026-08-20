<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('minigame_reward_claims', function (Blueprint $table): void {
            $table->id();
            $table->uuid('round_id')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('game_id');
            $table->unsignedInteger('score');
            $table->unsignedInteger('coins');
            $table->timestamps();

            $table->index(['user_id', 'game_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('minigame_reward_claims');
    }
};
