<?php

use App\Http\Middleware\AuthenticatePlayer;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'player' => AuthenticatePlayer::class,
        ]);
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // An expired or mangled sign-up link: explain and offer a new one
        // instead of the framework's bare 403 page.
        $exceptions->render(function (InvalidSignatureException $e, Request $request) {
            if ($request->expectsJson()) {
                return null;
            }

            return redirect()->route('register')
                ->with('error', 'That confirmation link has expired or is incomplete. Enter your email below and we will send a fresh one.');
        });

        // A missing page (old nikniq.com URL, typo, stale link, unknown game
        // slug) sends the visitor to the catalogue instead of a 404 page.
        // API calls and asset-like paths (anything with a file extension)
        // still get a real 404 so clients and crawlers are not misled.
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson() || ! $request->isMethod('GET')) {
                return null;
            }
            if (preg_match('/\.[a-z0-9]{2,5}$/i', $request->path())) {
                return null;
            }

            // Unmatched routes never start a session, so a flashed message would be
            // lost; the catalogue reads this query flag instead.
            return redirect()->route('games.index', ['missing' => 1]);
        });
    })->create();

// The web root is public_html (shared hosting convention), not public.
$app->usePublicPath(dirname(__DIR__).'/public_html');

return $app;
