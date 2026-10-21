<?php

namespace Database\Seeders;

use App\Models\Game;
use Illuminate\Database\Seeder;

/**
 * Starter catalogue: the nq-* games. Safe to re-run: existing rows keep their
 * client credentials and admin edits; only missing games are inserted.
 */
class GamesSeeder extends Seeder
{
    public function run(): void
    {
        $games = [
            ['name' => 'Chopper', 'slug' => 'chopper', 'genre' => 'Arcade', 'featured' => true,
                'description' => 'Pilot a rescue helicopter through tight caverns, dodge obstacles and bring everyone home.'],
            ['name' => 'Blockworld', 'slug' => 'blockworld', 'genre' => 'Sandbox', 'featured' => true,
                'description' => 'Build, mine, and explore a voxel survival world block by block.'],
            ['name' => 'Towers', 'slug' => 'towers', 'genre' => 'Strategy',
                'description' => 'Place towers, hold the line, and survive every wave in this canvas tower defence.'],
            ['name' => 'Dungeon Adventure', 'slug' => 'dungeon', 'genre' => 'Adventure',
                'description' => 'Delve into a browser dungeon, fight monsters and find the treasure.'],
            ['name' => 'Space Invaders', 'slug' => 'space-invaders', 'genre' => 'Arcade',
                'description' => 'The classic alien shooter, rebuilt in HTML5 canvas with crisp SVG sprites.'],
            ['name' => 'Legal Memory', 'slug' => 'lawyer-memory', 'genre' => 'Puzzle',
                'description' => 'Flip the cards, find the pairs, close the case: a legal-themed memory game.', 'host' => 'lawyermemory'],
            ['name' => 'Castle Wolf', 'slug' => 'woolfie', 'genre' => 'Stealth',
                'description' => 'A tiny top-down stealth game: find the key, open the doors, reach the exit.'],
            ['name' => 'Field Command', 'slug' => 'field-command', 'genre' => 'Strategy',
                'description' => 'Mine crystal, build a base and command Rangers, Snipers and Medics in real time.'],
            ['name' => 'Bomberman', 'slug' => 'bomberman', 'genre' => 'Arcade',
                'description' => 'Drop bombs, clear the maze and outlast your rivals.'],
            ['name' => 'Frogger', 'slug' => 'frogger', 'genre' => 'Arcade',
                'description' => 'Hop across traffic and rivers to get the frog home.'],
            ['name' => 'Tetris', 'slug' => 'tetris', 'genre' => 'Puzzle',
                'description' => 'Stack falling blocks, clear lines, chase a high score.'],
            ['name' => 'Pac-Man', 'slug' => 'pacman', 'genre' => 'Arcade',
                'description' => 'Eat the dots, dodge the ghosts, clear the maze.'],
            ['name' => 'Snake', 'slug' => 'snake', 'genre' => 'Arcade',
                'description' => 'Grow the snake, avoid the walls and your own tail.'],
            ['name' => 'Pong', 'slug' => 'pong', 'genre' => 'Arcade',
                'description' => 'The original paddle game, with a leaderboard.'],
            ['name' => 'Reversi', 'slug' => 'reversi', 'genre' => 'Board',
                'description' => 'Flip discs and own the board in this Othello-style strategy game.'],
            ['name' => 'Minesweeper', 'slug' => 'minesweeper', 'genre' => 'Puzzle',
                'description' => 'Clear the field without setting off a mine.'],
            ['name' => 'Asteroids', 'slug' => 'asteroids', 'genre' => 'Arcade',
                'description' => 'Spin, thrust and blast your way through an asteroid field.'],
            ['name' => 'Missile Command', 'slug' => 'missile-command', 'genre' => 'Arcade',
                'description' => 'Defend your cities from incoming missiles.'],
            ['name' => 'Candy Rush', 'slug' => 'candy', 'genre' => 'Puzzle',
                'description' => 'Match three candies and clear the board in this sweet puzzle demo.'],
            ['name' => 'Space Huggers', 'slug' => 'space-huggers', 'genre' => 'Platformer',
                'description' => 'A run-and-gun platformer against an empire spreading across the galaxy.'],
            ['name' => 'Mini Civ', 'slug' => 'miniciv', 'genre' => 'Strategy',
                'description' => 'A pocket-sized civilisation builder.'],
            ['name' => 'Doctor Memory', 'slug' => 'doctor-memory', 'genre' => 'Puzzle',
                'description' => 'A medical-themed memory matching game.'],
        ];

        foreach ($games as $i => $g) {
            $host = $g['host'] ?? $g['slug'];

            Game::firstOrCreate(['slug' => $g['slug']], [
                'name' => $g['name'],
                'description' => $g['description'],
                'genre' => $g['genre'],
                'tags' => ['browser'],
                'url' => "https://{$host}.nikniq.com",
                'thumbnail' => file_exists(public_path("images/games/{$g['slug']}.png")) ? "/images/games/{$g['slug']}.png" : null,
                'client_id' => Game::generateClientId(),
                'client_secret' => null,
                'redirect_uris' => ["https://{$host}.nikniq.com/auth/callback"],
                'is_public' => true, // static JS games use PKCE; flip to confidential and rotate a secret in admin if the game has a server
                'is_enabled' => true,
                'is_featured' => $g['featured'] ?? false,
                'sort_order' => $i,
            ]);
        }
    }
}
