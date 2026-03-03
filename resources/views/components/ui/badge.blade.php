@props([
    'type' => 'default',
    'size' => 'md',
    'icon' => null,
])

@php
    $baseClasses = 'inline-flex items-center gap-1 font-medium rounded-full';
    
    $typeClasses = [
        'default' => 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400',
        'primary' => 'bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400',
        'success' => 'bg-emerald-100 dark:bg-emerald-900/50 text-emerald-600 dark:text-emerald-400',
        'warning' => 'bg-amber-100 dark:bg-amber-900/50 text-amber-600 dark:text-amber-400',
        'danger' => 'bg-red-100 dark:bg-red-900/50 text-red-600 dark:text-red-400',
        'info' => 'bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400',
    ];
    
    $sizeClasses = [
        'xs' => 'px-1.5 py-0.5 text-xs',
        'sm' => 'px-2 py-0.5 text-xs',
        'md' => 'px-2.5 py-1 text-sm',
        'lg' => 'px-3 py-1.5 text-base',
    ];
    
    $classes = $baseClasses . ' ' . ($typeClasses[$type] ?? $typeClasses['default']) . ' ' . ($sizeClasses[$size] ?? $sizeClasses['md']);
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    @if($icon)
        <i data-lucide="{{ $icon }}" class="w-3 h-3"></i>
    @endif
    {{ $slot }}
</span>
