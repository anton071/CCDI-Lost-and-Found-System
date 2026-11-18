@props(['title', 'kicker' => null, 'text' => null])
<header {{ $attributes->merge(['class' => 'flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between']) }}>
    <div class="min-w-0">
        @if ($kicker)<span class="kicker">{{ $kicker }}</span>@endif
        <h1 class="page-title">{{ $title }}</h1>
        @if ($text)<p class="page-lead">{{ $text }}</p>@endif
    </div>
    @if (! $slot->isEmpty())<div class="flex flex-wrap items-center gap-2 sm:shrink-0">{{ $slot }}</div>@endif
</header>
