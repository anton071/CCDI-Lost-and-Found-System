<x-layouts.app title="Find what you lost. Return what you found.">
    <section class="-mt-6 border-b-4 border-red-500 bg-navy-800 text-white">
        <div class="mx-auto grid max-w-[1600px] gap-10 px-4 py-12 sm:px-6 lg:grid-cols-[1.15fr_1fr] lg:items-center lg:gap-14 lg:px-8 lg:py-16">
            <div>
                <p class="inline-flex items-center gap-2 border-l-2 border-red-500 pl-3 text-xs font-bold tracking-[0.12em] text-navy-200 uppercase"><x-icon name="shield-check" class="h-4 w-4" />Verified by CCDI personnel before any release</p>
                <h1 class="mt-5 text-[1.875rem] leading-[1.15] font-bold tracking-tight sm:text-4xl lg:text-[2.75rem]">Help the CCDI community reconnect with misplaced belongings.</h1>
                <p class="mt-4 max-w-xl text-base text-navy-100 sm:text-lg">Report what you lost or found, search by description or photo, and let the system suggest possible matches for authorized personnel to verify.</p>
                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('reports.create', 'lost') }}" class="btn btn-accent btn-lg"><x-icon name="plus" class="h-5 w-5" />Report lost item</a>
                    <a href="{{ route('reports.create', 'found') }}" class="btn btn-on-dark btn-lg"><x-icon name="camera" class="h-5 w-5" />Report found item</a>
                </div>
            </div>
            <form method="GET" action="{{ route('items.index') }}" class="rounded-lg bg-white p-5 text-slate-800 shadow-xl sm:p-6" role="search">
                <span class="kicker">Search</span>
                <h2 class="text-lg font-bold text-navy-900">Search reported items</h2>
                <p class="mt-1 text-sm text-slate-500">Works for every report, with or without a photograph.</p>
                <label for="hero-q" class="label mt-4">What are you looking for?</label>
                <input id="hero-q" name="q" type="search" class="input" placeholder="e.g. black umbrella, blue wallet" maxlength="100">
                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                    <div>
                        <label for="hero-type" class="label">Report type</label>
                        <select id="hero-type" name="type" class="input"><option value="">Lost &amp; found</option><option value="lost">Lost items</option><option value="found">Found items</option></select>
                    </div>
                    <div>
                        <label for="hero-cat" class="label">Category</label>
                        <select id="hero-cat" name="category" class="input"><option value="">All categories</option>@foreach ($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select>
                    </div>
                </div>
                <button class="btn btn-primary btn-block mt-5" type="submit"><x-icon name="search" class="h-4 w-4" />Search items</button>
            </form>
        </div>
    </section>

    <section class="mx-auto max-w-[1600px] px-4 py-8 sm:px-6 lg:px-8" aria-label="Current figures">
        <dl class="grid gap-3 sm:grid-cols-3">
            <x-stat label="Lost items listed" :value="$stats['lost']" />
            <x-stat label="Found items listed" :value="$stats['found']" />
            <x-stat label="Items returned to owners" :value="$stats['returned']" />
        </dl>
    </section>

    <section class="mx-auto max-w-[1600px] px-4 pt-4 sm:px-6 lg:px-8">
        <div class="mb-5 flex items-end justify-between gap-4">
            <div><span class="kicker">Latest</span><h2 class="text-xl font-bold text-navy-900 sm:text-2xl">Recently reported</h2><p class="mt-1 text-sm text-slate-500">The latest published reports from the CCDI community.</p></div>
            <a href="{{ route('items.index') }}" class="btn btn-secondary btn-sm shrink-0">View all<x-icon name="arrow-right" class="h-4 w-4" /></a>
        </div>
        @if ($recent->isEmpty())
            <x-empty-state icon="inbox" title="No reports have been published yet" text="Be the first to report a lost or found belonging.">
                <a href="{{ route('reports.create', 'found') }}" class="btn btn-primary btn-sm">Report an item</a>
            </x-empty-state>
        @else
            <div class="grid gap-4 sm:grid-cols-2 sm:gap-5 lg:grid-cols-3 xl:grid-cols-4">@foreach ($recent as $item)<x-item-card :item="$item" />@endforeach</div>
        @endif
    </section>

    <section id="how-it-works" class="mx-auto mt-16 max-w-[1600px] scroll-mt-28 px-4 sm:px-6 lg:px-8">
        <span class="kicker">Process</span>
        <h2 class="text-xl font-bold text-navy-900 sm:text-2xl">How it works</h2>
        <ol class="mt-6 grid gap-x-8 gap-y-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['1', 'Report', 'Describe the item, where and when it was lost or found. Adding a photo is optional.', 'file-text'],
                ['2', 'Match', 'With photos, the system compares features using SIFT first and ORB as a fallback. Without photos, text search still works.', 'compare'],
                ['3', 'Claim', 'If you recognize a found item, submit a claim with details only the owner would know.', 'tag'],
                ['4', 'Verify &amp; release', 'An administrator verifies the claim and records the handover. Nothing is released automatically.', 'shield-check'],
            ] as [$n, $t, $d, $icon])
                <li class="border-t-2 border-navy-800 pt-4">
                    <div class="flex items-center gap-3"><span class="flex h-9 w-9 items-center justify-center rounded-md bg-navy-800 text-sm font-bold text-white">{{ $n }}</span><x-icon :name="$icon" class="h-5 w-5 text-navy-400" /></div>
                    <h3 class="mt-3 font-bold text-navy-900">{!! $t !!}</h3><p class="mt-1 text-sm leading-relaxed text-slate-500">{{ $d }}</p>
                </li>
            @endforeach
        </ol>
        <div class="callout callout-warn mt-8">
            <x-icon name="info" class="mt-0.5 h-5 w-5 shrink-0 text-amber-600" />
            <p><strong>Potential matches are suggestions, not proof.</strong> Photos can look alike and lighting or angle can change the result, so authorized personnel verify every claim before an item is handed over.</p>
        </div>
    </section>
</x-layouts.app>
