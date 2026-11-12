<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\User;
use App\Services\GameStatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReportsTest extends TestCase
{
    use RefreshDatabase;

    private function seedActivity(): Game
    {
        $game = Game::factory()->create(['name' => 'Reportable']);
        $player = User::factory()->create();
        $stats = app(GameStatsService::class);
        $stats->record($game, $player, ['event' => 'game_over', 'score' => 120, 'duration_seconds' => 180]);
        $stats->record($game, $player, ['event' => 'launch']);
        $player->favouriteGames()->attach($game);

        return $game;
    }

    public function test_non_admins_cannot_see_reports(): void
    {
        $this->get('/admin/reports')->assertRedirect();
        $this->actingAs(User::factory()->create())->get('/admin/reports')->assertForbidden();
    }

    public function test_admin_sees_kpis_charts_and_games(): void
    {
        $this->seedActivity();
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get('/admin/reports?days=7')->assertOk()
            ->assertSee('Active players per day')->assertSee('Reportable')->assertSee('120')
            ->assertSee('Last 7 days');
    }

    public function test_csv_exports(): void
    {
        $this->seedActivity();
        $admin = User::factory()->create(['is_admin' => true]);

        $games = $this->actingAs($admin)->get('/admin/reports/export?report=games&days=30');
        $games->assertOk();
        $csv = $games->streamedContent();
        $this->assertStringContainsString('game,slug,enabled,plays', $csv);
        $this->assertMatchesRegularExpression('/Reportable,[^,]+,yes,1,1,1,3,120,1,1/', $csv);

        $daily = $this->actingAs($admin)->get('/admin/reports/export?report=daily&days=7')->streamedContent();
        $this->assertSame(8, count(array_filter(explode("\n", $daily))));
    }
}
