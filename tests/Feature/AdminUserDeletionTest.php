<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\PlayerToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserDeletionTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_soft_delete_suspends_and_restore_brings_back(): void
    {
        $admin = $this->admin();
        $player = User::factory()->create(['email' => 'p@example.com', 'password' => 'secret123']);
        $token = PlayerToken::create(['game_id' => Game::factory()->create()->id, 'user_id' => $player->id, 'token_hash' => 'h', 'expires_at' => now()->addDay()]);

        $this->actingAs($admin)->delete("/admin/players/{$player->id}", ['reason' => 'spam'])->assertSessionHas('status');
        $this->assertSoftDeleted($player);
        $this->assertNotNull($token->fresh()->revoked_at);
        $this->assertSame('spam', User::withTrashed()->find($player->id)->deleted_reason);

        $this->actingAs($admin)->get('/admin/players?show=deleted')->assertSee('p@example.com')->assertSee('spam');
        $this->actingAs($admin)->get('/admin/players')->assertDontSee('p@example.com');

        // Suspended: can't log in or sign up again with that email.
        auth()->logout();
        $this->post('/login', ['email' => 'p@example.com', 'password' => 'secret123'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->post('/register', ['email' => 'p@example.com'])->assertSessionHasErrors('email');

        $this->actingAs($admin)->post("/admin/players/{$player->id}/restore")->assertSessionHas('status');
        $this->assertNotSoftDeleted($player);
    }

    public function test_hard_delete_needs_the_email_typed_and_removes_everything(): void
    {
        $admin = $this->admin();
        $player = User::factory()->create(['email' => 'gone@example.com']);
        $player->favouriteGames()->attach(Game::factory()->create());

        $this->actingAs($admin)->delete("/admin/players/{$player->id}/purge", ['confirm_email' => 'wrong@example.com'])->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $player->id]);

        $this->actingAs($admin)->delete("/admin/players/{$player->id}/purge", ['confirm_email' => 'GONE@example.com'])->assertSessionHas('status');
        $this->assertDatabaseMissing('users', ['id' => $player->id]);
        $this->assertDatabaseMissing('favourites', ['user_id' => $player->id]);
    }

    public function test_admins_cannot_delete_themselves_or_the_last_admin(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->delete("/admin/players/{$admin->id}")->assertSessionHas('error');
        $this->actingAs($admin)->delete("/admin/players/{$admin->id}/purge", ['confirm_email' => $admin->email])->assertSessionHas('error');
        $this->assertNotSoftDeleted($admin);

        $other = User::factory()->create();
        $this->actingAs($admin)->post("/admin/players/{$other->id}/admin");
        $this->assertTrue($other->fresh()->is_admin);
        $this->actingAs($admin)->post("/admin/players/{$other->id}/admin");
        $this->assertFalse($other->fresh()->is_admin);
    }

    public function test_non_admins_cannot_delete_users(): void
    {
        $player = User::factory()->create();
        $this->actingAs(User::factory()->create())->delete("/admin/players/{$player->id}")->assertForbidden();
        $this->assertNotSoftDeleted($player);
    }

    public function test_player_deleting_their_own_account_is_a_hard_delete(): void
    {
        $player = User::factory()->create();
        $this->actingAs($player)->delete('/profile', ['confirm_email' => $player->email]);
        $this->assertDatabaseMissing('users', ['id' => $player->id]);
    }
}
