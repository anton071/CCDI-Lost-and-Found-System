<x-layouts.app title="403">
    <div class="mx-auto flex max-w-xl flex-col items-center px-4 py-16 text-center sm:py-24">
        <x-brand size="h-16" />
        <p class="mt-8 text-6xl font-bold tracking-tight text-navy-800 tabular-nums">403<span class="text-red-500">.</span></p>
        <h1 class="mt-3 text-xl font-bold text-navy-900 sm:text-2xl">You don't have access to this page</h1>
        <p class="mt-2 max-w-md text-sm text-slate-500">Check the address, or go back to a page you know.</p>
        <div class="mt-8 flex flex-col gap-3 sm:flex-row"><a href="{{ route('home') }}" class="btn btn-primary">Back to home</a><a href="{{ route('items.index') }}" class="btn btn-secondary">Browse items</a></div>
    </div>
</x-layouts.app>
