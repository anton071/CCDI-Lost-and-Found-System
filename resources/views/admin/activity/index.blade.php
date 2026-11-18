<x-layouts.admin title="Activity log" crumb="Activity log">
    <x-page-header kicker="Administration" title="Activity log" text="An audit trail of actions taken in the system." />
    <form method="GET" class="card flex flex-col gap-3 p-4 sm:flex-row sm:items-end"><div class="sm:w-80"><label for="action" class="label">Action contains</label><input id="action" name="action" value="{{ $action }}" class="input" placeholder="claim., report., user."></div><div class="flex gap-2"><button class="btn btn-primary btn-sm"><x-icon name="search" class="h-4 w-4" />Filter</button>@if ($action)<a href="{{ route('admin.activity.index') }}" class="btn btn-secondary btn-sm">Clear</a>@endif</div></form>
    @if ($logs->isEmpty())
        <x-empty-state icon="activity" title="No activity recorded" />
    @else
        <div class="card table-card"><table class="data-table table-stack">
            <thead><tr><th>When</th><th>Who</th><th>Action</th><th>Details</th></tr></thead>
            <tbody>@foreach ($logs as $l)<tr><td data-label="When" class="whitespace-nowrap text-slate-600">{{ $l->created_at?->format('M j, Y H:i') }}</td><td data-label="Who">{{ $l->user?->name ?? 'System' }}</td><td data-label="Action"><span class="badge badge-slate font-mono">{{ $l->action }}</span></td><td data-label="Details" class="text-slate-600 md:max-w-md">{{ $l->description }}</td></tr>@endforeach</tbody></table></div>
        <div>{{ $logs->links() }}</div>
    @endif
</x-layouts.admin>
