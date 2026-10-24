<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class Game extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'description', 'url', 'thumbnail', 'genre', 'tags',
        'client_id', 'client_secret', 'redirect_uris',
        'is_public', 'is_enabled', 'is_featured', 'is_dementia', 'sort_order', 'notes',
    ];

    protected $hidden = ['client_secret'];

    protected $casts = [
        'tags' => 'array',
        'redirect_uris' => 'array',
        'is_public' => 'boolean',
        'is_enabled' => 'boolean',
        'is_featured' => 'boolean',
        'is_dementia' => 'boolean',
    ];

    protected static function booted(): void
    {
        $forget = fn () => Cache::forget('sitemap.xml');
        static::saved($forget);
        static::deleted($forget);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function favouritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favourites')->withTimestamps();
    }

    public function tokens(): HasMany
    {
        return $this->hasMany(PlayerToken::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(GameEvent::class);
    }

    public function stats(): HasMany
    {
        return $this->hasMany(PlayerGameStat::class);
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%")
                ->orWhere('genre', 'like', "%{$term}%");
        });
    }

    /**
     * Catalogue filters shared by the web and API listings.
     *
     * @param  array{genre?:string|null, featured?:bool, dementia?:bool, sort?:string|null}  $filters
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        if (! empty($filters['genre'])) {
            $query->where('genre', $filters['genre']);
        }
        if (! empty($filters['featured'])) {
            $query->where('is_featured', true);
        }
        if (! empty($filters['dementia'])) {
            $query->where('is_dementia', true);
        }

        return match ($filters['sort'] ?? null) {
            'name' => $query->orderBy('name'),
            'newest' => $query->orderByDesc('created_at')->orderBy('name'),
            'popular' => $query->orderByDesc('favourited_by_count')->orderBy('name'),
            default => $query->orderByDesc('is_featured')->orderBy('sort_order')->orderBy('name'),
        };
    }

    /** Other enabled games a player of this one might like: same genre first, then featured. */
    public function related(int $limit = 4): Collection
    {
        return static::enabled()->whereKeyNot($this->id)->withCount('favouritedBy')
            ->orderByRaw('case when genre = ? then 0 else 1 end', [$this->genre ?? ''])
            ->orderByDesc('is_featured')->orderByDesc('favourited_by_count')->orderBy('sort_order')
            ->limit($limit)->get();
    }

    /** Distinct genres of enabled games, for the filter chips. */
    public static function genres(): array
    {
        return static::enabled()->whereNotNull('genre')->distinct()->orderBy('genre')->pluck('genre')->all();
    }

    public static function generateClientId(): string
    {
        return Str::lower(Str::random(32));
    }

    public static function generateSecret(): string
    {
        return Str::random(48);
    }

    public function setPlainSecret(string $plain): void
    {
        $this->client_secret = Hash::make($plain);
    }

    public function verifySecret(?string $plain): bool
    {
        return is_string($plain) && $plain !== '' && $this->client_secret !== null
            && Hash::check($plain, $this->client_secret);
    }

    public function redirectAllowed(string $uri): bool
    {
        return in_array($uri, $this->redirect_uris ?? [], true);
    }

    public function thumbnailUrl(): ?string
    {
        if (! $this->thumbnail) {
            return null;
        }

        return Str::startsWith($this->thumbnail, ['http://', 'https://', '/'])
            ? $this->thumbnail
            : '/'.ltrim($this->thumbnail, '/');
    }
}
