<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OidcTest extends TestCase
{
    use RefreshDatabase;

    private const CALLBACK = 'https://app.nikniq.com/callback';

    private function authorize(Game $game, User $user, array $extra = []): string
    {
        $location = $this->actingAs($user)->get('/sso/authorize?'.http_build_query([
            'response_type' => 'code', 'client_id' => $game->client_id, 'redirect_uri' => self::CALLBACK, 'state' => 's1',
        ] + $extra))->assertRedirect()->headers->get('Location');
        parse_str(parse_url($location, PHP_URL_QUERY), $q);

        return $q['code'];
    }

    private function decode(string $jwt): array
    {
        [$h, $p, $sig] = explode('.', $jwt);
        $jwk = $this->getJson('/api/oauth/jwks')->assertOk()->json('keys.0');
        $this->assertSame(json_decode(base64_decode(strtr($h, '-_', '+/')), true)['kid'], $jwk['kid']);
        // Rebuild the public key from n/e via the signing key and verify the signature.
        $pub = openssl_pkey_get_details(\App\Support\Oidc::privateKey())['key'];
        $this->assertSame(1, openssl_verify("$h.$p", base64_decode(strtr($sig, '-_', '+/').str_repeat('=', (4 - strlen($sig) % 4) % 4)), $pub, OPENSSL_ALGO_SHA256));

        return json_decode(base64_decode(strtr($p, '-_', '+/')), true);
    }

    public function test_discovery_document_lists_the_endpoints(): void
    {
        $this->getJson('/.well-known/openid-configuration')->assertOk()
            ->assertJsonPath('issuer', rtrim(config('app.url'), '/'))
            ->assertJsonPath('response_types_supported', ['code'])
            ->assertJsonPath('code_challenge_methods_supported', ['S256'])
            ->assertJsonStructure(['authorization_endpoint', 'token_endpoint', 'userinfo_endpoint', 'jwks_uri', 'revocation_endpoint']);
    }

    public function test_standard_code_flow_with_basic_auth_returns_a_signed_id_token(): void
    {
        $game = Game::factory()->withSecret('s3cret')->create(['redirect_uris' => [self::CALLBACK]]);
        $user = User::factory()->create(['name' => 'Ada']);
        $code = $this->authorize($game, $user, ['scope' => 'openid profile email', 'nonce' => 'n-123']);

        $res = $this->withHeader('Authorization', 'Basic '.base64_encode($game->client_id.':s3cret'))
            ->post('/api/sso/token', ['grant_type' => 'authorization_code', 'code' => $code, 'redirect_uri' => self::CALLBACK], ['Accept' => 'application/json'])
            ->assertOk()->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('token_type', 'Bearer')->assertJsonPath('scope', 'openid profile email');
        $this->assertGreaterThan(0, $res->json('expires_in'));

        $claims = $this->decode($res->json('id_token'));
        $this->assertSame((string) $user->id, $claims['sub']);
        $this->assertSame($game->client_id, $claims['aud']);
        $this->assertSame('n-123', $claims['nonce']);
        $this->assertSame('Ada', $claims['name']);

        $this->flushHeaders();
        $this->withToken($res->json('access_token'))->getJson('/api/oauth/userinfo')
            ->assertOk()->assertJsonPath('sub', (string) $user->id)->assertJsonPath('email', $user->email);
    }

    public function test_scope_limits_userinfo_claims_and_tokens_can_be_revoked(): void
    {
        $game = Game::factory()->public()->create(['redirect_uris' => [self::CALLBACK]]);
        $verifier = str_repeat('v', 50);
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
        $code = $this->authorize($game, User::factory()->create(), ['scope' => 'openid', 'code_challenge' => $challenge, 'code_challenge_method' => 'S256']);

        $token = $this->postJson('/api/sso/token', ['grant_type' => 'authorization_code', 'client_id' => $game->client_id, 'code' => $code, 'redirect_uri' => self::CALLBACK, 'code_verifier' => $verifier])
            ->assertOk()->json('access_token');

        $info = $this->withToken($token)->getJson('/api/oauth/userinfo')->assertOk()->json();
        $this->assertSame(['sub'], array_keys($info));

        $this->flushHeaders();
        $this->postJson('/api/oauth/revoke', ['client_id' => $game->client_id, 'token' => $token])->assertOk();
        $this->withToken($token)->getJson('/api/oauth/userinfo')->assertUnauthorized()->assertHeader('WWW-Authenticate');
    }

    public function test_errors_use_oauth_codes(): void
    {
        $game = Game::factory()->create(['redirect_uris' => [self::CALLBACK]]);
        $this->postJson('/api/sso/token', ['grant_type' => 'password'])->assertStatus(400)->assertJsonPath('error', 'unsupported_grant_type');
        $this->postJson('/api/sso/token', ['grant_type' => 'authorization_code'])->assertStatus(400)->assertJsonPath('error', 'invalid_request');

        $location = $this->actingAs(User::factory()->create())->get('/sso/authorize?'.http_build_query(['response_type' => 'token', 'client_id' => $game->client_id, 'redirect_uri' => self::CALLBACK]))->headers->get('Location');
        $this->assertStringContainsString('error=unsupported_response_type', $location);
    }
}
