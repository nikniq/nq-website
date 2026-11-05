<?php

namespace Tests\Feature;

use App\Models\Game;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RandomGameTest extends TestCase
{
    use RefreshDatabase;

    public function test_surprise_me_opens_an_enabled_game(): void
    {
        $game = Game::factory()->create(['is_enabled' => true, 'url' => 'https://x.nikniq.com']);

        $this->get('/games/random')->assertRedirect(route('games.show', $game));
    }

    public function test_surprise_me_falls_back_to_catalogue(): void
    {
        $this->get('/games/random')->assertRedirect(route('games.index'));
    }

    public function test_favicon_files_exist(): void
    {
        foreach (['favicon.ico', 'favicon.svg', 'apple-touch-icon.png', 'images/icon-192.png'] as $f) {
            $this->assertFileExists(public_path($f));
        }
        $this->get('/')->assertSee('apple-touch-icon', false);
    }
}
