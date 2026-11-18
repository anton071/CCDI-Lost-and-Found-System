<x-layouts.admin title="Claims" crumb="Claims">
    <x-page-header kicker="Administration" title="Claims" text="Verify ownership claims before any item is released." />
    <form method="GET" class="card flex flex-col gap-3 p-4 sm:flex-row sm:items-end"><div class="sm:w-64"><label for="status" class="label">Status</label><select id="status" name="status" class="input" onchange="this.form.submit()"><option value="">All</option>@foreach ($statuses as $s)<option value="{{ $s->value }}" @selected($status === $s->value)>{{ $s->label() }}</option>@endforeach</select></div><noscript><button class="btn btn-primary btn-sm">Filter</button></noscript></form>
    @if ($claims->isEmpty())
        <x-empty-state icon="shield-check" title="No claims" text="Claims submitted for found items appear here." />
    @else
        <div class="card table-card"><table class="data-table table-stack">
            <thead><tr><th>Claim</th><th>Item</th><th>Claimant</th><th>Status</th><th>Submitted</th><th><span class="sr-only">Open</span></th></tr></thead>
            <tbody>@foreach ($claims as $c)
                <tr><td data-label="Claim" class="font-mono text-xs">#{{ $c->id }}</td><td data-label="Item" class="font-semibold text-navy-900 md:max-w-xs md:truncate">{{ $c->foundReport->item_name }}</td><td data-label="Claimant">{{ $c->claimant->name }}</td><td data-label="Status"><x-status-badge :status="$c->status" /></td><td data-label="Submitted" class="whitespace-nowrap text-slate-600">{{ $c->created_at->format('M j, Y') }}</td><td data-label="" class="text-right"><a href="{{ route('admin.claims.show', $c) }}" class="btn btn-secondary btn-sm">Review claim</a></td></tr>
            @endforeach</tbody></table></div>
        <div>{{ $claims->links() }}</div>
    @endif
</x-layouts.admin>
