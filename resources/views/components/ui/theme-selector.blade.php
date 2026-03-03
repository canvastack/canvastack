@props([
    'variant' => 'grid', // grid, list, compact
    'showMetadata' => true,
    'columns' => 3,
])

@php
    $themes = app('canvastack.theme')->all();
    $currentTheme = app('canvastack.theme')->current();
    
    $gridClasses = [
        2 => 'grid-cols-1 md:grid-cols-2',
        3 => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3',
        4 => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-4',
    ];
    
    $gridClass = $gridClasses[$columns] ?? $gridClasses[3];
@endphp

<div {{ $attributes->merge(['class' => 'theme-selector']) }}>
    @if($variant === 'grid')
        {{-- Grid Layout --}}
        <div class="grid {{ $gridClass }} gap-4">
            @foreach($themes as $theme)
                <div 
                    class="theme-card group relative bg-white dark:bg-gray-900 rounded-2xl border-2 transition-all duration-300 cursor-pointer overflow-hidden
                        {{ $currentTheme->getName() === $theme->getName() 
                            ? 'border-indigo-500 shadow-lg shadow-indigo-500/25' 
                            : 'border-gray-200 dark:border-gray-800 hover:border-indigo-300 dark:hover:border-indigo-700 hover:shadow-lg' 
                        }}"
                    onclick="window.switchTheme('{{ $theme->getName() }}')"
                    x-data="{ isActive: '{{ $currentTheme->getName() }}' === '{{ $theme->getName() }}' }"
                    x-on:theme:changed.window="isActive = $event.detail.theme === '{{ $theme->getName() }}'"
                >
                    {{-- Theme Preview --}}
                    <div class="aspect-video relative overflow-hidden bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900">
                        {{-- Color Palette Preview --}}
                        <div class="absolute inset-0 flex">
                            @php
                                $colors = $theme->getColors();
                                $primary = $colors['primary']['500'] ?? $colors['primary'] ?? '#6366f1';
                                $secondary = $colors['secondary']['500'] ?? $colors['secondary'] ?? '#8b5cf6';
                                $accent = $colors['accent']['500'] ?? $colors['accent'] ?? '#a855f7';
                            @endphp
                            <div class="flex-1" style="background-color: {{ $primary }}"></div>
                            <div class="flex-1" style="background-color: {{ $secondary }}"></div>
                            <div class="flex-1" style="background-color: {{ $accent }}"></div>
                        </div>
                        
                        {{-- Gradient Overlay --}}
                        @if($theme->get('gradient.primary'))
                            <div class="absolute inset-0 opacity-80" style="background: {{ $theme->get('gradient.primary') }}"></div>
                        @endif
                        
                        {{-- Active Indicator --}}
                        <div 
                            x-show="isActive"
                            class="absolute top-3 right-3 bg-white dark:bg-gray-900 rounded-full p-2 shadow-lg"
                        >
                            <i data-lucide="check" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                        </div>
                    </div>
                    
                    {{-- Theme Info --}}
                    <div class="p-4">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-1">
                            {{ $theme->getDisplayName() }}
                        </h3>
                        
                        @if($showMetadata)
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                {{ $theme->getDescription() }}
                            </p>
                            
                            <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-500">
                                <span>v{{ $theme->getVersion() }}</span>
                                <span>{{ $theme->getAuthor() }}</span>
                            </div>
                        @endif
                    </div>
                    
                    {{-- Hover Effect --}}
                    <div class="absolute inset-0 bg-indigo-500/5 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none"></div>
                </div>
            @endforeach
        </div>
        
    @elseif($variant === 'list')
        {{-- List Layout --}}
        <div class="space-y-3">
            @foreach($themes as $theme)
                <div 
                    class="theme-item flex items-center gap-4 p-4 bg-white dark:bg-gray-900 rounded-xl border-2 transition-all duration-300 cursor-pointer
                        {{ $currentTheme->getName() === $theme->getName() 
                            ? 'border-indigo-500 shadow-lg shadow-indigo-500/25' 
                            : 'border-gray-200 dark:border-gray-800 hover:border-indigo-300 dark:hover:border-indigo-700' 
                        }}"
                    onclick="window.switchTheme('{{ $theme->getName() }}')"
                    x-data="{ isActive: '{{ $currentTheme->getName() }}' === '{{ $theme->getName() }}' }"
                    x-on:theme:changed.window="isActive = $event.detail.theme === '{{ $theme->getName() }}'"
                >
                    {{-- Color Preview --}}
                    <div class="flex-shrink-0 w-16 h-16 rounded-lg overflow-hidden flex">
                        @php
                            $colors = $theme->getColors();
                            $primary = $colors['primary']['500'] ?? $colors['primary'] ?? '#6366f1';
                            $secondary = $colors['secondary']['500'] ?? $colors['secondary'] ?? '#8b5cf6';
                            $accent = $colors['accent']['500'] ?? $colors['accent'] ?? '#a855f7';
                        @endphp
                        <div class="flex-1" style="background-color: {{ $primary }}"></div>
                        <div class="flex-1" style="background-color: {{ $secondary }}"></div>
                        <div class="flex-1" style="background-color: {{ $accent }}"></div>
                    </div>
                    
                    {{-- Theme Info --}}
                    <div class="flex-1">
                        <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">
                            {{ $theme->getDisplayName() }}
                        </h3>
                        @if($showMetadata)
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $theme->getDescription() }}
                            </p>
                        @endif
                    </div>
                    
                    {{-- Active Indicator --}}
                    <div 
                        x-show="isActive"
                        class="flex-shrink-0"
                    >
                        <i data-lucide="check-circle" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
                    </div>
                </div>
            @endforeach
        </div>
        
    @elseif($variant === 'compact')
        {{-- Compact Layout (Color Swatches) --}}
        <div class="flex flex-wrap gap-3">
            @foreach($themes as $theme)
                <button
                    class="theme-swatch group relative"
                    onclick="window.switchTheme('{{ $theme->getName() }}')"
                    x-data="{ isActive: '{{ $currentTheme->getName() }}' === '{{ $theme->getName() }}' }"
                    x-on:theme:changed.window="isActive = $event.detail.theme === '{{ $theme->getName() }}'"
                    title="{{ $theme->getDisplayName() }}"
                >
                    {{-- Color Swatch --}}
                    <div class="w-12 h-12 rounded-lg overflow-hidden flex border-2 transition-all
                        {{ $currentTheme->getName() === $theme->getName() 
                            ? 'border-indigo-500 shadow-lg' 
                            : 'border-gray-300 dark:border-gray-700 hover:border-indigo-300 dark:hover:border-indigo-700' 
                        }}">
                        @php
                            $colors = $theme->getColors();
                            $primary = $colors['primary']['500'] ?? $colors['primary'] ?? '#6366f1';
                            $secondary = $colors['secondary']['500'] ?? $colors['secondary'] ?? '#8b5cf6';
                            $accent = $colors['accent']['500'] ?? $colors['accent'] ?? '#a855f7';
                        @endphp
                        <div class="flex-1" style="background-color: {{ $primary }}"></div>
                        <div class="flex-1" style="background-color: {{ $secondary }}"></div>
                        <div class="flex-1" style="background-color: {{ $accent }}"></div>
                    </div>
                    
                    {{-- Active Indicator --}}
                    <div 
                        x-show="isActive"
                        class="absolute -top-1 -right-1 bg-indigo-600 rounded-full p-0.5"
                    >
                        <i data-lucide="check" class="w-3 h-3 text-white"></i>
                    </div>
                    
                    {{-- Tooltip --}}
                    <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none">
                        {{ $theme->getDisplayName() }}
                    </div>
                </button>
            @endforeach
        </div>
    @endif
</div>

@once
    @push('scripts')
    <script>
        // Initialize Lucide icons after component renders
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
    @endpush
@endonce
