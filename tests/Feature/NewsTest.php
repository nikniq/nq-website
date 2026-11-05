<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Support\News;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_news_index_lists_every_article(): void
    {
        $response = $this->get('/news')->assertOk();
        foreach (News::all() as $article) {
            $response->assertSee($article->title);
        }
    }

    public function test_every_article_renders(): void
    {
        foreach (News::all() as $article) {
            $this->get('/news/'.$article->slug)->assertOk()->assertSee($article->title)->assertSee('NewsArticle', false);
        }
    }

    public function test_unknown_article_redirects_home(): void
    {
        $this->get('/news/nope')->assertRedirect();
    }

    public function test_sitemap_includes_news(): void
    {
        $this->get('/sitemap.xml')->assertSee(route('news.show', News::all()->first()->slug));
    }

    public function test_catalogue_shows_latest_news(): void
    {
        Game::factory()->create(['is_enabled' => true, 'is_featured' => true]);

        $this->get('/')->assertSee(News::all()->first()->title)
            ->assertSee('<title>Retro &amp; Arcade games', false);
    }
}
