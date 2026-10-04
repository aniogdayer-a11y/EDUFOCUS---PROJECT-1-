<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f8f8f5">
    <title>{{ $register ? 'Create an account' : 'Log in' }} - EduFocus</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-page">
    <main class="auth-shell {{ $register ? 'auth-shell--register' : '' }}">
        <section class="auth-art" aria-labelledby="buddy-heading">
            <div class="auth-window-dots" aria-hidden="true"><i></i><i></i><i></i></div>
            <span class="auth-spark auth-spark--one" aria-hidden="true">✛</span>
            <span class="auth-spark auth-spark--two" aria-hidden="true">✛</span>
            <h1 id="buddy-heading" class="auth-headline">
                @if ($register)
                    <span>A fresh start.</span>
                    <span>Your study buddy</span>
                    <span>is ready.</span>
                @else
                    <span>Stay focused.</span>
                    <span>Your study buddy</span>
                    <span>is watching.</span>
                @endif
            </h1>
            @include('auth.study-scene')
        </section>
        <section class="auth-panel" aria-label="{{ $register ? 'Registration' : 'Login' }}">
            <header class="auth-topbar">
                <span>EDUFOCUS.EXE</span>
                <a href="{{ url('/landing') }}" aria-label="Explore EduFocus" title="Explore EduFocus">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M14 3h7v7M21 3 9 15"/><path d="M10 4H3v17h17v-7"/></svg>
                </a>
            </header>
            <div class="auth-content">
                <a class="auth-brand" href="{{ url('/') }}" aria-label="EduFocus home">
                    <svg class="auth-brand-mark" viewBox="0 0 72 72" aria-hidden="true"><path fill="currentColor" d="M24 0h44v48H46V24H24ZM2 24h22v24H2Zm22 24h22v24H24Zm32 10h14v14H56Z"/></svg>
                    <span>EduFocus</span>
                </a>
                <h2 class="auth-form-heading">{{ $register ? 'A little focus. A lot of possibility.' : 'Welcome back. Let’s make progress.' }}</h2>
                @if ($errors->any())
                    <div class="auth-errors" role="alert">
                        <strong>{{ $register ? 'Let’s check those details.' : 'We couldn’t log you in.' }}</strong>
                        <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif
                <form method="POST" action="{{ url($register ? '/register' : '/login') }}" class="auth-form">
                    @csrf
                    @if ($register)
                        <div class="auth-field">
                            <label class="auth-sr-only" for="name">Full name</label>
                            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="7" r="4"/><path d="M4 22v-3a8 8 0 0 1 16 0v3"/></svg>
                            <input id="name" type="text" name="name" value="{{ old('name') }}" autocomplete="name" placeholder="Full name" maxlength="255" required>
                        </div>
                    @endif
                    <div class="auth-field">
                        <label class="auth-sr-only" for="email">Email address</label>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 4h20v16H2zM2 4l10 9L22 4"/></svg>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" placeholder="Email address" maxlength="255" required>
                    </div>
                    <div class="auth-field">
                        <label class="auth-sr-only" for="password">Password</label>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 10h16v12H4zM7 10V6a5 5 0 0 1 10 0v4M12 15v3"/></svg>
                        <input id="password" type="password" name="password" autocomplete="{{ $register ? 'new-password' : 'current-password' }}" placeholder="Password" @if($register) minlength="8" aria-describedby="password-hint" @endif required>
                        <button class="auth-password-toggle" type="button" data-password-toggle="password" aria-label="Show password" aria-pressed="false" hidden>@include('auth.eye-icon')</button>
                    </div>
                    @if ($register)
                        <div class="auth-field">
                            <label class="auth-sr-only" for="password_confirmation">Confirm password</label>
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 10h16v12H4zM7 10V6a5 5 0 0 1 10 0v4M12 15v3"/></svg>
                            <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" placeholder="Confirm password" minlength="8" required>
                            <button class="auth-password-toggle" type="button" data-password-toggle="password_confirmation" aria-label="Show confirm password" aria-pressed="false" hidden>@include('auth.eye-icon')</button>
                        </div>
                        <p class="auth-password-hint" id="password-hint">Make it yours. Use at least 8 characters.</p>
                    @else
                        <label class="auth-remember"><input type="checkbox" name="remember" value="1" @checked(old('remember'))><span>Remember me</span></label>
                    @endif
                    <button type="submit" class="auth-submit">
                        <span>{{ $register ? 'Create account' : 'Log In' }}</span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M3 12h17m-7-7 7 7-7 7"/></svg>
                    </button>
                </form>
                <div class="auth-divider" aria-hidden="true"><span>YOUR NEXT CHAPTER</span></div>
                <p class="auth-switch">
                    {{ $register ? 'Already have an account?' : 'Don’t have an account?' }}
                    <a href="{{ url($register ? '/login' : '/register') }}">{{ $register ? 'Log in' : 'Create one' }}</a>
                </p>
            </div>
            <footer class="auth-footer"><span class="auth-status-dot" aria-hidden="true"></span> SMALL STEPS. EVERY DAY.</footer>
        </section>
    </main>
</body>
</html>
