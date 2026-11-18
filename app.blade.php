@props(['title' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-full flex-col">
@php $user = auth()->user(); $unread = $user ? $user->unreadNotifications()->count() : 0; @endphp
<a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-3 focus:top-3 focus:z-50 focus:rounded focus:bg-white focus:px-3 focus:py-2">Skip to content</a>

<header class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur">
    <div class="flex min-h-[76px] w-full items-center justify-between gap-4 px-4 sm:px-6 lg:px-10">
        <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-3" aria-label="CCDI Lost and Found home">
            <img src="{{ asset('images/ccdi-logo.png') }}" alt="CCDI logo" class="h-14 w-14 shrink-0 object-contain sm:h-16 sm:w-16">
            <span class="leading-tight">
                <span class="block text-base font-bold tracking-tight text-navy-900 sm:text-lg">CCDI Lost &amp; Found</span>
                <span class="block text-xs font-medium text-slate-500 sm:text-sm">Retrieval System</span>
            </span>
        </a>

        <nav class="hidden items-center gap-1 lg:flex" aria-label="Main">
            <a href="{{ route('items.index') }}" class="nav-link {{ request()->routeIs('items.*') ? 'nav-link-active' : '' }}">Browse items</a>
            <a href="{{ route('home') }}#how-it-works" class="nav-link">How it works</a>
            @auth
                <a href="{{ route('reports.create', 'lost') }}" class="nav-link">Report lost</a>
                <a href="{{ route('reports.create', 'found') }}" class="nav-link">Report found</a>
            @endauth
        </nav>

        <div class="hidden items-center gap-2 lg:flex">
            @auth
                <a href="{{ route('notifications.index') }}" class="relative rounded-lg p-2 text-slate-600 hover:bg-navy-50" aria-label="Notifications{{ $unread ? " ($unread unread)" : '' }}">
                    <x-icon name="bell" />@if($unread)<span class="absolute right-1 top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-teal-600 px-1 text-[10px] font-bold text-white">{{ $unread }}</span>@endif
                </a>
                @if ($user->isAdmin())<a href="{{ route('admin.dashboard') }}" class="btn btn-secondary btn-sm">Admin</a>@endif
                <a href="{{ route('dashboard') }}" class="btn btn-primary btn-sm">My dashboard</a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="rounded-lg p-2 text-slate-600 hover:bg-navy-50" aria-label="Sign out" title="Sign out"><x-icon name="log-out" /></button></form>
            @else
                <a href="{{ route('login') }}" class="btn btn-ghost btn-sm">Sign in</a>
                <a href="{{ route('register') }}" class="btn btn-primary btn-sm">Create account</a>
            @endauth
        </div>

        <button type="button" class="rounded-lg p-2 text-slate-700 hover:bg-navy-50 lg:hidden" data-toggle="mobile-nav" aria-expanded="false" aria-controls="mobile-nav" aria-label="Toggle menu"><x-icon name="menu" class="h-6 w-6" /></button>
    </div>

    <div id="mobile-nav" class="hidden border-t border-slate-200 bg-white lg:hidden">
        <nav class="mx-auto flex max-w-7xl flex-col gap-1 px-4 py-3" aria-label="Mobile">
            <a href="{{ route('items.index') }}" class="nav-link">Browse items</a>
            <a href="{{ route('home') }}#how-it-works" class="nav-link">How it works</a>
            @auth
                <a href="{{ route('reports.create', 'lost') }}" class="nav-link">Report lost item</a>
                <a href="{{ route('reports.create', 'found') }}" class="nav-link">Report found item</a>
                <a href="{{ route('dashboard') }}" class="nav-link">My dashboard</a>
                <a href="{{ route('notifications.index') }}" class="nav-link">Notifications {{ $unread ? "($unread)" : '' }}</a>
                @if ($user->isAdmin())<a href="{{ route('admin.dashboard') }}" class="nav-link">Administration</a>@endif
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="nav-link w-full text-left">Sign out</button></form>
            @else
                <a href="{{ route('login') }}" class="nav-link">Sign in</a>
                <a href="{{ route('register') }}" class="nav-link">Create account</a>
            @endauth
        </nav>
    </div>
</header>

<main id="main" class="flex-1">
    <div class="w-full px-4 pt-6 empty:hidden sm:px-6 lg:px-10"><x-alerts /></div>
    {{ $slot }}
</main>

<footer class="mt-16 border-t border-slate-200 bg-white">
    <div class="flex w-full flex-col gap-3 px-4 py-8 text-sm text-slate-600 sm:px-6 md:flex-row md:items-start md:justify-between lg:px-10">
        <div>
            <div class="mb-2 flex items-center gap-2"><img src="{{ asset('images/ccdi-logo.png') }}" alt="CCDI logo" class="h-8 w-8 object-contain"><p class="font-semibold text-navy-900">Computer Communication and Development Institution</p></div>
            <p>Sikatuna, Old Albay District, Legazpi City, Albay</p>
        </div>
        <p class="max-w-md text-xs leading-relaxed text-slate-500">Image-matching results are <strong>potential matches only</strong>. Every claim is verified by authorized personnel before an item is released. This system is an academic project of BSIT students.</p>
    </div>
</footer>
</body>
</html>
