<x-layouts.member title="My claims">
    <x-page-header kicker="My account" title="My claims" text="Claims you submitted for found items, and where each one stands." />
    @if ($claims->isEmpty())
        <x-empty-state icon="shield-check" title="No claims yet" text="When you recognize a found item, open it and submit a claim.">
            <a href="{{ route('items.index', ['type' => 'found']) }}" class="btn btn-primary btn-sm">Browse found items</a>
        </x-empty-state>
    @else
        <div class="card overflow-hidden"><ul class="divide-y divide-slate-200">@foreach ($claims as $c)
            <li><a href="{{ route('claims.show', $c) }}" class="flex items-center gap-3 p-3 hover:bg-slate-50 sm:gap-4 sm:p-4">
                <x-item-photo :report="$c->foundReport" class="w-16 shrink-0 rounded-md sm:w-24" />
                <div class="min-w-0 flex-1"><p class="truncate font-semibold text-navy-900">{{ $c->foundReport->item_name }}</p><p class="text-xs text-slate-500">Claim #{{ $c->id }} &middot; {{ $c->created_at->format('M j, Y') }}</p><div class="mt-1.5 sm:hidden"><x-status-badge :status="$c->status" /></div></div>
                <div class="hidden sm:block"><x-status-badge :status="$c->status" /></div>
                <x-icon name="chevron-right" class="h-5 w-5 shrink-0 text-slate-400" /></a></li>
        @endforeach</ul></div>
        <div>{{ $claims->links() }}</div>
    @endif
</x-layouts.member>
