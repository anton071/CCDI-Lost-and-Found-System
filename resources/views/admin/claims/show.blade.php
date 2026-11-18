@php $found = $claim->foundReport; $st = $claim->status->value; @endphp
<x-layouts.admin :title="'Claim #'.$claim->id" crumb="Claims / #{{ $claim->id }}">
    <a href="{{ route('admin.claims.index') }}" class="inline-flex min-h-11 items-center gap-1.5 text-sm font-semibold text-slate-500 hover:text-navy-800"><x-icon name="arrow-left" class="h-4 w-4" />All claims</a>
    <x-page-header kicker="Claim review" :title="'Claim #'.$claim->id"><x-status-badge :status="$claim->status" class="!text-sm" /></x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="card card-pad space-y-3">
                <h2 class="section-title">Claimant</h2>
                <p class="text-sm text-slate-700"><strong>{{ $claim->claimant->name }}</strong> · {{ $claim->claimant->email }} · {{ $claim->claimant->role->label() }}@if($claim->claimant->student_number) · ID {{ $claim->claimant->student_number }}@endif @if($claim->claimant->contact_number) · {{ $claim->claimant->contact_number }}@endif</p>
                <div><h3 class="eyebrow">Claimant's statement</h3><p class="mt-1.5 whitespace-pre-line rounded-md border-l-4 border-navy-600 bg-slate-50 p-3 text-sm text-slate-700">{{ $claim->claim_message }}</p></div>
                @if ($claim->relatedLostReport)<p class="text-sm text-slate-600">Linked lost report: <a class="link" href="{{ route('admin.reports.show', $claim->relatedLostReport) }}">{{ $claim->relatedLostReport->item_name }}</a></p>@endif
            </section>

            <section class="card overflow-hidden">
                <div class="grid sm:grid-cols-2"><x-item-photo :report="$found" ratio="aspect-[4/3]" />
                    <div class="space-y-1.5 p-5 text-sm"><h2 class="section-title">Found item</h2><a href="{{ route('admin.reports.show', $found) }}" class="link">{{ $found->item_name }}</a><p class="font-mono text-xs text-slate-500">{{ $found->reference_code }}</p><p class="text-slate-600">{{ $found->location_description }} · {{ $found->incident_date->format('M j, Y') }}</p><p class="text-slate-600">Finder: {{ $found->submitter?->name ?? '—' }}</p></div></div>
                <div class="border-t border-slate-200 bg-slate-50 p-5 text-sm"><h3 class="eyebrow flex items-center gap-1.5"><x-icon name="lock" class="h-3.5 w-3.5" />Private verification details recorded by the finder</h3><p class="mt-1.5 whitespace-pre-line text-slate-700">{{ $found->identifying_features ?: 'None recorded.' }}</p><p class="mt-3 text-xs text-slate-500">Compare these with the claimant's statement. Photo similarity alone is not verification.</p></div>
            </section>

            @if ($matches->isNotEmpty())
                <section><h2 class="section-title mb-3 text-lg">Related potential matches (reference only)</h2>
                    @foreach ($matches as $m)<div class="card mb-3 p-4"><p class="mb-2 text-sm font-medium text-navy-900">Lost report: {{ $m->lostReport->item_name }}</p><x-match-evidence :match="$m" :admin="true" /></div>@endforeach</section>
            @endif
        </div>

        <aside class="space-y-4">
            @if ($claim->review_notes)<section class="card card-pad text-sm"><h2 class="section-title">Review notes</h2><p class="mt-1 whitespace-pre-line text-slate-700">{{ $claim->review_notes }}</p><p class="mt-2 text-xs text-slate-500">{{ $claim->reviewer?->name }} · {{ $claim->reviewed_at?->format('M j, Y g:i A') }}</p></section>@endif
            @if ($claim->release)<section class="card card-pad text-sm"><h2 class="section-title">Handover recorded</h2><p class="mt-1">Reference <span class="font-mono">{{ $claim->release->release_reference }}</span></p><p class="text-slate-600">{{ $claim->release->released_at->format('M j, Y g:i A') }} by {{ $claim->release->releasedBy?->name }}</p></section>@endif

            @if (! $canAct)
                <section class="callout callout-warn">You cannot act on your own claim. Another administrator must review it.</section>
            @elseif (in_array($st, ['pending', 'under_review']))
                <section class="card card-pad space-y-3">
                    <h2 class="section-title">Decision</h2>
                    @if ($st === 'pending')<form method="POST" action="{{ route('admin.claims.under-review', $claim) }}">@csrf<button type="submit" class="btn btn-secondary btn-block"><x-icon name="eye" class="h-4 w-4" />Mark as under review</button></form>@endif
                    <form method="POST" action="{{ route('admin.claims.approve', $claim) }}" class="space-y-2" data-confirm="Approve this claim? The claimant will be told to collect the item.">@csrf
                        <label for="a-notes" class="label">Verification notes</label><textarea id="a-notes" name="notes" rows="3" class="input" maxlength="1000" placeholder="What did you verify (ID, description, proof)?"></textarea>
                        <button class="btn btn-success btn-block"><x-icon name="check" class="h-4 w-4" />Approve claim</button></form>
                    <form method="POST" action="{{ route('admin.claims.reject', $claim) }}" class="space-y-2 border-t border-slate-200 pt-4" data-confirm="Reject this claim?">@csrf
                        <label for="r-reason" class="label">Reason for rejection <span class="req" aria-hidden="true">*</span></label><textarea id="r-reason" name="reason" rows="3" required minlength="5" maxlength="1000" class="input @error('reason') input-error @enderror"></textarea>@error('reason')<p class="field-error">{{ $message }}</p>@enderror
                        <button class="btn btn-danger btn-block"><x-icon name="x-circle" class="h-4 w-4" />Reject claim</button></form>
                </section>
            @elseif ($st === 'approved' && ! $claim->release)
                <section class="card card-pad space-y-3"><h2 class="section-title">Record handover</h2><p class="text-sm text-slate-600">Use this only after you have physically handed the item to the verified claimant.</p>
                    <form method="POST" action="{{ route('admin.claims.release', $claim) }}" class="space-y-2" data-confirm="Record the item as released to this claimant? This closes the found report.">@csrf
                        <label for="rel-notes" class="label">Handover notes</label><textarea id="rel-notes" name="notes" rows="3" class="input" maxlength="1000" placeholder="ID checked, who received the item…"></textarea>
                        <button class="btn btn-primary btn-block"><x-icon name="package" class="h-4 w-4" />Record release</button></form></section>
            @endif
        </aside>
    </div>
</x-layouts.admin>
