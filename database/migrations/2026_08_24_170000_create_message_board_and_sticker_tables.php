<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pinboard_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sender_id')->index();
            $table->unsignedBigInteger('receiver_id')->index();
            $table->unsignedBigInteger('parent_message_id')->nullable()->index();
            $table->unsignedSmallInteger('type_id');
            $table->string('content', 500);
            $table->boolean('read')->default(false);
            $table->boolean('deleted')->default(false);
            $table->timestamps();
        });

        Schema::create('player_stickers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedSmallInteger('definition_id');
            $table->unsignedInteger('amount')->default(0);
            $table->timestamps();
            $table->unique(['user_id', 'definition_id']);
        });

        Schema::create('tivola_scores', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->primary();
            $table->unsignedInteger('math')->default(0);
            $table->unsignedInteger('english')->default(0);
            $table->unsignedInteger('german')->default(0);
            $table->unsignedInteger('concentration')->default(0);
            $table->unsignedInteger('slot')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tivola_scores');
        Schema::dropIfExists('player_stickers');
        Schema::dropIfExists('pinboard_messages');
    }
};
