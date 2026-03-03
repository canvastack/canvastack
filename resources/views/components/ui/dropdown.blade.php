@props([
    'align' => 'right',
    'width' => '48',
    'contentClasses' => '',
])

@php
    $alignmentClasses = [
        'left' => 'left-0',
        'right' => 'right-0',
        'top' => 'bottom-full mb-2',
    ];
    
    $widthClasses = [
        '48' => 'w-48',
        '56' => 'w-56',
        '64' => 'w-64',
        '72' => 'w-72',
    ];
    
    $alignClass = $alignmentClasses[$align] ?? $alignmentClasses['right'];
    $widthClass = $widthClasses[$width] ?? $widthClasses['48'];
@endphp

<div class="relative" x-data="{ open: false }" @click.away="open = false">
    <!-- Trigger -->
    <div @click="open = !open">
        {{ $trigger }}
    </div>
    
    <!-- Dropdown Content -->
    <div 
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute {{ $alignClass }} mt-2 {{ $widthClass }} bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-lg py-1 z-50 {{ $contentClasses }}"
        style="display: none;"
        @click="open = false"
    >
        {{ $slot }}
    </div>
</div>
