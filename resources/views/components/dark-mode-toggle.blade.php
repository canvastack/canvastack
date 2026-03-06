{{--
    Dark Mode Toggle Button Component
    
    This component provides a toggle button for switching between light and dark modes.
    It integrates with the DarkModeManager JavaScript module.
    
    Requirements Validated:
    - 15.4: Add dark mode toggle button (optional)
    - 15.4: Persist dark mode preference
    - 15.4: Sync with system dark mode preference
    
    Usage:
    <x-canvastack::dark-mode-toggle />
    
    With custom classes:
    <x-canvastack::dark-mode-toggle class="custom-class" />
    
    With custom size:
    <x-canvastack::dark-mode-toggle size="lg" />
    
    @package CanvaStack
    @subpackage Components\Table
    @version 1.0.0
--}}

@props([
    'size' => 'md', // sm, md, lg
    'showLabel' => false,
    'position' => 'relative', // relative, fixed
])

@php
    $sizeClasses = [
        'sm' => 'p-1.5',
        'md' => 'p-2',
        'lg' => 'p-3',
    ];
    
    $iconSizes = [
        'sm' => 'w-4 h-4',
        'md' => 'w-5 h-5',
        'lg' => 'w-6 h-6',
    ];
    
    $buttonSize = $sizeClasses[$size] ?? $sizeClasses['md'];
    $iconSize = $iconSizes[$size] ?? $iconSizes['md'];
    
    $positionClasses = $position === 'fixed' 
        ? 'fixed bottom-4 right-4 z-50' 
        : 'relative';
@endphp

<div 
    x-data="darkModeToggle()" 
    {{ $attributes->merge(['class' => $positionClasses]) }}
>
    <button 
        @click="toggle()"
        type="button"
        class="{{ $buttonSize }} rounded-xl transition-all duration-200 ease-in-out
               bg-gray-100 dark:bg-gray-800 
               hover:bg-gray-200 dark:hover:bg-gray-700
               text-gray-700 dark:text-gray-300
               focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400
               focus:ring-offset-2 dark:focus:ring-offset-gray-900
               shadow-sm hover:shadow-md
               flex items-center gap-2"
        :aria-label="isDark ? '{{ __('components.table.dark_mode.switch_to_light') }}' : '{{ __('components.table.dark_mode.switch_to_dark') }}'"
        :title="isDark ? '{{ __('components.table.dark_mode.switch_to_light') }}' : '{{ __('components.table.dark_mode.switch_to_dark') }}'"
    >
        {{-- Moon icon (shown in light mode) --}}
        <i 
            x-show="!isDark" 
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-90"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-90"
            data-lucide="moon" 
            class="{{ $iconSize }}"
        ></i>
        
        {{-- Sun icon (shown in dark mode) --}}
        <i 
            x-show="isDark" 
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-90"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-90"
            data-lucide="sun" 
            class="{{ $iconSize }}"
            style="display: none;"
        ></i>
        
        @if($showLabel)
            <span 
                x-text="isDark ? '{{ __('components.table.dark_mode.light') }}' : '{{ __('components.table.dark_mode.dark') }}'"
                class="text-sm font-medium"
            ></span>
        @endif
    </button>
    
    {{-- Optional: Show system preference indicator --}}
    @if(config('canvastack-table.dark_mode.show_system_indicator', false))
        <div 
            x-show="!localStorage.getItem('canvastack_dark_mode')"
            class="absolute -top-1 -right-1 w-3 h-3 bg-blue-500 rounded-full border-2 border-white dark:border-gray-900"
            :title="'{{ __('components.table.dark_mode.using_system_preference') }}'"
        ></div>
    @endif
</div>

{{-- Initialize Lucide icons after Alpine renders --}}
@push('scripts')
<script>
    document.addEventListener('alpine:initialized', () => {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
    
    // Re-create icons when dark mode changes
    document.addEventListener('darkModeChange', () => {
        setTimeout(() => {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }, 100);
    });
</script>
@endpush
