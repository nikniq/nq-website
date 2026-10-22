<?php

namespace Tests\Unit;

use App\Models\Game;
use App\Models\SsoCode;
use PHPUnit\Framework\TestCase;

class SsoCodeTest extends TestCase
{
    private function challengeFor(string $verifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    }

    public function test_pkce_verifier_must_match_the_s256_challenge(): void
    {
        $verifier = str_repeat('a', 43);
        $code = new SsoCode(['code_challenge' => $this->challengeFor($verifier)]);

        $this->assertTrue($code->verifierMatches($verifier));
        $this->assertFalse($code->verifierMatches(str_repeat('b', 43)));
        $this->assertFalse($code->verifierMatches(null));
        $this->assertFalse($code->verifierMatches(''));
    }

    public function test_codes_without_a_challenge_accept_any_verifier(): void
    {
        $this->assertTrue((new SsoCode(['code_challenge' => null]))->verifierMatches(null));
    }

    public function test_redirect_uris_match_exactly(): void
    {
        $game = new Game(['redirect_uris' => ['https://chopper.nikniq.com/auth/callback']]);

        $this->assertTrue($game->redirectAllowed('https://chopper.nikniq.com/auth/callback'));
        $this->assertFalse($game->redirectAllowed('https://chopper.nikniq.com/auth/callback/'));
        $this->assertFalse($game->redirectAllowed('https://chopper.nikniq.com.evil.example/auth/callback'));
        $this->assertFalse($game->redirectAllowed('https://chopper.nikniq.com/auth/callback?next=x'));
    }
}
