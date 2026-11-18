<x-layouts.member title="Notifications">
    <x-page-header kicker="My account" title="Notifications" text="Status changes on your reports, claims and possible matches.">
        @if (auth()->user()->unreadNotifications()->exists())<form method="POST" action="{{ route('notifications.read-all') }}">@csrf<button class="btn btn-secondary btn-sm"><x-icon name="check" class="h-4 w-4" />Mark all as read</button></form>@endif
    </x-page-header>
    @if ($notifications->isEmpty())
        <x-empty-state icon="bell" title="You're all caught up" text="Status changes on your reports and claims appear here." />
    @else
        <div class="card overflow-hidden"><ul class="divide-y divide-slate-200">@foreach ($notifications as $n)
            <li><a href="{{ route('notifications.open', $n->id) }}" class="relative flex items-start gap-3 px-4 py-4 hover:bg-slate-50 sm:px-5 {{ $n->read_at ? '' : 'bg-navy-50/50' }}">
                @unless ($n->read_at)<span class="absolute inset-y-0 left-0 w-1 bg-red-500" aria-hidden="true"></span>@endunless
                <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-md {{ $n->read_at ? 'bg-slate-100 text-slate-400' : 'bg-navy-800 text-white' }}"><x-icon name="bell" class="h-4.5 w-4.5" /></span>
                <div class="min-w-0 flex-1"><p class="text-sm {{ $n->read_at ? 'font-medium text-slate-700' : 'font-bold text-navy-900' }}">{{ $n->data['title'] ?? 'Update' }}@unless ($n->read_at)<span class="sr-only"> (unread)</span>@endunless</p>@if (!empty($n->data['body']))<p class="mt-0.5 text-sm text-slate-500">{{ $n->data['body'] }}</p>@endif<p class="mt-1 text-xs text-slate-400">{{ $n->created_at->diffForHumans() }}</p></div>
                <x-icon name="chevron-right" class="mt-2 h-4 w-4 shrink-0 text-slate-400" /></a></li>
        @endforeach</ul></div>
        <div>{{ $notifications->links() }}</div>
    @endif
</x-layouts.member>
