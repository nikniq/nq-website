<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Services\GameStatsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GamesController extends Controller
{
    public function __construct(private GameStatsService $stats) {}

    public function index(Request $request): View|JsonResponse
    {
        $user = $request->user();
        $favouriteIds = $user ? $user->favouriteGames()->pluck('games.id')->all() : [];

        $filters = [
            'q' => (string) $request->query('q', ''),
            'genre' => (string) $request->query('genre', ''),
            'featured' => $request->boolean('featured'),
            'dementia' => $request->boolean('dementia'),
            'favourites' => $user && $request->boolean('favourites'),
            'sort' => in_array($request->query('sort'), ['name', 'newest', 'popular'], true) ? $request->query('sort') : '',
        ];

        $games = Game::enabled()
            ->search($filters['q'])
            ->withCount('favouritedBy')
            ->filter($filters)
            ->when($filters['favourites'], fn ($q) => $q->whereIn('id', $favouriteIds ?: [0]))
            ->get();

        // Favourites float to the top for signed-in players (default order only).
        if ($favouriteIds && $filters['sort'] === '') {
            $games = $games->sortBy(fn (Game $g) => in_array($g->id, $favouriteIds, true) ? 0 : 1, SORT_NUMERIC, false)->values();
        }

        if ($request->wantsJson()) {
            return response()->json([
                'games' => $games->map(fn (Game $g) => [
                    'slug' => $g->slug, 'name' => $g->name, 'url' => $g->url,
                    'thumbnail' => $g->thumbnailUrl(), 'favourite' => in_array($g->id, $favouriteIds, true), 'dementia' => $g->is_dementia,
                ]),
            ]);
        }

        return view('games.index', [
            'games' => $games,
            'favouriteIds' => $favouriteIds,
            'query' => $filters['q'],
            'filters' => $filters,
            'genres' => Game::genres(),
            'activeFilters' => count(array_filter([$filters['genre'], $filters['featured'], $filters['dementia'], $filters['favourites']])),
        ]);
    }

    public function show(Request $request, Game $game): View
    {
        abort_unless($game->is_enabled, 404);
        $user = $request->user();

        return view('games.show', [
            'game' => $game,
            'isFavourite' => $user ? $user->hasFavourited($game) : false,
            'favouriteCount' => $game->favouritedBy()->count(),
            'leaderboard' => $this->stats->leaderboard($game, 10),
            'myStats' => $user ? $this->stats->statsFor($game, $user) : null,
            'myRank' => $user ? $this->stats->rankFor($game, $user) : null,
            'playerCount' => $game->stats()->count(),
        ]);
    }

    /** Send the player to the game. Records a `launch` activity for signed-in players. */
    public function play(Request $request, Game $game): RedirectResponse
    {
        abort_unless($game->is_enabled && $game->url, 404);

        if ($user = $request->user()) {
            $this->stats->record($game, $user, ['event' => 'launch', 'metadata' => ['via' => 'portal']]);
        }

        return redirect()->away($game->url);
    }

    public function leaderboard(Game $game): View
    {
        abort_unless($game->is_enabled, 404);

        return view('games.leaderboard', [
            'game' => $game,
            'leaderboard' => $this->stats->leaderboard($game, (int) config('portal.leaderboard_max', 100)),
        ]);
    }
}
