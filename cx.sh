#!/bin/bash
#================================================================
# LOCAL command-line assistant for the NikniQ Games portal — cx.sh
#
#   ./cx.sh                 # interactive menu
#   ./cx.sh 20              # run an option directly
#   ./cx.sh 21 GamesSeeder  # ...with a second parameter
#
# ⚠ Runs on your LOCAL machine only — never copy this to the server.
#   Its counterpart on the server is deploy.sh (pull + migrate + caches).
#
# Requires a vars.sh next to it defining REMOTE, PROJECT, APP_SSH, etc.
# See vars.sh.example. vars.sh is gitignored because it holds host and
# account details.
#
# Options
# - deploy, what's-live, migrations, caches, composer (20-26)
# - health, logs, artisan, config, disk (30-35)
# - games, players, seeders, SSO housekeeping (40-44)
# - storage link, maintenance mode, log truncation (70-73)
# - ssh, top, backup (00, 05, 91)
#================================================================

if [ ! -f ./vars.sh ]; then
  echo "vars.sh not found next to cx.sh — copy vars.sh.example to vars.sh and fill it in."
  exit 1
fi
source ./vars.sh

REMOTE_APP="${REMOTE_APP:-www/$PROJECT}"
APP_URL="${APP_URL:-https://$PROJECT}"
LOG_OUTPUT="${LOG_OUTPUT:-./log/remote.out}"
APP_SSH="${APP_SSH:-ssh}"
APP_SCP="${APP_SCP:-scp}"

# Run on the server it would try to ssh to itself.
if [ "$(hostname -s 2>/dev/null)" = "${REMOTE_HOST%%.*}" ]; then
  echo "⚠ This looks like $REMOTE_HOST — cx.sh is the LOCAL script. Use deploy.sh here."
  exit 1
fi

#---------------------------------------------------------------
# Helpers
#---------------------------------------------------------------

log_history() {
  mkdir -p ./log
  touch ./log/history.log
  printf '%s  %s\n' "$(date '+%Y-%m-%d %H:%M')" "$1" > tmpfile
  cat ./log/history.log >> tmpfile && mv tmpfile ./log/history.log
}

# Run a command on the server, in the app directory.
remote() {
  $APP_SSH "$REMOTE" "cd ~/$REMOTE_APP && $*"
}

# Same, with a terminal attached — for anything interactive or long-running.
remote_tty() {
  $APP_SSH -t "$REMOTE" "cd ~/$REMOTE_APP && $*"
}

# Run a PHP snippet through tinker on the server. Pass plain PHP; quoting is handled here.
remote_tinker() {
  local code
  code=$(printf '%s' "$1" | sed 's/"/\\"/g; s/\$/\\$/g')
  remote "php artisan tinker --execute=\"$code\""
}

need_param() {
  if [ -z "$PARAM2" ]; then
    read -r -p "$1: " PARAM2
  fi
}

banner() {
  echo ===========================================================
  echo "$1"
  echo ===========================================================
}

# Gate for anything that drops the site or is hard to undo.
confirm() {
  echo "⚠ $1"
  read -r -p "Type 'yes' to continue: " reply
  [ "$reply" = "yes" ] || { echo "Skipped $SELECTION."; exit 0; }
}

echo =============================================================
echo "Hi $USER@$HOSTNAME — acting on $PROJECT ($REMOTE)"
echo What do you want to do?
echo -------------------------------------------------------------
echo "ROUTINE — day to day"
echo "20 : DEPLOY: pull + migrate + caches on $PROJECT"
echo "21 : DEPLOY + re-run one seeder class (e.g. GamesSeeder)"
echo "22 : WHAT'S LIVE: compare the server's HEAD with local"
echo "23 : MIGRATIONS: status (what is pending on the server)"
echo "24 : CACHES: refresh config/routes/views"
echo "25 : CACHES: clear everything (when a cached value looks stale)"
echo "26 : COMPOSER: install production dependencies"
echo ----------------------------------------------
echo DIAGNOSE
echo "30 : HEALTH: curl $APP_URL and key routes"
echo "31 : LOGS: tail the Laravel log"
echo "32 : LOGS: last errors with stack traces"
echo "33 : ARTISAN: run a command (e.g. 'migrate:status')"
echo "34 : CONFIG: portal settings on the server"
echo "35 : DISK: what is using space in the app"
echo ----------------------------------------------
echo "GAMES & PLAYERS"
echo "40 : SEED: run one seeder class (no pull, no migrate)"
echo "41 : GAMES: list games with client id, URL and status"
echo "42 : STATS: players, events and favourites per game"
echo "43 : ADMIN: promote a user to admin by email"
echo "44 : SSO: purge expired codes and tokens"
echo ----------------------------------------------
echo MAINTENANCE
echo "70 : STORAGE LINK: ensure public_html/storage symlink exists"
echo "71 : MAINTENANCE MODE: on  [site goes down]"
echo "72 : MAINTENANCE MODE: off"
echo "73 : LOGS: truncate the Laravel log  [discards log history]"
echo ----------------------------------------------
echo ACCESS
echo "00 : CONNECT: ssh to $REMOTE"
echo "05 : MONITOR: remote top"
echo "91 : BACKUP: dump the remote database to ./backups"
echo "qq : Exit [Quit]"
echo Enter [Selection] to continue
echo =============================================================

