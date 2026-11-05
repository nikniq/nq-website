<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\User;
use App\Services\GameStatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_player_can_download_their_data(): void
    {
        $user = User::factory()->create();
        $game = Game::factory()->create(['slug' => 'chopper']);
        $user->favouriteGames()->attach($game);
        app(GameStatsService::class)->record($game, $user, ['event' => 'game_over', 'score' => 77]);

        $this->actingAs($user)->get('/profile/export')->assertOk()
            ->assertHeader('Content-Disposition')
            ->assertJsonPath('account.email', $user->email)
            ->assertJsonPath('favourites.0.slug', 'chopper')
            ->assertJsonPath('stats.0.best_score', 77)
            ->assertJsonPath('events.0.event', 'game_over')
            ->assertJsonMissingPath('account.password');
    }

    public function test_account_deletion_requires_typing_the_email_and_removes_everything(): void
    {
        $user = User::factory()->create(['email' => 'gone@example.com']);
        $game = Game::factory()->create();
        $user->favouriteGames()->attach($game);
        app(GameStatsService::class)->record($game, $user, ['event' => 'game_over', 'score' => 5]);

        $this->actingAs($user)->delete('/profile', ['confirm_email' => 'wrong@example.com'])->assertSessionHasErrors('confirm_email');
        $this->assertDatabaseHas('users', ['id' => $user->id]);

        $this->actingAs($user)->delete('/profile', ['confirm_email' => 'GONE@example.com '])->assertRedirect('/');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseCount('favourites', 0);
        $this->assertDatabaseCount('game_events', 0);
        $this->assertDatabaseCount('player_game_stats', 0);
    }

    public function test_developer_page_and_sdk_are_served(): void
    {
        $game = Game::factory()->create(['slug' => 'chopper']);
        $this->get('/developers')->assertOk()->assertSee('NikniQ.init', false)->assertSee('CLIENT_ID');
        $this->actingAs(User::factory()->create(['is_admin' => true]))->get('/developers?game=chopper')->assertSee($game->client_id);
        $this->assertFileExists(public_path('js/nikniq.js'));
    }
}
