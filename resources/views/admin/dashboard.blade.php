<x-layouts.admin title="Dashboard" crumb="Dashboard">
    <x-page-header kicker="Administration" title="Dashboard" text="Live figures from the database.">
        <a href="{{ route('admin.reports.index', ['status' => 'pending_review']) }}" class="btn btn-primary"><x-icon name="file-text" class="h-4 w-4" />Review reports</a>
        <a href="{{ route('admin.claims.index') }}" class="btn btn-secondary"><x-icon name="shield-check" class="h-4 w-4" />Review claims</a>
    </x-page-header>

    <dl class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-5">
        <x-stat label="Lost reports" :value="$stats['lost']" />
        <x-stat label="Found reports" :value="$stats['found']" />
        <x-stat label="Pending claims" :value="$stats['pending_claims']" :alert="$stats['pending_claims'] > 0" />
        <x-stat label="Unresolved (listed)" :value="$stats['unresolved']" />
        <x-stat label="Items returned" :value="$stats['returned']" class="col-span-2 lg:col-span-1" />
    </dl>

    <section aria-labelledby="queue-h">
        <h2 id="queue-h" class="eyebrow mb-3">Needs your attention</h2>
        <div class="grid gap-3 sm:grid-cols-3 sm:gap-4">
            @foreach ([
                ['Reports awaiting review', $tasks['reports'], route('admin.reports.index', ['status' => 'pending_review']), 'file-text'],
                ['Claims to verify', $tasks['claims'], route('admin.claims.index'), 'shield-check'],
                ['Potential matches to review', $tasks['matches'], route('admin.matches.index', ['status' => 'pending_review']), 'compare'],
            ] as [$label, $n, $href, $icon])
                <a href="{{ $href }}" class="card card-hover flex items-center gap-4 p-4">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-md {{ $n ? 'bg-red-50 text-red-600' : 'bg-navy-50 text-navy-500' }}"><x-icon :name="$icon" class="h-5 w-5" /></span>
                    <div class="min-w-0 flex-1"><p class="text-2xl leading-none font-bold text-navy-900 tabular-nums">{{ $n }}</p><p class="mt-1 text-xs font-semibold text-slate-500">{{ $label }}</p></div>
                    <x-icon name="chevron-right" class="h-5 w-5 shrink-0 text-slate-400" />
                </a>
            @endforeach
        </div>
    </section>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="card overflow-hidden">
            <div class="panel-head"><h2 class="panel-title">Reports awaiting review</h2><a href="{{ route('admin.reports.index', ['status' => 'pending_review']) }}" class="link text-sm">View all</a></div>
            @forelse ($pendingReports as $r)
                <a href="{{ route('admin.reports.show', $r) }}" class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-3 last:border-0 hover:bg-slate-50 sm:px-5"><div class="min-w-0"><p class="truncate text-sm font-semibold text-navy-900">{{ $r->item_name }}</p><p class="text-xs text-slate-500"><span class="font-mono">{{ $r->reference_code }}</span> &middot; {{ $r->created_at->diffForHumans() }}</p></div><x-status-badge :status="$r->report_type" /></a>
            @empty
                <p class="px-5 py-10 text-center text-sm text-slate-500">Nothing is waiting for review.</p>
            @endforelse
        </section>
        <section class="card overflow-hidden">
            <div class="panel-head"><h2 class="panel-title">Claims to verify</h2><a href="{{ route('admin.claims.index') }}" class="link text-sm">View all</a></div>
            @forelse ($pendingClaims as $c)
                <a href="{{ route('admin.claims.show', $c) }}" class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-3 last:border-0 hover:bg-slate-50 sm:px-5"><div class="min-w-0"><p class="truncate text-sm font-semibold text-navy-900">{{ $c->foundReport->item_name }}</p><p class="text-xs text-slate-500">by {{ $c->claimant->name }} &middot; {{ $c->created_at->diffForHumans() }}</p></div><x-status-badge :status="$c->status" /></a>
            @empty
                <p class="px-5 py-10 text-center text-sm text-slate-500">No open claims.</p>
            @endforelse
        </section>
    </div>

    <section class="card overflow-hidden">
        <div class="panel-head"><h2 class="panel-title">Recent activity</h2><a href="{{ route('admin.activity.index') }}" class="link text-sm">Full log</a></div>
        @forelse ($activity as $a)
            <div class="flex flex-col gap-0.5 border-b border-slate-100 px-4 py-3 text-sm last:border-0 sm:flex-row sm:items-start sm:justify-between sm:gap-3 sm:px-5"><p class="min-w-0 text-slate-700">{{ $a->description ?: $a->action }}<span class="text-slate-400"> &middot; {{ $a->user?->name ?? 'System' }}</span></p><span class="shrink-0 text-xs text-slate-400">{{ $a->created_at?->diffForHumans() }}</span></div>
        @empty
            <p class="px-5 py-10 text-center text-sm text-slate-500">No activity recorded yet.</p>
        @endforelse
    </section>
</x-layouts.admin>
