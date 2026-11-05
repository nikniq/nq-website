<?php

namespace App\Http\Controllers;

use App\Models\PlayerToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

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

    /** Download everything the portal holds about the signed-in player, as JSON. */
    public function export(Request $request): Response
    {
        $user = $request->user();
        $data = [
            'exported_at' => now()->toIso8601String(),
            'account' => $user->only(['id', 'name', 'email', 'avatar', 'provider_name', 'is_public', 'created_at', 'email_verified_at']),
            'favourites' => $user->favouriteGames()->get(['games.slug', 'games.name'])->map(fn ($g) => ['slug' => $g->slug, 'name' => $g->name, 'added_at' => $g->pivot->created_at])->all(),
            'stats' => $user->gameStats()->with('game:id,slug')->get()->map(fn ($s) => ['game' => $s->game?->slug] + $s->toApi())->all(),
            'events' => $user->gameEvents()->with('game:id,slug')->orderBy('occurred_at')->get()
                ->map(fn ($e) => ['game' => $e->game?->slug, 'event' => $e->event, 'score' => $e->score, 'level' => $e->level, 'duration_seconds' => $e->duration_seconds, 'won' => $e->won, 'metadata' => $e->metadata, 'occurred_at' => $e->occurred_at?->toIso8601String()])->all(),
            'connected_games' => $user->playerTokens()->with('game:id,slug')->whereNull('revoked_at')->get()->map(fn ($t) => ['game' => $t->game?->slug, 'connected_at' => $t->created_at, 'last_used_at' => $t->last_used_at])->all(),
        ];

        return response()->json($data, 200, ['Content-Disposition' => 'attachment; filename="nikniq-data-'.now()->format('Y-m-d').'.json"'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /** Delete the account and everything attached to it (favourites, tokens, events, stats cascade). */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();
        $request->validate([
            'confirm_email' => ['required', 'string', function ($attr, $value, $fail) use ($user) {
                if (strtolower(trim($value)) !== strtolower($user->email)) {
                    $fail('Type your email address exactly to confirm.');
                }
            }],
        ]);

        Auth::logout();
        $user->delete();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('games.index')->with('status', 'Your account and all of its data have been deleted.');
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
