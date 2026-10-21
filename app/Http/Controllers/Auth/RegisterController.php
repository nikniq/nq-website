<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\VerifyEmail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Email-first sign-up.
 *
 *  1. POST /register with just an email: a password-less, unverified account is
 *     created (or an existing unverified one reused) and a signed link is mailed.
 *  2. GET  /register/verify/{user}/{hash} (signed, time-limited): marks the email
 *     verified, logs the player in and sends them to /register/password.
 *  3. PUT  /register/password: sets name and password.
 *
 * An account with no password cannot log in with the form; it can only
 * request a fresh link (POST /register/resend).
 */
class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);
        $email = strtolower(trim($data['email']));

        $user = User::where('email', $email)->first();

        if ($user && $user->hasPassword()) {
            // Don't reveal that the address is taken. Point them at login instead.
            return redirect()->route('register.sent')->with('email', $email);
        }

        if (! $user) {
            $user = User::create([
                'name' => Str::before($email, '@'),
                'email' => $email,
                'password' => null,
            ]);
        }

        Mail::to($user->email)->send(new VerifyEmail($user));

        return redirect()->route('register.sent')->with('email', $email);
    }

    public function sent(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('email')) {
            return redirect()->route('register');
        }

        return view('auth.sent', ['email' => $request->session()->get('email')]);
    }

    /** Re-send the link for an account that has not finished signing up. */
    public function resend(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);
        $email = strtolower(trim($data['email']));

        $user = User::where('email', $email)->first();
        if ($user && ! $user->hasPassword()) {
            Mail::to($user->email)->send(new VerifyEmail($user));
        }

        // Same response either way, so the form cannot be used to probe addresses.
        return redirect()->route('register.sent')->with('email', $email);
    }

    public function verify(Request $request, User $user, string $hash): RedirectResponse
    {
        if (! hash_equals(sha1($user->email), $hash)) {
            abort(403);
        }

        if ($user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        Auth::login($user);
        $request->session()->regenerate();

        if ($user->hasPassword()) {
            return redirect()->intended(route('dashboard'))->with('status', 'Email confirmed.');
        }

        return redirect()->route('register.password');
    }

    public function password(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasPassword()) {
            return redirect()->route('dashboard');
        }

        return view('auth.password', ['user' => $request->user()]);
    }

    public function storePassword(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user->hasPassword()) {
            return redirect()->route('dashboard');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->forceFill([
            'name' => $data['name'],
            'password' => $data['password'],
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        return redirect()->intended(route('dashboard'))->with('status', 'Welcome aboard! Your account is ready.');
    }
}
