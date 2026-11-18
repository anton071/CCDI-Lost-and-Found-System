{{--
  Dashboard shell shared by the member area and the administration area:
  navy sidebar on desktop, slide-in drawer on phones/tablets, slim top bar with notifications.
--}}
@props(['title' => null, 'crumb' => null, 'area' => 'Member area', 'nav' => [], 'secondary' => [], 'admin' => false])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#10203f">
    <title>{{ $title ? $title.' · ' : '' }}{{ $admin ? 'Administration · ' : '' }}{{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/ccdi-logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full">
@php
    $user = auth()->user();
    $unread = $user->unreadNotifications()->count();
    $initials = \Illuminate\Support\Str::of($user->name)->explode(' ')->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
@endphp
<a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-3 focus:top-3 focus:z-[60] focus:rounded focus:bg-white focus:px-3 focus:py-2">Skip to content</a>

<div class="lg:flex lg:min-h-screen">
    <div id="sidebar-overlay" class="fixed inset-0 z-40 hidden bg-navy-950/60 lg:hidden" data-close="sidebar" aria-hidden="true"></div>

    <aside id="sidebar" data-open="false" data-close-on-nav
           class="fixed inset-y-0 left-0 z-50 flex w-[17.5rem] max-w-[85vw] -translate-x-full flex-col bg-navy-900 text-navy-100 transition-transform duration-200 data-[open=true]:translate-x-0 lg:sticky lg:top-0 lg:h-screen lg:w-64 lg:max-w-none lg:translate-x-0 lg:shrink-0"
           aria-label="Sidebar">
        <div class="flex h-[4.25rem] shrink-0 items-center justify-between gap-2 border-b border-white/10 px-4">
            <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-3" aria-label="CCDI Lost and Found home">
                <x-brand size="h-10" plate />
                <span class="min-w-0 leading-tight"><span class="block truncate text-sm font-bold text-white">CCDI Lost &amp; Found</span><span class="block truncate text-[0.625rem] font-semibold tracking-[0.12em] text-navy-300 uppercase">{{ $area }}</span></span>
            </a>
            <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-md text-navy-200 hover:bg-white/10 lg:hidden" data-close="sidebar" aria-label="Close menu"><x-icon name="x" /></button>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-3" aria-label="{{ $area }}">
            <p class="side-heading !pt-1">Menu</p>
            <div class="space-y-0.5">
                @foreach ($nav as [$route, $label, $icon, $match])
                    @php $active = request()->routeIs($match); @endphp
                    <a href="{{ route($route) }}" @if($active) aria-current="page" @endif class="side-link {{ $active ? 'side-link-active' : '' }}">
                        <x-icon :name="$icon" class="h-[1.15rem] w-[1.15rem] shrink-0" /><span class="flex-1">{{ $label }}</span>
                        @if ($route === 'notifications.index' && $unread)<span class="rounded-sm bg-red-500 px-1.5 text-[0.6875rem] leading-5 font-bold text-white">{{ $unread }}</span>@endif
                    </a>
                @endforeach
            </div>
            @if ($secondary)
                <p class="side-heading">{{ $admin ? 'Site' : 'Shortcuts' }}</p>
                <div class="space-y-0.5">
                    @foreach ($secondary as [$href, $label, $icon])
                        <a href="{{ $href }}" class="side-link"><x-icon :name="$icon" class="h-[1.15rem] w-[1.15rem] shrink-0" />{{ $label }}</a>
                    @endforeach
                </div>
            @endif
        </nav>

        <div class="shrink-0 border-t border-white/10 p-3">
            <div class="flex items-center gap-3 rounded-md bg-white/5 p-2.5">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-red-500 text-sm font-bold text-white" aria-hidden="true">{{ $initials }}</span>
                <div class="min-w-0 leading-tight"><p class="truncate text-sm font-semibold text-white">{{ $user->name }}</p><p class="truncate text-xs text-navy-300">{{ $user->role->label() }}</p></div>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="mt-2">@csrf<button class="side-link w-full"><x-icon name="log-out" class="h-[1.15rem] w-[1.15rem]" />Sign out</button></form>
        </div>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
        <header class="sticky top-0 z-30 flex h-14 items-center justify-between gap-3 border-b border-slate-200 bg-white px-3 sm:px-6 lg:h-[4.25rem]">
            <div class="flex min-w-0 items-center gap-1">
                <button type="button" class="inline-flex h-11 w-11 items-center justify-center rounded-md text-navy-800 hover:bg-navy-50 lg:hidden" data-open="sidebar" aria-controls="sidebar" aria-expanded="false" aria-label="Open menu"><x-icon name="menu" class="h-6 w-6" /></button>
                <a href="{{ route('home') }}" class="mr-1 inline-flex h-11 items-center lg:hidden" aria-label="CCDI Lost and Found home"><x-brand size="h-8" /></a>
                <nav class="hidden min-w-0 items-center gap-1.5 text-sm text-slate-500 lg:flex" aria-label="Breadcrumb">
                    <span>{{ $area }}</span>@if ($crumb)<x-icon name="chevron-right" class="h-3.5 w-3.5 text-slate-400" /><span class="truncate font-semibold text-navy-900">{{ $crumb }}</span>@endif
                </nav>
                <span class="truncate text-sm font-semibold text-navy-900 lg:hidden">{{ $crumb }}</span>
            </div>
            <div class="flex shrink-0 items-center gap-1 sm:gap-2">
                <a href="{{ route('notifications.index') }}" class="relative inline-flex h-11 w-11 items-center justify-center rounded-md text-slate-600 hover:bg-navy-50 hover:text-navy-800" aria-label="Notifications{{ $unread ? " ($unread unread)" : '' }}">
                    <x-icon name="bell" />@if($unread)<span class="absolute top-1.5 right-1.5 flex h-4 min-w-4 items-center justify-center rounded-sm bg-red-500 px-1 text-[0.625rem] font-bold text-white">{{ $unread }}</span>@endif
                </a>
                <a href="{{ route('profile.edit') }}" class="hidden items-center gap-2.5 rounded-md py-1 pr-2 pl-1 hover:bg-navy-50 sm:flex" aria-label="My profile">
                    <span class="flex h-8 w-8 items-center justify-center rounded-md bg-navy-800 text-xs font-bold text-white" aria-hidden="true">{{ $initials }}</span>
                    <span class="max-w-40 truncate text-sm font-semibold text-navy-900">{{ $user->name }}</span>
                </a>
            </div>
        </header>

        <main id="main" class="mx-auto w-full max-w-[1600px] flex-1 space-y-6 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
            <x-alerts />
            {{ $slot }}
        </main>
        <footer class="border-t border-slate-200 px-4 py-4 text-center text-xs text-slate-500 sm:px-6">Potential matches are suggestions only &middot; all releases are verified by authorized CCDI personnel.</footer>
    </div>
</div>
</body>
</html>
