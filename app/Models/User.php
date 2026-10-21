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

    protected $fillable = ['name', 'email', 'password', 'avatar', 'provider_name', 'provider_id', 'email_verified_at'];

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

    public function hasPassword(): bool
    {
        return ! empty($this->password);
    }

    public function hasLinkedProvider(): bool
    {
        return ! empty($this->provider_name) && ! empty($this->provider_id);
    }

    public function providerLabel(): string
    {
        return match ($this->provider_name) {
            'google' => 'Google',
            default => ucfirst((string) $this->provider_name),
        };
    }

    public function hasFavourited(Game $game): bool
    {
        return $this->favouriteGames()->whereKey($game->id)->exists();
    }
}
