@props(['label', 'value', 'alert' => false, 'href' => null])
@php $tag = $href ? 'a' : 'div'; @endphp
<{{ $tag }} @if($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => 'stat '.($alert ? 'stat-alert ' : '').($href ? 'card-hover' : '')]) }}>
    <dt class="stat-label">{{ $label }}</dt>
    <dd class="stat-value">{{ $value }}</dd>
</{{ $tag }}>
