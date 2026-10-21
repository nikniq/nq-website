<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Lets a game toggle its own favourite flag for the signed-in player. */
class FavouriteController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        return $this->set($request, true);
    }

    public function destroy(Request $request): JsonResponse
    {
        return $this->set($request, false);
    }

    private function set(Request $request, bool $on): JsonResponse
    {
        /** @var User $player */
        $player = $request->attributes->get('player');
        /** @var Game $game */
        $game = $request->attributes->get('game');

        $on ? $player->favouriteGames()->syncWithoutDetaching([$game->id])
            : $player->favouriteGames()->detach($game->id);

        return response()->json(['game' => $game->slug, 'favourite' => $on]);
    }
}
