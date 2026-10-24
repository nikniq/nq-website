<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\User;
use App\Services\GameStatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscoveryAndAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_continue_playing_shows_recent_games_for_signed_in_users_only(): void
    {
        $a = Game::factory()->create(['name' => 'Alpha Quest']);
        $b = Game::factory()->create(['name' => 'Beta Blast']);
        $user = User::factory()->create();
        $stats = app(GameStatsService::class);
        $stats->record($a, $user, ['event' => 'launch', 'occurred_at' => now()->subDay()]);
        $stats->record($b, $user, ['event' => 'game_over', 'score' => 5]);

        $this->get('/')->assertDontSee('Continue playing');
        $html = $this->actingAs($user)->get('/')->assertSee('Continue playing')->getContent();
        $section = substr($html, strpos($html, 'Continue playing'), strpos($html, 'All games') - strpos($html, 'Continue playing'));
        $this->assertLessThan(strpos($section, 'Alpha Quest'), strpos($section, 'Beta Blast'), 'most recent first');
        $this->actingAs($user)->get('/?genre=Arcade')->assertDontSee('Continue playing');
    }

    public function test_game_page_suggests_related_games_same_genre_first(): void
    {
        $g = Game::factory()->create(['slug' => 'main', 'genre' => 'Puzzle']);
        Game::factory()->create(['name' => 'Puzzle Pal', 'genre' => 'Puzzle']);
        Game::factory()->create(['name' => 'Shooty', 'genre' => 'Arcade', 'is_featured' => true]);
        Game::factory()->create(['name' => 'Hidden Gem', 'genre' => 'Puzzle', 'is_enabled' => false]);

        $html = $this->get('/games/main')->assertSee('You might also like')->assertDontSee('Hidden Gem')->getContent();
        $section = substr($html, strpos($html, 'You might also like'));
        $this->assertLessThan(strpos($section, 'Shooty'), strpos($section, 'Puzzle Pal'));
    }

    public function test_bulk_ranks_match_single_ranks(): void
    {
        $g = Game::factory()->create();
        [$u1, $u2, $u3] = User::factory()->count(3)->create();
        $stats = app(GameStatsService::class);
        $stats->record($g, $u1, ['event' => 'game_over', 'score' => 100]);
        $stats->record($g, $u2, ['event' => 'game_over', 'score' => 300]);
        $stats->record($g, $u3, ['event' => 'game_over', 'score' => 200]);

        $this->assertSame([$g->id => 3], $stats->ranksFor($u1));
        $this->assertSame($stats->rankFor($g, $u3), $stats->ranksFor($u3)[$g->id]);
        $this->actingAs($u3)->get('/dashboard')->assertSee('#2');
    }

    public function test_admin_players_and_activity_pages(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $player = User::factory()->create(['name' => 'Zed Zephyr']);
        $g = Game::factory()->create(['name' => 'Rocket Run']);
        app(GameStatsService::class)->record($g, $player, ['event' => 'game_over', 'score' => 42]);

        $this->actingAs($admin)->get('/admin/players')->assertOk()->assertSee('Zed Zephyr');
        $this->actingAs($admin)->get('/admin/players?q=zephyr')->assertSee('Zed Zephyr')->assertDontSee($admin->email);
        $this->actingAs($admin)->get('/admin/activity')->assertOk()->assertSee('Rocket Run')->assertSee('game_over');
        $this->actingAs($player)->get('/admin/players')->assertForbidden();
    }
}
