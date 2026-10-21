<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['game_id', 'user_id', 'event', 'score', 'level', 'duration_seconds', 'won', 'metadata', 'occurred_at'];

    protected $casts = [
        'score' => 'integer',
        'duration_seconds' => 'integer',
        'won' => 'boolean',
        'metadata' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
