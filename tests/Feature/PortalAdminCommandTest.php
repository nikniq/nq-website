<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PortalAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_grant_revoke_and_password(): void
    {
        $this->artisan('portal:admin create boss@example.com --name=Boss')->expectsOutputToContain('Temporary password')->assertSuccessful();
        $this->assertTrue(User::where('email', 'boss@example.com')->value('is_admin'));

        $player = User::factory()->create(['email' => 'p@example.com']);
        $this->artisan('portal:admin grant P@example.com')->assertSuccessful();
        $this->assertTrue($player->fresh()->is_admin);

        $this->artisan('portal:admin revoke p@example.com')->assertSuccessful();
        $this->assertFalse($player->fresh()->is_admin);

        $old = $player->fresh()->password;
        $this->artisan('portal:admin password p@example.com')->expectsOutputToContain('New password')->assertSuccessful();
        $this->assertNotSame($old, $player->fresh()->password);
    }

    public function test_refuses_to_remove_the_last_admin_or_unknown_users(): void
    {
        User::factory()->create(['email' => 'only@example.com', 'is_admin' => true]);
        $this->artisan('portal:admin revoke only@example.com')->assertFailed();
        $this->artisan('portal:admin grant nobody@example.com')->assertFailed();
        $this->artisan('portal:admin grant not-an-email')->assertExitCode(2);
        $this->artisan('portal:admin list')->expectsOutputToContain('only@example.com')->assertSuccessful();
    }
}
