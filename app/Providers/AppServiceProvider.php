<?php

namespace App\Providers;

use App\Models\User;
use App\Support\Audit;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Sign-in log for /admin/logs and the player page. Viewing as a player is logged separately.
        Event::listen(function (Login $event) {
            if (! $event->user instanceof User || (request()->hasSession() && session()->has('impersonator_id')) || request()->routeIs('impersonate.stop', 'admin.players.impersonate')) {
                return;
            }
            $event->user->forceFill(['last_login_at' => now(), 'last_login_ip' => request()->ip()])->saveQuietly();
            Audit::log('login', $event->user, array_filter(['remember' => $event->remember ?: null, 'route' => request()->route()?->getName()]), $event->user);
        });
        Event::listen(function (Failed $event) {
            $email = strtolower(trim((string) ($event->credentials['email'] ?? '')));
            Audit::log('login_failed', $event->user instanceof User ? $event->user : null, [], null, $email ?: null);
        });
        Event::listen(function (Logout $event) {
            if ($event->user instanceof User && ! (request()->hasSession() && session()->has('impersonator_id')) && ! request()->routeIs('impersonate.stop', 'admin.players.impersonate')) {
                Audit::log('logout', $event->user, [], $event->user);
            }
        });
    }
}
