<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sso_codes', function (Blueprint $table) {
            $table->string('scope', 255)->nullable()->after('code_challenge');
            $table->string('nonce', 255)->nullable()->after('scope');
        });
        Schema::table('player_tokens', function (Blueprint $table) {
            $table->string('scope', 255)->nullable()->after('token_hash');
        });
    }

    public function down(): void
    {
        Schema::table('sso_codes', fn (Blueprint $table) => $table->dropColumn(['scope', 'nonce']));
        Schema::table('player_tokens', fn (Blueprint $table) => $table->dropColumn('scope'));
    }
};
