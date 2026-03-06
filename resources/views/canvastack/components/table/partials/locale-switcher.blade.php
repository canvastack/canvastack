{{--
    Locale Switcher Component
    
    Provides a dropdown UI for switching locales without page reload.
    Integrates with Alpine.js and the locale-switcher.js component.
    
    @param array $locales - Available locales
    @param string $currentLocale - Current locale code
    @param string $switchUrl - URL endpoint for locale switching
    @param string $position - Position of dropdown (left, right)
--}}

@php
    $locales = $locales ?? app('canvastack.locale.integration')->getAvailableLocales();
    $currentLocale = $currentLocale ?? app('canvastack.locale.integration')->getLocale();
    $switchUrl = $switchUrl ?? route('canvastack.locale.switch');
    $position = $position ?? 'right';
    
    $currentLocaleInfo = $locales[$currentLocale] ?? [];
@endphp

<div 
    x-data="localeSwitcher({
        currentLocale: '{{ $currentLocale }}',
        availableLocales: {{ json_encode($locales) }},
        switchUrl: '{{ $switchUrl }}'
    })"
    class="relative inline-block text-left"
    @click.away="close()"
>
    {{-- Trigger Button --}}
    <button
        @click="toggle()"
        type="button"
        class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-lg
               bg-white dark:bg-gray-800 
               text-gray-700 dark:text-gray-200
               border border-gray-300 dark:border-gray-600
               hover:bg-gray-50 dark:hover:bg-gray-700
               focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500
               transition-colors duration-200"
        :disabled="isSwitching"
        :class="{ 'opacity-50 cursor-not-allowed': isSwitching }"
        :aria-label="__('components.table.locale_switcher.toggle')"
    >
        {{-- Flag --}}
        <span class="text-lg" x-text="currentFlag"></span>
        
        {{-- Locale Name --}}
        <span x-text="currentNativeName"></span>
        
        {{-- Loading Spinner --}}
        <svg 
            x-show="isSwitching" 
            class="animate-spin h-4 w-4 text-gray-500" 
            xmlns="http://www.w3.org/2000/svg" 
            fill="none" 
            viewBox="0 0 24 24"
        >
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        
        {{-- Chevron Icon --}}
        <svg 
            x-show="!isSwitching"
            class="h-4 w-4 text-gray-500 transition-transform duration-200"
            :class="{ 'rotate-180': isOpen }"
            xmlns="http://www.w3.org/2000/svg" 
            viewBox="0 0 20 20" 
            fill="currentColor"
        >
            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
        </svg>
    </button>

    {{-- Dropdown Menu --}}
    <div
        x-show="isOpen"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute z-50 mt-2 w-56 rounded-lg shadow-lg
               bg-white dark:bg-gray-800
               border border-gray-200 dark:border-gray-700
               {{ $position === 'left' ? 'left-0' : 'right-0' }}"
        style="display: none;"
    >
        <div class="py-1" role="menu" aria-orientation="vertical">
            {{-- Locale Options --}}
            <template x-for="(localeInfo, localeCode) in availableLocales" :key="localeCode">
                <button
                    @click="switchLocale(localeCode)"
                    type="button"
                    class="w-full text-left px-4 py-2 text-sm
                           hover:bg-gray-100 dark:hover:bg-gray-700
                           transition-colors duration-150
                           flex items-center gap-3"
                    :class="{
                        'bg-gray-50 dark:bg-gray-700': localeCode === currentLocale,
                        'text-gray-900 dark:text-gray-100': localeCode === currentLocale,
                        'text-gray-700 dark:text-gray-300': localeCode !== currentLocale
                    }"
                    role="menuitem"
                >
                    {{-- Flag --}}
                    <span class="text-lg" x-text="localeInfo.flag"></span>
                    
                    {{-- Locale Name --}}
                    <div class="flex-1">
                        <div class="font-medium" x-text="localeInfo.native"></div>
                        <div class="text-xs text-gray-500 dark:text-gray-400" x-text="localeInfo.name"></div>
                    </div>
                    
                    {{-- Check Icon for Current Locale --}}
                    <svg 
                        x-show="localeCode === currentLocale"
                        class="h-5 w-5 text-primary-600 dark:text-primary-400" 
                        xmlns="http://www.w3.org/2000/svg" 
                        viewBox="0 0 20 20" 
                        fill="currentColor"
                    >
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                </button>
            </template>
        </div>

        {{-- Error Message --}}
        <div 
            x-show="error" 
            class="px-4 py-2 text-sm text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 border-t border-gray-200 dark:border-gray-700"
            x-text="error"
        ></div>

        {{-- Keyboard Shortcut Hint --}}
        <div class="px-4 py-2 text-xs text-gray-500 dark:text-gray-400 border-t border-gray-200 dark:border-gray-700">
            {{ __('components.table.locale_switcher.keyboard_hint') }}
        </div>
    </div>
</div>
