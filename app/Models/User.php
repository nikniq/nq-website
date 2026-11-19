<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = ['name', 'email', 'password', 'avatar', 'provider_name', 'provider_id', 'email_verified_at', 'is_public'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_public' => 'boolean',
            'plus_until' => 'datetime',
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

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /** Has a live Plus subscription (cached in plus_until by BillingService). */
    public function isPlus(): bool
    {
        return $this->plus_until !== null && $this->plus_until->isFuture();
    }

    public function currentSubscription(): ?Subscription
    {
        return $this->subscriptions()->orderByRaw("case when status in ('active','trialing') then 0 when status = 'past_due' then 1 else 2 end")->latest('updated_at')->first();
    }

    /** Suspended (soft-deleted) accounts can't sign up, log in or use Google with this email. */
    public static function isSuspendedEmail(?string $email): bool
    {
        return $email !== null && $email !== '' && static::onlyTrashed()->where('email', strtolower(trim($email)))->exists();
    }

    /** Soft delete: hide the account, end its sessions and revoke its game tokens. Restorable. */
    public function suspend(?string $reason = null): void
    {
        $this->forceFill(['deleted_reason' => $reason ? mb_substr($reason, 0, 255) : null])->saveQuietly();
        $this->playerTokens()->whereNull('revoked_at')->update(['revoked_at' => now()]);
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $this->id)->delete();
        }
        $this->setRememberToken(null);
        $this->saveQuietly();
        $this->delete();
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
