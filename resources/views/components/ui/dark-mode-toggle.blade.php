@props([
    'variant' => 'icon', // icon, button, switch
    'size' => 'md',
])

@php
    $sizeClasses = [
        'sm' => 'p-1.5',
        'md' => 'p-2',
        'lg' => 'p-2.5',
    ];
    
    $iconSizes = [
        'sm' => 'w-4 h-4',
        'md' => 'w-5 h-5',
        'lg' => 'w-6 h-6',
    ];
    
    $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
    $iconSize = $iconSizes[$size] ?? $iconSizes['md'];
@endphp

@if($variant === 'icon')
    {{-- Icon Button Toggle --}}
    <button 
        @click="window.toggleDark()"
        class="{{ $sizeClass }} rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition"
        aria-label="{{ __('ui.navigation.toggle_dark_mode') }}"
        x-data="{ isDark: document.documentElement.classList.contains('dark') }"
        x-on:darkmode:enabled.window="isDark = true"
        x-on:darkmode:disabled.window="isDark = false"
        {{ $attributes }}
    >
        <i x-show="!isDark" data-lucide="moon" class="{{ $iconSize }}"></i>
        <i x-show="isDark" data-lucide="sun" class="{{ $iconSize }}" style="display: none;"></i>
    </button>

@elseif($variant === 'button')
    {{-- Full Button Toggle --}}
    <button 
        @click="window.toggleDark()"
        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 transition text-sm font-medium"
        x-data="{ isDark: document.documentElement.classList.contains('dark') }"
        x-on:darkmode:enabled.window="isDark = true"
        x-on:darkmode:disabled.window="isDark = false"
        {{ $attributes }}
    >
        <i x-show="!isDark" data-lucide="moon" class="w-4 h-4"></i>
        <i x-show="isDark" data-lucide="sun" class="w-4 h-4" style="display: none;"></i>
        <span x-text="isDark ? '{{ __('ui.dark_mode.light_mode') }}' : '{{ __('ui.dark_mode.dark_mode') }}'"></span>
    </button>

@elseif($variant === 'switch')
    {{-- Toggle Switch --}}
    <label 
        class="relative inline-flex items-center cursor-pointer"
        x-data="{ isDark: document.documentElement.classList.contains('dark') }"
        x-on:darkmode:enabled.window="isDark = true"
        x-on:darkmode:disabled.window="isDark = false"
        {{ $attributes }}
    >
        <input 
            type="checkbox" 
            class="sr-only peer" 
            x-model="isDark"
            @change="window.toggleDark()"
        >
        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
        <span class="ms-3 text-sm font-medium text-gray-900 dark:text-gray-300">
            <span x-show="!isDark">{{ __('ui.dark_mode.light') }}</span>
            <span x-show="isDark" style="display: none;">{{ __('ui.dark_mode.dark') }}</span>
        </span>
    </label>

@endif
