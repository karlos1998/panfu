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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('sex')->default(false)->after('password');
            $table->integer('coins')->nullable()->after('sex');
            $table->integer('goldpanda')->default(1)->after('coins');
            $table->boolean('sheriff')->default(false)->after('goldpanda');
            $table->integer('social_level')->default(1)->after('sheriff');
            $table->integer('social_score')->nullable()->after('social_level');
            $table->integer('current_gameserver')->nullable()->after('social_score');
            $table->boolean('tour_finished')->default(true)->after('current_gameserver');
            $table->string('ticket_id')->nullable()->after('tour_finished');
            $table->date('birthday')->nullable()->after('ticket_id');
            $table->date('last_login')->nullable()->after('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'sex',
                'coins',
                'goldpanda',
                'sheriff',
                'social_level',
                'social_score',
                'current_gameserver',
                'tour_finished',
                'ticket_id',
                'birthday',
                'last_login',
            ]);
        });
    }
};
