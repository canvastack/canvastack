@props([
    'hover' => false,
    'padding' => true,
])

@php
    $baseClasses = 'bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800';
    
    if ($hover) {
        $baseClasses .= ' hover:shadow-xl hover:-translate-y-1 transition-all duration-300 cursor-pointer';
    } else {
        $baseClasses .= ' shadow-sm';
    }
    
    if ($padding) {
        $baseClasses .= ' p-6';
    }
@endphp

<div {{ $attributes->merge(['class' => $baseClasses]) }}>
    @if(isset($header))
        <div class="mb-4 pb-4 border-b border-gray-200 dark:border-gray-800">
            {{ $header }}
        </div>
    @endif
    
    {{ $slot }}
    
    @if(isset($footer))
        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-800">
            {{ $footer }}
        </div>
    @endif
</div>
