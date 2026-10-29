<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/** GET /sitemap.xml: public pages plus every enabled game and its leaderboard. */
class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $xml = Cache::remember('sitemap.xml', now()->addMinutes(30), function () {
            $urls = [
                ['loc' => route('games.index'), 'changefreq' => 'daily', 'priority' => '1.0', 'lastmod' => Game::enabled()->max('updated_at')],
                ['loc' => route('hall'), 'changefreq' => 'daily', 'priority' => '0.7'],
                ['loc' => route('register'), 'changefreq' => 'monthly', 'priority' => '0.3'],
                ['loc' => route('privacy'), 'changefreq' => 'yearly', 'priority' => '0.1'],
                ['loc' => route('terms'), 'changefreq' => 'yearly', 'priority' => '0.1'],
                ['loc' => route('login'), 'changefreq' => 'monthly', 'priority' => '0.2'],
            ];

            foreach (Game::enabled()->orderBy('sort_order')->orderBy('name')->get() as $game) {
                $urls[] = ['loc' => route('games.show', $game), 'changefreq' => 'weekly', 'priority' => $game->is_featured ? '0.9' : '0.8', 'lastmod' => $game->updated_at, 'image' => $game->thumbnailUrl() ? url($game->thumbnailUrl()) : null, 'title' => $game->name];
                $urls[] = ['loc' => route('games.leaderboard', $game), 'changefreq' => 'hourly', 'priority' => '0.5'];
            }

            $out = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
            $out .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">'."\n";
            foreach ($urls as $u) {
                $out .= "  <url>\n    <loc>".e($u['loc'])."</loc>\n";
                if (! empty($u['lastmod'])) {
                    $out .= '    <lastmod>'.Carbon::parse($u['lastmod'])->toAtomString()."</lastmod>\n";
                }
                $out .= '    <changefreq>'.$u['changefreq']."</changefreq>\n    <priority>".$u['priority']."</priority>\n";
                if (! empty($u['image'])) {
                    $out .= '    <image:image><image:loc>'.e($u['image']).'</image:loc><image:title>'.e($u['title'])."</image:title></image:image>\n";
                }
                $out .= "  </url>\n";
            }

            return $out.'</urlset>'."\n";
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
