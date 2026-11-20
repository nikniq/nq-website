<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\GameEvent;
use App\Models\PlayerGameStat;
use App\Models\User;
use App\Services\BillingService;
use App\Services\GameStatsService;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PlayerController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $show = in_array($request->query('show'), ['deleted', 'admins'], true) ? $request->query('show') : 'active';

        $players = User::query()
            ->when($show === 'deleted', fn ($query) => $query->onlyTrashed())
            ->when($show === 'admins', fn ($query) => $query->where('is_admin', true))
            ->withCount(['favouriteGames', 'gameEvents'])
            ->withMax('gameEvents', 'occurred_at')
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")))
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        return view('admin.players.index', [
            'players' => $players, 'q' => $q, 'show' => $show,
            'counts' => ['active' => User::count(), 'admins' => User::where('is_admin', true)->count(), 'deleted' => User::onlyTrashed()->count()],
        ]);
    }

    /** Soft delete: suspend the account (restorable). */
    public function destroy(Request $request, int $id): RedirectResponse
    {
        $user = User::findOrFail($id);
        if ($error = $this->guard($request, $user)) {
            return back()->with('error', $error);
        }
        app(BillingService::class)->cancelAll($user);
        $user->suspend($request->input('reason'));
        Audit::log('player_suspended', $user, array_filter(['reason' => $request->input('reason')]));

        return back()->with('status', "{$user->email} was suspended. Restore it from Deleted, or delete it permanently there.");
    }

    public function restore(int $id): RedirectResponse
    {
        $user = User::onlyTrashed()->findOrFail($id);
        $user->restore();
        $user->forceFill(['deleted_reason' => null])->saveQuietly();
        Audit::log('player_restored', $user);

        return back()->with('status', "{$user->email} was restored. Revoked game sign-ins stay revoked; the player signs in again.");
    }

    /** Hard delete: remove the user and, through the foreign keys, all of their data. */
    public function purge(Request $request, int $id): RedirectResponse
    {
        $user = User::withTrashed()->findOrFail($id);
        if ($error = $this->guard($request, $user)) {
            return back()->with('error', $error);
        }
        if (strtolower(trim((string) $request->input('confirm_email'))) !== strtolower($user->email)) {
            return back()->with('error', "Type {$user->email} exactly to delete it permanently.");
        }
        $email = $user->email;
        app(BillingService::class)->cancelAll($user);
        $user->forceDelete();
        Audit::log('player_deleted', null, ['id' => $id], null, $email);

        return redirect()->route('admin.players.index', array_filter(['show' => $request->query('show')]))->with('status', "{$email} and all of its data were permanently deleted.");
    }

    public function toggleAdmin(Request $request, int $id): RedirectResponse
    {
        $user = User::findOrFail($id);
        if ($user->is_admin && $error = $this->guard($request, $user)) {
            return back()->with('error', $error);
        }
        $user->forceFill(['is_admin' => ! $user->is_admin])->save();
        Audit::log($user->is_admin ? 'admin_granted' : 'admin_revoked', $user);

        return back()->with('status', $user->is_admin ? "{$user->email} is now an admin." : "{$user->email} is no longer an admin.");
    }

    /** Never act on yourself, and never remove the last admin. */
    private function guard(Request $request, User $user): ?string
    {
        if ($user->is($request->user())) {
            return 'You cannot do that to your own account. Use Profile, or ask another admin.';
        }
        if ($user->is_admin && ! $user->trashed() && User::where('is_admin', true)->count() <= 1) {
            return 'That is the only admin. Make another account an admin first.';
        }

        return null;
    }

    /** Everything about one player: details form, their own view of the account, usage charts and log. */
    public function show(Request $request, int $id, GameStatsService $stats): View
    {
        $user = User::withTrashed()->findOrFail($id);
        $days = in_array((int) $request->query('days'), [7, 30, 90], true) ? (int) $request->query('days') : 30;

        $gameStats = PlayerGameStat::with('game:id,name,slug')->where('user_id', $user->id)->orderByDesc('last_played_at')->get()->filter(fn ($s) => $s->game);

        return view('admin.players.show', [
            'player' => $user,
            'days' => $days,
            'summary' => $stats->summaryFor($user),
            'gameStats' => $gameStats,
            'ranks' => $stats->ranksFor($user),
            'favourites' => $user->favouriteGames()->orderBy('name')->get(['games.id', 'games.name', 'games.slug']),
            'daily' => $this->dailyFor($user, $days),
            'events' => GameEvent::with('game:id,name,slug')->where('user_id', $user->id)->orderByDesc('occurred_at')->limit(25)->get(),
            'tokens' => $user->playerTokens()->with('game:id,name')->whereNull('revoked_at')->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->latest()->get(),
            'logs' => AuditLog::with('actor:id,name')->where('subject_id', $user->id)->latest('created_at')->limit(30)->get(),
            'subscription' => $user->currentSubscription(),
            'canImpersonate' => ! $user->trashed() && ! $user->is_admin && ! $user->is($request->user()),
        ]);
    }

    /** Per-day plays, minutes and launches for one player, zero-filled. */
    private function dailyFor(User $user, int $days): Collection
    {
        $from = now()->subDays($days - 1)->startOfDay();
        $plays = config('portal.play_events');
        $rows = DB::table('game_events')->where('user_id', $user->id)->where('occurred_at', '>=', $from)
            ->selectRaw('DATE(occurred_at) as d')
            ->selectRaw('SUM(CASE WHEN event IN ('.implode(',', array_fill(0, count($plays), '?')).') THEN 1 ELSE 0 END) as plays', $plays)
            ->selectRaw("SUM(CASE WHEN event = 'launch' THEN 1 ELSE 0 END) as launches")
            ->selectRaw('COALESCE(SUM(duration_seconds),0) as seconds')
            ->groupBy('d')->get()->keyBy('d');

        return collect(range(0, $days - 1))->map(function ($i) use ($from, $rows) {
            $d = $from->copy()->addDays($i)->toDateString();
            $r = $rows->get($d);

            return (object) ['date' => Carbon::parse($d), 'plays' => (int) ($r->plays ?? 0), 'launches' => (int) ($r->launches ?? 0), 'minutes' => intdiv((int) ($r->seconds ?? 0), 60)];
        });
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $user = User::withTrashed()->findOrFail($id);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'is_public' => ['nullable', 'boolean'],
            'email_verified' => ['nullable', 'boolean'],
        ]);
        $before = $user->only(['name', 'email', 'is_public']) + ['email_verified' => $user->email_verified_at !== null];
        $user->name = $data['name'];
        $user->email = strtolower(trim($data['email']));
        $user->is_public = $request->boolean('is_public');
        $verified = $request->boolean('email_verified');
        if ($verified !== ($user->email_verified_at !== null)) {
            $user->email_verified_at = $verified ? now() : null;
        }
        $after = $user->only(['name', 'email', 'is_public']) + ['email_verified' => $user->email_verified_at !== null];
        $changes = collect($after)->filter(fn ($v, $k) => $before[$k] !== $v)->map(fn ($v, $k) => ['from' => $before[$k], 'to' => $v])->all();
        if (! $changes) {
            return back()->with('status', 'Nothing changed.');
        }
        $user->save();
        Audit::log('player_updated', $user, $changes);

        return back()->with('status', "Saved {$user->email}: ".implode(', ', array_keys($changes)).' changed.');
    }

    public function revokeTokens(int $id): RedirectResponse
    {
        $user = User::withTrashed()->findOrFail($id);
        $n = $user->playerTokens()->whereNull('revoked_at')->update(['revoked_at' => now()]);
        Audit::log('tokens_revoked', $user, ['count' => $n]);

        return back()->with('status', "Signed {$user->email} out of {$n} game".($n === 1 ? '' : 's').'.');
    }

    /** See the site exactly as the player does (read-only). Admins and suspended accounts are excluded. */
    public function impersonate(Request $request, int $id): RedirectResponse
    {
        $user = User::findOrFail($id);
        if ($user->is_admin || $user->is($request->user())) {
            return back()->with('error', 'You can only view the site as a non-admin player.');
        }
        $admin = $request->user();
        Audit::log('impersonation_started', $user, [], $admin);
        $request->session()->put('impersonator_id', $admin->id);
        Auth::guard('web')->login($user);
        $request->session()->put('impersonator_id', $admin->id);

        return redirect()->route('dashboard');
    }

    public function stopImpersonating(Request $request): RedirectResponse
    {
        $adminId = $request->session()->pull('impersonator_id');
        $player = $request->user();
        $admin = $adminId ? User::find($adminId) : null;
        if (! $admin || ! $admin->is_admin) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();

            return redirect()->route('login');
        }
        Auth::guard('web')->login($admin);
        Audit::log('impersonation_stopped', $player, [], $admin);

        return redirect()->route('admin.players.show', $player->id)->with('status', 'Back to your own account.');
    }

    /** Account and admin log with filters and a daily chart. */
    public function logs(Request $request): View
    {
        $action = array_key_exists((string) $request->query('action'), Audit::ACTIONS) ? $request->query('action') : null;
        $q = trim((string) $request->query('q', ''));
        $days = in_array((int) $request->query('days'), [7, 30, 90], true) ? (int) $request->query('days') : 30;
        $from = now()->subDays($days - 1)->startOfDay();

        $counts = AuditLog::where('created_at', '>=', $from)->selectRaw('DATE(created_at) as d, action, COUNT(*) as n')->groupBy('d', 'action')->get();
        $daily = collect(range(0, $days - 1))->map(function ($i) use ($from, $counts) {
            $d = $from->copy()->addDays($i)->toDateString();
            $day = $counts->where('d', $d);

            return (object) [
                'date' => Carbon::parse($d),
                'logins' => (int) $day->where('action', 'login')->sum('n'),
                'failed' => (int) $day->where('action', 'login_failed')->sum('n'),
                'admin' => (int) $day->whereNotIn('action', ['login', 'login_failed', 'logout'])->sum('n'),
            ];
        });

        return view('admin.logs.index', [
            'logs' => AuditLog::with(['actor:id,name', 'subject:id,name,email'])
                ->when($action, fn ($w) => $w->where('action', $action))
                ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('subject_label', 'like', "%{$q}%")->orWhere('ip', 'like', "%{$q}%")))
                ->latest('created_at')->latest('id')->paginate(50)->withQueryString(),
            'daily' => $daily, 'days' => $days, 'action' => $action, 'q' => $q,
            'totals' => $counts->groupBy('action')->map(fn ($g) => (int) $g->sum('n'))->sortDesc(),
            'uniqueLogins' => AuditLog::where('action', 'login')->where('created_at', '>=', $from)->distinct()->count('subject_id'),
        ]);
    }

    public function activity(): View
    {
        return view('admin.players.activity', [
            'events' => GameEvent::with(['game:id,name,slug', 'user:id,name'])->orderByDesc('occurred_at')->paginate(100),
        ]);
    }
}
