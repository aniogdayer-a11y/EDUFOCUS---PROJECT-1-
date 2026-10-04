<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f8f8f5">
    <meta name="description" content="EduFocus helps students study in shorter, more interactive sessions. Organize your PDF lessons and make time to focus.">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Home') - EduFocus</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="site-page">
    @php
        $navigationHome = isset($workspace) && $workspace ? route('dashboard') : route('home');
        $homeAnchor = isset($workspace) && $workspace ? 'dashboard-home' : 'home';
    @endphp
    <a class="site-skip" href="#main-content">Skip to content</a>
    <header class="site-header">
        <nav class="site-nav site-container" aria-label="Main navigation">
            <a class="site-brand" href="{{ $navigationHome }}#{{ $homeAnchor }}">@include('partials.brand-mark')<span>EduFocus</span></a>
            <button class="site-menu-toggle" type="button" aria-expanded="false" aria-controls="site-navigation" hidden>Menu <span aria-hidden="true">☰</span></button>
            <div class="site-nav-links" id="site-navigation">
                <a class="site-nav-home" href="{{ $navigationHome }}#{{ $homeAnchor }}" @if(request()->routeIs('home') || request()->routeIs('dashboard')) aria-current="page" @endif>Home</a>
                <a href="{{ $navigationHome }}#about" data-nav-about>About</a>
                @if (isset($workspace) && $workspace)
                    <a href="{{ route('lessons.index') }}" @if(request()->routeIs('lessons.*')) aria-current="page" @endif>Lessons</a>
                    <a href="{{ route('profile') }}" @if(request()->routeIs('profile')) aria-current="page" @endif>Profile</a>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="site-nav-logout" type="submit">Log out ↗</button></form>
                @else
                    <a href="{{ route('home') }}#features">Features</a>
                    @auth
                        <a class="site-button site-button--small" href="{{ route('dashboard') }}">My dashboard <span aria-hidden="true">↗</span></a>
                    @else
                        <a href="{{ route('login') }}">Login</a>
                    @endauth
                @endif
            </div>
        </nav>
    </header>
    <main id="main-content">@yield('content')</main>
    <footer class="site-footer">
        <div class="site-container site-footer-inner">
            <a class="site-brand" href="{{ $navigationHome }}#{{ $homeAnchor }}">@include('partials.brand-mark')<span>EduFocus</span></a>
            <p>Small steps. Every day.</p>
            <nav aria-label="Footer navigation"><a href="{{ $navigationHome }}#about">About</a><a href="{{ $navigationHome }}#contact">Contact</a><a href="{{ $navigationHome }}#privacy">Privacy</a></nav>
            <small>© {{ date('Y') }} EduFocus</small>
        </div>
    </footer>
</body>
</html>
