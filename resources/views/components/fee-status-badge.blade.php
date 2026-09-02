@props(['status'])

@php
    $classes = match ($status) {
        'paid' => 'bg-emerald-100 text-emerald-800',
        'partial' => 'bg-amber-100 text-amber-800',
        default => 'bg-rose-100 text-rose-800',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {$classes}"]) }}>
    {{ ucfirst($status) }}
</span>
