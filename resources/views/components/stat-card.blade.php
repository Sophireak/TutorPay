@props(['label', 'value', 'hint' => null, 'tone' => 'default'])

@php
    $tones = [
        'default' => 'text-gray-900',
        'positive' => 'text-emerald-600',
        'negative' => 'text-rose-600',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'bg-white shadow-sm rounded-lg p-5 border border-gray-100']) }}>
    <div class="text-sm font-medium text-gray-500">{{ $label }}</div>
    <div class="mt-2 text-2xl font-semibold {{ $tones[$tone] ?? $tones['default'] }}">{{ $value }}</div>
    @if ($hint)
        <div class="mt-1 text-xs text-gray-400">{{ $hint }}</div>
    @endif
</div>
