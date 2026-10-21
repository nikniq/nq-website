<?php

namespace App\Services;

use App\Models\Game;
use App\Models\GameEvent;
use App\Models\PlayerGameStat;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GameStatsService
{
    /** Store one event and fold it into the player's aggregate for that game. */
    public function record(Game $game, User $player, array $data): GameEvent
    {
        return DB::transaction(function () use ($game, $player, $data) {
            $occurredAt = isset($data['occurred_at']) ? now()->parse($data['occurred_at']) : now();

            $event = GameEvent::create([
                'game_id' => $game->id,
                'user_id' => $player->id,
                'event' => $data['event'],
                'score' => $data['score'] ?? null,
                'level' => $data['level'] ?? null,
                'duration_seconds' => $data['duration_seconds'] ?? null,
                'won' => $data['won'] ?? null,
                'metadata' => $data['metadata'] ?? null,
                'occurred_at' => $occurredAt,
            ]);

            $stat = PlayerGameStat::query()
                ->where('game_id', $game->id)
                ->where('user_id', $player->id)
                ->lockForUpdate()
                ->first();

            if (! $stat) {
                $stat = new PlayerGameStat([
                    'game_id' => $game->id,
                    'user_id' => $player->id,
                    'first_played_at' => $occurredAt,
                ]);
            }

            $stat->events += 1;
            if (in_array($data['event'], config('portal.play_events', []), true)) {
                $stat->plays += 1;
            }
            if (! empty($data['won'])) {
                $stat->wins += 1;
            }
            if (isset($data['score'])) {
                $stat->total_score += (int) $data['score'];
                if ($stat->best_score === null || (int) $data['score'] > $stat->best_score) {
                    $stat->best_score = (int) $data['score'];
                }
            }
            if (isset($data['duration_seconds'])) {
                $stat->total_duration_seconds += (int) $data['duration_seconds'];
            }
            if (! empty($data['level'])) {
                $stat->last_level = $data['level'];
            }
            if ($stat->first_played_at === null || $occurredAt->lt($stat->first_played_at)) {
                $stat->first_played_at = $occurredAt;
            }
            if ($stat->last_played_at === null || $occurredAt->gt($stat->last_played_at)) {
                $stat->last_played_at = $occurredAt;
            }
            $stat->save();

            return $event;
        });
    }

    public function statsFor(Game $game, User $player): ?PlayerGameStat
    {
        return PlayerGameStat::where('game_id', $game->id)->where('user_id', $player->id)->first();
    }

    public function leaderboard(Game $game, int $limit): Collection
    {
        $limit = max(1, min($limit, (int) config('portal.leaderboard_max', 100)));

        return PlayerGameStat::with('user:id,name,avatar')
            ->where('game_id', $game->id)
            ->whereNotNull('best_score')
            ->orderByDesc('best_score')
            ->orderBy('last_played_at')
            ->limit($limit)
            ->get()
            ->values()
            ->map(fn (PlayerGameStat $s, int $i) => [
                'rank' => $i + 1,
                'player' => ['id' => $s->user_id, 'name' => $s->user?->name, 'avatar' => $s->user?->avatar],
                'best_score' => $s->best_score,
                'plays' => $s->plays,
                'wins' => $s->wins,
                'last_played_at' => $s->last_played_at?->toIso8601String(),
            ]);
    }

    /** Rank of a player on a game's leaderboard (1-based), or null if unranked. */
    public function rankFor(Game $game, User $player): ?int
    {
        $mine = $this->statsFor($game, $player);
        if (! $mine || $mine->best_score === null) {
            return null;
        }

        return 1 + PlayerGameStat::where('game_id', $game->id)
            ->whereNotNull('best_score')
            ->where('best_score', '>', $mine->best_score)
            ->count();
    }

    /** Totals across every game for one player: the dashboard summary row. */
    public function summaryFor(User $player): array
    {
        $row = PlayerGameStat::where('user_id', $player->id)
            ->selectRaw('count(*) as games, coalesce(sum(plays),0) as plays, coalesce(sum(wins),0) as wins, coalesce(sum(total_score),0) as total_score, coalesce(sum(total_duration_seconds),0) as seconds')
            ->first();

        return [
            'games' => (int) ($row->games ?? 0),
            'plays' => (int) ($row->plays ?? 0),
            'wins' => (int) ($row->wins ?? 0),
            'total_score' => (int) ($row->total_score ?? 0),
            'total_duration_seconds' => (int) ($row->seconds ?? 0),
        ];
    }
}
