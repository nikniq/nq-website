#!/usr/bin/env bash
# cx.sh - setup checks and corrections for the NikniQ Games portal.
#
# Runs on whichever machine hosts the app (server or local), from the
# project root. Needs no settings file: database details come from the
# app's own config via `php artisan tinker`, so .env and any cached
# config are honoured.
#
# Usage:
#   ./cx.sh          report problems only
#   ./cx.sh --fix    report and apply corrections
#
# Checks: PHP binary, version and extensions, Composer, vendor/ and its
# platform check, .env and APP_KEY (generated if missing), APP_DEBUG in
# production, database connection, pending migrations, games catalogue
# (seeded if empty), admin user, storage link, storage permissions,
# config cache freshness.

set -u

cd "$(dirname "$0")"

FIX=0
[ "${1:-}" = "--fix" ] && FIX=1

PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"

RED=$'\033[31m'; GREEN=$'\033[32m'; YELLOW=$'\033[33m'; RESET=$'\033[0m'
problems=0

ok()    { echo "${GREEN}[ok]${RESET}   $*"; }
warn()  { echo "${YELLOW}[warn]${RESET} $*"; }
fail()  { echo "${RED}[fail]${RESET} $*"; problems=$((problems + 1)); }
fixed() { echo "${GREEN}[fixed]${RESET} $*"; }

# Evaluate PHP inside the booted app and print its output. Tinker is the
# supported way to read config() and the models without duplicating the
# .env parsing here.
tinker() {
    "$PHP_BIN" artisan tinker --no-interaction --execute="$1" 2>/dev/null | tr -d '\r'
}

# --- PHP ------------------------------------------------------------------
if ! command -v "$PHP_BIN" >/dev/null 2>&1; then
    fail "PHP not found (PHP_BIN=$PHP_BIN). Install PHP 8.3+ or set PHP_BIN."
    exit 1
fi
PHP_VERSION=$("$PHP_BIN" -r 'echo PHP_VERSION;')
PHP_VERSION_ID=$("$PHP_BIN" -r 'echo PHP_VERSION_ID;')
ok "PHP $PHP_VERSION at $(command -v "$PHP_BIN")"

REQUIRED_ID=80300
if [ -f vendor/composer/platform_check.php ]; then
    found=$(grep -oE 'PHP_VERSION_ID >= [0-9]+' vendor/composer/platform_check.php | grep -oE '[0-9]+$' || true)
    [ -n "$found" ] && REQUIRED_ID=$found
fi
if [ "$PHP_VERSION_ID" -lt "$REQUIRED_ID" ]; then
    fail "PHP $PHP_VERSION is below the version required by vendor/ (ID $REQUIRED_ID)."
    echo "       The web server may use a different PHP than the CLI. Check phpinfo() in the browser."
fi

for ext in pdo_mysql pdo_sqlite mbstring openssl tokenizer xml ctype json fileinfo curl; do
    if "$PHP_BIN" -m | grep -qi "^$ext$"; then
        ok "ext: $ext"
    else
        fail "PHP extension missing: $ext"
    fi
done

# --- Composer -------------------------------------------------------------
if command -v "$COMPOSER_BIN" >/dev/null 2>&1; then
    ok "Composer $("$COMPOSER_BIN" --version 2>/dev/null | grep -oE 'version [0-9.]+' | cut -d' ' -f2)"
else
    warn "Composer not found (COMPOSER_BIN=$COMPOSER_BIN); vendor/ cannot be (re)installed from here."
fi

if [ ! -d vendor ] || [ ! -f vendor/autoload.php ]; then
    fail "vendor/ missing. Run: $COMPOSER_BIN install --no-dev"
    if [ "$FIX" -eq 1 ] && command -v "$COMPOSER_BIN" >/dev/null 2>&1; then
        "$COMPOSER_BIN" install --no-interaction --prefer-dist --no-dev --optimize-autoloader && fixed "composer install"
    fi
