<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            // Suitable for players living with dementia: calm pace, simple
            // rules, no time pressure. Surfaced as a catalogue filter.
            $table->boolean('is_dementia')->default(false)->after('is_featured')->index();
        });
    }

    public function down(): void
    {
        Schema::table('games', fn (Blueprint $table) => $table->dropColumn('is_dementia'));
    }
};
