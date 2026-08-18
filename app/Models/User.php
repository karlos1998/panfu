<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
            'last_login' => 'date',
            'sex' => 'boolean',
            'sheriff' => 'boolean',
            'social_level' => 'integer',
            'social_score' => 'integer',
            'tour_finished' => 'boolean',
        ];
    }
}
