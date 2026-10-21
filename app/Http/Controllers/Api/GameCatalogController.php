<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Game;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** GET /api/games — public catalogue, so games can show "more games" links. */
class GameCatalogController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $games = Game::enabled()
            ->search($request->query('q'))
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderBy('name')
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
            ]);

        return response()->json(['games' => $games]);
    }
}
