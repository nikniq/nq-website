<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Game;
use App\Models\User;
use App\Services\GameStatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPlayerDetailsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_player_page_shows_usage_and_their_view(): void
    {
        $player = User::factory()->create(['name' => 'Zed Player']);
        $game = Game::factory()->create(['name' => 'Rocket']);
        app(GameStatsService::class)->record($game, $player, ['event' => 'game_over', 'score' => 4321, 'duration_seconds' => 120]);

        $this->actingAs($this->admin())->get(route('admin.players.show', $player->id))
            ->assertOk()->assertSee('Zed Player')->assertSee('Plays per day')->assertSee('What Zed Player sees')->assertSee('4,321')->assertSee('Rocket')->assertSee('View site as');
        $this->actingAs($player)->get(route('admin.players.show', $player->id))->assertForbidden();
    }

    public function test_admin_can_edit_player_and_it_is_logged(): void
    {
        $admin = $this->admin();
        $player = User::factory()->create(['name' => 'Old', 'email' => 'old@example.com', 'is_public' => false]);
        User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($admin)->put(route('admin.players.update', $player->id), ['name' => 'X', 'email' => 'taken@example.com'])->assertSessionHasErrors('email');
        $this->actingAs($admin)->put(route('admin.players.update', $player->id), ['name' => 'New', 'email' => 'New@Example.com', 'is_public' => '1', 'email_verified' => '0'])->assertRedirect();

        $player->refresh();
        $this->assertSame(['New', 'new@example.com', true], [$player->name, $player->email, $player->is_public]);
        $log = AuditLog::where('action', 'player_updated')->firstOrFail();
        $this->assertSame($admin->id, $log->actor_id);
        $this->assertSame(['from' => 'Old', 'to' => 'New'], $log->details['name']);
        $this->actingAs($admin)->get(route('admin.logs.index'))->assertOk()->assertSee('Player edited')->assertSee('Logins per day');
    }

    public function test_view_as_player_is_read_only_and_returns_to_admin(): void
    {
        $admin = $this->admin();
        $player = User::factory()->create(['name' => 'Pat']);

        $this->actingAs($admin)->post(route('admin.players.impersonate', $player->id))->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($player);
        $this->get('/dashboard')->assertOk()->assertSee('Viewing as')->assertSee('Hi, Pat');
        $this->put('/profile', ['name' => 'Hacked'])->assertSessionHas('error');
        $this->assertSame('Pat', $player->fresh()->name);
        $this->get(route('admin.players.index'))->assertForbidden();

        $this->post(route('impersonate.stop'))->assertRedirect(route('admin.players.show', $player->id));
        $this->assertAuthenticatedAs($admin);
        $this->assertSame(1, AuditLog::where('action', 'impersonation_started')->count());
        $this->assertSame(1, AuditLog::where('action', 'impersonation_stopped')->count());
        $this->assertSame(0, AuditLog::where('action', 'login')->where('subject_id', $player->id)->count());

        $other = $this->admin();
        $this->actingAs($admin)->post(route('admin.players.impersonate', $other->id))->assertSessionHas('error');
        $this->assertAuthenticatedAs($admin);
    }

    public function test_logins_failures_and_deletion_are_logged(): void
    {
        $player = User::factory()->create(['email' => 'p@example.com', 'password' => 'secret-pass']);
        $this->post('/login', ['email' => 'p@example.com', 'password' => 'wrong'])->assertSessionHasErrors();
        $this->post('/login', ['email' => 'p@example.com', 'password' => 'secret-pass']);
        $this->assertSame(1, AuditLog::where('action', 'login_failed')->count());
        $this->assertSame(1, AuditLog::where('action', 'login')->where('subject_id', $player->id)->count());
        $this->assertNotNull($player->fresh()->last_login_at);

        $this->post('/logout');
        $this->actingAs($this->admin())->delete(route('admin.players.purge', $player->id), ['confirm_email' => 'p@example.com'])->assertRedirect();
        $this->assertDatabaseMissing('users', ['id' => $player->id]);
        $this->assertSame('p@example.com', AuditLog::where('action', 'player_deleted')->value('subject_label'));
        $this->assertSame(0, AuditLog::whereNotNull('subject_id')->where('subject_id', $player->id)->count());
    }
}
