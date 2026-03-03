@props([
    'position' => 'bottom-right', // bottom-right, bottom-left, top-right, top-left
    'showLabel' => true,
    'showPreview' => true,
    'compact' => false,
])

@php
    $themes = app('canvastack.theme')->all();
    $currentTheme = app('canvastack.theme')->current();
    
    $positionClasses = [
        'bottom-right' => 'right-0 mt-2',
        'bottom-left' => 'left-0 mt-2',
        'top-right' => 'right-0 bottom-full mb-2',
        'top-left' => 'left-0 bottom-full mb-2',
    ];
    
    $dropdownClass = $positionClasses[$position] ?? $positionClasses['bottom-right'];
@endphp

<div 
    {{ $attributes->merge(['class' => 'theme-switcher relative']) }}
    x-data="themeSwitcher()"
    x-init="init()"
    @click.away="open = false"
>
    {{-- Trigger Button --}}
    <button
        @click="open = !open"
        class="flex items-center gap-2 px-3 py-2 rounded-xl border-2 border-gray-200 dark:border-gray-800 
               bg-white dark:bg-gray-900 hover:border-indigo-300 dark:hover:border-indigo-700 
               transition-all duration-200 group"
        :class="{ 'border-indigo-500 shadow-lg': open }"
        type="button"
    >
        {{-- Current Theme Preview --}}
        <div class="flex w-6 h-6 rounded-md overflow-hidden border border-gray-300 dark:border-gray-700">
            @php
                $colors = $currentTheme->getColors();
                $primary = $colors['primary']['500'] ?? $colors['primary'] ?? '#6366f1';
                $secondary = $colors['secondary']['500'] ?? $colors['secondary'] ?? '#8b5cf6';
                $accent = $colors['accent']['500'] ?? $colors['accent'] ?? '#a855f7';
            @endphp
            <div class="flex-1" style="background-color: {{ $primary }}"></div>
            <div class="flex-1" style="background-color: {{ $secondary }}"></div>
            <div class="flex-1" style="background-color: {{ $accent }}"></div>
        </div>
        
        @if($showLabel)
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300" x-text="currentThemeName">
                {{ $currentTheme->getDisplayName() }}
            </span>
        @endif
        
        {{-- Chevron Icon --}}
        <i 
            data-lucide="chevron-down" 
            class="w-4 h-4 text-gray-500 dark:text-gray-400 transition-transform duration-200"
            :class="{ 'rotate-180': open }"
        ></i>
    </button>
    
    {{-- Dropdown Menu --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute {{ $dropdownClass }} z-50 w-72 bg-white dark:bg-gray-900 rounded-2xl border-2 border-gray-200 dark:border-gray-800 shadow-2xl overflow-hidden"
        @click.stop
        style="display: none;"
    >
        {{-- Header --}}
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-800">
            <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100">
                {{ __('canvastack::ui.theme.choose_theme') }}
            </h3>
            <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">
                {{ __('canvastack::ui.theme.select_preferred') }}
            </p>
        </div>
        
        {{-- Theme List --}}
        <div class="max-h-96 overflow-y-auto p-2">
            @foreach($themes as $theme)
                @php
                    $themeColors = $theme->getColors();
                    $themePrimary = $themeColors['primary']['500'] ?? $themeColors['primary'] ?? '#6366f1';
                    $themeSecondary = $themeColors['secondary']['500'] ?? $themeColors['secondary'] ?? '#8b5cf6';
                    $themeAccent = $themeColors['accent']['500'] ?? $themeColors['accent'] ?? '#a855f7';
                    $themeGradient = $theme->get('gradient.primary', "linear-gradient(135deg, {$themePrimary}, {$themeSecondary}, {$themeAccent})");
                @endphp
                
                <button
                    @click="switchTheme('{{ $theme->getName() }}')"
                    class="w-full flex items-center gap-3 p-3 rounded-xl transition-all duration-200 group
                           hover:bg-gray-50 dark:hover:bg-gray-800"
                    :class="{ 
                        'bg-indigo-50 dark:bg-indigo-950/30 ring-2 ring-indigo-500': currentTheme === '{{ $theme->getName() }}',
                        'bg-transparent': currentTheme !== '{{ $theme->getName() }}'
                    }"
                    type="button"
                >
                    @if($showPreview)
                        {{-- Theme Preview --}}
                        <div class="flex-shrink-0 w-12 h-12 rounded-lg overflow-hidden border-2 transition-all
                                    {{ $currentTheme->getName() === $theme->getName() 
                                        ? 'border-indigo-500 shadow-lg' 
                                        : 'border-gray-300 dark:border-gray-700 group-hover:border-indigo-300 dark:group-hover:border-indigo-700' 
                                    }}">
                            <div class="w-full h-full" style="background: {{ $themeGradient }}"></div>
                        </div>
                    @else
                        {{-- Color Swatches --}}
                        <div class="flex-shrink-0 flex w-12 h-12 rounded-lg overflow-hidden border-2 transition-all
                                    {{ $currentTheme->getName() === $theme->getName() 
                                        ? 'border-indigo-500 shadow-lg' 
                                        : 'border-gray-300 dark:border-gray-700 group-hover:border-indigo-300 dark:group-hover:border-indigo-700' 
                                    }}">
                            <div class="flex-1" style="background-color: {{ $themePrimary }}"></div>
                            <div class="flex-1" style="background-color: {{ $themeSecondary }}"></div>
                            <div class="flex-1" style="background-color: {{ $themeAccent }}"></div>
                        </div>
                    @endif
                    
                    {{-- Theme Info --}}
                    <div class="flex-1 text-left">
                        <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                            {{ $theme->getDisplayName() }}
                        </div>
                        @if(!$compact)
                            <div class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">
                                {{ $theme->getDescription() }}
                            </div>
                        @endif
                    </div>
                    
                    {{-- Active Indicator --}}
                    <div 
                        class="flex-shrink-0"
                        x-show="currentTheme === '{{ $theme->getName() }}'"
                    >
                        <i data-lucide="check-circle" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                    </div>
                </button>
            @endforeach
        </div>
        
        {{-- Footer (Optional) --}}
        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50">
            <p class="text-xs text-gray-600 dark:text-gray-400 text-center">
                {{ __('canvastack::ui.theme.saved_automatically') }}
            </p>
        </div>
    </div>
</div>

@once
    @push('scripts')
    <script>
        /**
         * Theme Switcher Alpine.js Component
         * 
         * Manages theme switching with localStorage persistence and Alpine.js reactivity.
         */
        function themeSwitcher() {
            return {
                open: false,
                currentTheme: '{{ $currentTheme->getName() }}',
                currentThemeName: '{{ $currentTheme->getDisplayName() }}',
                
                /**
                 * Initialize the component
                 */
                init() {
                    // Load theme from localStorage
                    const savedTheme = localStorage.getItem('canvastack_theme');
                    if (savedTheme && savedTheme !== this.currentTheme) {
                        this.switchTheme(savedTheme, false);
                    }
                    
                    // Listen for theme changes from other components
                    window.addEventListener('theme:changed', (event) => {
                        this.currentTheme = event.detail.theme;
                        this.currentThemeName = event.detail.themeName;
                    });
                    
                    // Initialize Lucide icons
                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                },
                
                /**
                 * Switch to a different theme
                 * 
                 * @param {string} themeName - The theme name to switch to
                 * @param {boolean} closeDropdown - Whether to close the dropdown after switching
                 */
                switchTheme(themeName, closeDropdown = true) {
                    if (themeName === this.currentTheme) {
                        if (closeDropdown) {
                            this.open = false;
                        }
                        return;
                    }
                    
                    // Call global theme switcher
                    if (typeof window.switchTheme === 'function') {
                        window.switchTheme(themeName);
                    }
                    
                    // Update local state
                    this.currentTheme = themeName;
                    
                    // Find theme display name
                    const themes = @json(collect($themes)->map(fn($t) => ['name' => $t->getName(), 'display_name' => $t->getDisplayName()])->values());
                    const theme = themes.find(t => t.name === themeName);
                    if (theme) {
                        this.currentThemeName = theme.display_name;
                    }
                    
                    // Close dropdown
                    if (closeDropdown) {
                        this.open = false;
                    }
                    
                    // Reinitialize Lucide icons after theme change
                    this.$nextTick(() => {
                        if (typeof lucide !== 'undefined') {
                            lucide.createIcons();
                        }
                    });
                }
            };
        }
    </script>
    @endpush
@endonce

