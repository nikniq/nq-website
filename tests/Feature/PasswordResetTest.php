<?php

namespace Tests\Feature;

use App\Mail\VerifyEmail;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_links_to_forgot_password(): void
    {
        $this->get('/login')->assertSee('Forgot your password?');
        $this->get('/forgot-password')->assertOk();
    }

    public function test_reset_link_is_sent_and_response_does_not_reveal_accounts(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])->assertRedirect()->assertSessionHas('status');
        Notification::assertSentTo($user, ResetPassword::class);

        $this->post('/forgot-password', ['email' => 'nobody@example.com'])->assertRedirect()->assertSessionHas('status');
        Notification::assertCount(1);
    }

    public function test_unfinished_signup_gets_the_confirmation_link_instead(): void
    {
        Mail::fake();
        Notification::fake();
        $user = User::factory()->create(['password' => null, 'email_verified_at' => null]);

        $this->post('/forgot-password', ['email' => $user->email]);
        Mail::assertSent(VerifyEmail::class);
        Notification::assertNothingSent();
    }

    public function test_password_can_be_reset_with_a_valid_token(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->get("/reset-password/{$token}?email={$user->email}")->assertOk()->assertSee('Choose a new password');
        $this->post('/reset-password', ['token' => $token, 'email' => $user->email, 'password' => 'brand-new-pass-1', 'password_confirmation' => 'brand-new-pass-1'])
            ->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);

        $this->post('/logout');
        $this->post('/login', ['email' => $user->email, 'password' => 'brand-new-pass-1'])->assertRedirect('/dashboard');
    }

    public function test_bad_token_is_rejected(): void
    {
        $user = User::factory()->create();
        $this->post('/reset-password', ['token' => 'nope', 'email' => $user->email, 'password' => 'brand-new-pass-1', 'password_confirmation' => 'brand-new-pass-1'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
