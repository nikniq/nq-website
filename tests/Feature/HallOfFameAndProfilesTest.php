<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\User;
use App\Services\GameStatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HallOfFameAndProfilesTest extends TestCase
{
    use RefreshDatabase;

    private function seedScores(): array
    {
        $a = Game::factory()->create(['name' => 'Alpha']);
        $b = Game::factory()->create(['name' => 'Beta']);
        [$u1, $u2] = User::factory()->count(2)->create();
        $s = app(GameStatsService::class);
        $s->record($a, $u1, ['event' => 'game_over', 'score' => 900, 'won' => true]);
        $s->record($a, $u2, ['event' => 'game_over', 'score' => 100]);
        $s->record($b, $u2, ['event' => 'game_over', 'score' => 50]);

        return [$a, $b, $u1, $u2];
    }

    public function test_hall_of_fame_ranks_players_and_lists_champions(): void
    {
        [$a, $b, $u1, $u2] = $this->seedScores();
        $html = $this->get('/hall-of-fame')->assertOk()->assertSee($u1->name)->assertSee($u2->name)->getContent();
        // u2 has two top-3 finishes (2 + 3 = 5 points) vs u1's one win (3 points), so u2 leads.
        $table = substr($html, strpos($html, 'Overall standings'), strpos($html, 'Champions') - strpos($html, 'Overall standings'));
        $this->assertLessThan(strpos($table, $u1->name), strpos($table, $u2->name));
        $this->assertStringContainsString('🏆', $html);
        $this->get('/hall-of-fame?period=week')->assertOk();
    }

    public function test_public_profile_and_hiding(): void
    {
        [$a, $b, $u1, $u2] = $this->seedScores();
        $this->get("/players/{$u1->id}")->assertOk()->assertSee($u1->name)->assertSee('Alpha')->assertSee('#1');

        $this->actingAs($u1)->put('/profile/visibility', ['is_public' => 0])->assertRedirect();
        $this->assertFalse($u1->fresh()->is_public);
        $this->actingAs($u1)->get("/players/{$u1->id}")->assertOk(); // owner still sees it

        $this->post('/logout');
        $this->get('/hall-of-fame')->assertDontSee($u1->name);
        $this->get("/players/{$u1->id}")->assertRedirect('/?missing=1'); // hidden: treated as missing page
    }

    public function test_leaderboard_links_to_player_pages_and_game_page_has_share_buttons(): void
    {
        [$a] = $this->seedScores();
        $this->get("/games/{$a->slug}/leaderboard")->assertSee('/players/', false);
        $this->get("/games/{$a->slug}")->assertSee('data-share', false)->assertSee('wa.me', false);
        $this->get('/')->assertSee('id="calm-toggle"', false)->assertSee('Hall of fame');
    }
}
