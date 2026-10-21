<?php

use App\Http\Middleware\AuthenticatePlayer;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
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

            return redirect()->route('games.index')
                ->with('status', 'That page does not exist here, so here is the games catalogue.');
        });
    })->create();

// The web root is public_html (shared hosting convention), not public.
$app->usePublicPath(dirname(__DIR__).'/public_html');

return $app;
