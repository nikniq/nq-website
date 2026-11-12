<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<body style="margin:0;padding:24px;background:#0b1020;font-family:ui-sans-serif,system-ui,-apple-system,'Segoe UI',Roboto,Arial;color:#e6ecff">
<div style="max-width:520px;margin:0 auto;background:#121a30;border:1px solid rgba(255,255,255,.08);border-radius:12px;padding:28px">
    <p style="margin:0 0 8px;font-weight:900;letter-spacing:1px;color:#fff;font-size:18px">NIK<span style="color:#ff3b81">NIQ</span> GAMES</p>
    <h1 style="font-size:22px;margin:0 0 12px;color:#fff">{{ __('Confirm your email') }}</h1>
    <p style="margin:0 0 20px;color:#9aa7c7">{!! __('Tap the button to confirm :email and choose a password.', ['email' => '<strong style="color:#e6ecff">'.e($user->email).'</strong>']) !!} {{ __('The link works for :minutes minutes.', ['minutes' => config('portal.verify_ttl_minutes', 60)]) }}</p>
    <p style="margin:0 0 24px"><a href="{{ $url }}" style="display:inline-block;background:#00d1ff;color:#021122;padding:12px 20px;border-radius:8px;font-weight:800;text-decoration:none">{{ __('Confirm email & set password') }}</a></p>
    <p style="margin:0;color:#9aa7c7;font-size:13px">{{ __('If the button does not work, copy this link into your browser:') }}<br><a href="{{ $url }}" style="color:#00d1ff;word-break:break-all">{{ $url }}</a></p>
    <p style="margin:20px 0 0;color:#9aa7c7;font-size:13px">{{ __("Didn't sign up?") }} {{ __('You can ignore this email.') }}</p>
</div>
</body>
</html>
