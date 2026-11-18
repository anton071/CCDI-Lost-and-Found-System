@props(['status'])
@php
    $value = is_object($status) ? $status->value : (string) $status;
    $label = is_object($status) && method_exists($status, 'label') ? $status->label() : ucfirst(str_replace('_', ' ', $value));
    // Report type is shown as an outlined tag; workflow status as a tinted badge with a dot.
    $type = in_array($value, ['lost', 'found'], true);
    $tone = match ($value) {
        'pending', 'pending_review' => 'amber',
        'published', 'approved', 'confirmed_match', 'released' => 'green',
        'rejected', 'dismissed', 'cancelled' => 'red',
        'resolved', 'reviewed' => 'blue',
        'under_review' => 'indigo',
        default => 'slate',
    };
    $class = $type ? "badge badge-$value" : "badge badge-dot badge-$tone";
@endphp
<span {{ $attributes->merge(['class' => $class]) }}>{{ $label }}</span>
