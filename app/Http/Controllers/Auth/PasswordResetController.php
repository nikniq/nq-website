<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\VerifyEmail;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

/**
 * Forgot password. The response never reveals whether an address has an
 * account. Accounts that never finished email sign-up get their
 * confirmation link again instead of a reset link.
 */
class PasswordResetController extends Controller
{
    public function request(): View
    {
        return view('auth.forgot');
    }

    public function email(Request $request): RedirectResponse
    {
        $email = strtolower(trim($request->validate(['email' => ['required', 'email', 'max:255']])['email']));
        $user = User::where('email', $email)->first();

        if ($user && ! $user->hasPassword() && ! $user->hasLinkedProvider()) {
            Mail::to($user->email)->send(new VerifyEmail($user));
        } elseif ($user) {
            Password::sendResetLink(['email' => $email]);
        }

        return back()->with('status', 'If that address has an account, we have emailed it a link. It works for 60 minutes.');
    }

    public function edit(Request $request, string $token): View
    {
        return view('auth.reset', ['token' => $token, 'email' => (string) $request->query('email')]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::reset($data, function (User $user, string $password) {
            $user->forceFill([
                'password' => $password,
                'remember_token' => Str::random(60),
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();
            event(new PasswordReset($user));
            Auth::login($user);
        });

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withInput($request->only('email'))->withErrors(['email' => 'That reset link is invalid or has expired. Request a new one below.']);
        }

        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', 'Password updated. You are signed in.');
    }
}
