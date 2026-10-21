<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerGameStat extends Model
{
    protected $fillable = [
        'game_id', 'user_id', 'events', 'plays', 'wins', 'total_score', 'best_score',
        'total_duration_seconds', 'last_level', 'first_played_at', 'last_played_at',
    ];

    protected $casts = [
        'events' => 'integer',
        'plays' => 'integer',
        'wins' => 'integer',
        'total_score' => 'integer',
        'best_score' => 'integer',
        'total_duration_seconds' => 'integer',
        'first_played_at' => 'datetime',
        'last_played_at' => 'datetime',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function toApi(): array
    {
        return [
            'events' => $this->events,
            'plays' => $this->plays,
            'wins' => $this->wins,
            'total_score' => $this->total_score,
            'best_score' => $this->best_score,
            'total_duration_seconds' => $this->total_duration_seconds,
            'last_level' => $this->last_level,
            'first_played_at' => $this->first_played_at?->toIso8601String(),
            'last_played_at' => $this->last_played_at?->toIso8601String(),
        ];
    }
}
