<x-layouts.member :title="'Claim #'.$claim->id" :crumb="'Claim #'.$claim->id">
    <a href="{{ route('claims.index') }}" class="inline-flex min-h-11 items-center gap-1.5 text-sm font-semibold text-slate-500 hover:text-navy-800"><x-icon name="arrow-left" class="h-4 w-4" />My claims</a>
    <div class="mx-auto max-w-5xl space-y-5">
        <x-page-header kicker="Claim" :title="'Claim #'.$claim->id"><x-status-badge :status="$claim->status" class="!text-sm" /></x-page-header>

        <section class="card card-pad space-y-5">
            <a href="{{ route('reports.show', $claim->foundReport) }}" class="card-hover flex items-center gap-4 rounded-lg border border-slate-200 p-3">
                <x-item-photo :report="$claim->foundReport" class="w-24 shrink-0 rounded-md" />
                <div class="min-w-0"><p class="eyebrow !text-[0.6875rem]">Item you claimed</p><p class="font-semibold text-navy-900">{{ $claim->foundReport->item_name }}</p><p class="text-xs text-slate-500">{{ $claim->foundReport->location_description }} &middot; {{ $claim->foundReport->incident_date->format('M j, Y') }}</p></div>
            </a>
            <div><h2 class="eyebrow">What you told us</h2><p class="mt-1.5 whitespace-pre-line text-[0.9375rem] leading-relaxed text-slate-700">{{ $claim->claim_message }}</p></div>
            @switch($claim->status->value)
                @case('pending') <p class="callout callout-warn"><x-icon name="clock" class="mt-0.5 h-5 w-5 shrink-0" />Waiting for an administrator to review your claim.</p> @break
                @case('under_review') <p class="callout"><x-icon name="eye" class="mt-0.5 h-5 w-5 shrink-0" />An administrator is reviewing your claim.</p> @break
                @case('approved') <p class="callout callout-success"><x-icon name="check-circle" class="mt-0.5 h-5 w-5 shrink-0" />Approved. Bring a valid ID to the lost-and-found office to receive the item. The handover is recorded by the administrator.</p> @break
                @case('rejected') <p class="callout callout-danger"><x-icon name="x-circle" class="mt-0.5 h-5 w-5 shrink-0" /><span><strong>Not approved.</strong> {{ $claim->review_notes }}</span></p> @break
            @endswitch
            @if ($claim->release)<p class="callout callout-success"><x-icon name="package" class="mt-0.5 h-5 w-5 shrink-0" /><span>Item released on {{ $claim->release->released_at->format('F j, Y g:i A') }} &middot; Reference <span class="font-mono">{{ $claim->release->release_reference }}</span></span></p>@endif
            @can('cancel', $claim)
                <form method="POST" action="{{ route('claims.cancel', $claim) }}" data-confirm="Cancel this claim?" class="border-t border-slate-200 pt-5">@csrf<button class="btn btn-danger btn-sm">Cancel claim</button></form>
            @endcan
        </section>
    </div>
</x-layouts.member>
