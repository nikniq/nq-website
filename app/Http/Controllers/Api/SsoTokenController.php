<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\PlayerToken;
use App\Models\SsoCode;
use App\Models\User;
use App\Services\GameStatsService;
use App\Support\Oidc;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SsoTokenController extends Controller
{
    public function __construct(private GameStatsService $stats) {}

    /**
     * Step 2 of game sign-in (OAuth 2.0 token endpoint, RFC 6749 §4.1.3):
     * exchange the one-time code for an access token, plus an OpenID Connect
     * id_token when the "openid" scope was requested.
     *
     *   POST /api/sso/token   (form or JSON)
     *   grant_type=authorization_code, code, redirect_uri, client_id,
     *   client_secret (or HTTP Basic auth) for confidential clients | code_verifier (PKCE)
     */
    public function token(Request $request): JsonResponse
    {
        [$clientId, $clientSecret] = $this->clientCredentials($request);
        $grantType = $request->input('grant_type');
        if ($grantType !== 'authorization_code') {
            return $this->oauthError($grantType ? 'unsupported_grant_type' : 'invalid_request', 'grant_type must be authorization_code.');
        }
        foreach (['code', 'redirect_uri'] as $field) {
            if (! is_string($request->input($field)) || $request->input($field) === '') {
                return $this->oauthError('invalid_request', "{$field} is required.");
            }
        }
        if ($clientId === null) {
            return $this->oauthError('invalid_request', 'client_id is required.');
        }

        $game = Game::where('client_id', $clientId)->first();
        if (! $game || ! $game->is_enabled) {
            return $this->oauthError('invalid_client', 'Unknown or disabled client.', 401);
        }
        if (! $game->is_public && ! $game->verifySecret($clientSecret)) {
            return $this->oauthError('invalid_client', 'Client authentication failed.', 401);
        }

        $code = SsoCode::with('user')
            ->where('code_hash', SsoCode::hashFor((string) $request->input('code')))
            ->where('game_id', $game->id)
            ->first();

        if (! $code || ! $code->isUsable()) {
            return $this->oauthError('invalid_grant', 'Authorization code is invalid, expired, or already used.');
        }
        if (! hash_equals($code->redirect_uri, (string) $request->input('redirect_uri'))) {
            return $this->oauthError('invalid_grant', 'redirect_uri does not match the authorization request.');
        }
        if ($game->is_public && $code->code_challenge === null) {
            return $this->oauthError('invalid_grant', 'PKCE is required for this client.');
        }
        $verifier = $request->input('code_verifier');
        if (! $code->verifierMatches(is_string($verifier) ? $verifier : null)) {
            return $this->oauthError('invalid_grant', 'code_verifier does not match code_challenge.');
        }

        // Single use, even if issuing the token below fails.
        $code->forceFill(['used_at' => now()])->save();

        $plain = Str::random(80);
        $token = PlayerToken::create([
            'game_id' => $game->id,
            'user_id' => $code->user_id,
            'token_hash' => PlayerToken::hashFor($plain),
            'scope' => $code->scope,
            'expires_at' => now()->addDays((int) config('portal.token_ttl_days', 30)),
        ]);

        $body = [
            'token_type' => 'Bearer',
            'access_token' => $plain,
            'expires_in' => (int) now()->diffInSeconds($token->expires_at, true),
            'expires_at' => $token->expires_at->toIso8601String(),
        ];
        if ($code->scope !== null) {
            $body['scope'] = $code->scope;
        }
        if (Oidc::hasScope($code->scope, 'openid')) {
            $body['id_token'] = Oidc::idToken($code->user, $game, $code->scope, $code->nonce, $plain);
        }
        $body['player'] = $this->playerPayload($code->user);
        $body['stats'] = $this->stats->statsFor($game, $code->user)?->toApi();

        return response()->json($body)->withHeaders(['Cache-Control' => 'no-store', 'Pragma' => 'no-cache']);
    }

    /** OpenID Connect UserInfo: GET|POST /api/oauth/userinfo with the bearer token. */
    public function userinfo(Request $request): JsonResponse
    {
        /** @var PlayerToken $token */
        $token = $request->attributes->get('playerToken');

        return response()->json(Oidc::claims($request->attributes->get('player'), $token->scope));
    }

    /** RFC 7009 token revocation: POST /api/oauth/revoke (token, client auth). Always 200. */
    public function revoke(Request $request): JsonResponse
    {
        [$clientId, $clientSecret] = $this->clientCredentials($request);
        $game = $clientId ? Game::where('client_id', $clientId)->first() : null;
        if (! $game || (! $game->is_public && ! $game->verifySecret($clientSecret))) {
            return $this->oauthError('invalid_client', 'Client authentication failed.', 401);
        }
        if (is_string($request->input('token'))) {
            PlayerToken::where('token_hash', PlayerToken::hashFor($request->input('token')))
                ->where('game_id', $game->id)->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);
        }

        return response()->json([]);
    }

    /** client_secret_basic (Authorization: Basic) or client_secret_post. */
    private function clientCredentials(Request $request): array
    {
        $header = (string) $request->header('Authorization', '');
        if (str_starts_with($header, 'Basic ')) {
            $decoded = base64_decode(substr($header, 6), true);
            if ($decoded !== false && str_contains($decoded, ':')) {
                [$id, $secret] = explode(':', $decoded, 2);

                return [urldecode($id), urldecode($secret)];
            }
        }
        $id = $request->input('client_id');
        $secret = $request->input('client_secret');

        return [is_string($id) && $id !== '' ? $id : null, is_string($secret) ? $secret : null];
    }

    /** GET /api/sso/me */
    public function me(Request $request): JsonResponse
    {
        /** @var User $player */
        $player = $request->attributes->get('player');
        /** @var Game $game */
        $game = $request->attributes->get('game');

        return response()->json([
            'player' => $this->playerPayload($player),
            'game' => ['slug' => $game->slug, 'name' => $game->name],
            'favourite' => $player->hasFavourited($game),
            'stats' => $this->stats->statsFor($game, $player)?->toApi(),
            'rank' => $this->stats->rankFor($game, $player),
        ]);
    }

    /** POST /api/sso/logout */
    public function logout(Request $request): JsonResponse
    {
        /** @var PlayerToken $token */
        $token = $request->attributes->get('playerToken');
        $token->forceFill(['revoked_at' => now()])->save();

        return response()->json(['revoked' => true]);
    }

    private function playerPayload(User $user): array
    {
        return ['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'avatar' => $user->avatar];
    }

    private function oauthError(string $error, string $description, int $status = 400): JsonResponse
    {
        $response = response()->json(['error' => $error, 'error_description' => $description], $status)
            ->withHeaders(['Cache-Control' => 'no-store']);

        return $status === 401 ? $response->withHeaders(['WWW-Authenticate' => 'Basic realm="nikniq"']) : $response;
    }
}
