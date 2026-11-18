@props(['title' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#172b50">
    <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/ccdi-logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-full flex-col">
@php
    $user = auth()->user();
    $unread = $user ? $user->unreadNotifications()->count() : 0;
    $links = [
        ['items.index', 'Browse items', request()->routeIs('items.*')],
        ['reports.create:lost', 'Report lost', request()->is('reports/new/lost')],
        ['reports.create:found', 'Report found', request()->is('reports/new/found')],
    ];
    $href = fn ($r) => str_contains($r, ':') ? route(...explode(':', $r, 2)) : route($r);
@endphp
<a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-3 focus:top-3 focus:z-50 focus:rounded focus:bg-white focus:px-3 focus:py-2">Skip to content</a>

<div class="bg-navy-900 text-navy-200">
    <div class="mx-auto flex h-8 max-w-[1600px] items-center justify-between gap-4 px-4 text-[0.6875rem] tracking-wide sm:px-6 lg:px-8">
        <p class="truncate font-medium">Computer Communication Development Institute <span class="hidden sm:inline">&middot; Legazpi City, Albay</span></p>
        <p class="hidden shrink-0 font-semibold tracking-[0.1em] text-navy-300 uppercase sm:block">Lost &amp; Found Retrieval System</p>
    </div>
</div>

<header class="sticky top-0 z-40 border-b border-slate-200 bg-white">
    <div class="mx-auto flex h-16 max-w-[1600px] items-center justify-between gap-4 px-4 sm:px-6 lg:h-[4.25rem] lg:px-8">
        <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-3" aria-label="CCDI Lost and Found home">
            <x-brand size="h-10 lg:h-11" />
            <span class="min-w-0 leading-tight"><span class="block truncate text-[0.9375rem] font-bold text-navy-900">CCDI Lost &amp; Found</span><span class="block truncate text-[0.6875rem] font-semibold tracking-wide text-slate-500 uppercase">Retrieval System</span></span>
        </a>

        <nav class="hidden items-center lg:flex" aria-label="Main">
            @foreach ($links as [$r, $label, $active])
                @continue(! $user && str_starts_with($r, 'reports.create'))
                <a href="{{ $href($r) }}" class="nav-link {{ $active ? 'nav-link-active' : '' }}" @if($active) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
            <a href="{{ route('home') }}#how-it-works" class="nav-link">How it works</a>
        </nav>

        <div class="hidden items-center gap-2 lg:flex">
            @auth
                <a href="{{ route('notifications.index') }}" class="relative inline-flex h-10 w-10 items-center justify-center rounded-md text-slate-600 hover:bg-navy-50 hover:text-navy-800" aria-label="Notifications{{ $unread ? " ($unread unread)" : '' }}">
                    <x-icon name="bell" />@if($unread)<span class="absolute top-1 right-1 flex h-4 min-w-4 items-center justify-center rounded-sm bg-red-500 px-1 text-[0.625rem] font-bold text-white">{{ $unread }}</span>@endif
                </a>
                @if ($user->isAdmin())<a href="{{ route('admin.dashboard') }}" class="btn btn-secondary btn-sm">Administration</a>@endif
                <a href="{{ route('dashboard') }}" class="btn btn-primary btn-sm">My dashboard</a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="inline-flex h-10 w-10 items-center justify-center rounded-md text-slate-600 hover:bg-navy-50 hover:text-navy-800" aria-label="Sign out" title="Sign out"><x-icon name="log-out" /></button></form>
            @else
                <a href="{{ route('login') }}" class="btn btn-secondary btn-sm">Sign in</a>
                <a href="{{ route('register') }}" class="btn btn-primary btn-sm">Create account</a>
            @endauth
        </div>

        <div class="flex items-center gap-1 lg:hidden">
            @auth
                <a href="{{ route('notifications.index') }}" class="relative inline-flex h-11 w-11 items-center justify-center rounded-md text-slate-700 hover:bg-navy-50" aria-label="Notifications{{ $unread ? " ($unread unread)" : '' }}">
                    <x-icon name="bell" />@if($unread)<span class="absolute top-1.5 right-1.5 flex h-4 min-w-4 items-center justify-center rounded-sm bg-red-500 px-1 text-[0.625rem] font-bold text-white">{{ $unread }}</span>@endif
                </a>
            @endauth
            <button type="button" class="inline-flex h-11 w-11 items-center justify-center rounded-md text-navy-800 hover:bg-navy-50" data-toggle="mobile-nav" aria-expanded="false" aria-controls="mobile-nav" aria-label="Open menu"><x-icon name="menu" class="h-6 w-6" /></button>
        </div>
    </div>

    <div id="mobile-nav" class="hidden border-t border-slate-200 bg-white shadow-md lg:hidden" data-close-on-nav>
        <nav class="mx-auto flex max-w-[1600px] flex-col px-4 py-2" aria-label="Mobile">
            @foreach ($links as [$r, $label, $active])
                @continue(! $user && str_starts_with($r, 'reports.create'))
                <a href="{{ $href($r) }}" class="flex min-h-12 items-center justify-between border-b border-slate-100 text-[0.9375rem] font-semibold {{ $active ? 'text-navy-900' : 'text-slate-700' }}">{{ $label }}<x-icon name="chevron-right" class="h-4 w-4 text-slate-400" /></a>
            @endforeach
            <a href="{{ route('home') }}#how-it-works" class="flex min-h-12 items-center justify-between border-b border-slate-100 text-[0.9375rem] font-semibold text-slate-700">How it works<x-icon name="chevron-right" class="h-4 w-4 text-slate-400" /></a>
            <div class="grid gap-2 py-4">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-primary btn-block">My dashboard</a>
                    @if ($user->isAdmin())<a href="{{ route('admin.dashboard') }}" class="btn btn-secondary btn-block">Administration</a>@endif
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-ghost btn-block">Sign out</button></form>
                @else
                    <a href="{{ route('login') }}" class="btn btn-secondary btn-block">Sign in</a>
                    <a href="{{ route('register') }}" class="btn btn-primary btn-block">Create account</a>
                @endauth
            </div>
        </nav>
    </div>
</header>

<main id="main" class="flex-1">
    <div class="mx-auto max-w-[1600px] px-4 pt-6 empty:hidden sm:px-6 lg:px-8"><x-alerts /></div>
    {{ $slot }}
</main>

<footer class="mt-16 border-t-[3px] border-red-500 bg-navy-900 text-navy-200">
    <div class="mx-auto grid max-w-[1600px] gap-8 px-4 py-10 sm:px-6 md:grid-cols-[1.2fr_1fr_1fr] lg:px-8">
        <div class="flex items-start gap-3">
            <x-brand size="h-12" plate />
            <div class="text-sm"><p class="font-bold text-white">Computer Communication Development Institute</p><p class="mt-1 text-navy-300">Sikatuna, Old Albay District, Legazpi City, Albay</p></div>
        </div>
        <nav aria-label="Footer" class="text-sm">
            <p class="mb-2 text-[0.6875rem] font-bold tracking-[0.12em] text-navy-300 uppercase">System</p>
            <ul><li><a class="inline-block py-2 hover:text-white" href="{{ route('items.index') }}">Browse items</a></li><li><a class="inline-block py-2 hover:text-white" href="{{ route('home') }}#how-it-works">How it works</a></li>@guest<li><a class="inline-block py-2 hover:text-white" href="{{ route('login') }}">Sign in</a></li>@endguest</ul>
        </nav>
        <p class="text-xs leading-relaxed text-navy-300"><strong class="text-white">Potential matches only.</strong> Image-matching results are suggestions. Every claim is verified by authorized personnel before an item is released. This system is an academic project of BSIT students.</p>
    </div>
</footer>
</body>
</html>
