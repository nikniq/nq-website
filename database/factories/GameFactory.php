<?php

namespace Database\Factories;

use App\Models\Game;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Game> */
class GameFactory extends Factory
{
    protected $model = Game::class;

    public function definition(): array
    {
        $name = ucfirst($this->faker->unique()->words(2, true));

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $this->faker->sentence(12),
            'url' => 'https://'.Str::slug($name).'.nikniq.com',
            'genre' => 'Arcade',
            'tags' => ['browser'],
            'client_id' => Game::generateClientId(),
            'client_secret' => null,
            'redirect_uris' => ['https://'.Str::slug($name).'.nikniq.com/auth/callback'],
            'is_public' => false,
            'is_enabled' => true,
            'is_featured' => false,
            'sort_order' => 0,
        ];
    }

    public function withSecret(string $plain): static
    {
        return $this->state(fn () => ['client_secret' => bcrypt($plain)]);
    }

    public function public(): static
    {
        return $this->state(fn () => ['is_public' => true, 'client_secret' => null]);
    }
}
