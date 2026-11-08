<?php

namespace Tests\Feature;

use App\Models\Game;
use Database\Seeders\GamesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederHostChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_changed_subdomain_moves_url_and_redirect_uris(): void
    {
        Game::factory()->create([
            'slug' => 'pacman', 'name' => 'Pac-Man', 'url' => 'https://pacman.nikniq.com',
            'redirect_uris' => ['https://pacman.nikniq.com/auth/callback'],
        ]);

        $this->seed(GamesSeeder::class);

        $game = Game::where('slug', 'pacman')->first();
        $this->assertSame('Packman', $game->name);
        $this->assertSame('https://packman.nikniq.com', $game->url);
        $this->assertSame(['https://packman.nikniq.com/auth/callback'], $game->redirect_uris);
    }
}
