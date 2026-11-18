@props(['icon' => 'inbox', 'title', 'text' => null])
<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center rounded-lg border border-dashed border-slate-300 bg-white px-6 py-12 text-center']) }}>
    <span class="mb-3 flex h-12 w-12 items-center justify-center rounded-md bg-navy-50 text-navy-600"><x-icon :name="$icon" class="h-6 w-6" /></span>
    <h3 class="font-semibold text-navy-900">{{ $title }}</h3>
    @if ($text)<p class="mt-1 max-w-md text-sm text-slate-500">{{ $text }}</p>@endif
    @if (! $slot->isEmpty())<div class="mt-5 flex flex-wrap justify-center gap-2">{{ $slot }}</div>@endif
</div>
