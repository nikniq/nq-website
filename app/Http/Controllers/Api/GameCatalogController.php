<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Game;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /api/games — public catalogue, so games can show "more games" links.
 * Filters: q, genre, featured=1, dementia=1, sort=name|newest|popular.
 */
class GameCatalogController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $games = Game::enabled()
            ->search($request->query('q'))
            ->withCount('favouritedBy')
            ->filter([
                'genre' => $request->query('genre'),
                'featured' => $request->boolean('featured'),
                'dementia' => $request->boolean('dementia'),
                'sort' => $request->query('sort'),
            ])
            ->get()
            ->map(fn (Game $g) => [
                'slug' => $g->slug,
                'name' => $g->name,
                'description' => $g->description,
                'genre' => $g->genre,
                'tags' => $g->tags ?? [],
                'url' => $g->url,
                'thumbnail' => $g->thumbnailUrl(),
                'featured' => $g->is_featured,
                'dementia' => $g->is_dementia,
                'favourites' => $g->favourited_by_count,
            ]);

        return response()->json(['games' => $games, 'genres' => Game::genres()]);
    }
}
