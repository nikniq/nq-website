<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['social.providers.google.enabled' => true]);
    }

    private function fakeGoogle(string $id, string $email, bool $verified = true, string $name = 'Google Player'): void
    {
        $user = (new SocialiteUser)->setRaw(['email_verified' => $verified])->map([
            'id' => $id, 'name' => $name, 'email' => $email, 'avatar' => 'https://lh3.example/avatar.png',
        ]);
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($user);
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    public function test_buttons_show_only_when_enabled(): void
    {
        $this->get('/login')->assertSee('Continue with Google');
        config(['social.providers.google.enabled' => false]);
        $this->get('/login')->assertDontSee('Continue with Google');
        $this->get('/auth/google/redirect')->assertRedirect('/?missing=1'); // disabled feature: missing-page redirect
    }

    public function test_new_google_user_gets_an_account_without_a_password(): void
    {
        $this->fakeGoogle('g-1', 'new@example.com');

        $this->get('/auth/google/callback')->assertRedirect('/dashboard');

        $user = User::where('email', 'new@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertNull($user->password);
        $this->assertSame('g-1', $user->provider_id);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_verified_google_email_links_to_existing_password_account(): void
    {
        $existing = User::factory()->create(['email' => 'old@example.com']);
        $this->fakeGoogle('g-2', 'old@example.com');

        $this->get('/auth/google/callback')->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($existing);
        $this->assertSame('g-2', $existing->fresh()->provider_id);
    }

    public function test_unverified_google_email_cannot_take_over_an_account(): void
    {
        User::factory()->create(['email' => 'old@example.com']);
        $this->fakeGoogle('g-3', 'old@example.com', verified: false);

        $this->get('/auth/google/callback')->assertRedirect('/login')->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_password_form_rejects_google_only_accounts(): void
    {
        User::factory()->create(['email' => 'g@example.com', 'password' => null, 'provider_name' => 'google', 'provider_id' => 'g-4']);

        $this->post('/login', ['email' => 'g@example.com', 'password' => 'anything'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_google_only_user_can_set_a_password_then_unlink(): void
    {
        $user = User::factory()->create(['password' => null, 'provider_name' => 'google', 'provider_id' => 'g-5']);

        $this->actingAs($user)->post('/auth/unlink')->assertRedirect('/profile')->assertSessionHasErrors('provider');

        $this->actingAs($user)->put('/profile/password', ['password' => 'new-password-123', 'password_confirmation' => 'new-password-123'])->assertSessionHasNoErrors();
        $this->assertTrue($user->fresh()->hasPassword());

        $this->actingAs($user)->post('/auth/unlink')->assertRedirect('/profile')->assertSessionHasNoErrors();
        $this->assertNull($user->fresh()->provider_id);
    }

    public function test_sso_round_trip_survives_google_login(): void
    {
        $game = Game::factory()->public()->create(['redirect_uris' => ['https://g.nikniq.com/cb']]);
        $challenge = rtrim(strtr(base64_encode(hash('sha256', str_repeat('v', 50), true)), '+/', '-_'), '=');
        $authorize = '/sso/authorize?'.http_build_query(['client_id' => $game->client_id, 'redirect_uri' => 'https://g.nikniq.com/cb', 'code_challenge' => $challenge]);

        $this->get($authorize)->assertRedirect('/login');
        $this->fakeGoogle('g-6', 'player@example.com');

        $location = $this->get('/auth/google/callback')->assertRedirect()->headers->get('Location');
        $this->assertStringStartsWith(url('/sso/authorize?'), $location);
        parse_str(parse_url($location, PHP_URL_QUERY), $query);
        $this->assertSame($game->client_id, $query['client_id']);
        $this->assertSame($challenge, $query['code_challenge']);
    }
}
