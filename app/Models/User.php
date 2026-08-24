<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'sex'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** @var array<string, mixed> */
    protected $attributes = [
        'role' => UserRole::User->value,
    ];

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /** @return HasMany<Inventory, $this> */
    public function inventoryEntries(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    /** @return HasMany<PokoPet, $this> */
    public function pokoPets(): HasMany
    {
        return $this->hasMany(PokoPet::class);
    }

    /** @return HasMany<Bolly, $this> */
    public function bollies(): HasMany
    {
        return $this->hasMany(Bolly::class);
    }

    /** @return HasMany<GameHighScore, $this> */
    public function gameHighScores(): HasMany
    {
        return $this->hasMany(GameHighScore::class);
    }

    /** @return HasMany<ChatMessage, $this> */
    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    /** @return HasMany<BlogPost, $this> */
    public function blogPosts(): HasMany
    {
        return $this->hasMany(BlogPost::class, 'author_id');
    }

    /** @return HasMany<BlogComment, $this> */
    public function blogComments(): HasMany
    {
        return $this->hasMany(BlogComment::class);
    }

    /** @return HasMany<PlayerState, $this> */
    public function states(): HasMany
    {
        return $this->hasMany(PlayerState::class);
    }

    /** @return HasMany<UserRelation, $this> */
    public function outgoingRelations(): HasMany
    {
        return $this->hasMany(UserRelation::class, 'player1');
    }

    /** @return HasOne<PlayerProfile, $this> */
    public function gameProfile(): HasOne
    {
        return $this->hasOne(PlayerProfile::class);
    }

    /** @return HasMany<PinboardMessage, $this> */
    public function receivedPinboardMessages(): HasMany
    {
        return $this->hasMany(PinboardMessage::class, 'receiver_id');
    }

    /** @return HasMany<PlayerSticker, $this> */
    public function stickers(): HasMany
    {
        return $this->hasMany(PlayerSticker::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'birthday' => 'date',
            'coins' => 'integer',
            'current_gameserver' => 'integer',
            'goldpanda' => 'integer',
            'best_friend_id' => 'integer',
            'home_locked' => 'boolean',
            'helper_status' => 'boolean',
            'last_login' => 'date',
            'sex' => 'boolean',
            'sheriff' => 'boolean',
            'social_level' => 'integer',
            'social_score' => 'integer',
            'tour_finished' => 'boolean',
        ];
    }
}
