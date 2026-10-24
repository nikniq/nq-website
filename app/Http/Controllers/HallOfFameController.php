<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\PlayerGameStat;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** GET /hall-of-fame: the best players across every game, plus each game's champion. */
class HallOfFameController extends Controller
{
    public function __invoke(Request $request): View
    {
        $period = in_array($request->query('period'), ['week', 'month'], true) ? $request->query('period') : 'all';
        $since = match ($period) {
            'week' => now()->subWeek(), 'month' => now()->subMonth(), default => null
        };

        // Overall standings: a player's "medals" are top-3 finishes per game, weighted 3/2/1,
        // plus wins and plays as tie-breakers. Only enabled games count.
        $stats = PlayerGameStat::query()
            ->join('games', 'games.id', '=', 'player_game_stats.game_id')
            ->where('games.is_enabled', true)
            ->whereNotNull('best_score')
            ->when($since, fn ($q) => $q->where('last_played_at', '>=', $since))
            ->get(['player_game_stats.*']);

        $points = [];
        foreach ($stats->groupBy('game_id') as $gameStats) {
            $ranked = $gameStats->sortByDesc('best_score')->values();
            foreach ($ranked->take(3) as $i => $s) {
                $points[$s->user_id] = ($points[$s->user_id] ?? 0) + (3 - $i);
            }
        }
        $totals = $stats->groupBy('user_id')->map(fn ($rows) => [
            'games' => $rows->count(), 'wins' => $rows->sum('wins'), 'plays' => $rows->sum('plays'), 'score' => $rows->sum('total_score'),
        ]);
        $users = User::whereIn('id', array_keys($points) ?: [0])->where('is_public', true)->get()->keyBy('id');

        $overall = collect($points)->map(fn ($p, $uid) => ['user' => $users[$uid] ?? null, 'points' => $p] + $totals[$uid])
            ->filter(fn ($r) => $r['user'])
            ->sortByDesc(fn ($r) => [$r['points'], $r['wins'], $r['plays']])
            ->take(25)->values();

        // Per-game champions.
        $champions = Game::enabled()->orderBy('sort_order')->orderBy('name')->get()->map(function (Game $g) use ($stats) {
            $top = $stats->where('game_id', $g->id)->sortByDesc('best_score')->first();

            return ['game' => $g, 'stat' => $top, 'user' => $top ? User::find($top->user_id) : null];
        })->filter(fn ($c) => $c['stat'] && $c['user'] && $c['user']->is_public)->values();

        return view('hall-of-fame', ['overall' => $overall, 'champions' => $champions, 'period' => $period]);
    }
}
