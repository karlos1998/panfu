<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_high_scores', function (Blueprint $table): void {
            $table->unsignedTinyInteger('correct_answers')->nullable()->after('score');
            $table->unsignedTinyInteger('false_answers')->nullable()->after('correct_answers');
            $table->unsignedInteger('completion_time')->nullable()->after('false_answers');
        });
    }

    public function down(): void
    {
        Schema::table('game_high_scores', function (Blueprint $table): void {
            $table->dropColumn(['correct_answers', 'false_answers', 'completion_time']);
        });
    }
};
