# NikniQ Games portal (nq-website)

The central games portal that replaces the games side of nikniq.com. It is a Laravel 13 app whose web root is `public_html/` (shared-hosting convention), not `public/`.

What it does:

- **Directs players to games.** The homepage is a searchable catalogue of games hosted on the `*.nikniq.com` subdomains. *Play* records a `launch` event for signed-in players and redirects to the game.
- **Tracks favourites.** Signed-in players star games; favourites float to the top of the catalogue and sit on the dashboard. Games can also set the flag through the API.
- **Aggregates scores and activity.** Games sign players in through the portal (OAuth-style authorization code flow, with PKCE for browser-only games) and report events. The portal stores every event and keeps per-player, per-game aggregates (plays, wins, best/total score, time played, last level), builds leaderboards, and shows each player everything on `/dashboard`.

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed       # creates demo@example.com / password (admin) and the starter catalogue
php artisan serve                # serves public_html/
php artisan test
```

The default `.env` uses sqlite (`database/database.sqlite`). For MySQL set the `DB_*` variables and re-run the migration. `SESSION_DRIVER=database` is the default, so the sessions table must exist (it is created by the migrations).

Thumbnails live in `public_html/images/games/{slug}.svg` (one tile per seeded game). To replace one with a screenshot, drop the file in that folder and set the thumbnail path in the admin form. The seeder refreshes catalogue fields (name, description, genre, URL, thumbnail, order) on every run but never touches credentials or the enabled/featured flags.

### Sign-up and email

Sign-up asks for an email only. The portal creates a password-less account, emails a signed link (valid for `VERIFY_TTL_MINUTES`, default 60), and the link confirms the address, logs the player in and asks them to choose a display name and password. Unfinished accounts cannot log in with the form; the login page offers to resend the link. `/forgot-password` emails a reset link (standard Laravel password broker, 60 minutes); for an unfinished account it re-sends the confirmation link instead, and the response never reveals whether an address exists. Set a real mailer in `.env` for production:

```
MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_SCHEME=tls
MAIL_FROM_ADDRESS=hello@nikniq.com
```

With `MAIL_MAILER=log` (the default) the link is written to `storage/logs/laravel.log` instead of being sent.

To test delivery, `./cx.sh` reports the mailer, sender and `APP_URL` problems, and `./cx.sh --mail you@example.com` sends a plain test message plus a rendered sign-up email through the configured mailer, printing the SMTP error if the server rejects it.

### Themes and accessibility

The portal has dark and light themes. It follows the device's colour-scheme setting until the player uses the sun/moon toggle in the header, which is remembered in the browser. Colours are CSS variables at the top of `resources/views/layouts/app.blade.php`. The layout also provides a skip link, visible keyboard focus, reduced motion when the device asks for it, and in-place favourite toggles (no page reload).

### Dementia-friendly flag

Games can be marked *Dementia-friendly* (calm pace, simple rules, no time pressure) in the admin form or in `GamesSeeder::GAMES` (sixth element). The flag shows as a badge on cards and game pages and as a catalogue filter (`?dementia=1`). The seeder owns the flag along with the other catalogue fields, so edit the seeder list for permanent changes.

### Google sign-in

Create an OAuth client in Google Cloud with the authorised redirect URI `https://nikniq.com/auth/google/callback`, then set in `.env`:

```
GOOGLE_OAUTH_ENABLED=true
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
```

Google sign-up requires a verified Google email. A Google email only takes over an existing password account when Google reports it verified; otherwise the player logs in with their password and links Google from `/profile`. Accounts created through Google have no password until they set one on `/profile`, and unlinking requires a password so nobody gets locked out.

### SEO and sharing

