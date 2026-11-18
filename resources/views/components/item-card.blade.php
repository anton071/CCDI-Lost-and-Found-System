@props(['item'])
<a href="{{ route('reports.show', $item) }}" class="card card-hover group flex flex-col overflow-hidden">
    <div class="relative">
        <x-item-photo :report="$item" />
        <x-status-badge :status="$item->report_type" class="absolute top-3 left-3 shadow-xs" />
    </div>
    <div class="flex flex-1 flex-col gap-2 p-4">
        <p class="truncate text-xs font-semibold tracking-wide text-slate-500 uppercase">{{ $item->category?->name ?? 'Uncategorised' }}</p>
        <h3 class="line-clamp-2 min-h-[2.75rem] text-base leading-snug font-semibold text-navy-900 group-hover:text-navy-600">{{ $item->item_name }}</h3>
        <dl class="mt-auto space-y-1.5 border-t border-slate-100 pt-3 text-[0.8125rem] text-slate-600">
            <div class="flex items-center gap-2"><x-icon name="map-pin" class="h-4 w-4 shrink-0 text-slate-400" /><dd class="truncate">{{ $item->location_description }}</dd></div>
            <div class="flex items-center gap-2"><x-icon name="calendar" class="h-4 w-4 shrink-0 text-slate-400" /><dd>{{ $item->incident_date->format('M j, Y') }}</dd></div>
        </dl>
    </div>
</a>
