@props([
    'position' => 'bottom-right', // bottom-right, bottom-left, top-right, top-left
    'showFlag' => true,
    'showName' => true,
    'compact' => false,
])

@php
    $localeManager = app('canvastack.locale');
    $currentLocale = $localeManager->getLocale();
    $availableLocales = $localeManager->getAvailableLocales();
    $currentInfo = $localeManager->getLocaleInfo($currentLocale);
    
    // Position classes
    $positionClasses = [
        'bottom-right' => 'right-0 mt-2',
        'bottom-left' => 'left-0 mt-2',
        'top-right' => 'right-0 bottom-full mb-2',
        'top-left' => 'left-0 bottom-full mb-2',
    ];
    
    $dropdownPosition = $positionClasses[$position] ?? $positionClasses['bottom-right'];
@endphp

<div class="relative" x-data="{ open: false }">
    <!-- Trigger Button -->
    <button 
        @click="open = !open"
        class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors duration-200"
        aria-label="{{ __('ui.labels.language') }}"
        aria-expanded="false"
        x-bind:aria-expanded="open.toString()"
    >
        @if($showFlag && isset($currentInfo['flag']))
            <span class="text-lg" aria-hidden="true">{{ $currentInfo['flag'] }}</span>
        @endif
        
        @if($showName && !$compact)
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ $currentInfo['native'] ?? $currentInfo['name'] ?? strtoupper($currentLocale) }}
            </span>
        @elseif($compact)
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                {{ $currentLocale }}
            </span>
        @endif
        
        <i data-lucide="chevron-down" class="w-3 h-3 text-gray-500 dark:text-gray-400 transition-transform duration-200" x-bind:class="{ 'rotate-180': open }"></i>
    </button>
    
    <!-- Dropdown Menu -->
    <div 
        x-show="open"
        @click.away="open = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95 translate-y-1"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-1"
        class="absolute {{ $dropdownPosition }} w-56 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-lg py-2 z-50"
        style="display: none;"
        role="menu"
        aria-orientation="vertical"
    >
        <!-- Header -->
        <div class="px-4 py-2 border-b border-gray-200 dark:border-gray-800">
            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                {{ __('ui.labels.language') }}
            </p>
        </div>
        
        <!-- Locale Options -->
        <div class="py-1">
            @foreach($availableLocales as $code => $info)
                <form method="POST" action="{{ route('locale.switch') }}" class="w-full">
                    @csrf
                    <input type="hidden" name="locale" value="{{ $code }}">
                    <button 
                        type="submit"
                        class="w-full flex items-center gap-3 px-4 py-2.5 text-sm hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors duration-150 {{ $code === $currentLocale ? 'bg-indigo-50 dark:bg-indigo-900/20' : '' }}"
                        role="menuitem"
                    >
                        <!-- Flag -->
                        @if(isset($info['flag']))
                            <span class="text-xl flex-shrink-0" aria-hidden="true">{{ $info['flag'] }}</span>
                        @endif
                        
                        <!-- Language Names -->
                        <div class="flex-1 text-left">
                            <div class="font-medium text-gray-900 dark:text-gray-100">
                                {{ $info['native'] ?? $info['name'] }}
                            </div>
                            @if(isset($info['name']) && $info['name'] !== ($info['native'] ?? ''))
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $info['name'] }}
                                </div>
                            @endif
                        </div>
                        
                        <!-- Active Indicator -->
                        @if($code === $currentLocale)
                            <i data-lucide="check" class="w-4 h-4 text-indigo-600 dark:text-indigo-400 flex-shrink-0"></i>
                        @endif
                    </button>
                </form>
            @endforeach
        </div>
        
        <!-- Footer (optional) -->
        @if(auth()->check() && auth()->user()->can('manage-locales'))
            <div class="border-t border-gray-200 dark:border-gray-800 mt-1 pt-1">
                <a 
                    href="{{ route('admin.locales.index') }}"
                    class="flex items-center gap-2 px-4 py-2 text-xs text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors duration-150"
                >
                    <i data-lucide="settings" class="w-3 h-3"></i>
                    {{ __('ui.navigation.settings') }}
                </a>
            </div>
        @endif
    </div>
</div>
