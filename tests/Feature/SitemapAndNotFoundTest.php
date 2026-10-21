<?php

namespace Tests\Feature;

use App\Models\Game;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapAndNotFoundTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_lists_enabled_games_only(): void
    {
        Game::factory()->create(['slug' => 'chopper', 'thumbnail' => '/images/games/chopper.svg']);
        Game::factory()->create(['slug' => 'hidden', 'is_enabled' => false]);

        $response = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $response->assertSee('<loc>'.url('/').'</loc>', false)
            ->assertSee('<loc>'.url('/games/chopper').'</loc>', false)
            ->assertSee('<loc>'.url('/games/chopper/leaderboard').'</loc>', false)
            ->assertSee('<image:loc>'.url('/images/games/chopper.svg').'</image:loc>', false)
            ->assertDontSee('/games/hidden', false);

        // Cache is dropped when a game changes.
        Game::factory()->create(['slug' => 'newgame']);
        $this->get('/sitemap.xml')->assertSee('/games/newgame', false);
    }

    public function test_missing_pages_redirect_to_the_catalogue(): void
    {
        $this->get('/shop')->assertRedirect('/');
        $this->get('/games/does-not-exist')->assertRedirect('/')->assertSessionHas('status');
        $this->get('/license/ABCD-EFGH-IJKL')->assertRedirect('/');
    }

    public function test_api_assets_and_non_get_requests_still_get_404(): void
    {
        $this->getJson('/api/games/nope/leaderboard')->assertNotFound();
        $this->get('/images/games/missing.png')->assertNotFound();
        $this->get('/old-file.php')->assertNotFound();
        $this->post('/nothing-here')->assertNotFound();
    }
}
