<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GameEvent;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $user->suspend($request->input('reason'));

        return back()->with('status', "{$user->email} was suspended. Restore it from Deleted, or delete it permanently there.");
    }

    public function restore(int $id): RedirectResponse
    {
        $user = User::onlyTrashed()->findOrFail($id);
        $user->restore();
        $user->forceFill(['deleted_reason' => null])->saveQuietly();

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
        $user->forceDelete();

        return redirect()->route('admin.players.index', ['show' => $request->query('show')])->with('status', "{$email} and all of its data were permanently deleted.");
    }

    public function toggleAdmin(Request $request, int $id): RedirectResponse
    {
        $user = User::findOrFail($id);
        if ($user->is_admin && $error = $this->guard($request, $user)) {
            return back()->with('error', $error);
        }
        $user->forceFill(['is_admin' => ! $user->is_admin])->save();

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

    public function activity(): View
    {
        return view('admin.players.activity', [
            'events' => GameEvent::with(['game:id,name,slug', 'user:id,name'])->orderByDesc('occurred_at')->paginate(100),
        ]);
    }
}
