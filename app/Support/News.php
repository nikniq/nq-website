<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * News articles live in the repo: metadata here, body in
 * resources/views/news/articles/{slug}.blade.php. Newest first.
 */
class News
{
    public const ARTICLES = [
        ['a-fresh-look-and-a-toolkit-for-game-makers', 'A fresh look, and a toolkit for game makers', '2026-11-05', 'The portal has a calmer, friendlier design, and any game can now save scores to your NikniQ account with a few lines of code.', '/images/games/space-invaders.jpg'],
        ['calm-mode-and-dementia-friendly-games', 'Calm mode and dementia-friendly games', '2026-10-25', 'A new filter finds games with a gentle pace and simple rules, and calm mode turns down motion and colour across the site.', '/images/games/reversi.jpg'],
        ['one-account-for-every-game', 'One account for every NikniQ game', '2026-10-21', 'The new nikniq.com portal brings every game together, with favourites, saved scores and leaderboards under a single sign-in.', '/images/games/pacman.jpg'],
    ];

    /** @return Collection<int, object{slug:string,title:string,date:Carbon,summary:string,image:string}> */
    public static function all(): Collection
    {
        return collect(self::ARTICLES)->map(fn ($a) => (object) [
            'slug' => $a[0], 'title' => $a[1], 'date' => Carbon::parse($a[2]), 'summary' => $a[3], 'image' => $a[4],
        ]);
    }

    public static function find(string $slug): ?object
    {
        return self::all()->firstWhere('slug', $slug);
    }
}
