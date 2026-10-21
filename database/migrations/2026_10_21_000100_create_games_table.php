<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The old nikniq.com app had a polymorphic `favourites` table (users ->
        // products). Keep it aside so the new pivot can take the name; its rows
        // are ported into the new table at the end of this migration.
        $legacyFavourites = Schema::hasTable('favourites') && Schema::hasColumn('favourites', 'favoritable_type');
        if ($legacyFavourites) {
            Schema::rename('favourites', 'legacy_favourites');
        }

        // A game hosted elsewhere (usually a nikniq.com subdomain). The row is
        // both the catalogue entry and the SSO client the game authenticates as.
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('url', 2048)->nullable();            // where "Play" sends the player
            $table->string('thumbnail', 2048)->nullable();      // image URL or /path under public_html
            $table->string('genre', 64)->nullable();
            $table->json('tags')->nullable();
            $table->string('client_id', 64)->unique();
            $table->string('client_secret')->nullable();        // hashed; null for public (PKCE) clients
            $table->json('redirect_uris')->nullable();
            $table->boolean('is_public')->default(false);       // PKCE-only, no secret (static JS games)
            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('favourites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'game_id']);
        });

        // One-time authorization codes handed to the game via redirect.
        Schema::create('sso_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('code_hash', 64)->unique();
            $table->string('redirect_uri', 2048);
            $table->string('code_challenge', 128)->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });

        // Long-lived bearer tokens a game holds for a signed-in player.
        Schema::create('player_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'game_id']);
        });

        // Raw activity reported by games.
        Schema::create('game_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('event', 64);
            $table->bigInteger('score')->nullable();
            $table->string('level', 64)->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->boolean('won')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamp('created_at')->nullable();
            $table->index(['game_id', 'user_id', 'occurred_at']);
            $table->index(['user_id', 'occurred_at']);
            $table->index(['game_id', 'event']);
        });

        // Per-player, per-game aggregates kept current as events arrive.
        Schema::create('player_game_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('events')->default(0);
            $table->unsignedInteger('plays')->default(0);
            $table->unsignedInteger('wins')->default(0);
            $table->bigInteger('total_score')->default(0);
            $table->bigInteger('best_score')->nullable();
            $table->unsignedBigInteger('total_duration_seconds')->default(0);
            $table->string('last_level', 64)->nullable();
            $table->timestamp('first_played_at')->nullable();
            $table->timestamp('last_played_at')->nullable();
            $table->timestamps();
            $table->unique(['game_id', 'user_id']);
            $table->index(['game_id', 'best_score']);
        });

        if ($legacyFavourites) {
            $this->portLegacyFavourites();
        }
    }

    /**
     * Old favourites pointed at `products` rows; match them to games by name.
     * Games only exist after the seeder runs, so this also runs again from
     * GamesSeeder, and only inserts pairs that are still missing.
     */
    private function portLegacyFavourites(): void
    {
        if (! Schema::hasTable('products') || ! Schema::hasTable('legacy_favourites')) {
            return;
        }

        $rows = DB::table('legacy_favourites as f')
            ->join('products as p', 'p.id', '=', 'f.favoritable_id')
            ->join('games as g', 'g.name', '=', 'p.name')
            ->where('f.favoritable_type', 'like', '%Product')
            ->select('f.user_id', 'g.id as game_id', 'f.created_at')
            ->get();

        foreach ($rows as $row) {
            DB::table('favourites')->insertOrIgnore([
                'user_id' => $row->user_id,
                'game_id' => $row->game_id,
                'created_at' => $row->created_at ?? now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('player_game_stats');
        Schema::dropIfExists('game_events');
        Schema::dropIfExists('player_tokens');
        Schema::dropIfExists('sso_codes');
        Schema::dropIfExists('favourites');
        Schema::dropIfExists('games');
        if (Schema::hasTable('legacy_favourites')) {
            Schema::rename('legacy_favourites', 'favourites');
        }
    }
};
