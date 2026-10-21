<?php

namespace Tests\Feature;

use Database\Seeders\GamesSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Production inherits the old nikniq.com database: users already have
 * is_admin/avatar, and `favourites` is a polymorphic users->products table.
 * Migrations must run cleanly on top of it and keep the favourites.
 */
class LegacyDatabaseMigrationTest extends TestCase
{
    public function test_migrations_run_on_the_old_nikniq_schema_and_port_favourites(): void
    {
        // Old schema, as the previous app's migrations left it.
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->boolean('is_admin')->default(false);
            $table->string('avatar')->nullable();
            $table->timestamps();
        });
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('product_code')->unique();
            $table->string('category')->nullable();
            $table->timestamps();
        });
        Schema::create('favourites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('favoritable_type');
            $table->unsignedBigInteger('favoritable_id');
            $table->timestamps();
        });
        Schema::create('migrations', function (Blueprint $table) {
            $table->increments('id');
            $table->string('migration');
            $table->integer('batch');
        });
        DB::table('migrations')->insert(['migration' => '0001_01_01_000000_create_users_table', 'batch' => 1]);
        DB::table('users')->insert(['id' => 1, 'name' => 'Old Player', 'email' => 'old@example.com', 'is_admin' => true]);
        DB::table('products')->insert([
            ['id' => 10, 'name' => 'Chopper', 'product_code' => 'GAME-CHP-01', 'category' => 'Game'],
            ['id' => 11, 'name' => 'Puzzle Grove', 'product_code' => 'GAME-PZG-01', 'category' => 'Game'],
        ]);
        DB::table('favourites')->insert([
            ['user_id' => 1, 'favoritable_type' => 'App\\Models\\Product', 'favoritable_id' => 10],
            ['user_id' => 1, 'favoritable_type' => 'App\\Models\\Product', 'favoritable_id' => 11],
        ]);

        Artisan::call('migrate', ['--force' => true]);
        $this->seed(GamesSeeder::class);

        $this->assertTrue(Schema::hasTable('games'));
        $this->assertTrue(Schema::hasTable('legacy_favourites'));
        $this->assertTrue(Schema::hasColumn('users', 'is_admin'));

        $chopper = DB::table('games')->where('slug', 'chopper')->first();
        $this->assertDatabaseHas('favourites', ['user_id' => 1, 'game_id' => $chopper->id]);
        $this->assertSame(1, DB::table('favourites')->count(), 'only games that exist get a favourite');

        // Running the seeder again does not duplicate.
        $this->seed(GamesSeeder::class);
        $this->assertSame(1, DB::table('favourites')->count());

        $this->get('/')->assertOk()->assertSee('Chopper');
    }
}
