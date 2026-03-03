@props([
    'variant' => 'primary',
    'size' => 'md',
    'icon' => null,
    'iconPosition' => 'left',
    'href' => null,
    'type' => 'button',
    'disabled' => false,
    'loading' => false,
])

@php
    $baseClasses = 'inline-flex items-center justify-center gap-2 font-semibold transition focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed';
    
    $variantClasses = [
        'primary' => 'gradient-bg text-white hover:opacity-90 shadow-lg shadow-indigo-500/25 focus:ring-indigo-500',
        'secondary' => 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-gray-100 hover:bg-gray-200 dark:hover:bg-gray-700 focus:ring-gray-500',
        'outline' => 'border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 focus:ring-gray-500',
        'ghost' => 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 focus:ring-gray-500',
        'danger' => 'bg-red-600 text-white hover:bg-red-700 shadow-lg shadow-red-500/25 focus:ring-red-500',
        'success' => 'bg-emerald-600 text-white hover:bg-emerald-700 shadow-lg shadow-emerald-500/25 focus:ring-emerald-500',
        'warning' => 'bg-amber-600 text-white hover:bg-amber-700 shadow-lg shadow-amber-500/25 focus:ring-amber-500',
    ];
    
    $sizeClasses = [
        'xs' => 'px-2.5 py-1.5 text-xs rounded-lg',
        'sm' => 'px-3 py-1.5 text-xs rounded-xl',
        'md' => 'px-4 py-2.5 text-sm rounded-xl',
        'lg' => 'px-6 py-3 text-base rounded-xl',
        'xl' => 'px-8 py-4 text-lg rounded-2xl',
    ];
    
    $classes = $baseClasses . ' ' . ($variantClasses[$variant] ?? $variantClasses['primary']) . ' ' . ($sizeClasses[$size] ?? $sizeClasses['md']);
    
    $tag = $href ? 'a' : 'button';
@endphp

<{{ $tag }}
    @if($href)
        href="{{ $href }}"
    @else
        type="{{ $type }}"
    @endif
    {{ $attributes->merge(['class' => $classes]) }}
    @if($disabled || $loading) disabled @endif
>
    @if($loading)
        <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    @elseif($icon && $iconPosition === 'left')
        <i data-lucide="{{ $icon }}" class="w-4 h-4"></i>
    @endif
    
    {{ $slot }}
    
    @if($icon && $iconPosition === 'right' && !$loading)
        <i data-lucide="{{ $icon }}" class="w-4 h-4"></i>
    @endif
</{{ $tag }}>
