<?php

namespace Tests\Feature;

use App\Mail\VerifyEmail;
use App\Models\Game;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailSignupTest extends TestCase
{
    use RefreshDatabase;

    private function verifyUrl(User $user, bool $expired = false): string
    {
        return URL::temporarySignedRoute('register.verify', $expired ? now()->subMinute() : now()->addHour(), ['user' => $user->id, 'hash' => sha1($user->email)]);
    }

    public function test_signup_form_asks_only_for_email(): void
    {
        $this->get('/register')->assertOk()->assertSee('name="email"', false)->assertDontSee('name="password"', false);
    }

    public function test_signup_creates_passwordless_account_and_mails_a_link(): void
    {
        Mail::fake();

        $this->post('/register', ['email' => 'New@Example.com'])->assertRedirect('/register/sent');

        $user = User::where('email', 'new@example.com')->firstOrFail();
        $this->assertNull($user->password);
        $this->assertNull($user->email_verified_at);
        $this->assertGuest();
        Mail::assertSent(VerifyEmail::class, fn (VerifyEmail $m) => $m->hasTo('new@example.com') && str_contains($m->url, '/register/verify/'.$user->id.'/'));
    }

    public function test_link_verifies_logs_in_and_asks_for_password(): void
    {
        $user = User::factory()->create(['password' => null, 'email_verified_at' => null]);

        $this->get($this->verifyUrl($user))->assertRedirect('/register/password');
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->email_verified_at);

        $this->get('/register/password')->assertOk()->assertSee('Choose a password');
        $this->put('/register/password', ['name' => 'Ace', 'password' => 'secret-pass-123', 'password_confirmation' => 'secret-pass-123'])->assertRedirect('/dashboard');

        $user->refresh();
        $this->assertSame('Ace', $user->name);
        $this->assertTrue($user->hasPassword());

        // From now on the normal login form works.
        $this->post('/logout');
        $this->post('/login', ['email' => $user->email, 'password' => 'secret-pass-123'])->assertRedirect('/dashboard');
    }

    public function test_tampered_or_expired_links_are_rejected(): void
    {
        $user = User::factory()->create(['password' => null, 'email_verified_at' => null]);

        $this->get($this->verifyUrl($user, expired: true))->assertStatus(403);
        $this->get(str_replace(sha1($user->email), sha1('other@example.com'), $this->verifyUrl($user)))->assertStatus(403);
        $this->assertGuest();
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_unfinished_account_cannot_log_in_but_can_resend(): void
    {
        Mail::fake();
        $user = User::factory()->create(['password' => null, 'email_verified_at' => null]);

        $this->post('/login', ['email' => $user->email, 'password' => 'whatever'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post('/register/resend', ['email' => $user->email])->assertRedirect('/register/sent');
        Mail::assertSent(VerifyEmail::class, 1);
    }

    public function test_existing_password_account_is_not_disturbed_and_not_revealed(): void
    {
        Mail::fake();
        $user = User::factory()->create();

        $this->post('/register', ['email' => $user->email])->assertRedirect('/register/sent');
        Mail::assertNothingSent();
        $this->assertTrue($user->fresh()->hasPassword());

        $this->post('/register/resend', ['email' => $user->email])->assertRedirect('/register/sent');
        Mail::assertNothingSent();
    }

    public function test_signup_during_sso_round_trip_returns_to_the_game(): void
    {
        $game = Game::factory()->public()->create(['redirect_uris' => ['https://g.nikniq.com/cb']]);
        $challenge = rtrim(strtr(base64_encode(hash('sha256', str_repeat('v', 50), true)), '+/', '-_'), '=');
        $authorize = '/sso/authorize?'.http_build_query(['client_id' => $game->client_id, 'redirect_uri' => 'https://g.nikniq.com/cb', 'code_challenge' => $challenge]);
        $this->get($authorize)->assertRedirect('/login');

        $user = User::factory()->create(['password' => null, 'email_verified_at' => null]);
        $this->get($this->verifyUrl($user))->assertRedirect('/register/password');
        $location = $this->put('/register/password', ['name' => 'Ace', 'password' => 'secret-pass-123', 'password_confirmation' => 'secret-pass-123'])->headers->get('Location');
        $this->assertStringStartsWith(url('/sso/authorize?'), $location);
    }
}
