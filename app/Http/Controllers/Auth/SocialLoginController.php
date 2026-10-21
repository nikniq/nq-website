<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Contracts\User as SocialUser;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

/**
 * Google sign-in. The callback serves four cases:
 *  - a guest whose Google id is already linked signs in;
 *  - a guest whose verified Google email matches an existing account links it and signs in;
 *  - a guest with no account gets one (no password until they set one on /profile);
 *  - a signed-in user links Google to their own account.
 */
class SocialLoginController extends Controller
{
    public function redirect(string $provider): RedirectResponse
    {
        return $this->driver($provider)->redirect();
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        $provider = strtolower($provider);

        try {
            $socialUser = $this->driver($provider)->user();
        } catch (Throwable $e) {
            Log::warning('oauth.callback.failed', ['provider' => $provider, 'error' => $e->getMessage()]);

            return $this->fail('Unable to sign in with Google. Please try again.');
        }

        $email = strtolower(trim((string) $socialUser->getEmail()));
        if ($email === '') {
            return $this->fail('Google did not return an email address.');
        }

        $emailVerified = $this->emailVerified($socialUser);

        if (Auth::check()) {
            return $this->link($request, $provider, $socialUser);
        }

        $user = User::where('provider_name', $provider)->where('provider_id', $socialUser->getId())->first();

        if (! $user) {
            $user = User::where('email', $email)->first();

            // Only let a Google email take over an existing account when Google
            // says it has verified that address.
            if ($user && ! $emailVerified) {
                return $this->fail("Your Google email is not verified, so it cannot sign in to the existing account for {$email}. Log in with your password and link Google from your profile.");
            }
            if ($user && $user->hasLinkedProvider() && ($user->provider_name !== $provider || $user->provider_id !== $socialUser->getId())) {
                return $this->fail('This account is already linked to a different Google login.');
            }
        }

        if (! $user) {
            if (! $emailVerified) {
                return $this->fail('Your Google email address must be verified before you can sign up with it.');
            }

            $user = User::create([
                'name' => $socialUser->getName() ?: $socialUser->getNickname() ?: 'Player',
                'email' => $email,
                'password' => null,
                'avatar' => $socialUser->getAvatar(),
                'provider_name' => $provider,
                'provider_id' => $socialUser->getId(),
                'email_verified_at' => now(),
            ]);
        } else {
            $user->forceFill([
                'provider_name' => $provider,
                'provider_id' => $socialUser->getId(),
                'avatar' => $socialUser->getAvatar() ?: $user->avatar,
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        // intended() keeps a game's SSO round trip working: /sso/authorize -> login -> Google -> back.
        return redirect()->intended(route('dashboard'))->with('status', 'Signed in with Google.');
    }

    /** Remove the link. Only allowed when the user can still log in with a password. */
    public function unlink(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->hasLinkedProvider()) {
            return redirect()->route('profile');
        }
        if (! $user->hasPassword()) {
            return redirect()->route('profile')->withErrors(['provider' => 'Set a password before unlinking Google, or you will be locked out.']);
        }

        $user->forceFill(['provider_name' => null, 'provider_id' => null])->save();

        return redirect()->route('profile')->with('status', 'Google account unlinked.');
    }

    private function link(Request $request, string $provider, SocialUser $socialUser): RedirectResponse
    {
        $user = $request->user();

        $owner = User::where('provider_name', $provider)->where('provider_id', $socialUser->getId())->first();
        if ($owner && $owner->id !== $user->id) {
            return redirect()->route('profile')->withErrors(['provider' => 'That Google account is already linked to another player.']);
        }

        $user->forceFill([
            'provider_name' => $provider,
            'provider_id' => $socialUser->getId(),
            'avatar' => $socialUser->getAvatar() ?: $user->avatar,
        ])->save();

        return redirect()->route('profile')->with('status', 'Google account linked.');
    }

    private function fail(string $message): RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('profile')->withErrors(['provider' => $message]);
        }

        return redirect()->route('login')->withErrors(['email' => $message]);
    }

    private function emailVerified(SocialUser $socialUser): bool
    {
        $raw = method_exists($socialUser, 'getRaw') ? (array) $socialUser->getRaw() : [];

        return filter_var($raw['email_verified'] ?? $raw['verified_email'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    private function driver(string $provider)
    {
        abort_unless(config("social.providers.{$provider}.enabled"), 404);

        return Socialite::driver($provider);
    }
}