else
    if "$PHP_BIN" -r 'require "vendor/composer/platform_check.php";' >/dev/null 2>&1; then
        ok "vendor/ platform check passes"
    else
        fail "vendor/ platform check fails for PHP $PHP_VERSION"
        if [ "$FIX" -eq 1 ] && command -v "$COMPOSER_BIN" >/dev/null 2>&1; then
            "$COMPOSER_BIN" install --no-interaction --prefer-dist --no-dev --optimize-autoloader && fixed "composer install"
        fi
    fi
fi

# --- .env -----------------------------------------------------------------
if [ ! -f .env ]; then
    fail ".env missing"
    if [ "$FIX" -eq 1 ]; then
        cp .env.example .env && fixed "copied .env.example to .env (now set DB_* and APP_URL)"
    fi
else
    ok ".env present"
fi

if [ -f .env ]; then
    if ! grep -qE '^APP_KEY=.+' .env; then
        if [ -f vendor/autoload.php ]; then
            if "$PHP_BIN" artisan key:generate --force --no-interaction >/dev/null; then
                fixed "APP_KEY generated"
            else
                fail "APP_KEY missing and key:generate failed"
            fi
        else
            fail "APP_KEY missing (install vendor/ first, then re-run)"
        fi
    else
        ok "APP_KEY set"
    fi
fi

# Everything below needs a bootable app.
if [ ! -f vendor/autoload.php ] || [ ! -f .env ]; then
    echo
    echo "${RED}Cannot boot the app yet; fix the problems above and re-run.${RESET}"
    exit 1
fi

# --- app config (through tinker) -------------------------------------------
app_info=$(tinker 'echo implode("|", [config("app.env"), config("app.debug") ? "true" : "false", config("app.url"), config("session.driver")]);')
if [ -z "$app_info" ]; then
    fail "The app does not boot (php artisan tinker failed). Run: $PHP_BIN artisan about"
    echo
    echo "${RED}$problems problem(s) found.${RESET}"
    exit 1
fi
IFS='|' read -r app_env app_debug app_url session_driver <<<"$app_info"
ok "APP_ENV=$app_env APP_URL=$app_url SESSION_DRIVER=$session_driver"

if [ "$app_env" = "production" ] && [ "$app_debug" = "true" ]; then
    fail "APP_DEBUG=true in production exposes stack traces, cookies and config to visitors"
    if [ "$FIX" -eq 1 ]; then
        sed -i.bak -E 's/^APP_DEBUG=.*/APP_DEBUG=false/' .env && rm -f .env.bak && fixed "APP_DEBUG=false in .env"
    fi
fi

# Config cache: if it exists but is older than .env, the app is running on stale values.
if [ -f bootstrap/cache/config.php ]; then
    if [ .env -nt bootstrap/cache/config.php ]; then
        fail "bootstrap/cache/config.php is older than .env: the app is using cached, stale settings"
        [ "$FIX" -eq 1 ] && "$PHP_BIN" artisan optimize:clear >/dev/null && "$PHP_BIN" artisan optimize >/dev/null && fixed "rebuilt caches"
    else
        ok "config cache is newer than .env"
    fi
else
    warn "no config cache (fine for development; run 'php artisan optimize' in production)"
fi

# --- database (credentials from the app's config, via tinker) --------------
db_info=$(tinker '$c = config("database.default"); $cfg = config("database.connections.$c"); echo implode("|", [$c, $cfg["driver"] ?? "", $cfg["host"] ?? "", $cfg["port"] ?? "", $cfg["database"] ?? "", $cfg["username"] ?? ""]);')
IFS='|' read -r db_conn db_driver db_host db_port db_name db_user <<<"$db_info"

if [ "$db_driver" = "sqlite" ]; then
    db_file=${db_name:-database/database.sqlite}
    if [ -f "$db_file" ]; then
        ok "SQLite database $db_file"
    else
        fail "SQLite database $db_file missing"
        [ "$FIX" -eq 1 ] && touch "$db_file" && fixed "created $db_file"
    fi
