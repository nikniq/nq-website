<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Guarded: the inherited nikniq.com users table already has these. */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'provider_name')) {
                $table->string('provider_name', 32)->nullable();
            }
            if (! Schema::hasColumn('users', 'provider_id')) {
                $table->string('provider_id', 191)->nullable();
            }
            // Accounts created through Google have no password until they set one.
            $table->string('password')->nullable()->change();
        });

        if (! collect(Schema::getIndexes('users'))->contains(fn ($i) => $i['name'] === 'users_provider_name_provider_id_index')) {
            Schema::table('users', fn (Blueprint $table) => $table->index(['provider_name', 'provider_id']));
        }
    }

    public function down(): void
    {
        // Columns may predate this app; leave them in place.
    }
};
