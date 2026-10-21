<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GamesCatalogueTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_lists_enabled_games_only(): void
    {
        Game::factory()->create(['name' => 'Chopper Rescue']);
        Game::factory()->create(['name' => 'Hidden One', 'is_enabled' => false]);

        $this->get('/')->assertOk()->assertSee('Chopper Rescue')->assertDontSee('Hidden One');
    }

    public function test_search_filters_games(): void
    {
        Game::factory()->create(['name' => 'Chopper Rescue']);
        Game::factory()->create(['name' => 'Tetris Blocks']);

        $this->get('/?q=chopper')->assertOk()->assertSee('Chopper Rescue')->assertDontSee('Tetris Blocks');
    }

    public function test_json_catalogue_is_public(): void
    {
        Game::factory()->create(['name' => 'Chopper Rescue', 'slug' => 'chopper']);

        $this->getJson('/api/games')->assertOk()->assertJsonPath('games.0.slug', 'chopper');
    }

    public function test_play_redirects_to_the_game_and_logs_a_launch(): void
    {
        $game = Game::factory()->create(['url' => 'https://chopper.nikniq.com']);
        $user = User::factory()->create();

        $this->actingAs($user)->get("/games/{$game->slug}/play")->assertRedirect('https://chopper.nikniq.com');

        $this->assertDatabaseHas('game_events', ['game_id' => $game->id, 'user_id' => $user->id, 'event' => 'launch']);
    }

    public function test_user_can_toggle_a_favourite(): void
    {
        $game = Game::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)->post("/games/{$game->slug}/favourite")->assertRedirect();
        $this->assertDatabaseHas('favourites', ['user_id' => $user->id, 'game_id' => $game->id]);

        $this->actingAs($user)->post("/games/{$game->slug}/favourite")->assertRedirect();
        $this->assertDatabaseMissing('favourites', ['user_id' => $user->id, 'game_id' => $game->id]);
    }

    public function test_guests_cannot_favourite(): void
    {
        $game = Game::factory()->create();

        $this->post("/games/{$game->slug}/favourite")->assertRedirect('/login');
    }

    public function test_favourites_sort_first(): void
    {
        $a = Game::factory()->create(['name' => 'Aardvark']);
        $z = Game::factory()->create(['name' => 'Zebra']);
        $user = User::factory()->create();
        $user->favouriteGames()->attach($z);

        $this->actingAs($user)->getJson('/')->assertOk()->assertJsonPath('games.0.slug', $z->slug);
    }
}
