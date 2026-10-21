<?php

namespace App\Http\Controllers\Sso;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\SsoCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Step 1 of game sign-in. The game sends the player here; the portal makes
 * sure they are logged in, then redirects back with a one-time code.
 *
 *   GET /sso/authorize?client_id=...&redirect_uri=...&state=...
 *       [&code_challenge=...&code_challenge_method=S256]
 */
class AuthorizeController extends Controller
{
    public function __invoke(Request $request): RedirectResponse|View
    {
        $game = Game::where('client_id', (string) $request->query('client_id'))->first();
        $redirectUri = (string) $request->query('redirect_uri', '');

        // Never redirect to an unregistered URI: show the error on the portal instead.
        if (! $game || ! $game->is_enabled) {
            return view('sso.error', ['message' => 'Unknown or disabled game client.']);
        }
        if ($redirectUri === '' || ! $game->redirectAllowed($redirectUri)) {
            return view('sso.error', ['message' => 'The redirect URI is not registered for '.$game->name.'.']);
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

        $plain = Str::random(64);

        SsoCode::create([
            'game_id' => $game->id,
            'user_id' => $request->user()->id,
            'code_hash' => SsoCode::hashFor($plain),
            'redirect_uri' => $redirectUri,
            'code_challenge' => $challenge,
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
