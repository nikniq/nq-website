<?php

namespace App\Http\Controllers;

use App\Models\GameEvent;
use App\Models\PlayerGameStat;
use App\Services\GameStatsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private GameStatsService $stats) {}

    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $gameStats = PlayerGameStat::with('game')
            ->where('user_id', $user->id)
            ->orderByDesc('last_played_at')
            ->get()
            ->filter(fn (PlayerGameStat $s) => $s->game !== null);

        $ranks = $gameStats->mapWithKeys(fn (PlayerGameStat $s) => [$s->game_id => $this->stats->rankFor($s->game, $user)]);

        $activity = GameEvent::with('game:id,name,slug')
            ->where('user_id', $user->id)
            ->orderByDesc('occurred_at')
            ->limit((int) config('portal.activity_limit', 30))
            ->get();

        return view('dashboard.index', [
            'summary' => $this->stats->summaryFor($user),
            'gameStats' => $gameStats,
            'ranks' => $ranks,
            'favourites' => $user->favouriteGames()->enabled()->orderBy('name')->get(),
            'activity' => $activity,
        ]);
    }
}
