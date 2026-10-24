# nq-website

Laravel 13 games portal for nikniq.com: catalogue of games hosted on subdomains, favourites, and a central store of every player's scores and activity reported by the games over an OAuth-style SSO API. Replaces the games side of the older `nikniq.com` repo.

## Commands

```bash
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed          # demo@example.com / password (admin) + starter catalogue
php artisan serve                   # web root is public_html, not public
php artisan test                    # in-memory sqlite via phpunit.xml
./vendor/bin/pint                   # code style
```

Deployment: `deploy.sh` runs on the server (pull, composer if lockfile moved, migrate, optional seeder, optimize). `cx.sh [--fix | --mail addr]` is a server-side setup checker that reads config through tinker; it needs no settings file. `--mail` sends a test message and a rendered sign-up email.

## Layout

- Web root is `public_html/` (`bootstrap/app.php` calls `usePublicPath`; Vite's `publicDirectory` is set too, but views use inline CSS and need no build).
- `routes/web.php`: catalogue, auth, dashboard, profile, `/sso/authorize` (needs `auth`), admin under `/admin` with `admin.` names.
- `routes/api.php`: `/api/sso/token` (client-authenticated), then `player`-middleware routes (`/api/sso/me|logout|favourite`, `/api/games/{slug}/events`), plus public `/api/games` and `/api/games/{slug}/leaderboard`.
- `app/Services/GameStatsService.php` is the only writer of `game_events` and `player_game_stats`; call `record()` rather than touching those tables.
- `config/portal.php` holds the name, token/code lifetimes, `play_events`, leaderboard and batch limits.

## Domain notes

- A `Game` row is both the catalogue entry and the SSO client (`client_id`, hashed `client_secret`, `redirect_uris` allow-list, `is_public` for PKCE-only clients). Route binding is by `slug`.
- `SsoCode` is one-time (`used_at`), 5 minutes. `PlayerToken` is a sha256-hashed bearer token, 30 days, revocable from `/profile` or `POST /api/sso/logout`. The `player` middleware sets `player`, `game` and `playerToken` request attributes.
- The events endpoint rejects a token issued for a different game (403). Batches are capped at `portal.max_batch`.
- `GET /games/{slug}/play` records a `launch` event for signed-in users before redirecting; `launch` is not in `play_events` so it does not count as a play.
- Catalogue filters live in `Game::scopeFilter` (genre, featured, dementia, sort) and are shared by `GamesController@index` and the API catalogue; `Game::genres()` feeds the chips. `games.is_dementia` is seeder-owned (sixth element of `GamesSeeder::GAMES`).
- Favourites are a `user_id`/`game_id` pivot (`favourites`); toggled on the web via `POST /games/{slug}/favourite` and from games via `POST|DELETE /api/sso/favourite`.
- Sign-up is email-first (`Auth\RegisterController`): create password-less user, mail `App\Mail\VerifyEmail` (temporary signed route `register.verify`), the link logs in and sends to `register.password`. Users with `password = null` and no provider cannot log in; the login error offers a resend. The layout nags signed-in password-less users.
- Password reset is `Auth\PasswordResetController` on Laravel's password broker (`password.*` routes); unfinished sign-ups get `VerifyEmail` again instead.
- SEO: the layout reads `title`, `description`, `image`, `og_type` and `jsonld` sections; set them per view. Mobile (≤640px) uses a compact two-column card grid defined in the layout CSS.
- Google sign-in lives in `Auth\SocialLoginController` (Socialite), enabled by `GOOGLE_OAUTH_ENABLED`. OAuth-only users have `password = null`; `User::hasPassword()` gates the login form, unlinking, and the current-password check. Tests mock `Socialite::shouldReceive('driver')` (see `tests/Feature/GoogleLoginTest.php`).
- Google Analytics: `portal.ga_measurement_id`, rendered by the layout in production only (`portal.ga_always` forces it).
- Seeded catalogue fields are refreshed on every `GamesSeeder` run; credentials and flags are insert-only. Hostnames in `GamesSeeder::GAMES` were verified live (e.g. `spaceinvaders`, `missilecommand`, `candyrush`, `lawyermemory`).
- `/sitemap.xml` is `SitemapController`, cached under `sitemap.xml`; the `Game` model forgets it on save/delete. `bootstrap/app.php` turns `NotFoundHttpException` on HTML GETs without a file extension into a redirect to `games.index`; API, assets and non-GET keep a 404.
- Admin is the `users.is_admin` flag (`EnsureUserIsAdmin`, alias `admin`). The client secret is shown once after create/rotate via the `client_secret` flash.

## Conventions

- Public or unauthenticated POST endpoints get `throttle` middleware in the route file.
- Form validation for admin lives in `app/Http/Requests/GameRequest.php`; textarea/CSV fields are normalised in `gameAttributes()`.
- Migrations are additive; never edit a shipped migration.
- Seeders are idempotent (`firstOrCreate` by slug) so `--seed` on the server never clobbers admin edits.
