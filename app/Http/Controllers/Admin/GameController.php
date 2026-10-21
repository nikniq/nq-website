<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\GameRequest;
use App\Models\Game;
use App\Models\GameEvent;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GameController extends Controller
{
    public function index(): View
    {
        return view('admin.games.index', [
            'games' => Game::withCount(['favouritedBy', 'stats', 'events'])->orderBy('sort_order')->orderBy('name')->get(),
            'totals' => [
                'users' => User::count(),
                'events' => GameEvent::count(),
                'events_today' => GameEvent::where('occurred_at', '>=', now()->startOfDay())->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.games.form', ['game' => new Game(['is_enabled' => true])]);
    }

    public function store(GameRequest $request): RedirectResponse
    {
        $game = new Game($request->gameAttributes());
        $game->client_id = Game::generateClientId();

        $secret = null;
        if (! $game->is_public) {
            $secret = Game::generateSecret();
            $game->setPlainSecret($secret);
        }
        $game->save();

        return redirect()->route('admin.games.edit', $game)
            ->with('status', 'Game created.')
            ->with('client_secret', $secret);
    }

    public function edit(Game $game): View
    {
        return view('admin.games.form', ['game' => $game]);
    }

    public function update(GameRequest $request, Game $game): RedirectResponse
    {
        $game->fill($request->gameAttributes())->save();

        return redirect()->route('admin.games.edit', $game)->with('status', 'Game updated.');
    }

    /** Issue a fresh secret; the old one stops working immediately. */
    public function rotateSecret(Game $game): RedirectResponse
    {
        $secret = Game::generateSecret();
        $game->setPlainSecret($secret);
        $game->save();

        return back()->with('status', 'Secret rotated.')->with('client_secret', $secret);
    }

    public function destroy(Game $game): RedirectResponse
    {
        $game->delete();

        return redirect()->route('admin.games.index')->with('status', 'Game deleted, with its favourites, tokens and stats.');
    }
}
