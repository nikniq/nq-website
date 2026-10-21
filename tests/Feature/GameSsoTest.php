<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameSsoTest extends TestCase
{
    use RefreshDatabase;

    private const CALLBACK = 'https://chopper.nikniq.com/auth/callback';

    private function confidentialGame(): Game
    {
        return Game::factory()->withSecret('top-secret')->create(['slug' => 'chopper', 'redirect_uris' => [self::CALLBACK]]);
    }

    private function codeFor(Game $game, User $user, array $extra = []): string
    {
        $response = $this->actingAs($user)->get('/sso/authorize?'.http_build_query([
            'client_id' => $game->client_id, 'redirect_uri' => self::CALLBACK, 'state' => 'xyz',
        ] + $extra));
        $response->assertRedirect();
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->assertSame('xyz', $query['state']);

        return $query['code'];
    }

    private function tokenFor(Game $game, User $user): string
    {
        $code = $this->codeFor($game, $user);

        return $this->postJson('/api/sso/token', [
            'grant_type' => 'authorization_code', 'client_id' => $game->client_id, 'client_secret' => 'top-secret',
            'code' => $code, 'redirect_uri' => self::CALLBACK,
        ])->assertCreated()->json('access_token');
    }

    public function test_guest_is_sent_to_login_and_back(): void
    {
        $game = $this->confidentialGame();
        $this->get('/sso/authorize?client_id='.$game->client_id)->assertRedirect('/login');
    }

    public function test_unregistered_redirect_uri_shows_error_page_without_redirecting(): void
    {
        $game = $this->confidentialGame();
        $this->actingAs(User::factory()->create())
            ->get('/sso/authorize?client_id='.$game->client_id.'&redirect_uri=https://evil.example/cb')
            ->assertOk()->assertSee('not registered');
    }

    public function test_full_code_exchange_and_event_reporting(): void
    {
        $game = $this->confidentialGame();
        $user = User::factory()->create();
        $token = $this->tokenFor($game, $user);

        $this->withToken($token)->postJson('/api/games/chopper/events', ['event' => 'game_over', 'score' => 1240, 'won' => false, 'duration_seconds' => 87, 'level' => 'cave-3'])
            ->assertCreated()->assertJsonPath('stats.plays', 1)->assertJsonPath('stats.best_score', 1240)->assertJsonPath('rank', 1);

        $this->withToken($token)->postJson('/api/games/chopper/events', ['events' => [
            ['event' => 'game_over', 'score' => 900, 'won' => true],
            ['event' => 'checkpoint', 'level' => 'cave-4'],
        ]])->assertCreated()->assertJsonPath('recorded', 2)->assertJsonPath('stats.plays', 2)->assertJsonPath('stats.wins', 1)
            ->assertJsonPath('stats.best_score', 1240)->assertJsonPath('stats.total_score', 2140)->assertJsonPath('stats.last_level', 'cave-4');

        $this->withToken($token)->getJson('/api/sso/me')->assertOk()->assertJsonPath('player.id', $user->id)->assertJsonPath('stats.events', 3);

        $this->getJson('/api/games/chopper/leaderboard')->assertOk()->assertJsonPath('leaderboard.0.player.id', $user->id);

        // The portal dashboard aggregates the same data.
        $this->actingAs($user)->get('/dashboard')->assertOk()->assertSee('1,240')->assertSee('cave-4');
    }

    public function test_code_is_single_use_and_secret_is_checked(): void
    {
        $game = $this->confidentialGame();
        $code = $this->codeFor($game, User::factory()->create());
        $body = ['grant_type' => 'authorization_code', 'client_id' => $game->client_id, 'code' => $code, 'redirect_uri' => self::CALLBACK];

        $this->postJson('/api/sso/token', $body + ['client_secret' => 'wrong'])->assertStatus(401);
        $this->postJson('/api/sso/token', $body + ['client_secret' => 'top-secret'])->assertCreated();
        $this->postJson('/api/sso/token', $body + ['client_secret' => 'top-secret'])->assertStatus(400)->assertJsonPath('error', 'invalid_grant');
    }

    public function test_public_client_requires_pkce(): void
    {
        $game = Game::factory()->public()->create(['slug' => 'pong', 'redirect_uris' => [self::CALLBACK]]);
        $user = User::factory()->create();

        $noPkce = $this->actingAs($user)->get('/sso/authorize?'.http_build_query(['client_id' => $game->client_id, 'redirect_uri' => self::CALLBACK]));
        $this->assertStringContainsString('error=invalid_request', $noPkce->headers->get('Location'));

        $verifier = str_repeat('v', 50);
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
        $code = $this->codeFor($game, $user, ['code_challenge' => $challenge, 'code_challenge_method' => 'S256']);
        $body = ['grant_type' => 'authorization_code', 'client_id' => $game->client_id, 'code' => $code, 'redirect_uri' => self::CALLBACK];

        $this->postJson('/api/sso/token', $body)->assertStatus(400)->assertJsonPath('error', 'invalid_grant');
        $this->postJson('/api/sso/token', $body + ['code_verifier' => str_repeat('x', 50)])->assertStatus(400);
        $this->postJson('/api/sso/token', $body + ['code_verifier' => $verifier])->assertCreated();
    }

    public function test_token_is_scoped_to_its_game_and_can_be_revoked(): void
    {
        $game = $this->confidentialGame();
        $other = Game::factory()->create(['slug' => 'other']);
        $token = $this->tokenFor($game, User::factory()->create());

        $this->withToken($token)->postJson('/api/games/other/events', ['event' => 'x'])->assertStatus(403);
        $this->withToken($token)->postJson('/api/sso/favourite')->assertOk()->assertJsonPath('favourite', true);
        $this->withToken($token)->postJson('/api/sso/logout')->assertOk();
        $this->withToken($token)->getJson('/api/sso/me')->assertStatus(401);
    }

    public function test_player_can_sign_out_of_a_game_from_profile(): void
    {
        $game = $this->confidentialGame();
        $user = User::factory()->create();
        $token = $this->tokenFor($game, $user);

        $this->actingAs($user)->get('/profile')->assertOk()->assertSee($game->name);
        $this->actingAs($user)->post('/profile/disconnect', ['game_id' => $game->id])->assertRedirect();
        $this->withToken($token)->getJson('/api/sso/me')->assertStatus(401);
    }
}
