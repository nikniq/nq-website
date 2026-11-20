<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** While an admin views the site as a player, keep it read-only and don't sign them in to games. */
class RestrictImpersonation
{
    private const ALLOWED = ['impersonate.stop', 'logout'];

    private const BLOCKED_GETS = ['sso.authorize', 'games.play', 'profile.export', 'billing.success'];

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->hasSession() || ! $request->session()->has('impersonator_id')) {
            return $next($request);
        }
        $name = $request->route()?->getName();
        if (in_array($name, self::ALLOWED, true)) {
            return $next($request);
        }
        if (! $request->isMethodSafe() || in_array($name, self::BLOCKED_GETS, true)) {
            return back(fallback: route('dashboard'))->with('error', 'Read-only while viewing as a player. Stop viewing as this player to make changes.');
        }

        return $next($request);
    }
}
