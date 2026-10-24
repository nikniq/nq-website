<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameFiltersTest extends TestCase
{
    use RefreshDatabase;

    private function catalogue(): void
    {
        Game::factory()->create(['name' => 'Legal Memory', 'slug' => 'legal-memory', 'genre' => 'Puzzle', 'is_dementia' => true]);
        Game::factory()->create(['name' => 'Space Invaders', 'slug' => 'space-invaders', 'genre' => 'Arcade', 'is_featured' => true]);
        Game::factory()->create(['name' => 'Towers', 'slug' => 'towers', 'genre' => 'Strategy']);
    }

    public function test_genre_filter(): void
    {
        $this->catalogue();
        $this->get('/?genre=Arcade')->assertOk()->assertSee('Space Invaders')->assertDontSee('Legal Memory')->assertDontSee('Towers');
    }

    public function test_dementia_filter_and_badge(): void
    {
        $this->catalogue();
        $this->get('/')->assertSee('Dementia-friendly');
        $this->get('/?dementia=1')->assertOk()->assertSee('Legal Memory')->assertDontSee('Space Invaders')->assertSee('suitable for players living with dementia');
        $this->get('/games/legal-memory')->assertSee('Dementia-friendly');
        $this->get('/games/towers')->assertDontSee('class="badge calm"', false);
    }

    public function test_featured_filter_sort_and_filters_combine(): void
    {
        $this->catalogue();
        $this->get('/?featured=1')->assertSee('Space Invaders')->assertDontSee('Towers');
        $this->get('/?genre=Puzzle&dementia=1')->assertSee('Legal Memory')->assertDontSee('Space Invaders');
        $this->get('/?genre=Puzzle&featured=1')->assertSee('No games match');

        $json = $this->getJson('/?sort=name')->json('games');
        $this->assertSame(['Legal Memory', 'Space Invaders', 'Towers'], array_column(array_map(fn ($g) => ['n' => $g['name']], $json), 'n'));
    }

    public function test_favourites_filter_for_signed_in_users(): void
    {
        $this->catalogue();
        $user = User::factory()->create();
        $user->favouriteGames()->attach(Game::where('slug', 'towers')->first());

        $this->get('/?favourites=1')->assertSee('Legal Memory'); // guests: the filter is ignored
        $this->actingAs($user)->get('/?favourites=1')->assertSee('Towers')->assertDontSee('Legal Memory');
    }

    public function test_api_catalogue_filters_and_genres(): void
    {
        $this->catalogue();
        $this->getJson('/api/games?dementia=1')->assertOk()->assertJsonCount(1, 'games')->assertJsonPath('games.0.dementia', true)
            ->assertJsonPath('genres', ['Arcade', 'Puzzle', 'Strategy']);
        $this->getJson('/api/games?genre=Strategy&sort=name')->assertJsonPath('games.0.slug', 'towers');
    }

    public function test_admin_can_set_the_dementia_flag(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $game = Game::factory()->create();

        $this->actingAs($admin)->put("/admin/games/{$game->slug}", ['name' => $game->name, 'slug' => $game->slug, 'is_enabled' => 1, 'is_dementia' => 1])->assertRedirect();
        $this->assertTrue($game->fresh()->is_dementia);
    }
}
