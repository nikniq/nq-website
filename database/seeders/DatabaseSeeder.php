<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Demo admin. Change the password (or delete the user) in production.
        User::firstOrCreate(
            ['email' => 'demo@example.com'],
            ['name' => 'Demo Admin', 'password' => 'password', 'is_admin' => true],
        );
        User::where('email', 'demo@example.com')->update(['is_admin' => true]);

        $this->call(GamesSeeder::class);
    }
}
