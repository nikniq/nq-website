<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Picks the page language: ?lang=xx (remembered in a cookie for a year),
 * then the cookie, then the browser's Accept-Language, then English.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = array_keys(config('portal.locales'));
        $chosen = $request->query('lang');
        $valid = is_string($chosen) && in_array($chosen, $supported, true);

        $locale = $valid ? $chosen
            : (in_array($request->cookie('lang'), $supported, true) ? $request->cookie('lang')
            : ($request->getPreferredLanguage($supported) ?? config('app.locale')));

        app()->setLocale($locale);

        $response = $next($request);

        if ($valid && $request->cookie('lang') !== $chosen) {
            $response->headers->setCookie(cookie('lang', $chosen, 60 * 24 * 365));
        }

        return $response;
    }
}
