<x-layouts.admin :title="$report->reference_code" crumb="Reports / {{ $report->reference_code }}">
    <a href="{{ route('admin.reports.index') }}" class="inline-flex min-h-11 items-center gap-1.5 text-sm font-semibold text-slate-500 hover:text-navy-800"><x-icon name="arrow-left" class="h-4 w-4" />All reports</a>
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <article class="card overflow-hidden">
                <div class="grid gap-0 sm:grid-cols-2">
                    <x-item-photo :report="$report" ratio="aspect-[4/3]" />
                    <div class="space-y-2 p-5">
                        <div class="flex flex-wrap gap-2"><x-status-badge :status="$report->report_type" /><x-status-badge :status="$report->report_status" /></div>
                        <h1 class="text-xl leading-snug font-bold text-navy-900">{{ $report->item_name }}</h1>
                        <p class="font-mono text-xs text-slate-500">{{ $report->reference_code }}</p>
                        <p class="text-sm text-slate-600">{{ $report->category?->name }}{{ $report->brand ? ' · '.$report->brand : '' }}{{ $report->color ? ' · '.$report->color : '' }}</p>
                        <p class="text-sm text-slate-600"><x-icon name="map-pin" class="mr-1 inline h-4 w-4 text-navy-400" />{{ $report->location_description }}</p>
                        <p class="text-sm text-slate-600"><x-icon name="calendar" class="mr-1 inline h-4 w-4 text-navy-400" />{{ $report->incident_date->format('F j, Y') }}{{ $report->incident_time ? ' · '.\Illuminate\Support\Carbon::parse($report->incident_time)->format('g:i A') : '' }}</p>
                    </div>
                </div>
                <div class="space-y-3 border-t border-slate-100 p-5 text-sm">
                    <div><h2 class="eyebrow">Description</h2><p class="mt-1 whitespace-pre-line text-slate-700">{{ $report->description }}</p></div>
                    <div><h2 class="eyebrow flex items-center gap-1.5"><x-icon name="lock" class="h-3.5 w-3.5" />Private identifying details</h2><p class="mt-1 whitespace-pre-line text-slate-700">{{ $report->identifying_features ?: '—' }}</p></div>
                    <p class="text-slate-600">Reported by <strong>{{ $report->submitter?->name ?? 'Deleted user' }}</strong>{{ $report->submitter ? ' ('.$report->submitter->email.')' : '' }} on {{ $report->created_at->format('M j, Y g:i A') }}</p>
                    @if ($report->reviewed_at)<p class="text-slate-600">Last reviewed by {{ $report->reviewer?->name ?? 'unknown' }} on {{ $report->reviewed_at->format('M j, Y g:i A') }}@if($report->review_notes) — “{{ $report->review_notes }}”@endif</p>@endif
                </div>
            </article>

            <section>
                <div class="mb-3 flex items-center justify-between gap-3"><div><span class="kicker">Image matching</span><h2 class="section-title text-lg">Potential matches (photographs)</h2></div>
                    @if ($report->images->isNotEmpty())<form method="POST" action="{{ route('admin.reports.rematch', $report) }}" data-loading="Matching…">@csrf<button type="submit" class="btn btn-secondary btn-sm"><x-icon name="refresh" class="h-4 w-4" />Run matching again</button></form>@endif</div>
                @forelse ($matches as $m)
                    @php $other = $report->isLost() ? $m->foundReport : $m->lostReport; @endphp
                    <div class="card mb-3 p-3 sm:p-4">
                        <div class="flex gap-4"><x-item-photo :report="$other" class="w-24 shrink-0 rounded-lg" />
                            <div class="min-w-0 flex-1"><a href="{{ route('admin.reports.show', $other) }}" class="font-semibold text-navy-900 hover:text-navy-600">{{ $other->item_name }}</a><p class="text-xs text-slate-500">{{ $other->reference_code }} · {{ $other->location_description }}</p><div class="mt-1"><x-status-badge :status="$m->match_status" /></div></div></div>
                        <div class="mt-3"><x-match-evidence :match="$m" :admin="true" /></div>
                    </div>
                @empty
                    <x-empty-state icon="compare" title="No photograph-based matches stored" text="{{ $report->images->isEmpty() ? 'This report has no photograph.' : 'Either nothing met the SIFT/ORB criteria or matching has not run yet.' }}" />
                @endforelse
            </section>

            @if ($textMatches->isNotEmpty())
                <section><h2 class="section-title mb-3 text-lg">Possible matches (descriptions)</h2>
                    <div class="grid gap-3 sm:grid-cols-2">@foreach ($textMatches as $t)<a href="{{ route('admin.reports.show', $t['report']) }}" class="card card-hover flex gap-3 p-3"><x-item-photo :report="$t['report']" class="w-16 shrink-0 rounded-md" /><div class="min-w-0"><p class="truncate font-semibold text-navy-900">{{ $t['report']->item_name }}</p><p class="text-xs text-slate-500">Shared: {{ implode(', ', array_slice($t['shared'], 0, 5)) }}</p></div></a>@endforeach</div></section>
            @endif

            @if ($report->isFound())
                <section class="card overflow-hidden"><div class="panel-head"><h2 class="panel-title">Claims on this item</h2></div>
                    @forelse ($report->claims as $c)
                        <a href="{{ route('admin.claims.show', $c) }}" class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-3 last:border-0 hover:bg-slate-50"><span class="text-sm text-slate-700">#{{ $c->id }} · {{ $c->claimant->name }}</span><x-status-badge :status="$c->status" /></a>
                    @empty
                        <p class="px-5 py-6 text-center text-sm text-slate-500">No claims yet.</p>
                    @endforelse
                </section>
            @endif
        </div>

        <aside class="space-y-6">
            <section class="card card-pad">
                <span class="kicker">Decision</span><h2 class="section-title text-lg">Change status</h2>
                @if (count($transitions))
                    <form method="POST" action="{{ route('admin.reports.status', $report) }}" class="mt-3 space-y-3" data-loading="Saving…">
                        @csrf
                        <div><label for="status" class="label">New status</label><select id="status" name="status" class="input" required>@foreach ($transitions as $t)<option value="{{ $t->value }}">{{ $t->label() }}</option>@endforeach</select></div>
                        <div><label for="notes" class="label">Notes <span class="opt">(required when rejecting)</span></label><textarea id="notes" name="notes" rows="3" class="input" maxlength="1000"></textarea></div>
                        <button class="btn btn-primary btn-block">Update status</button>
                    </form>
                @else
                    <p class="callout mt-3">This report is {{ strtolower($report->report_status->label()) }} and cannot be changed further.</p>
                @endif
            </section>
        </aside>
    </div>
</x-layouts.admin>
