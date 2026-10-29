<?php

namespace Tests\Feature;

use App\Models\Game;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagesAndImagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_privacy_and_terms_pages_are_linked_from_every_page(): void
    {
        $this->get('/privacy')->assertOk()->assertSee('Privacy policy')->assertSee('Google Analytics');
        $this->get('/terms')->assertOk()->assertSee('Fair play');
        $this->get('/')->assertSee(route('privacy'))->assertSee(route('terms'));
        $this->get('/register')->assertSee(route('privacy'));
        $this->get('/sitemap.xml')->assertSee(route('privacy'));
    }

    public function test_webp_is_offered_when_a_sibling_file_exists(): void
    {
        $withWebp = Game::factory()->create(['slug' => 'chopper', 'thumbnail' => '/images/games/chopper.jpg']);
        $svgOnly = Game::factory()->create(['slug' => 'blockworld', 'thumbnail' => '/images/games/blockworld.svg']);

        $this->assertSame('/images/games/chopper.webp', $withWebp->webpUrl());
        $this->assertNull($svgOnly->webpUrl());
        $this->get('/')->assertSee('srcset="/images/games/chopper.webp"', false)->assertSee('fetchpriority="high"', false);
    }

    public function test_new_game_page_invites_the_first_player_instead_of_showing_zeros(): void
    {
        Game::factory()->create(['slug' => 'fresh', 'url' => 'https://fresh.nikniq.com']);
        $this->get('/games/fresh')->assertSee('Be the first')->assertDontSee('>Players<', false);
    }
}