Every page sets a title, meta description, canonical URL and Open Graph/Twitter tags (game pages use the game's thumbnail; other pages use `public_html/images/og-default.png`). Game pages carry VideoGame and BreadcrumbList JSON-LD; the catalogue carries WebSite (with SearchAction) and ItemList. `public_html/manifest.webmanifest` makes the portal installable.

### Google Analytics

`GA_MEASUREMENT_ID` (default `G-34RWEG01MV`) renders the gtag snippet in every layout, in production only. Set `GA_ALWAYS=true` to force it elsewhere, or set the id empty to disable.

## Pages

| Route | Purpose |
| --- | --- |
| `/` | Catalogue with search and filters (`?q=`, `genre=`, `featured=1`, `dementia=1`, `favourites=1`, `sort=popular\|name\|newest`); returns JSON with `Accept: application/json` |
| `/games/{slug}` | Game page: description, player's stats and rank, top 10 |
| `/games/{slug}/play` | Logs a launch and redirects to the game's URL |
| `/games/{slug}/leaderboard` | Full leaderboard |
| `/dashboard` | Totals across all games, favourites, per-game stats, recent activity |
| `/profile` | Name/email, password, and the list of games currently signed in (sign out of one or all) |
| `/admin/games` | Admin CRUD for games and their SSO credentials (`users.is_admin`) |
| `/sitemap.xml` | Generated sitemap: catalogue, sign-up, every enabled game and leaderboard, with tile images. Cached 30 minutes, dropped when a game changes |

Unknown HTML pages (old nikniq.com URLs, typos, unknown game slugs) redirect to the catalogue with a note. API calls, paths that look like files, and non-GET requests still return 404.

## Integrating a game

1. **Register the game** at `/admin/games`. Add the exact callback URL the game will use as a redirect URI. Tick *Public client* for a static JS game with no server (it then uses PKCE instead of a secret). For a server-side game, copy the secret when it is shown; it can only be rotated afterwards.

2. **Send the player to the portal** when they press "Sign in":

   ```
   https://nikniq.com/sso/authorize
       ?client_id=CLIENT_ID
       &redirect_uri=https://chopper.nikniq.com/auth/callback
       &state=RANDOM
       &code_challenge=BASE64URL(SHA256(verifier))   # required for public clients
       &code_challenge_method=S256
   ```

   The portal logs the player in if needed and redirects back to `redirect_uri?code=...&state=...` within 5 minutes. On failure it sends `error` and `error_description`, or shows an error page if the redirect URI is not registered.

3. **Exchange the code** for a player token:

   ```bash
   curl -X POST https://nikniq.com/api/sso/token \
     -H 'Content-Type: application/json' \
     -d '{"grant_type":"authorization_code","client_id":"CLIENT_ID","client_secret":"SECRET",
          "code":"CODE","redirect_uri":"https://chopper.nikniq.com/auth/callback"}'
   ```

   Public clients send `code_verifier` instead of `client_secret`. Response: `{ token_type, access_token, expires_at, player: {id,name,email,avatar}, stats }`. Tokens last 30 days.

4. **Report activity** as the player plays:

   ```bash
   curl -X POST https://nikniq.com/api/games/chopper/events \
     -H 'Authorization: Bearer TOKEN' -H 'Content-Type: application/json' \
     -d '{"event":"game_over","score":1240,"won":false,"duration_seconds":87,"level":"cave-3","metadata":{"deaths":2}}'
   ```

   Fields: `event` (required, `[a-z0-9_.-]`), `score`, `level`, `duration_seconds`, `won`, `metadata` (object, up to 50 keys), `occurred_at`. Send `{"events":[...]}` for a batch of up to 50. Events named `game_over`, `finish`, `complete`, `round_end` or `session_end` count as one play. The response carries the updated stats and rank.

5. **Other endpoints** (all with the bearer token unless noted):

   | Endpoint | Purpose |
   | --- | --- |
   | `GET /api/sso/me` | Player, their stats and rank in this game, whether it is a favourite |
   | `POST` / `DELETE /api/sso/favourite` | Add or remove this game from the player's favourites |
   | `POST /api/sso/logout` | Revoke the token |
   | `GET /api/games` | Public catalogue (`?q=`, `genre=`, `featured=1`, `dementia=1`, `sort=`); includes the list of genres |
   | `GET /api/games/{slug}/leaderboard?limit=25` | Public leaderboard |

   CORS allows any origin on `/api/*`, so browser-only games call these directly. All SSO endpoints are rate limited.

Minimal browser flow for a public client:

```js
const verifier = base64url(crypto.getRandomValues(new Uint8Array(32)));
sessionStorage.setItem('pkce', verifier);
const challenge = base64url(await crypto.subtle.digest('SHA-256', new TextEncoder().encode(verifier)));
location.href = `https://nikniq.com/sso/authorize?client_id=${CLIENT_ID}&redirect_uri=${encodeURIComponent(CALLBACK)}&state=${state}&code_challenge=${challenge}&code_challenge_method=S256`;

// on the callback page
const code = new URLSearchParams(location.search).get('code');
const res = await fetch('https://nikniq.com/api/sso/token', {method:'POST', headers:{'Content-Type':'application/json'},
  body: JSON.stringify({grant_type:'authorization_code', client_id: CLIENT_ID, code, redirect_uri: CALLBACK, code_verifier: sessionStorage.getItem('pkce')})});
const { access_token, player } = await res.json();
```

## Deployment

Two scripts, both run on the server from the project root:

| Script | Purpose |
| --- | --- |
| `deploy.sh` | pull, `composer install` if the lockfile moved, migrate, rebuild caches (optionally run a seeder: `./deploy.sh GamesSeeder` or `--seed`) |
| `cx.sh` | setup checker: PHP and extensions, Composer and vendor/, `.env` and `APP_KEY`, `APP_DEBUG`, database connection, pending migrations, empty catalogue, admin user, mail configuration, storage permissions and link, stale config cache. `./cx.sh --fix` applies the corrections (migrate, seed, key, chmod, caches); `./cx.sh --mail you@example.com` sends a test email. |

`cx.sh` needs no settings file: it reads the database and app settings from the app's own config through `php artisan tinker`.

```bash
git push             # the server deploys by pulling, so unpushed commits never deploy
./deploy.sh          # on the server
./cx.sh --fix        # on the server, when something looks wrong
```

Point the web server (or the hosting account's document root) at `public_html/`. The app has no front-end build step; views use inline CSS.

## Layout

```
app/Http/Controllers/          GamesController, FavouriteController, DashboardController, ProfileController
app/Http/Controllers/Auth/     login and registration
app/Http/Controllers/Sso/      /sso/authorize
app/Http/Controllers/Api/      token exchange, events, leaderboard, catalogue, favourite
app/Http/Controllers/Admin/    game CRUD
app/Http/Middleware/           AuthenticatePlayer (bearer token), EnsureUserIsAdmin
app/Services/GameStatsService  records events and maintains aggregates, leaderboards, ranks
app/Models/                    Game, Favourite, SsoCode, PlayerToken, GameEvent, PlayerGameStat, User
config/portal.php              name, token lifetimes, play events, limits
database/seeders/GamesSeeder   starter catalogue (the nq-* games)
public_html/                   web root
```
# nq-website
