<?php

namespace App\Http\Controllers;

use App\Models\PlayerGameStat;
use App\Models\User;
use App\Services\GameStatsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Public player pages at /players/{user}. Players can hide theirs from /profile. */
class PlayerProfileController extends Controller
{
    public function __construct(private GameStatsService $stats) {}

    public function show(Request $request, User $player): View
    {
        abort_unless($player->is_public || $request->user()?->id === $player->id, 404);

        $gameStats = PlayerGameStat::with('game')->where('user_id', $player->id)->whereHas('game', fn ($q) => $q->where('is_enabled', true))
            ->orderByDesc('best_score')->get();

        return view('players.show', [
            'player' => $player,
            'summary' => $this->stats->summaryFor($player),
            'gameStats' => $gameStats,
            'ranks' => $this->stats->ranksFor($player),
            'favourites' => $player->favouriteGames()->enabled()->orderBy('name')->get(),
            'isMe' => $request->user()?->id === $player->id,
        ]);
    }

    public function visibility(Request $request): RedirectResponse
    {
        $request->user()->update(['is_public' => $request->boolean('is_public')]);

        return back()->with('status', $request->boolean('is_public') ? 'Your player page is now public.' : 'Your player page is now hidden.');
    }
}
