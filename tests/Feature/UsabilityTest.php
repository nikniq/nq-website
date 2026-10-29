<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\User;
use App\Services\GameStatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_header_has_phone_menu_with_the_same_links(): void
    {
        $html = $this->actingAs(User::factory()->create(['is_admin' => true]))->get('/')->getContent();
        $this->assertStringContainsString('<details class="menu">', $html);
        $panel = substr($html, strpos($html, 'menu-panel'));
        foreach (['Hall of fame', 'My dashboard', 'Profile', 'Admin', 'Log out'] as $label) {
            $this->assertStringContainsString($label, $panel);
        }
    }

    public function test_new_player_dashboard_shows_get_started_and_suggestions(): void
    {
        Game::factory()->create(['name' => 'Starter Game', 'is_featured' => true]);
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard')->assertSee('Get started')->assertSee('Starter Game')->assertDontSee('Total score');

        app(GameStatsService::class)->record(Game::first(), $user, ['event' => 'game_over', 'score' => 3]);
        $this->actingAs($user)->get('/dashboard')->assertDontSee('Get started')->assertSee('Total score');
    }

    public function test_flash_messages_are_dismissible(): void
    {
        $user = User::factory()->create();
        $game = Game::factory()->create();
        $this->actingAs($user)->post("/games/{$game->slug}/favourite");
        $this->actingAs($user)->get('/')->assertSee('flash-close', false);
    }
}
