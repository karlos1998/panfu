<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pokopets', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id')->index();
            $table->unsignedTinyInteger('type');
            $table->string('name', 191);
            $table->boolean('selected')->default(false);
            $table->string('state', 32)->default('idle');
            $table->integer('x')->default(0);
            $table->integer('y')->default(0);
            $table->unsignedTinyInteger('health')->default(5);
            $table->unsignedTinyInteger('max_health')->default(5);
            $table->unsignedInteger('speed')->default(1);
            $table->unsignedInteger('agility')->default(1);
            $table->unsignedInteger('power')->default(1);
            $table->unsignedInteger('experience')->default(0);
            $table->unsignedInteger('level')->default(1);
            $table->text('abilities')->nullable();
            $table->timestamp('last_fed')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'type']);
            $table->index(['user_id', 'selected']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pokopets');
    }
};
