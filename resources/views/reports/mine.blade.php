<x-layouts.member title="My reports">
    <x-page-header kicker="My account" title="My reports" text="Everything you have reported, with its current status.">
        <a href="{{ route('reports.create', 'lost') }}" class="btn btn-primary btn-sm"><x-icon name="plus" class="h-4 w-4" />Report lost</a>
        <a href="{{ route('reports.create', 'found') }}" class="btn btn-secondary btn-sm"><x-icon name="camera" class="h-4 w-4" />Report found</a>
    </x-page-header>

    @if ($reports->isEmpty())
        <x-empty-state icon="file-text" title="You have not submitted any reports" text="Reports you submit appear here with their current status.">
            <a href="{{ route('reports.create', 'lost') }}" class="btn btn-primary btn-sm">Report a lost item</a>
            <a href="{{ route('reports.create', 'found') }}" class="btn btn-secondary btn-sm">Report a found item</a>
        </x-empty-state>
    @else
        <div class="card overflow-hidden">
            <ul class="divide-y divide-slate-200">
                @foreach ($reports as $r)
                    <li>
                        <a href="{{ route('reports.show', $r) }}" class="flex items-center gap-3 p-3 transition-colors hover:bg-slate-50 sm:gap-4 sm:p-4">
                            <x-item-photo :report="$r" class="w-20 shrink-0 rounded-md sm:w-28" />
                            <div class="min-w-0 flex-1">
                                <p class="line-clamp-2 font-semibold text-navy-900 sm:truncate">{{ $r->item_name }}</p>
                                <p class="mt-0.5 text-xs text-slate-500"><span class="font-mono">{{ $r->reference_code }}</span> &middot; {{ $r->incident_date->format('M j, Y') }}</p>
                                <div class="mt-2 flex flex-wrap gap-1.5"><x-status-badge :status="$r->report_type" /><x-status-badge :status="$r->report_status" /></div>
                            </div>
                            <x-icon name="chevron-right" class="h-5 w-5 shrink-0 text-slate-400" />
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
        <div>{{ $reports->links() }}</div>
    @endif
</x-layouts.member>
