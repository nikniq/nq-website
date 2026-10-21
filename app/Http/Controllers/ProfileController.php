<?php

namespace App\Http\Controllers;

use App\Models\PlayerToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();

        // One row per game currently holding a live token for this player.
        $connected = PlayerToken::with('game:id,name,slug')
            ->where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->orderByDesc('last_used_at')
            ->get()
            ->groupBy('game_id')
            ->map(fn ($tokens) => [
                'game' => $tokens->first()->game,
                'connected_at' => $tokens->min('created_at'),
                'last_active_at' => $tokens->max('last_used_at'),
                'tokens' => $tokens->count(),
            ])
            ->filter(fn ($row) => $row['game'] !== null)
            ->values();

        return view('profile.show', ['user' => $user, 'connected' => $connected]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
        ]);
        $user->update($data);

        return back()->with('status', 'Profile updated.');
    }

    public function password(Request $request): RedirectResponse
    {
        $rules = ['password' => ['required', 'confirmed', Password::defaults()]];
        if ($request->user()->hasPassword()) {
            $rules['current_password'] = ['required', 'current_password'];
        }
        $request->validate($rules);
        $request->user()->update(['password' => $request->input('password')]);

        return back()->with('status', $request->user()->wasChanged('password') ? 'Password set.' : 'Password changed.');
    }

    /** Sign out of one game (revokes every token it holds), or all games. */
    public function disconnect(Request $request): RedirectResponse
    {
        $query = PlayerToken::where('user_id', $request->user()->id)->whereNull('revoked_at');
        if ($request->filled('game_id')) {
            $query->where('game_id', (int) $request->input('game_id'));
        }
        $count = $query->update(['revoked_at' => now()]);

        return back()->with('status', $count ? 'Signed out of the game(s).' : 'Nothing to sign out of.');
    }
}
