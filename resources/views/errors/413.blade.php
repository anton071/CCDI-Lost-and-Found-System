<x-layouts.app title="413">
    <div class="mx-auto flex max-w-xl flex-col items-center px-4 py-16 text-center sm:py-24">
        <x-brand size="h-16" />
        <p class="mt-8 text-6xl font-bold tracking-tight text-navy-800 tabular-nums">413<span class="text-red-500">.</span></p>
        <h1 class="mt-3 text-xl font-bold text-navy-900 sm:text-2xl">That upload is too large for the server</h1>
        <p class="mt-2 max-w-md text-sm text-slate-500">Photos can be up to {{ config('ccdi.uploads.max_kb') / 1024 }} MB. If your photo is smaller than that, ask the administrator to raise PHP&rsquo;s <span class="font-mono">upload_max_filesize</span> and <span class="font-mono">post_max_size</span> (see the README).</p>
        <div class="mt-8 flex flex-col gap-3 sm:flex-row"><a href="javascript:history.back()" class="btn btn-primary">Go back</a><a href="{{ route('home') }}" class="btn btn-secondary">Back to home</a></div>
    </div>
</x-layouts.app>
