<?php

namespace App\Support;

use App\Models\Game;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Catalogue search with a small query language, run in PHP over the (small)
 * enabled catalogue so it can match translated descriptions and genres:
 *
 *   snake pong          every word must match (or any word with $matchAny)
 *   "power pellets"     exact phrase
 *   -memory             exclude games matching a word
 *   genre:puzzle        genre contains (English or translated name)
 *   name:pac            name contains
 *   is:featured / is:calm (dementia-friendly) / is:favourite
 *
 * Matching ignores case and accents; a word that matches nothing exactly may
 * match a game-name word within one or two typos. Results are ranked: name
 * start, name, genre, then description.
 */
class GameSearch
{
    /** @var array<int, array{type:string, value:string, negate:bool}> */
    public array $terms = [];

    public function __construct(public string $raw, public bool $namesOnly = false, public bool $matchAny = false)
    {
        $this->terms = self::parse($raw);
    }

    /** @return array<int, array{type:string, value:string, negate:bool}> */
    public static function parse(string $raw): array
    {
        preg_match_all('/(-?)(?:(\w+):)?(?:"([^"]*)"|(\S+))/u', $raw, $m, PREG_SET_ORDER);
        $terms = [];
        foreach ($m as $part) {
            $negate = $part[1] === '-';
            $field = strtolower($part[2] ?? '');
            $phrase = ($part[3] ?? '') !== '';
            $value = self::norm($phrase ? $part[3] : ($part[4] ?? ''));
            if ($value === '' || $value === '-') {
                continue;
            }
            $type = match (true) {
                in_array($field, ['genre', 'name', 'is'], true) => $field,
                $field !== '' => 'word', // unknown operator: treat "foo:bar" as text
                $phrase => 'phrase',
                default => 'word',
            };
            if ($field !== '' && $type === 'word') {
                $value = self::norm($field.':'.$value);
            }
            $terms[] = ['type' => $type, 'value' => $value, 'negate' => $negate];
        }

        return $terms;
    }

    public static function norm(string $s): string
    {
        // Lowercase and strip accents (é → e) but keep non-Latin scripts such as
        // Japanese intact; the browser does the same with NFD + \p{M} removal.
        $s = preg_replace_callback('/[\x{00C0}-\x{024F}]/u', fn ($m) => Str::ascii($m[0]), $s);

        return trim(Str::lower($s));
    }

    public function isEmpty(): bool
    {
        return $this->terms === [];
    }

    /**
     * Filter and (when $rank) order the games.
     *
     * @param  Collection<int, Game>  $games
     * @param  array<int, int>  $favouriteIds
     * @return Collection<int, Game>
     */
    public function apply(Collection $games, array $favouriteIds = [], bool $rank = true): Collection
    {
        if ($this->isEmpty()) {
            return $games;
        }

        $scored = $games->map(function (Game $g) use ($favouriteIds) {
            $name = self::norm($g->name);
            $genre = self::norm(($g->genre ?? '').' '.(Game::genreLabel($g->genre) ?? ''));
            $desc = $this->namesOnly ? '' : self::norm(($g->description ?? '').' '.($g->localDescription() ?? ''));
            $nameWords = preg_split('/[^\p{L}\p{N}]+/u', $name, -1, PREG_SPLIT_NO_EMPTY);

            $score = 0;
            $positives = 0;
            $hits = 0;
            foreach ($this->terms as $t) {
                $s = $this->termScore($t, $g, $name, $nameWords, $genre, $desc, $favouriteIds);
                if ($t['negate']) {
                    if ($s > 0) {
                        return null;
                    }

                    continue;
                }
                $positives++;
                $isFilter = in_array($t['type'], ['is', 'genre', 'name'], true);
                if ($s === 0 && ($isFilter || ! $this->matchAny)) {
                    return null;
                }
                if ($s > 0) {
                    $hits++;
                    $score += $s;
                }
            }

            return ($positives === 0 || $hits > 0) ? ['game' => $g, 'score' => $score] : null;
        })->filter()->values();

        if ($rank) {
            $scored = $scored->sortByDesc('score', SORT_NUMERIC)->values();
        }

        return $scored->pluck('game');
    }

    private function termScore(array $t, Game $g, string $name, array $nameWords, string $genre, string $desc, array $favouriteIds): int
    {
        $v = $t['value'];

        return match ($t['type']) {
            'is' => match ($v) {
                'featured' => $g->is_featured ? 1 : 0,
                'calm', 'dementia', 'dementia-friendly' => $g->is_dementia ? 1 : 0,
                'favourite', 'favorite', 'fav' => in_array($g->id, $favouriteIds, true) ? 1 : 0,
                default => 0,
            },
            'genre' => str_contains($genre, $v) ? 1 : 0,
            'name' => str_contains($name, $v) ? 10 : 0,
            default => match (true) {
                str_starts_with($name, $v) => 100,
                (bool) preg_match('/(^|[^\p{L}\p{N}])'.preg_quote($v, '/').'/u', $name) => 80,
                str_contains($name, $v) => 60,
                str_contains($genre, $v) => 40,
                $desc !== '' && str_contains($desc, $v) => 20,
                $t['type'] === 'word' && ! $t['negate'] && $this->fuzzy($v, $nameWords) => 30,
                default => 0,
            },
        };
    }

    /** One typo for short words, two for longer ones; letters only, at least 4 long. */
    private function fuzzy(string $word, array $nameWords): bool
    {
        if (mb_strlen($word) < 4) {
            return false;
        }
        $max = mb_strlen($word) >= 7 ? 2 : 1;
        foreach ($nameWords as $w) {
            if (abs(mb_strlen($w) - mb_strlen($word)) <= $max && levenshtein($word, $w) <= $max) {
                return true;
            }
        }

        return false;
    }
}
