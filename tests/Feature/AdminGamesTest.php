<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminGamesTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admins_are_forbidden(): void
    {
        $this->get('/admin/games')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/admin/games')->assertForbidden();
    }

    public function test_admin_can_create_a_confidential_game_and_sees_the_secret_once(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->post('/admin/games', [
            'name' => 'Chopper', 'slug' => 'chopper', 'url' => 'https://chopper.nikniq.com',
            'redirect_uris' => "https://chopper.nikniq.com/auth/callback\nhttp://localhost:8000/auth/callback",
            'tags' => 'arcade, helicopter', 'is_enabled' => 1,
        ]);

        $game = Game::where('slug', 'chopper')->firstOrFail();
        $response->assertRedirect(route('admin.games.edit', $game))->assertSessionHas('client_secret');
        $this->assertTrue($game->verifySecret(session('client_secret')));
        $this->assertSame(['https://chopper.nikniq.com/auth/callback', 'http://localhost:8000/auth/callback'], $game->redirect_uris);
        $this->assertSame(['arcade', 'helicopter'], $game->tags);
    }

    public function test_admin_can_update_and_delete(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $game = Game::factory()->create();

        $this->actingAs($admin)->put("/admin/games/{$game->slug}", ['name' => 'Renamed', 'slug' => $game->slug, 'is_enabled' => 0])->assertRedirect();
        $this->assertDatabaseHas('games', ['id' => $game->id, 'name' => 'Renamed', 'is_enabled' => false]);

        $this->actingAs($admin)->delete("/admin/games/{$game->slug}")->assertRedirect('/admin/games');
        $this->assertDatabaseMissing('games', ['id' => $game->id]);
    }
}
