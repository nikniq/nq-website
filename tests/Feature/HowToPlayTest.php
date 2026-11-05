<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Support\HowToPlay;
use Database\Seeders\GamesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HowToPlayTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_seeded_game_has_tips(): void
    {
        $this->assertEqualsCanonicalizing(array_keys(GamesSeeder::GAMES), array_keys(HowToPlay::TIPS));
    }

    public function test_game_page_shows_how_to_play(): void
    {
        $game = Game::factory()->create(['slug' => 'pacman', 'is_enabled' => true]);

        $this->get(route('games.show', $game))->assertSee('How to play')->assertSee('power pellets');
    }
}
