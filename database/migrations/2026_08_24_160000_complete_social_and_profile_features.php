<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('best_friend_id')->nullable()->after('birthday');
            $table->boolean('home_locked')->default(false)->after('best_friend_id');
            $table->boolean('helper_status')->default(false)->after('home_locked');
            $table->string('player_state', 80)->default('')->after('helper_status');
        });

        Schema::create('player_profiles', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->primary();
            $table->unsignedBigInteger('last_blocked')->default(0);
            foreach (['movie', 'color', 'hobby', 'book', 'song', 'band', 'school_subject', 'sport', 'animal', 'rel_status', 'motto', 'best_char', 'worst_char', 'like_most', 'like_least'] as $field) {
                $table->string($field, 160)->default('');
                $table->boolean($field.'_checked')->default(true);
            }
            $table->timestamps();
        });

        Schema::create('player_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reporter_id')->index();
            $table->unsignedBigInteger('reported_id')->index();
            $table->string('reason', 255);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_reports');
        Schema::dropIfExists('player_profiles');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['best_friend_id', 'home_locked', 'helper_status', 'player_state']);
        });
    }
};
