<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bollies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedInteger('definition_id');
            $table->string('state', 30)->default('normal');
            $table->string('activity', 60)->default('bollyNormal');
            $table->unsignedTinyInteger('health')->default(100);
            $table->unsignedTinyInteger('rest')->default(100);
            $table->unsignedTinyInteger('energy')->default(100);
            $table->timestamps();
            $table->unique(['user_id', 'definition_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bollies');
    }
};