if [ -n "$1" ]; then
  SELECTION=$1
else
  read -n 2 SELECTION
fi
PARAM2="${2:-}"
echo
echo "Your selection is : $SELECTION."

case "$SELECTION" in

  #-------------------------------------------------------------
  # ROUTINE
  #-------------------------------------------------------------

  "20" )
  banner "DEPLOY: $PROJECT"
  # The server deploys by pulling from git, so anything not pushed is not deployed.
  if [ -n "$(git status --porcelain 2>/dev/null)" ]; then
    echo "⚠ Uncommitted local changes — these will NOT deploy:"
    git status --short
    echo
  fi
  UNPUSHED=$(git log --oneline @{u}.. 2>/dev/null)
  if [ -n "$UNPUSHED" ]; then
    echo "⚠ Commits not pushed — these will NOT deploy:"
    echo "$UNPUSHED"
    echo
    confirm "Deploy anyway?"
  fi
  echo "Local HEAD : $(git log --oneline -1)"
  echo
  remote_tty "./deploy.sh"
  log_history "Deployed $PROJECT"
  ;;

  "21" )
  banner "DEPLOY + SEEDER: $PROJECT"
  need_param "Seeder class (e.g. GamesSeeder)"
  remote_tty "./deploy.sh $PARAM2"
  log_history "Deployed $PROJECT with seeder $PARAM2"
  ;;

  "22" )
  banner "WHAT'S LIVE on $PROJECT"
  echo "--- server ---"
  remote "git log --oneline -3 && git status --short"
  echo
  echo "--- local ---"
  git log --oneline -3
  echo
  LOCAL_HEAD=$(git rev-parse HEAD 2>/dev/null)
  SERVER_HEAD=$(remote "git rev-parse HEAD" 2>/dev/null | tr -d '\r')
  if [ "$LOCAL_HEAD" = "$SERVER_HEAD" ]; then
    echo "✓ server matches local HEAD"
  else
    echo "⚠ server is on a different commit than local"
  fi
  ;;

  "23" )
  banner "MIGRATION STATUS on $PROJECT"
  remote "php artisan migrate:status"
  ;;

  "24" )
  banner "REFRESH CACHES on $PROJECT"
  remote "php artisan optimize"
  log_history "Refreshed caches on $PROJECT"
  ;;

  "25" )
  banner "CLEAR ALL CACHES on $PROJECT"
  remote "php artisan optimize:clear && php artisan cache:clear"
  log_history "Cleared all caches on $PROJECT"
  ;;

  "26" )
  banner "COMPOSER INSTALL on $PROJECT"
  remote_tty "composer install --no-dev --optimize-autoloader --no-interaction"
  log_history "Composer install on $PROJECT"
  ;;

  #-------------------------------------------------------------
  # DIAGNOSE
  #-------------------------------------------------------------

  "30" )
  banner "HEALTH: $APP_URL"
  for path in / /up /login /register /api/games /dashboard; do
    code=$(curl -s -o /dev/null -w '%{http_code}' --max-time 15 "$APP_URL$path")
    printf '  %-12s %s\n' "$path" "$code"
  done
  echo "  (dashboard 302 is expected: it redirects guests to /login)"
  ;;

  "31" )
  banner "TAIL LARAVEL LOG on $PROJECT"
  remote_tty "tail -f storage/logs/laravel.log"
  ;;

  "32" )
  banner "LAST ERRORS on $PROJECT"
  remote "grep -n 'ERROR\|CRITICAL\|Exception' storage/logs/laravel.log | tail -20"
  ;;

  "33" )
  banner "ARTISAN on $PROJECT"
  need_param "Artisan command (e.g. migrate:status)"
  remote_tty "php artisan $PARAM2"
  log_history "Artisan '$PARAM2' on $PROJECT"
  ;;

  "34" )
  banner "PORTAL CONFIG on $PROJECT"
  remote "grep -E '^(APP_ENV|APP_DEBUG|APP_URL|APP_NAME|PORTAL_|SSO_|SESSION_DRIVER|DB_CONNECTION|DB_HOST|DB_DATABASE|MAIL_MAILER)' .env"
  ;;

  "35" )
  banner "DISK USAGE on $PROJECT"
  remote "du -sh storage/app/public storage/logs vendor public_html 2>/dev/null; df -h . | tail -1"
  ;;

  #-------------------------------------------------------------
  # GAMES & PLAYERS
  #-------------------------------------------------------------

  "40" )
  banner "SEED on $PROJECT"
  need_param "Seeder class (e.g. GamesSeeder)"
  remote_tty "php artisan db:seed --class=$PARAM2 --force"
  log_history "Seeded $PARAM2 on $PROJECT"
  ;;

  "41" )
  banner "GAMES on $PROJECT"
  remote_tinker "App\Models\Game::orderBy('sort_order')->orderBy('name')->get()->each(fn(\$g) => print(str_pad(\$g->slug, 18).str_pad(\$g->is_enabled ? 'on ' : 'off', 5).str_pad(\$g->is_public ? 'public' : 'secret', 8).str_pad(\$g->client_id, 34).(\$g->url ?? '-').PHP_EOL));"
  ;;

  "42" )
  banner "STATS on $PROJECT"
  remote_tinker "print('users: '.App\Models\User::count().'  events: '.App\Models\GameEvent::count().'  events today: '.App\Models\GameEvent::where('occurred_at','>=',now()->startOfDay())->count().PHP_EOL.PHP_EOL); App\Models\Game::withCount(['stats','events','favouritedBy'])->orderByDesc('events_count')->get()->each(fn(\$g) => print(str_pad(\$g->slug, 18).'players='.str_pad(\$g->stats_count, 6).'events='.str_pad(\$g->events_count, 8).'favourites='.\$g->favourited_by_count.PHP_EOL));"
  ;;

  "43" )
  banner "PROMOTE ADMIN on $PROJECT"
  need_param "User email"
  remote_tinker "\$n = App\Models\User::where('email', '$PARAM2')->update(['is_admin' => true]); print(\$n ? 'promoted' : 'no such user'); print(PHP_EOL);"
  log_history "Promoted $PARAM2 to admin on $PROJECT"
  ;;

  "44" )
  banner "PURGE EXPIRED SSO ROWS on $PROJECT"
  remote_tinker "\$c = App\Models\SsoCode::where('expires_at','<',now()->subDay())->delete(); \$t = App\Models\PlayerToken::where(fn(\$q) => \$q->where('expires_at','<',now()->subDays(7))->orWhere('revoked_at','<',now()->subDays(7)))->delete(); print(\"codes: \$c  tokens: \$t\".PHP_EOL);"
  log_history "Purged expired SSO rows on $PROJECT"
  ;;

  #-------------------------------------------------------------
  # MAINTENANCE
  #-------------------------------------------------------------

  "70" )
  banner "STORAGE LINK on $PROJECT"
  remote "[ -e public_html/storage ] && echo 'link exists' || php artisan storage:link"
  ;;

  "71" )
  banner "MAINTENANCE MODE ON: $PROJECT"
  confirm "This takes $APP_URL down for visitors."
  remote "php artisan down"
  log_history "Maintenance mode ON for $PROJECT"
  ;;

  "72" )
  banner "MAINTENANCE MODE OFF: $PROJECT"
  remote "php artisan up"
  log_history "Maintenance mode OFF for $PROJECT"
  ;;

  "73" )
  banner "TRUNCATE LARAVEL LOG on $PROJECT"
  confirm "This discards the whole Laravel log on the server."
  remote ": > storage/logs/laravel.log && echo truncated"
  log_history "Truncated Laravel log on $PROJECT"
  ;;

  #-------------------------------------------------------------
  # ACCESS
  #-------------------------------------------------------------

  "00" )
  banner "CONNECT: $REMOTE"
  $APP_SSH -t "$REMOTE" "cd ~/$REMOTE_APP && exec \$SHELL -l"
  ;;

  "05" )
  banner "TOP on $REMOTE"
  $APP_SSH -t "$REMOTE" "top"
  ;;

  "91" )
  banner "BACKUP DATABASE from $PROJECT"
  mkdir -p ./backups
  STAMP=$(date '+%Y%m%d-%H%M')
  OUT="./backups/${PROJECT}-${STAMP}.sql.gz"
  # Credentials are read from the server's .env so none live in vars.sh.
  remote "set -a; . ./.env; set +a; if [ \"\$DB_CONNECTION\" = sqlite ]; then gzip -c \"\${DB_DATABASE:-database/database.sqlite}\"; else mysqldump -h \"\$DB_HOST\" -P \"\${DB_PORT:-3306}\" -u \"\$DB_USERNAME\" -p\"\$DB_PASSWORD\" \"\$DB_DATABASE\" | gzip; fi" > "$OUT"
  ls -lh "$OUT"
  log_history "Database backup to $OUT"
  ;;

  "qq" )
  echo "Bye."
  ;;

  * )
  echo "Unknown selection: $SELECTION"
  exit 1
  ;;
esac
