<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('player_name');
            $table->unsignedInteger('room_id');
            $table->boolean('is_home')->default(false);
            $table->string('message', 120);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['is_home', 'room_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['player_name', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
