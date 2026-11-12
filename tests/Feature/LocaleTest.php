<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_lang_query_switches_language_and_is_remembered(): void
    {
        $this->get('/?lang=fr')->assertOk()->assertSee('<html lang="fr">', false)->assertCookie('lang', 'fr');
        $this->withCookie('lang', 'ja')->get('/')->assertSee('<html lang="ja">', false);
    }

    public function test_browser_language_is_used_when_nothing_chosen(): void
    {
        $this->withHeader('Accept-Language', 'de-DE,de;q=0.9')->get('/')->assertSee('<html lang="de">', false);
    }

    public function test_english_without_any_preference(): void
    {
        $this->get('/')->assertSee('<html lang="en">', false);
    }

    public function test_unknown_language_falls_back_to_english(): void
    {
        $this->get('/?lang=xx')->assertSee('<html lang="en">', false)->assertCookieMissing('lang');
    }

    public function test_pages_list_hreflang_alternates(): void
    {
        $html = $this->get('/?lang=es')->assertOk()->getContent();
        foreach (['en', 'ja', 'fr', 'de', 'es', 'x-default'] as $code) {
            $this->assertStringContainsString('hreflang="'.$code.'"', $html);
        }
        $this->assertStringContainsString('rel="canonical" href="'.url('/').'?lang=es"', $html);
    }

    public function test_every_language_file_has_every_key(): void
    {
        $keys = json_decode(shell_exec('php '.base_path('scripts/lang-keys.php')), true);
        $this->assertNotEmpty($keys);
        foreach (array_keys(config('portal.locales')) as $code) {
            if ($code === 'en') {
                continue;
            }
            $have = json_decode(file_get_contents(lang_path("$code.json")), true);
            $this->assertSame([], array_values(array_diff($keys, array_keys($have))), "lang/$code.json is missing keys");
        }
    }
}
