<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FavouriteController extends Controller
{
    public function toggle(Request $request, Game $game): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        if ($user->hasFavourited($game)) {
            $user->favouriteGames()->detach($game->id);
            $on = false;
        } else {
            $user->favouriteGames()->syncWithoutDetaching([$game->id]);
            $on = true;
        }

        if ($request->wantsJson()) {
            return response()->json(['game' => $game->slug, 'favourite' => $on]);
        }

        return back()->with('status', $on ? __('Added :game to your favourites.', ['game' => $game->name]) : __('Removed :game from your favourites.', ['game' => $game->name]));
    }
}
