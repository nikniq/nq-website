<?php

namespace App\Services;

use App\Models\Game;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Read-only aggregates for /admin/reports. A "period" is the last N whole
 * days including today; each KPI is compared with the N days before it.
 */
class ReportService
{
    public const RANGES = [7, 30, 90];

    /** @return array{from:Carbon,to:Carbon,prevFrom:Carbon} */
    public function window(int $days): array
    {
        $to = now()->endOfDay();
        $from = now()->subDays($days - 1)->startOfDay();

        return ['from' => $from, 'to' => $to, 'prevFrom' => $from->copy()->subDays($days)];
    }

    /** KPI => [current, previous]. */
    public function kpis(int $days): array
    {
        ['from' => $from, 'to' => $to, 'prevFrom' => $prevFrom] = $this->window($days);
        $prevTo = $from->copy()->subSecond();
        $plays = config('portal.play_events');

        $pair = fn (callable $q) => [$q($from, $to), $q($prevFrom, $prevTo)];

        return [
            'new_players' => $pair(fn ($a, $b) => User::whereBetween('created_at', [$a, $b])->count()),
            'active_players' => $pair(fn ($a, $b) => DB::table('game_events')->whereBetween('occurred_at', [$a, $b])->distinct()->count('user_id')),
            'plays' => $pair(fn ($a, $b) => DB::table('game_events')->whereIn('event', $plays)->whereBetween('occurred_at', [$a, $b])->count()),
            'launches' => $pair(fn ($a, $b) => DB::table('game_events')->where('event', 'launch')->whereBetween('occurred_at', [$a, $b])->count()),
            'favourites' => $pair(fn ($a, $b) => DB::table('favourites')->whereBetween('created_at', [$a, $b])->count()),
            'minutes' => $pair(fn ($a, $b) => intdiv((int) DB::table('game_events')->whereBetween('occurred_at', [$a, $b])->sum('duration_seconds'), 60)),
        ];
    }

    /**
     * One row per day in the period (zero-filled):
     * date, plays, launches, active_players, new_players.
     */
    public function daily(int $days): Collection
    {
        ['from' => $from, 'to' => $to] = $this->window($days);
        $plays = config('portal.play_events');

        $events = DB::table('game_events')
            ->whereBetween('occurred_at', [$from, $to])
            ->selectRaw('DATE(occurred_at) as d')
            ->selectRaw('SUM(CASE WHEN event IN ('.implode(',', array_fill(0, count($plays), '?')).') THEN 1 ELSE 0 END) as plays', $plays)
            ->selectRaw("SUM(CASE WHEN event = 'launch' THEN 1 ELSE 0 END) as launches")
            ->selectRaw('COUNT(DISTINCT user_id) as active_players')
            ->groupBy('d')->get()->keyBy('d');

        $signups = User::whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as d, COUNT(*) as n')->groupBy('d')->pluck('n', 'd');

        return collect(range(0, $days - 1))->map(function ($i) use ($from, $events, $signups) {
            $d = $from->copy()->addDays($i)->toDateString();
            $e = $events->get($d);

            return (object) [
                'date' => Carbon::parse($d),
                'plays' => (int) ($e->plays ?? 0),
                'launches' => (int) ($e->launches ?? 0),
                'active_players' => (int) ($e->active_players ?? 0),
                'new_players' => (int) ($signups[$d] ?? 0),
            ];
        });
    }

    /** Per-game activity in the period, busiest first. */
    public function games(int $days): Collection
    {
        ['from' => $from, 'to' => $to] = $this->window($days);
        $plays = config('portal.play_events');
        $in = implode(',', array_fill(0, count($plays), '?'));

        $events = DB::table('game_events')
            ->whereBetween('occurred_at', [$from, $to])
            ->select('game_id')
            ->selectRaw("SUM(CASE WHEN event IN ($in) THEN 1 ELSE 0 END) as plays", $plays)
            ->selectRaw("SUM(CASE WHEN event = 'launch' THEN 1 ELSE 0 END) as launches")
            ->selectRaw('COUNT(DISTINCT user_id) as players')
            ->selectRaw('COALESCE(SUM(duration_seconds), 0) as seconds')
            ->selectRaw('MAX(score) as best_score')
            ->groupBy('game_id')->get()->keyBy('game_id');

        $favs = DB::table('favourites')->whereBetween('created_at', [$from, $to])
            ->selectRaw('game_id, COUNT(*) as n')->groupBy('game_id')->pluck('n', 'game_id');
        $favTotal = DB::table('favourites')->selectRaw('game_id, COUNT(*) as n')->groupBy('game_id')->pluck('n', 'game_id');

        return Game::orderBy('name')->get(['id', 'slug', 'name', 'is_enabled'])->map(function ($g) use ($events, $favs, $favTotal) {
            $e = $events->get($g->id);

            return (object) [
                'game' => $g,
                'plays' => (int) ($e->plays ?? 0),
                'launches' => (int) ($e->launches ?? 0),
                'players' => (int) ($e->players ?? 0),
                'minutes' => intdiv((int) ($e->seconds ?? 0), 60),
                'best_score' => $e->best_score ?? null,
                'favourites_added' => (int) ($favs[$g->id] ?? 0),
                'favourites_total' => (int) ($favTotal[$g->id] ?? 0),
            ];
        })->sortBy([fn ($a, $b) => $b->plays <=> $a->plays, fn ($a, $b) => $b->launches <=> $a->launches, fn ($a, $b) => $b->favourites_total <=> $a->favourites_total])->values();
    }

    /** Most active players in the period. */
    public function topPlayers(int $days, int $limit = 10): Collection
    {
        ['from' => $from, 'to' => $to] = $this->window($days);

        return DB::table('game_events')
            ->join('users', 'users.id', '=', 'game_events.user_id')
            ->whereBetween('occurred_at', [$from, $to])
            ->groupBy('users.id', 'users.name')
            ->select('users.id', 'users.name')
            ->selectRaw('COUNT(*) as events')
            ->selectRaw('COUNT(DISTINCT game_events.game_id) as games')
            ->selectRaw('MAX(game_events.occurred_at) as last_seen')
            ->orderByDesc('events')->limit($limit)->get();
    }
}
