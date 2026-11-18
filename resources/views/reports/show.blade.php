@php $isLost = $report->isLost(); @endphp
<x-layouts.app :title="$report->item_name">
    <div class="mx-auto max-w-[1600px] px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
        <nav class="mb-5 flex items-center gap-1.5 text-sm text-slate-500" aria-label="Breadcrumb">
            <a href="{{ route('items.index') }}" class="inline-flex min-h-11 items-center font-semibold hover:text-navy-800">Browse items</a><x-icon name="chevron-right" class="h-3.5 w-3.5" /><span class="font-mono text-xs">{{ $report->reference_code }}</span>
        </nav>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <article class="card overflow-hidden">
                    <x-item-photo :report="$report" ratio="aspect-[16/10]" />
                    <div class="card-pad space-y-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <x-status-badge :status="$report->report_type" />
                            <x-status-badge :status="$report->report_status" />
                            @if ($report->category)<span class="badge badge-slate">{{ $report->category->name }}</span>@endif
                        </div>
                        <h1 class="page-title">{{ $report->item_name }}</h1>
                        <dl class="grid gap-4 border-y border-slate-200 py-4 text-sm sm:grid-cols-2">
                            <div class="flex items-start gap-2"><x-icon name="map-pin" class="mt-1 h-4 w-4 text-navy-400" /><div><dt class="eyebrow !text-[0.6875rem]">{{ $isLost ? 'Lost at' : 'Found at' }}</dt><dd class="font-semibold text-slate-800">{{ $report->location_description }}</dd></div></div>
                            <div class="flex items-start gap-2"><x-icon name="calendar" class="mt-1 h-4 w-4 text-navy-400" /><div><dt class="eyebrow !text-[0.6875rem]">Date</dt><dd class="font-semibold text-slate-800">{{ $report->incident_date->format('F j, Y') }}{{ $report->incident_time ? ' · '.\Illuminate\Support\Carbon::parse($report->incident_time)->format('g:i A') : '' }}</dd></div></div>
                            @if ($report->brand)<div><dt class="eyebrow !text-[0.6875rem]">Brand</dt><dd class="font-semibold text-slate-800">{{ $report->brand }}</dd></div>@endif
                            @if ($report->color)<div><dt class="eyebrow !text-[0.6875rem]">Colour</dt><dd class="font-semibold text-slate-800">{{ $report->color }}</dd></div>@endif
                        </dl>
                        <div><h2 class="eyebrow">Description</h2><p class="mt-1.5 whitespace-pre-line text-[0.9375rem] leading-relaxed text-slate-700">{{ $report->description }}</p></div>

                        @if ($canSeePrivate)
                            <div class="callout !border-l-navy-800 !bg-slate-50 !text-slate-800 flex-col !items-stretch gap-0">
                                <p class="flex items-center gap-2 text-xs font-bold tracking-wide text-navy-800 uppercase"><x-icon name="lock" class="h-4 w-4" />Visible only to you and administrators</p>
                                <p class="mt-2"><span class="text-slate-500">Reference:</span> <span class="font-mono">{{ $report->reference_code }}</span></p>
                                <p class="mt-1 whitespace-pre-line"><span class="text-slate-500">Private details:</span> {{ $report->identifying_features ?: '—' }}</p>
                                @if ($report->rejection_reason)<p class="callout callout-danger mt-3"><span><span class="font-bold">Rejected:</span> {{ $report->rejection_reason }}</span></p>@endif
                                @if ($report->report_status->value === 'pending_review')<p class="callout callout-warn mt-3">Waiting for administrator review before it is listed publicly.</p>@endif
                            </div>
                        @endif
                    </div>
                </article>

                @if ($isLost && $canSeePrivate)
                    <section aria-labelledby="matches-h">
                        <span class="kicker">Image matching</span><h2 id="matches-h" class="section-title text-lg">Possible matches from photographs</h2>
                        <p class="mt-1 mb-4 text-sm text-slate-500">Suggested by feature matching (SIFT, with ORB as a fallback). These are <strong>potential matches only</strong>; ownership is verified through a claim.</p>
                        @forelse ($imageMatches as $m)
                            @php $other = $m->foundReport; @endphp
                            <article class="card mb-4 overflow-hidden">
                                <div class="grid items-center gap-3 p-4 sm:grid-cols-[1fr_auto_1fr] sm:gap-4">
                                    <div class="min-w-0"><p class="eyebrow mb-1.5">Your lost item</p><x-item-photo :report="$report" class="rounded-md" /><p class="mt-2 truncate text-sm font-semibold text-navy-900">{{ $report->item_name }}</p></div>
                                    <span class="mx-auto flex h-9 w-9 items-center justify-center rounded-md bg-navy-50 text-navy-600" aria-hidden="true"><x-icon name="compare" class="h-5 w-5 rotate-90 sm:rotate-0" /></span>
                                    <div class="min-w-0"><p class="eyebrow mb-1.5">Possible match &middot; found</p><x-item-photo :report="$other" class="rounded-md" /><a href="{{ route('reports.show', $other) }}" class="mt-2 block truncate text-sm font-semibold text-navy-900 hover:text-navy-600">{{ $other->item_name }}</a></div>
                                </div>
                                <div class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-200 px-4 py-2.5 text-xs text-slate-500">
                                    <span class="truncate">{{ $other->location_description }} &middot; {{ $other->incident_date->format('M j, Y') }}</span>
                                    <span class="flex items-center gap-2"><x-status-badge :status="$m->match_status" /><a href="{{ route('reports.show', $other) }}" class="btn btn-secondary btn-sm">View possible match</a></span>
                                </div>
                                <div class="border-t border-slate-200 p-3 sm:p-4"><x-match-evidence :match="$m" /></div>
                            </article>
                        @empty
                            <x-empty-state icon="compare" title="No photograph-based matches yet" text="Either this report has no photograph, no published found item looked similar, or matching has not run. Text-based suggestions are shown below." />
                        @endforelse
                    </section>
                @endif

                @if ($canSeePrivate && $textMatches->isNotEmpty())
                    <section aria-labelledby="text-h">
                        <span class="kicker">Text matching</span><h2 id="text-h" class="section-title text-lg">Possible matches from descriptions</h2>
                        <p class="mt-1 mb-4 text-sm text-slate-500">Based on shared words in names, brands, colours and descriptions. They work even without photographs.</p>
                        <div class="grid gap-4 sm:grid-cols-2">
                            @foreach ($textMatches as $t)
                                <a href="{{ route('reports.show', $t['report']) }}" class="card card-hover flex gap-3 p-3">
                                    <x-item-photo :report="$t['report']" class="w-20 shrink-0 rounded-md" />
                                    <div class="min-w-0"><p class="truncate font-semibold text-navy-900">{{ $t['report']->item_name }}</p><p class="text-xs text-slate-500">Shared: {{ implode(', ', array_slice($t['shared'], 0, 5)) }}</p></div>
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>

            <aside class="space-y-6">
                @if ($canClaim)
                    <section class="card card-pad" aria-labelledby="claim-h">
                        <span class="kicker">Claim</span><h2 id="claim-h" class="section-title text-lg">This looks like mine</h2>
                        @if ($myOpenClaim)
                            <p class="mt-2 text-sm text-slate-500">You already have a claim for this item (<x-status-badge :status="$myOpenClaim->status" />).</p>
                            <a href="{{ route('claims.show', $myOpenClaim) }}" class="btn btn-secondary btn-sm mt-3">View my claim</a>
                        @else
                            <p class="mt-1 text-sm text-slate-500">Describe details only the owner would know. An administrator will verify your claim before releasing anything.</p>
                            <form method="POST" action="{{ route('claims.store', $report) }}" class="mt-4 space-y-3" data-loading="Submitting claim…">
                                @csrf
                                <div>
                                    <label for="claim_message" class="label">Proof of ownership</label>
                                    <textarea id="claim_message" name="claim_message" rows="5" required minlength="20" maxlength="2000" class="input @error('claim_message') input-error @enderror" placeholder="Contents, scratches, serial number, when and where you lost it…">{{ old('claim_message') }}</textarea>
                                    @error('claim_message')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                                </div>
                                @if ($myLostReports->isNotEmpty())
                                    <div>
                                        <label for="related_lost_report_id" class="label">Link to my lost report <span class="font-normal text-slate-400">(optional)</span></label>
                                        <select id="related_lost_report_id" name="related_lost_report_id" class="input"><option value="">None</option>@foreach ($myLostReports as $lr)<option value="{{ $lr->id }}" @selected((string) old('related_lost_report_id') === (string) $lr->id)>{{ $lr->item_name }} ({{ $lr->reference_code }})</option>@endforeach</select>
                                    </div>
                                @endif
                                <button type="submit" class="btn btn-primary btn-block">Submit claim</button>
                            </form>
                        @endif
                    </section>
                @elseif (! auth()->check() && ! $isLost)
                    <section class="card card-pad"><h2 class="section-title">Is this yours?</h2><p class="mt-1 text-sm text-slate-500">Sign in to view photos and submit a claim.</p><a href="{{ route('login') }}" class="btn btn-primary btn-sm mt-3">Sign in</a></section>
                @endif

                <section class="callout">
                    <x-icon name="shield-check" class="mt-0.5 h-5 w-5 shrink-0 text-navy-600" /><div><h2 class="font-bold">How releases work</h2>
                    <p class="mt-1 text-[0.8125rem]">Only authorized CCDI personnel can approve a claim and release an item. A photo match or a similar description never proves ownership by itself.</p></div>
                </section>
            </aside>
        </div>
    </div>
</x-layouts.app>
