<?php

namespace Tests\Feature;

use App\Models\Game;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchHelpTest extends TestCase
{
    use RefreshDatabase;

    public function test_misspelt_search_suggests_the_closest_game(): void
    {
        Game::factory()->create(['name' => 'Minesweeper', 'is_enabled' => true, 'is_featured' => true]);

        $this->get('/?q=minesweper')->assertSee('Did you mean')->assertSee('Minesweeper')->assertSee('Popular picks');
    }

    public function test_game_page_links_back_to_filtered_results(): void
    {
        $game = Game::factory()->create(['is_enabled' => true]);

        $this->get(route('games.show', $game), ['referer' => url('/?genre=Puzzle')])
            ->assertSee('Back to results')->assertSee('?genre=Puzzle', false);
        $this->get(route('games.show', $game))->assertSee('All games');
    }
    public function test_heading_counts_whole_catalogue_during_a_search(): void
    {
        Game::factory()->count(3)->create(['is_enabled' => true]);

        $this->get('/?q=zzzz')->assertSee('3 free games');
    }
}
