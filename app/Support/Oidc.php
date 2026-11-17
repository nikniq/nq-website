<?php

namespace App\Support;

use App\Models\Game;
use App\Models\User;
use RuntimeException;

/**
 * OpenID Connect helpers: the RS256 signing key, its JWKS, ID tokens and
 * standard claims. The private key comes from OIDC_PRIVATE_KEY (PEM) or
 * storage/app/oidc-private.pem, which is created on first use.
 */
class Oidc
{
    public const SCOPES = ['openid', 'profile', 'email'];

    public static function issuer(): string
    {
        return rtrim((string) config('app.url'), '/');
    }

    /** Space-separated scope string limited to what we support; null when none requested. */
    public static function normaliseScope(?string $scope): ?string
    {
        if ($scope === null || trim($scope) === '') {
            return null;
        }
        $parts = array_values(array_intersect(self::SCOPES, preg_split('/\s+/', trim($scope))));

        return $parts === [] ? null : implode(' ', $parts);
    }

    public static function hasScope(?string $scope, string $want): bool
    {
        return $scope !== null && in_array($want, explode(' ', $scope), true);
    }

    /** Standard claims for a user, filtered by scope (no scope = legacy SDK: everything). */
    public static function claims(User $user, ?string $scope): array
    {
        $claims = ['sub' => (string) $user->id];
        if ($scope === null || self::hasScope($scope, 'profile')) {
            $claims['name'] = $user->name;
            $claims['preferred_username'] = $user->name;
            if ($user->avatar) {
                $claims['picture'] = $user->avatar;
            }
            $claims['profile'] = $user->is_public ? route('players.show', $user) : null;
            $claims = array_filter($claims, fn ($v) => $v !== null);
        }
        if ($scope === null || self::hasScope($scope, 'email')) {
            $claims['email'] = $user->email;
            $claims['email_verified'] = $user->email_verified_at !== null;
        }

        return $claims;
    }

    public static function idToken(User $user, Game $game, ?string $scope, ?string $nonce, ?string $accessToken = null): string
    {
        $now = time();
        $payload = [
            'iss' => self::issuer(),
            'aud' => $game->client_id,
            'iat' => $now,
            'exp' => $now + 3600,
            'auth_time' => $now,
        ] + self::claims($user, $scope);
        if ($nonce !== null && $nonce !== '') {
            $payload['nonce'] = $nonce;
        }
        if ($accessToken !== null) {
            $hash = hash('sha256', $accessToken, true);
            $payload['at_hash'] = self::b64(substr($hash, 0, 16));
        }

        return self::sign($payload);
    }

    public static function sign(array $payload): string
    {
        $header = ['alg' => 'RS256', 'typ' => 'JWT', 'kid' => self::kid()];
        $input = self::b64(json_encode($header)).'.'.self::b64(json_encode($payload, JSON_UNESCAPED_SLASHES));
        if (! openssl_sign($input, $signature, self::privateKey(), OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Could not sign the ID token.');
        }

        return $input.'.'.self::b64($signature);
    }

    /** @return array{keys: array<int, array<string, string>>} */
    public static function jwks(): array
    {
        $rsa = openssl_pkey_get_details(self::privateKey())['rsa'];

        return ['keys' => [[
            'kty' => 'RSA', 'use' => 'sig', 'alg' => 'RS256', 'kid' => self::kid(),
            'n' => self::b64($rsa['n']), 'e' => self::b64($rsa['e']),
        ]]];
    }

    public static function kid(): string
    {
        $rsa = openssl_pkey_get_details(self::privateKey())['rsa'];

        return substr(hash('sha256', $rsa['n'].$rsa['e']), 0, 16);
    }

    private static $key = null;

    public static function privateKey(): \OpenSSLAsymmetricKey
    {
        if (self::$key) {
            return self::$key;
        }
        $pem = (string) config('portal.oidc_private_key');
        if ($pem === '') {
            $path = storage_path('app/oidc-private.pem');
            if (! is_file($path)) {
                $new = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
                openssl_pkey_export($new, $out);
                file_put_contents($path, $out);
                @chmod($path, 0600);
            }
            $pem = (string) file_get_contents($path);
        }
        $key = openssl_pkey_get_private(str_replace('\n', "\n", $pem));
        if (! $key) {
            throw new RuntimeException('OIDC private key is not a valid PEM RSA key.');
        }

        return self::$key = $key;
    }

    public static function b64(string $bin): string
    {
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }
}
