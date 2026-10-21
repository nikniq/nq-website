<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'avatar'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    public function favouriteGames(): BelongsToMany
    {
        return $this->belongsToMany(Game::class, 'favourites')->withTimestamps();
    }

    public function gameStats(): HasMany
    {
        return $this->hasMany(PlayerGameStat::class);
    }

    public function gameEvents(): HasMany
    {
        return $this->hasMany(GameEvent::class);
    }

    public function playerTokens(): HasMany
    {
        return $this->hasMany(PlayerToken::class);
    }

    public function hasFavourited(Game $game): bool
    {
        return $this->favouriteGames()->whereKey($game->id)->exists();
    }
}
