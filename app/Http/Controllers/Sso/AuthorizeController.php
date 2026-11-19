<?php

namespace App\Http\Controllers\Sso;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\SsoCode;
use App\Support\Oidc;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Step 1 of game sign-in. The game sends the player here; the portal makes
 * sure they are logged in, then redirects back with a one-time code.
 *
 *   GET /sso/authorize?client_id=...&redirect_uri=...&state=...
 *       [&response_type=code][&scope=openid profile email][&nonce=...]
 *       [&code_challenge=...&code_challenge_method=S256][&prompt=none][&provider=google]
 *
 * prompt=none is the silent check the SDK makes on load: a player already
 * logged in on the portal gets a code straight back; a guest gets
 * error=login_required instead of the login page.
 *
 * provider=google sends a guest straight to Google (when Google sign-in is
 * enabled) instead of the NikniQ login page; the player still ends up with a
 * NikniQ account and the game still gets a NikniQ code. Signed-in players skip it.
 */
class AuthorizeController extends Controller
{
    public function __invoke(Request $request): RedirectResponse|View
    {
        $game = Game::where('client_id', (string) $request->query('client_id'))->first();
        $redirectUri = (string) $request->query('redirect_uri', '');

        // Never redirect to an unregistered URI: show the error on the portal instead.
        if (! $game || ! $game->is_enabled) {
            return view('sso.error', ['message' => __('Unknown or disabled game client.')]);
        }
        if ($redirectUri === '' || ! $game->redirectAllowed($redirectUri)) {
            return view('sso.error', ['message' => __('The redirect URI is not registered for :game.', ['game' => $game->name])]);
        }

        $responseType = $request->query('response_type');
        if ($responseType !== null && $responseType !== 'code') {
            return $this->redirectError($redirectUri, $request->query('state'), 'unsupported_response_type', 'Only response_type=code is supported.');
        }

        $challenge = $request->query('code_challenge');
        $method = strtoupper((string) $request->query('code_challenge_method', 'S256'));
        $state = $request->query('state');

        if ($challenge !== null && ($method !== 'S256' || ! preg_match('/^[A-Za-z0-9\-_]{43,128}$/', (string) $challenge))) {
            return $this->redirectError($redirectUri, $state, 'invalid_request', 'code_challenge must be S256 and base64url encoded.');
        }
        if ($game->is_public && $challenge === null) {
            return $this->redirectError($redirectUri, $state, 'invalid_request', 'Public clients must use PKCE (code_challenge).');
        }

        if (! $request->user()) {
            if ($request->query('prompt') === 'none') {
                return $this->redirectError($redirectUri, $state, 'login_required', 'The player is not signed in to NikniQ.');
            }

            if (strtolower((string) $request->query('provider')) === 'google' && config('social.providers.google.enabled')) {
                return redirect()->guest(route('social.redirect', 'google'));
            }

            return redirect()->guest(route('login'));
        }

        $plain = Str::random(64);

        SsoCode::create([
            'game_id' => $game->id,
            'user_id' => $request->user()->id,
            'code_hash' => SsoCode::hashFor($plain),
            'redirect_uri' => $redirectUri,
            'code_challenge' => $challenge,
            'scope' => Oidc::normaliseScope($request->query('scope')),
            'nonce' => is_string($request->query('nonce')) ? substr($request->query('nonce'), 0, 255) : null,
            'expires_at' => now()->addMinutes((int) config('portal.code_ttl_minutes', 5)),
        ]);

        $query = ['code' => $plain];
        if ($request->filled('state')) {
            $query['state'] = (string) $state;
        }

        return redirect()->away($this->appendQuery($redirectUri, $query));
    }

    private function redirectError(string $uri, ?string $state, string $error, string $description): RedirectResponse
    {
        $query = ['error' => $error, 'error_description' => $description];
        if ($state !== null && $state !== '') {
            $query['state'] = $state;
        }

        return redirect()->away($this->appendQuery($uri, $query));
    }

    private function appendQuery(string $uri, array $query): string
    {
        return $uri.(str_contains($uri, '?') ? '&' : '?').http_build_query($query);
    }
}
