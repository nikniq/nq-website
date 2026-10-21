<?php

namespace Database\Seeders;

use App\Models\Game;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The catalogue: the nq-* games on their nikniq.com subdomains.
 *
 * Catalogue fields (name, description, genre, URL, thumbnail, order) are
 * owned by this seeder and refreshed on every run. Credentials and the
 * enabled/featured/public flags are set once on insert and never touched
 * again, so admin changes to them survive a re-seed.
 */
class GamesSeeder extends Seeder
{
    /** slug => [name, host (subdomain of nikniq.com), genre, description, featured?] */
    public const GAMES = [
        'chopper' => ['Chopper', 'chopper', 'Arcade', 'Pilot a rescue helicopter through tight caverns, dodge obstacles and bring everyone home.', true],
        'blockworld' => ['Blockworld', 'blockworld', 'Sandbox', 'Mine by day, survive the night: a voxel survival world built block by block.', true],
        'towers' => ['Towers', 'towers', 'Strategy', 'Place towers, hold the line, and survive every wave in this canvas tower defence.'],
        'dungeon' => ['Dungeon Adventure', 'dungeon', 'Roguelike', 'A free browser roguelike: delve, fight monsters, find the treasure.'],
        'space-invaders' => ['Space Invaders', 'spaceinvaders', 'Arcade', 'The classic alien shooter, rebuilt in HTML5 canvas with crisp SVG sprites.'],
        'missile-command' => ['Missile Command', 'missilecommand', 'Arcade', 'Defend your cities from incoming missiles.'],
        'asteroids' => ['Asteroids', 'asteroids', 'Arcade', 'Spin, thrust and blast your way through an asteroid field.'],
        'pacman' => ['Pac-Man', 'pacman', 'Arcade', 'Eat the dots, dodge the ghosts, clear the maze.'],
        'tetris' => ['Tetris', 'tetris', 'Puzzle', 'Stack falling blocks, clear lines, chase a high score.'],
        'snake' => ['Snake', 'snake', 'Arcade', 'Grow the snake, avoid the walls and your own tail.'],
        'pong' => ['Pong', 'pong', 'Arcade', 'The original paddle game, with a leaderboard.'],
        'frogger' => ['Frogger', 'frogger', 'Arcade', 'Hop across traffic and rivers to get the frog home.'],
        'bomberman' => ['Bomberman', 'bomberman', 'Arcade', 'Drop bombs, clear the maze and outlast your rivals.'],
        'minesweeper' => ['Minesweeper', 'minesweeper', 'Puzzle', 'Clear the field without setting off a mine.'],
        'reversi' => ['Reversi', 'reversi', 'Board', 'Flip discs and own the board in this Othello-style strategy game.'],
        'candy' => ['Candy Rush', 'candyrush', 'Puzzle', 'Match three candies and clear the board in this sweet puzzle.'],
        'space-huggers' => ['Space Huggers', 'spacehuggers', 'Platformer', 'A run-and-gun platformer against an empire spreading across the galaxy.'],
        'miniciv' => ['MiniCiv', 'miniciv', 'Strategy', 'A pocket-sized civilisation builder.'],
        'dwarfs' => ['Dwarfs', 'dwarfs', 'Adventure', 'Dwarfs: There and Back Again. Dig, gather and get the party home.'],
        'woolfie' => ['Castle Wolf', 'woolfie', 'Stealth', 'A tiny top-down stealth game: find the key, open the doors, reach the exit.'],
        'lawyer-memory' => ['Legal Memory', 'lawyermemory', 'Puzzle', 'Flip the cards, find the pairs, close the case: a legal-themed memory game.'],
        'doctor-memory' => ['Doctor Memory', 'doctormemory', 'Puzzle', 'A medical-themed memory matching game.'],
    ];

    public function run(): void
    {
        $i = 0;
        foreach (self::GAMES as $slug => [$name, $host, $genre, $description]) {
            $featured = (bool) (self::GAMES[$slug][4] ?? false);
            $thumb = "/images/games/{$slug}.svg";

            $catalogue = [
                'name' => $name,
                'description' => $description,
                'genre' => $genre,
                'tags' => ['browser'],
                'url' => "https://{$host}.nikniq.com",
                'thumbnail' => file_exists(public_path($thumb)) ? $thumb : null,
                'sort_order' => $i++,
            ];

            $game = Game::where('slug', $slug)->first();
            if ($game) {
                $game->fill($catalogue)->save();

                continue;
            }

            Game::create($catalogue + [
                'slug' => $slug,
                'client_id' => Game::generateClientId(),
                'client_secret' => null,
                'redirect_uris' => ["https://{$host}.nikniq.com/auth/callback"],
                'is_public' => true, // static JS games use PKCE; flip to confidential and rotate a secret in admin if the game has a server
                'is_enabled' => true,
                'is_featured' => $featured,
            ]);
        }

        $this->portLegacyFavourites();
    }

    /**
     * Favourites inherited from the old nikniq.com database pointed at
     * `products`; match them to games by name now that the games exist.
     */
    private function portLegacyFavourites(): void
    {
        if (! Schema::hasTable('legacy_favourites') || ! Schema::hasTable('products')) {
            return;
        }

        $rows = DB::table('legacy_favourites as f')
            ->join('products as p', 'p.id', '=', 'f.favoritable_id')
            ->join('games as g', 'g.name', '=', 'p.name')
            ->where('f.favoritable_type', 'like', '%Product')
            ->select('f.user_id', 'g.id as game_id', 'f.created_at')
            ->get();

        foreach ($rows as $row) {
            DB::table('favourites')->insertOrIgnore([
                'user_id' => $row->user_id,
                'game_id' => $row->game_id,
                'created_at' => $row->created_at ?? now(),
                'updated_at' => now(),
            ]);
        }
    }
}
