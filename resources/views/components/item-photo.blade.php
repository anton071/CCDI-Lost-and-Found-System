@props(['report', 'ratio' => 'aspect-[4/3]', 'compact' => false])
@php
    $img = $report->primaryImage;
    $visible = $img && \Illuminate\Support\Facades\Gate::allows('viewImage', $report);
@endphp
<div {{ $attributes->merge(['class' => "photo-frame $ratio"]) }}>
    @if ($visible)
        <img src="{{ route('images.show', $img) }}" alt="Photograph of {{ $report->item_name }}" loading="lazy" class="h-full w-full object-cover">
    @elseif ($img)
        <div class="flex h-full w-full flex-col items-center justify-center gap-1.5 text-navy-400">
            <x-icon name="lock" class="{{ $compact ? 'h-4 w-4' : 'h-6 w-6' }}" />
            @unless ($compact)<span class="px-3 text-center text-xs font-semibold">Sign in to view the photo</span>@endunless
        </div>
    @else
        <div class="flex h-full w-full flex-col items-center justify-center gap-1.5 text-navy-300">
            <x-icon name="image" class="{{ $compact ? 'h-5 w-5' : 'h-7 w-7' }}" />
            @unless ($compact)<span class="text-xs font-semibold">No photo</span>@endunless
        </div>
    @endif
</div>
