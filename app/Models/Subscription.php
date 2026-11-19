<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    public const LIVE = ['active', 'trialing'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['current_period_end' => 'datetime', 'ended_at' => 'datetime', 'cancel_at_period_end' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isLive(): bool
    {
        return in_array($this->status, self::LIVE, true) && (! $this->current_period_end || $this->current_period_end->isFuture());
    }
}
