<?php

use App\Http\Controllers\Admin\GameController as AdminGameController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\SocialLoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FavouriteController;
use App\Http\Controllers\GamesController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Sso\AuthorizeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [GamesController::class, 'index'])->name('games.index');
Route::get('/games', fn () => redirect()->route('games.index', request()->query()));
Route::get('/games/{game}', [GamesController::class, 'show'])->name('games.show');
Route::get('/games/{game}/play', [GamesController::class, 'play'])->name('games.play');
Route::get('/games/{game}/leaderboard', [GamesController::class, 'leaderboard'])->name('games.leaderboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:6,1');
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
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'password'])->name('profile.password');
    Route::post('/profile/disconnect', [ProfileController::class, 'disconnect'])->name('profile.disconnect');

    // Game sign-in: must be authenticated, so guests get bounced to /login and back.
    Route::get('/sso/authorize', AuthorizeController::class)->name('sso.authorize');

    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
        Route::get('/', fn () => redirect()->route('admin.games.index'));
        Route::post('/games/{game}/rotate-secret', [AdminGameController::class, 'rotateSecret'])->name('games.rotate-secret');
        Route::resource('games', AdminGameController::class)->except('show');
    });
});
