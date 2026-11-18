@props(['match', 'admin' => false])
@php
    $d = $match->matching_details ?? [];
    $algo = $match->algorithm_used->value;
    $fallback = ($d['fallback_used'] ?? false) && $algo === 'ORB';
@endphp
<div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 bg-navy-50/70 px-4 py-2">
        <p class="flex items-center gap-2 text-xs font-bold tracking-wide text-navy-800 uppercase"><x-icon name="compare" class="h-4 w-4" />Possible match &middot; suggestion only</p>
        <span class="badge badge-blue">{{ $algo }}{{ $fallback ? ' (fallback)' : '' }}</span>
    </div>
    <dl class="grid grid-cols-3 divide-x divide-slate-200 text-center">
        <div class="px-2 py-3"><dd class="text-2xl leading-none font-bold text-navy-900 tabular-nums">{{ (int) $match->similarity_score }}</dd><dt class="mt-1 text-[0.6875rem] leading-tight text-slate-500">verified feature correspondences</dt></div>
        <div class="px-2 py-3"><dd class="text-2xl leading-none font-bold text-navy-900 tabular-nums">{{ $match->good_matches }}</dd><dt class="mt-1 text-[0.6875rem] leading-tight text-slate-500">ratio-test matches</dt></div>
        <div class="px-2 py-3"><dd class="text-2xl leading-none font-bold text-navy-900 tabular-nums">{{ isset($d['inlier_ratio']) ? number_format($d['inlier_ratio'], 2) : '–' }}</dd><dt class="mt-1 text-[0.6875rem] leading-tight text-slate-500">inlier ratio</dt></div>
    </dl>
    <div class="space-y-1 border-t border-slate-200 bg-slate-50 px-4 py-3 text-xs leading-relaxed text-slate-600">
        <p>This counts feature points that agree on one geometric transformation. It is <strong class="text-slate-800">not a percentage</strong> and <strong class="text-slate-800">not proof of ownership</strong>; a person must still verify the claim.</p>
        @if ($admin && $d)
            <p class="text-slate-500">Criterion: &ge; {{ $d['criterion']['min_inliers'] ?? '?' }} inliers and ratio &ge; {{ $d['criterion']['min_inlier_ratio'] ?? '?' }}@isset($d['matched_at']) &middot; matched {{ \Illuminate\Support\Carbon::parse($d['matched_at'])->format('M j, Y H:i') }}@endisset</p>
        @endif
    </div>
</div>
