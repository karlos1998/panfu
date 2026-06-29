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
        Schema::create('gameservers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->integer('player_count')->nullable();
            $table->string('url')->nullable();
            $table->integer('port')->nullable();
            $table->boolean('goldpanda')->nullable();
            $table->string('secret_key', 64)->nullable();
            $table->timestamps();
        });

        Schema::create('items', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 191)->nullable();
            $table->tinyInteger('type')->nullable();
            $table->integer('price')->nullable();
            $table->integer('z')->nullable();
            $table->boolean('premium')->default(false);
        });

        Schema::create('inventories', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->index();
            $table->boolean('active');
            $table->boolean('bought');
            $table->integer('item_id')->index();
            $table->integer('x')->default(0);
            $table->integer('y')->default(0);
            $table->integer('rot')->default(0);
            $table->integer('room')->default(0);
            $table->timestamps();
            $table->unique(['user_id', 'item_id']);
        });

        Schema::create('states', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->nullable()->index();
            $table->integer('category')->nullable();
            $table->integer('name')->nullable();
            $table->integer('value')->nullable();
            $table->integer('last_changed')->nullable();
            $table->timestamps();
        });

        Schema::create('relations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('player1')->index();
            $table->integer('player2')->index();
            $table->tinyInteger('relation_type');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('relations');
        Schema::dropIfExists('states');
        Schema::dropIfExists('inventories');
        Schema::dropIfExists('items');
        Schema::dropIfExists('gameservers');
    }
};
