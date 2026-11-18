<x-layouts.admin title="Reports" crumb="Reports">
    <x-page-header kicker="Administration" title="Reports" text="Review, publish or reject lost and found reports." />
    <form method="GET" class="card" role="search">
        <div class="grid gap-4 p-4 sm:grid-cols-2 sm:p-5 lg:grid-cols-12">
            <div class="sm:col-span-2 lg:col-span-5"><label for="q" class="label">Search</label><input id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="input" placeholder="Name, brand, colour, location…"></div>
            <div class="lg:col-span-3"><label for="type" class="label">Type</label><select id="type" name="type" class="input"><option value="">All</option><option value="lost" @selected(($filters['type'] ?? '') === 'lost')>Lost</option><option value="found" @selected(($filters['type'] ?? '') === 'found')>Found</option></select></div>
            <div class="lg:col-span-4"><label for="status" class="label">Status</label><select id="status" name="status" class="input"><option value="">All</option>@foreach ($statuses as $s)<option value="{{ $s->value }}" @selected(($filters['status'] ?? '') === $s->value)>{{ $s->label() }}</option>@endforeach</select></div>
        </div>
        <div class="flex gap-2 border-t border-slate-200 bg-slate-50 px-4 py-3 sm:px-5"><button class="btn btn-primary btn-sm"><x-icon name="search" class="h-4 w-4" />Filter</button>@if (array_filter($filters))<a href="{{ route('admin.reports.index') }}" class="btn btn-secondary btn-sm">Clear filters</a>@endif</div>
    </form>
    @if ($reports->isEmpty())
        <x-empty-state icon="file-text" title="No reports found" text="Change the filters, or wait for members to submit reports." />
    @else
        <div class="card table-card">
            <table class="data-table table-stack">
                <thead><tr><th>Item</th><th>Type</th><th>Status</th><th>Reported by</th><th>Date</th><th><span class="sr-only">Open</span></th></tr></thead>
                <tbody>
                @foreach ($reports as $r)
                    <tr>
                        <td class="cell-main"><div class="flex items-center gap-3"><x-item-photo :report="$r" class="h-12 w-12 shrink-0 rounded-md" ratio="" compact /><div class="min-w-0"><p class="truncate font-semibold text-navy-900 md:max-w-xs">{{ $r->item_name }}</p><p class="font-mono text-xs text-slate-500">{{ $r->reference_code }}</p></div></div></td>
                        <td data-label="Type"><x-status-badge :status="$r->report_type" /></td>
                        <td data-label="Status"><x-status-badge :status="$r->report_status" /></td>
                        <td data-label="Reported by" class="text-slate-600">{{ $r->submitter?->name ?? 'Deleted user' }}</td>
                        <td data-label="Date" class="whitespace-nowrap text-slate-600">{{ $r->incident_date->format('M j, Y') }}</td>
                        <td data-label="" class="text-right"><a href="{{ route('admin.reports.show', $r) }}" class="btn btn-secondary btn-sm">Review report</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div>{{ $reports->links() }}</div>
    @endif
</x-layouts.admin>
