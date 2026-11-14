<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\User;
use App\Support\GameSearch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvancedSearchTest extends TestCase
{
    use RefreshDatabase;

    private function catalogue(): void
    {
        Game::factory()->create(['name' => 'Tetris', 'slug' => 'tetris', 'genre' => 'Puzzle', 'description' => 'Stack falling blocks.', 'is_enabled' => true]);
        Game::factory()->create(['name' => 'Legal Memory', 'slug' => 'legal-memory', 'genre' => 'Puzzle', 'description' => 'Find the pairs.', 'is_enabled' => true, 'is_dementia' => true]);
        Game::factory()->create(['name' => 'Snake', 'slug' => 'snake', 'genre' => 'Arcade', 'description' => 'Grow the snake, avoid your tail.', 'is_enabled' => true, 'is_featured' => true]);
        Game::factory()->create(['name' => 'Block Party', 'slug' => 'block-party', 'genre' => 'Arcade', 'description' => 'Puzzle pieces everywhere.', 'is_enabled' => true]);
    }

    private function names(string $query): array
    {
        $html = $this->get('/?'.$query)->assertOk()->getContent();
        // Only the results grid (an empty search also shows "Popular picks").
        $html = str_contains($html, 'data-live') ? explode('live-empty', explode('data-live', $html, 2)[1], 2)[0] : '';
        preg_match_all('#<h3 class="card-title">\s*<a[^>]*>([^<]+)</a>#', $html, $m);

        return array_map('trim', $m[1]);
    }

    public function test_parser_understands_the_query_language(): void
    {
        $terms = GameSearch::parse('snake "power pellets" -memory genre:puzzle is:calm Café');
        $this->assertSame([
            ['type' => 'word', 'value' => 'snake', 'negate' => false],
            ['type' => 'phrase', 'value' => 'power pellets', 'negate' => false],
            ['type' => 'word', 'value' => 'memory', 'negate' => true],
            ['type' => 'genre', 'value' => 'puzzle', 'negate' => false],
            ['type' => 'is', 'value' => 'calm', 'negate' => false],
            ['type' => 'word', 'value' => 'cafe', 'negate' => false],
        ], $terms);
    }

    public function test_operators_filter_results(): void
    {
        $this->catalogue();
        $this->assertEqualsCanonicalizing(['Tetris', 'Legal Memory'], $this->names('q=genre:puzzle'));
        $this->assertSame(['Tetris'], $this->names('q='.urlencode('genre:puzzle -memory')));
        $this->assertSame(['Legal Memory'], $this->names('q=is:calm'));
        $this->assertSame(['Snake'], $this->names('q=is:featured'));
        $this->assertSame(['Snake'], $this->names('q='.urlencode('"avoid your tail"')));
    }

    public function test_names_rank_above_descriptions_and_names_only_option(): void
    {
        $this->catalogue();
        // "block" is in Block Party's name and only in Tetris's description.
        $this->assertSame(['Block Party', 'Tetris'], $this->names('q=block'));
        $this->assertSame(['Block Party'], $this->names('q=block&in=name'));
    }

    public function test_all_versus_any_words(): void
    {
        $this->catalogue();
        $this->assertSame([], $this->names('q='.urlencode('snake tetris')));
        $this->assertEqualsCanonicalizing(['Snake', 'Tetris'], $this->names('q='.urlencode('snake tetris').'&match=any'));
    }

    public function test_typos_are_forgiven(): void
    {
        $this->catalogue();
        $this->assertSame(['Tetris'], $this->names('q=tetrs'));
    }

    public function test_favourite_operator_uses_the_signed_in_players_favourites(): void
    {
        $this->catalogue();
        $user = User::factory()->create();
        $user->favouriteGames()->attach(Game::where('slug', 'snake')->value('id'));
        $this->actingAs($user);
        $this->assertSame(['Snake'], $this->names('q=is:favourite'));
    }

    public function test_translated_descriptions_are_searchable(): void
    {
        Game::factory()->create(['name' => 'Packman', 'slug' => 'pacman', 'genre' => 'Arcade', 'description' => \Database\Seeders\GamesSeeder::GAMES['pacman'][3], 'is_enabled' => true]);
        $fr = require lang_path('fr/games.php');
        $word = collect(explode(' ', GameSearch::norm($fr['pacman'])))->sortByDesc(fn ($w) => strlen($w))->first();
        $this->assertSame(['Packman'], $this->names('lang=fr&q='.urlencode($word)));
    }

    public function test_suggestions_endpoint(): void
    {
        $this->catalogue();
        $this->getJson(route('games.suggest', ['lang' => 'de']))->assertOk()
            ->assertJsonCount(4)->assertJsonFragment(['n' => 'Tetris', 'g' => __('genres.Puzzle', [], 'de')]);
    }
}
