<?php

namespace Tests\Feature;

use Database\Seeders\GamesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JsonLdTest extends TestCase
{
    use RefreshDatabase;

    public function test_structured_data_is_valid_json(): void
    {
        $this->seed(GamesSeeder::class);
        foreach (['/', '/news/one-account-for-every-game', '/games/pacman'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
            $this->assertNotEmpty($m[1], $url);
            foreach ($m[1] as $json) {
                $this->assertIsArray(json_decode($json, true), "Invalid JSON-LD on $url");
            }
        }
    }
}
