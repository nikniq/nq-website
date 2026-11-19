<?php

use App\Http\Controllers\Api\FavouriteController;
use App\Http\Controllers\Api\GameCatalogController;
use App\Http\Controllers\Api\GameEventController;
use App\Http\Controllers\Api\LeaderboardController;
use App\Http\Controllers\Api\SsoTokenController;
use App\Http\Controllers\StripeWebhookController;
use App\Support\Oidc;
use Illuminate\Support\Facades\Route;

// Public, read-only.
Route::get('/games', GameCatalogController::class)->middleware('throttle:120,1')->name('api.games.index');
Route::get('/games/{game}/leaderboard', LeaderboardController::class)->middleware('throttle:120,1')->name('api.games.leaderboard');

// Game SSO: code exchange is client-authenticated, the rest needs a player token.
Route::post('/sso/token', [SsoTokenController::class, 'token'])->middleware('throttle:60,1')->name('api.sso.token');

Route::post('/oauth/revoke', [SsoTokenController::class, 'revoke'])->middleware('throttle:60,1')->name('api.oauth.revoke');
Route::get('/oauth/jwks', fn () => response()->json(Oidc::jwks())->header('Cache-Control', 'public, max-age=3600'))->name('api.oauth.jwks');

Route::middleware(['player', 'throttle:120,1'])->group(function () {
    Route::match(['get', 'post'], '/oauth/userinfo', [SsoTokenController::class, 'userinfo'])->name('api.oauth.userinfo');
    Route::get('/sso/me', [SsoTokenController::class, 'me'])->name('api.sso.me');
    Route::post('/sso/logout', [SsoTokenController::class, 'logout'])->name('api.sso.logout');
    Route::post('/sso/favourite', [FavouriteController::class, 'store'])->name('api.sso.favourite');
    Route::delete('/sso/favourite', [FavouriteController::class, 'destroy'])->name('api.sso.unfavourite');
    Route::post('/games/{game}/events', [GameEventController::class, 'store'])->name('api.games.events.store');
});

// Stripe webhooks (signature-verified; no session or CSRF).
Route::post('/stripe/webhook', StripeWebhookController::class)->middleware('throttle:120,1')->name('api.stripe.webhook');
