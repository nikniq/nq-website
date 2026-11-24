<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// mindsweeper.nikniq.com is live: put the (disabled) catalogue row back on.
return new class extends Migration
{
    public function up(): void
    {
        DB::table('games')->where('slug', 'mindsweeper')->update(['is_enabled' => true, 'url' => 'https://mindsweeper.nikniq.com', 'updated_at' => now()]);
    }

    public function down(): void
    {
        //
    }
};
