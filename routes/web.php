<?php

use App\Http\Controllers\Admin\GameController as AdminGameController;
use App\Http\Controllers\Admin\PlayerController as AdminPlayerController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SalesController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\SocialLoginController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FavouriteController;
use App\Http\Controllers\GamesController;
use App\Http\Controllers\HallOfFameController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\PlayerProfileController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\Sso\AuthorizeController;
use App\Models\Game;
use App\Support\Oidc;
use Illuminate\Support\Facades\Route;

// OpenID Connect discovery for subsites using standard OAuth/OIDC libraries.
Route::get('/.well-known/openid-configuration', function () {
    $iss = Oidc::issuer();

    return response()->json([
        'issuer' => $iss,
        'authorization_endpoint' => route('sso.authorize'),
        'token_endpoint' => route('api.sso.token'),
        'userinfo_endpoint' => route('api.oauth.userinfo'),
        'revocation_endpoint' => route('api.oauth.revoke'),
        'jwks_uri' => route('api.oauth.jwks'),
        'response_types_supported' => ['code'],
        'response_modes_supported' => ['query'],
        'grant_types_supported' => ['authorization_code'],
        'subject_types_supported' => ['public'],
        'id_token_signing_alg_values_supported' => ['RS256'],
        'scopes_supported' => Oidc::SCOPES,
        // Non-standard: values for the authorize endpoint's provider= parameter.
        'nikniq_login_providers' => array_values(array_filter(['nikniq', config('social.providers.google.enabled') ? 'google' : null])),
        'claims_supported' => ['sub', 'name', 'preferred_username', 'picture', 'profile', 'email', 'email_verified', 'iss', 'aud', 'exp', 'iat', 'auth_time', 'nonce', 'at_hash'],
        'token_endpoint_auth_methods_supported' => ['client_secret_basic', 'client_secret_post', 'none'],
        'code_challenge_methods_supported' => ['S256'],
        'prompt_values_supported' => ['none'],
    ])->header('Cache-Control', 'public, max-age=3600');
})->name('oidc.discovery');

// Game sign-in: guests go to /login and back, or get login_required with prompt=none.
Route::get('/sso/authorize', AuthorizeController::class)->middleware('throttle:60,1')->name('sso.authorize');

Route::get('/', [GamesController::class, 'index'])->name('games.index');
Route::get('/games', fn () => redirect()->route('games.index', request()->query()));
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/developers', fn () => view()->first(['pages.'.app()->getLocale().'.developers', 'pages.developers'], ['games' => Game::enabled()->orderBy('name')->get(['slug', 'name'])]))->name('developers');
Route::get('/news', [NewsController::class, 'index'])->name('news.index');
Route::get('/news/{slug}', [NewsController::class, 'show'])->where('slug', '[a-z0-9-]+')->name('news.show');
Route::get('/pricing', [BillingController::class, 'pricing'])->name('pricing');
Route::get('/privacy', fn () => view()->first(['pages.'.app()->getLocale().'.privacy', 'pages.privacy']))->name('privacy');
Route::get('/terms', fn () => view()->first(['pages.'.app()->getLocale().'.terms', 'pages.terms']))->name('terms');
Route::get('/hall-of-fame', HallOfFameController::class)->name('hall');
Route::get('/players/{player}', [PlayerProfileController::class, 'show'])->name('players.show');
Route::get('/games/random', [GamesController::class, 'random'])->name('games.random');
Route::get('/games/suggest', [GamesController::class, 'suggest'])->name('games.suggest');
Route::get('/games/{game}', [GamesController::class, 'show'])->name('games.show');
Route::get('/games/{game}/play', [GamesController::class, 'play'])->name('games.play');
Route::get('/games/{game}/leaderboard', [GamesController::class, 'leaderboard'])->name('games.leaderboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:6,1');
    Route::get('/forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'email'])->middleware('throttle:6,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->middleware('throttle:10,1')->name('password.update');
    Route::get('/register/sent', [RegisterController::class, 'sent'])->name('register.sent');
    Route::post('/register/resend', [RegisterController::class, 'resend'])->middleware('throttle:6,1')->name('register.resend');
});

// Email confirmation link. Not behind 'guest': a signed-in player may open it too.
Route::get('/register/verify/{user}/{hash}', [RegisterController::class, 'verify'])
    ->middleware(['signed', 'throttle:12,1'])->name('register.verify');

// Google sign-in. Guests sign in or sign up; signed-in users link the account.
Route::get('/auth/{provider}/redirect', [SocialLoginController::class, 'redirect'])->whereIn('provider', ['google'])->name('social.redirect');
Route::get('/auth/{provider}/callback', [SocialLoginController::class, 'callback'])->whereIn('provider', ['google'])->name('social.callback');

Route::middleware('auth')->group(function () {
    Route::get('/register/password', [RegisterController::class, 'password'])->name('register.password');
    Route::put('/register/password', [RegisterController::class, 'storePassword']);
    Route::post('/auth/unlink', [SocialLoginController::class, 'unlink'])->name('social.unlink');
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/games/{game}/favourite', [FavouriteController::class, 'toggle'])->name('games.favourite');

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::post('/billing/checkout', [BillingController::class, 'checkout'])->middleware('throttle:10,1')->name('billing.checkout');
    Route::get('/billing/plus', fn () => redirect()->route('pricing'))->name('billing.start'); // guests log in, then return here
    Route::get('/billing/success', [BillingController::class, 'success'])->name('billing.success');
    Route::post('/billing/portal', [BillingController::class, 'portal'])->middleware('throttle:10,1')->name('billing.portal');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'password'])->name('profile.password');
    Route::put('/profile/visibility', [PlayerProfileController::class, 'visibility'])->name('profile.visibility');
    Route::get('/profile/export', [ProfileController::class, 'export'])->name('profile.export');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->middleware('throttle:6,1')->name('profile.destroy');
    Route::post('/profile/disconnect', [ProfileController::class, 'disconnect'])->name('profile.disconnect');

    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
        Route::get('/', fn () => redirect()->route('admin.games.index'));
        Route::get('/sales', [SalesController::class, 'index'])->name('sales.index');
        Route::get('/sales/export', [SalesController::class, 'export'])->name('sales.export');
        Route::post('/games/{game}/rotate-secret', [AdminGameController::class, 'rotateSecret'])->name('games.rotate-secret');
        Route::resource('games', AdminGameController::class)->except('show');
        Route::get('/players', [AdminPlayerController::class, 'index'])->name('players.index');
        Route::delete('/players/{id}', [AdminPlayerController::class, 'destroy'])->whereNumber('id')->name('players.destroy');
        Route::post('/players/{id}/restore', [AdminPlayerController::class, 'restore'])->whereNumber('id')->name('players.restore');
        Route::delete('/players/{id}/purge', [AdminPlayerController::class, 'purge'])->whereNumber('id')->name('players.purge');
        Route::post('/players/{id}/admin', [AdminPlayerController::class, 'toggleAdmin'])->whereNumber('id')->name('players.admin');
        Route::get('/activity', [AdminPlayerController::class, 'activity'])->name('players.activity');
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
    });
});
