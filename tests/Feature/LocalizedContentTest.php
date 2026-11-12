<?php

namespace Tests\Feature;

use App\Support\HowToPlay;
use App\Support\News;
use Database\Seeders\GamesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalizedContentTest extends TestCase
{
    use RefreshDatabase;

    private function others(): array
    {
        return array_values(array_diff(array_keys(config('portal.locales')), ['en']));
    }

    public function test_every_language_covers_games_genres_tips_and_news(): void
    {
        $genres = array_unique(array_column(GamesSeeder::GAMES, 2));
        foreach ($this->others() as $code) {
            $games = require lang_path("$code/games.php");
            $this->assertSame([], array_values(array_diff(array_keys(GamesSeeder::GAMES), array_keys($games))), "$code games");
            $this->assertSame([], array_values(array_diff($genres, array_keys(require lang_path("$code/genres.php")))), "$code genres");
            $howto = require lang_path("$code/howto.php");
            foreach (HowToPlay::TIPS as $slug => [, $tips]) {
                $this->assertCount(count($tips), $howto[$slug][1] ?? [], "$code howto $slug");
            }
            $news = require lang_path("$code/news.php");
            foreach (News::ARTICLES as [$slug]) {
                $this->assertArrayHasKey('title', $news[$slug] ?? [], "$code news $slug");
                $this->assertFileExists(resource_path("views/news/articles/$code/$slug.blade.php"));
            }
            foreach (['privacy', 'terms', 'developers'] as $page) {
                $this->assertFileExists(resource_path("views/pages/$code/$page.blade.php"));
            }
        }
    }

    public function test_translated_content_is_served(): void
    {
        $this->seed(GamesSeeder::class);
        $fr = require lang_path('fr/games.php');
        $this->get('/games/pacman?lang=fr')->assertOk()->assertSee($fr['pacman'])->assertDontSee(GamesSeeder::GAMES['pacman'][3]);
        $this->get('/games/pacman?lang=en')->assertSee(GamesSeeder::GAMES['pacman'][3]);
        foreach (['/privacy', '/terms', '/developers', '/news/'.News::ARTICLES[0][0]] as $url) {
            $this->get($url.'?lang=ja')->assertOk();
        }
    }

    public function test_admin_edited_description_wins_over_translation(): void
    {
        $this->seed(GamesSeeder::class);
        \App\Models\Game::where('slug', 'pacman')->update(['description' => 'Edited by admin']);
        $this->get('/games/pacman?lang=de')->assertSee('Edited by admin');
    }
}
