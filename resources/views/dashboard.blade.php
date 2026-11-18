<x-layouts.member title="My dashboard" crumb="Dashboard">
    <x-page-header kicker="Member dashboard" :title="'Hello, '.\Illuminate\Support\Str::of(auth()->user()->name)->before(' ')" text="Your reports, claims and possible matches at a glance.">
        <a href="{{ route('reports.create', 'lost') }}" class="btn btn-primary"><x-icon name="plus" class="h-4 w-4" />Report lost item</a>
        <a href="{{ route('reports.create', 'found') }}" class="btn btn-secondary"><x-icon name="camera" class="h-4 w-4" />Report found item</a>
    </x-page-header>

    <dl class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-5">
        <x-stat label="My reports" :value="$counts['total']" />
        <x-stat label="Listed" :value="$counts['listed']" />
        <x-stat label="Awaiting review" :value="$counts['pending']" :alert="$counts['pending'] > 0" />
        <x-stat label="Resolved" :value="$counts['resolved']" />
        <x-stat label="Open claims" :value="$counts['open_claims']" :alert="$counts['open_claims'] > 0" class="col-span-2 lg:col-span-1" />
    </dl>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="min-w-0 space-y-6 xl:col-span-2">
            <section aria-labelledby="matches-h">
                <div class="mb-3 flex items-end justify-between gap-3"><div><span class="kicker">Image matching</span><h2 id="matches-h" class="section-title text-lg">Possible matches for my lost items</h2></div></div>
                @forelse ($matches as $m)
                    <article class="card card-hover mb-3 p-3 sm:p-4">
                        <div class="flex gap-3 sm:gap-4">
                            <x-item-photo :report="$m->foundReport" class="w-24 shrink-0 rounded-md sm:w-32" />
                            <div class="min-w-0 flex-1">
                                <p class="eyebrow !text-[0.6875rem]">For your lost &ldquo;{{ $m->lostReport->item_name }}&rdquo;</p>
                                <a href="{{ route('reports.show', $m->foundReport) }}" class="mt-0.5 block font-semibold text-navy-900 hover:text-navy-600">{{ $m->foundReport->item_name }}</a>
                                <p class="truncate text-xs text-slate-500">{{ $m->foundReport->location_description }}</p>
                                <p class="mt-2 flex items-start gap-1.5 text-xs text-slate-500"><x-icon name="info" class="mt-px h-3.5 w-3.5 shrink-0 text-navy-400" />Suggested by {{ $m->algorithm_used->value }}. A potential match, not confirmed.</p>
                                <a href="{{ route('reports.show', $m->foundReport) }}" class="btn btn-secondary btn-sm mt-3">View possible match</a>
                            </div>
                        </div>
                    </article>
                @empty
                    <x-empty-state icon="compare" title="No possible matches yet" text="When a published found item resembles one of your lost items, it shows up here and in your notifications." />
                @endforelse
            </section>

            <section aria-labelledby="recent-h" class="card table-card">
                <div class="panel-head"><h2 id="recent-h" class="panel-title">Recent reports</h2><a href="{{ route('reports.mine') }}" class="link text-sm">View all</a></div>
                @if ($recentReports->isEmpty())
                    <p class="px-5 py-10 text-center text-sm text-slate-500">You have not submitted any reports yet.</p>
                @else
                    <table class="data-table table-stack">
                        <thead><tr><th>Item</th><th>Type</th><th>Reported</th><th>Status</th><th><span class="sr-only">Action</span></th></tr></thead>
                        <tbody>
                        @foreach ($recentReports as $r)
                            <tr>
                                <td class="cell-main"><div class="flex items-center gap-3"><x-item-photo :report="$r" class="h-12 w-12 shrink-0 rounded-md" ratio="" compact /><div class="min-w-0"><p class="max-w-[13rem] truncate font-semibold text-navy-900">{{ $r->item_name }}</p><p class="truncate text-xs text-slate-500">{{ $r->location_description }}</p></div></div></td>
                                <td data-label="Type"><x-status-badge :status="$r->report_type" /></td>
                                <td data-label="Reported" class="whitespace-nowrap text-slate-600">{{ $r->created_at?->format('M j, Y') ?? $r->incident_date->format('M j, Y') }}</td>
                                <td data-label="Status"><x-status-badge :status="$r->report_status" /></td>
                                <td data-label="" class="text-right"><a href="{{ route('reports.show', $r) }}" class="btn btn-secondary btn-sm">View</a></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            </section>
        </div>

        <div class="min-w-0 space-y-6">
            <section class="card overflow-hidden" aria-labelledby="claims-h">
                <div class="panel-head"><h2 id="claims-h" class="panel-title">My claims</h2><a href="{{ route('claims.index') }}" class="link text-sm">View all</a></div>
                @forelse ($claims as $c)
                    <a href="{{ route('claims.show', $c) }}" class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-3 last:border-0 hover:bg-slate-50 sm:px-5"><span class="truncate text-sm font-semibold text-navy-900">{{ $c->foundReport->item_name }}</span><x-status-badge :status="$c->status" /></a>
                @empty
                    <p class="px-5 py-8 text-center text-sm text-slate-500">You have not submitted any claims.</p>
                @endforelse
            </section>
            <section class="callout" aria-label="Reminder">
                <x-icon name="shield-check" class="mt-0.5 h-5 w-5 shrink-0 text-navy-600" />
                <p class="text-[0.8125rem] leading-relaxed"><strong>Found something that is not yours?</strong> Report it and keep it safe. An administrator reviews it before it is listed.</p>
            </section>
        </div>
    </div>
</x-layouts.member>
