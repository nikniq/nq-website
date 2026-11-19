<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class GameScreenshotsTest extends TestCase
{
    use RefreshDatabase;

    public function test_game_page_and_featured_hero_show_a_gallery(): void
    {
        $game = Game::factory()->create(['slug' => 'zz-gallery', 'is_enabled' => true, 'is_featured' => true, 'thumbnail' => '/images/games/x.jpg', 'screenshots' => ['/s/one.jpg', 'https://cdn.example/two.png']]);

        $this->assertSame(['/s/one.jpg', 'https://cdn.example/two.png'], $game->screenshotUrls());
        $this->get('/games/zz-gallery')->assertOk()->assertSee('Screenshots')->assertSee('/s/one.jpg')->assertSee('data-sg', false);
        $this->get('/')->assertOk()->assertSee('https://cdn.example/two.png')->assertSee('class="hero-art"><div class="sg', false);
    }

    public function test_admin_can_upload_screenshots(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $game = Game::factory()->create(['slug' => 'zz-upload']);
        $dir = public_path('images/games/zz-upload');

        try {
            $this->actingAs($admin)->put(route('admin.games.update', $game), [
                'name' => $game->name, 'slug' => 'zz-upload', 'screenshots' => "/a.jpg\n/b.jpg",
                'screenshot_files' => [UploadedFile::fake()->image('shot.png', 1280, 720)],
            ])->assertRedirect();

            $shots = $game->fresh()->screenshots;
            $this->assertCount(3, $shots);
            $this->assertSame(['/a.jpg', '/b.jpg'], array_slice($shots, 0, 2));
            $this->assertFileExists(public_path(ltrim($shots[2], '/')));
            $this->assertCount(3, $game->fresh()->screenshotUrls(), 'uploaded file is not listed twice');
        } finally {
            File::deleteDirectory($dir);
        }
    }
}
