<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Admin account maintenance, used by ./cx.sh --admin ...
 *
 *   php artisan portal:admin list
 *   php artisan portal:admin grant  user@example.com
 *   php artisan portal:admin revoke user@example.com
 *   php artisan portal:admin create user@example.com [--name="Ann"]
 *   php artisan portal:admin password user@example.com
 */
class PortalAdmin extends Command
{
    protected $signature = 'portal:admin {action : list|grant|revoke|create|password} {email?} {--name= : Display name for create}';

    protected $description = 'List, grant, revoke or create portal admins, or reset a user\'s password';

    public function handle(): int
    {
        $action = $this->argument('action');
        if ($action === 'list') {
            return $this->list();
        }
        if (! in_array($action, ['grant', 'revoke', 'create', 'password'], true)) {
            $this->error("Unknown action '{$action}'. Use list, grant, revoke, create or password.");

            return self::INVALID;
        }

        $email = Str::lower(trim((string) $this->argument('email')));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('A valid email address is required.');

            return self::INVALID;
        }
        $user = User::where('email', $email)->first();

        if ($action === 'create') {
            if ($user) {
                $this->error("{$email} already exists. Use grant to make it an admin.");

                return self::FAILURE;
            }
            $password = Str::password(16, symbols: false);
            User::forceCreate([
                'name' => $this->option('name') ?: Str::before($email, '@'),
                'email' => $email,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'is_admin' => true,
            ]);
            $this->info("Created admin {$email}");
            $this->line("Temporary password: {$password}");
            $this->line('Log in at '.route('login').' and change it from your profile.');

            return self::SUCCESS;
        }

        if (! $user) {
            $this->error("No user with email {$email}. Sign up first, or use create.");

            return self::FAILURE;
        }

        if ($action === 'password') {
            $password = Str::password(16, symbols: false);
            $user->forceFill(['password' => Hash::make($password)])->save();
            $this->info("New password for {$email}: {$password}");

            return self::SUCCESS;
        }

        if ($action === 'revoke' && $user->is_admin && User::where('is_admin', true)->count() === 1) {
            $this->error("{$email} is the only admin; grant another account first.");

            return self::FAILURE;
        }
        $user->forceFill(['is_admin' => $action === 'grant'])->save();
        $this->info($action === 'grant' ? "{$email} is now an admin." : "{$email} is no longer an admin.");

        return self::SUCCESS;
    }

    private function list(): int
    {
        $admins = User::where('is_admin', true)->orderBy('email')->get(['email', 'name', 'password', 'created_at']);
        if ($admins->isEmpty()) {
            $this->warn('No admins.');

            return self::SUCCESS;
        }
        $this->table(['Email', 'Name', 'Password set', 'Created'], $admins->map(fn ($u) => [
            $u->email, $u->name, $u->password ? 'yes' : 'no', $u->created_at?->toDateString(),
        ]));
        if ($admins->contains('email', 'demo@example.com')) {
            $this->warn('demo@example.com is an admin with a publicly known password: revoke it or reset its password.');
        }

        return self::SUCCESS;
    }
}