else
    ok "database: $db_driver $db_user@$db_host:$db_port/$db_name (connection '$db_conn')"
fi

db_ping=$(tinker 'try { DB::connection()->getPdo(); echo "up"; } catch (\Throwable $e) { echo "down: ".$e->getMessage(); }')
if [ "$db_ping" = "up" ]; then
    ok "database connection works"
else
    fail "database connection failed: ${db_ping#down: }"
    echo "       Check DB_* in .env, then run 'php artisan optimize:clear' if config is cached."
fi

if [ "$db_ping" = "up" ]; then
    # --- migrations ---------------------------------------------------------
    if ! tinker 'echo Schema::hasTable("migrations") ? "yes" : "no";' | grep -q yes; then
        fail "database is empty (no migrations table)"
        [ "$FIX" -eq 1 ] && "$PHP_BIN" artisan migrate --force --no-interaction && fixed "migrated"
    fi
    pending=$("$PHP_BIN" artisan migrate:status --pending --no-ansi 2>/dev/null | grep -c 'Pending' || true)
    if [ "$pending" -gt 0 ]; then
        fail "$pending migration(s) pending"
        [ "$FIX" -eq 1 ] && "$PHP_BIN" artisan migrate --force --no-interaction && fixed "migrated"
    else
        ok "migrations up to date"
    fi

    # --- catalogue ----------------------------------------------------------
    if tinker 'echo Schema::hasTable("games") ? "yes" : "no";' | grep -q yes; then
        games=$(tinker 'echo App\Models\Game::count()."|".App\Models\Game::where("is_enabled", true)->count();')
        IFS='|' read -r games_total games_enabled <<<"$games"
        if [ "${games_total:-0}" -eq 0 ]; then
            fail "no games in the catalogue (the homepage will be empty)"
            [ "$FIX" -eq 1 ] && "$PHP_BIN" artisan db:seed --class=GamesSeeder --force --no-interaction >/dev/null && fixed "seeded GamesSeeder ($(tinker 'echo App\Models\Game::count();') games)"
        elif [ "${games_enabled:-0}" -eq 0 ]; then
            fail "$games_total games exist but none is enabled"
        else
            ok "$games_enabled of $games_total games enabled"
        fi

        admins=$(tinker 'echo App\Models\User::where("is_admin", true)->count();')
        if [ "${admins:-0}" -eq 0 ]; then
            warn "no admin user. Promote one: php artisan tinker --execute=\"App\\Models\\User::where('email','you@example.com')->update(['is_admin'=>true]);\""
        else
            ok "$admins admin user(s)"
        fi
    fi

    if [ "$session_driver" = "database" ] && ! tinker 'echo Schema::hasTable("sessions") ? "yes" : "no";' | grep -q yes; then
        fail "SESSION_DRIVER=database but the sessions table is missing (run migrations)"
    fi
fi

# --- storage / cache ------------------------------------------------------
for dir in storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache; do
    if [ ! -d "$dir" ]; then
        fail "$dir missing"
        [ "$FIX" -eq 1 ] && mkdir -p "$dir" && fixed "created $dir"
    elif [ ! -w "$dir" ]; then
        fail "$dir not writable"
        [ "$FIX" -eq 1 ] && chmod -R u+rwX,g+rwX "$dir" && fixed "chmod $dir"
    else
        ok "$dir writable"
    fi
done

if [ -e public_html/storage ]; then
    ok "public_html/storage link exists"
else
    warn "public_html/storage link missing (only needed for uploaded files)"
    [ "$FIX" -eq 1 ] && "$PHP_BIN" artisan storage:link >/dev/null && fixed "storage:link"
fi

# --- summary --------------------------------------------------------------
echo
if [ "$problems" -eq 0 ]; then
    echo "${GREEN}All checks passed.${RESET}"
else
    echo "${RED}$problems problem(s) found.${RESET}"
    [ "$FIX" -eq 0 ] && echo "Re-run with --fix to apply corrections."
    exit 1
fi
