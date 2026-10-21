<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $existing = User::where('email', $credentials['email'])->first();
        if ($existing && ! $existing->hasPassword()) {
            $message = $existing->hasLinkedProvider()
                ? 'This account signs in with '.$existing->providerLabel().'. Use the button below, or set a password from your profile after signing in.'
                : 'This account has not finished signing up. Check your email for the confirmation link, or request a new one below.';
            throw ValidationException::withMessages(['email' => $message])->redirectTo(route('login').'?resend='.urlencode($credentials['email']));
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => 'These credentials do not match our records.']);
        }

        $request->session()->regenerate();

        // intended() keeps an SSO round trip working: /sso/authorize -> login -> back.
        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('games.index');
    }
}
