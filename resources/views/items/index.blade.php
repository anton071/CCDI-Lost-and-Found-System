<x-layouts.app title="Browse items">
    <div class="mx-auto max-w-[1600px] px-4 py-8 sm:px-6 lg:px-8">
        <x-page-header kicker="Public listing" title="Browse lost & found items" text="Published reports only. Search covers names, descriptions, brands, colours and locations." />

        <form method="GET" action="{{ route('items.index') }}" class="card mt-6" role="search">
            <div class="grid gap-4 p-4 sm:p-5 md:grid-cols-2 lg:grid-cols-12">
                <div class="md:col-span-2 lg:col-span-4">
                    <label for="q" class="label">Search</label>
                    <input id="q" name="q" type="search" value="{{ $filters['q'] ?? '' }}" class="input" placeholder="e.g. black umbrella" maxlength="100">
                </div>
                <div class="lg:col-span-2">
                    <label for="type" class="label">Type</label>
                    <select id="type" name="type" class="input"><option value="">All</option><option value="lost" @selected(($filters['type'] ?? '') === 'lost')>Lost</option><option value="found" @selected(($filters['type'] ?? '') === 'found')>Found</option></select>
                </div>
                <div class="lg:col-span-3">
                    <label for="category" class="label">Category</label>
                    <select id="category" name="category" class="input"><option value="">All</option>@foreach ($categories as $c)<option value="{{ $c->id }}" @selected((string) ($filters['category'] ?? '') === (string) $c->id)>{{ $c->name }}</option>@endforeach</select>
                </div>
                <div class="lg:col-span-3">
                    <label for="location" class="label">Location</label>
                    <input id="location" name="location" value="{{ $filters['location'] ?? '' }}" class="input" placeholder="e.g. library" maxlength="100">
                </div>
                <div class="lg:col-span-3"><label for="from" class="label">From date</label><input id="from" name="from" type="date" value="{{ $filters['from'] ?? '' }}" class="input"></div>
                <div class="lg:col-span-3"><label for="to" class="label">To date</label><input id="to" name="to" type="date" value="{{ $filters['to'] ?? '' }}" class="input @error('to') input-error @enderror">@error('to')<p class="field-error">{{ $message }}</p>@enderror</div>
            </div>
            <div class="flex flex-wrap items-center gap-3 border-t border-slate-200 bg-slate-50 px-4 py-3 sm:px-5">
                <button class="btn btn-primary btn-sm" type="submit"><x-icon name="search" class="h-4 w-4" />Search items</button>
                @if (array_filter($filters))<a href="{{ route('items.index') }}" class="btn btn-secondary btn-sm">Clear filters</a>@endif
                <span class="ml-auto text-sm font-semibold text-slate-600">{{ $items->total() }} {{ \Illuminate\Support\Str::plural('result', $items->total()) }}</span>
            </div>
        </form>

        <div class="mt-6">
            @if ($items->isEmpty())
                <x-empty-state icon="search" title="No items match your search" text="Try fewer words, another category, or a wider date range. New reports appear here once published.">
                    @auth<a href="{{ route('reports.create', 'lost') }}" class="btn btn-primary btn-sm">Report a lost item</a>@else<a href="{{ route('register') }}" class="btn btn-primary btn-sm">Create an account to report</a>@endauth
                </x-empty-state>
            @else
                <div class="grid grid-cols-1 gap-4 min-[520px]:grid-cols-2 sm:gap-5 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5">@foreach ($items as $item)<x-item-card :item="$item" />@endforeach</div>
                <div class="mt-8">{{ $items->links() }}</div>
            @endif
        </div>
    </div>
</x-layouts.app>
