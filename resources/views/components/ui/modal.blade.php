@props([
    'name' => 'modal',
    'show' => false,
    'maxWidth' => 'md',
])

@php
    $maxWidthClasses = [
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
        '2xl' => 'max-w-2xl',
        '3xl' => 'max-w-3xl',
        '4xl' => 'max-w-4xl',
        '5xl' => 'max-w-5xl',
        '6xl' => 'max-w-6xl',
        'full' => 'max-w-full',
    ];
    
    $maxWidthClass = $maxWidthClasses[$maxWidth] ?? $maxWidthClasses['md'];
@endphp

<div 
    x-data="{ show: @js($show) }"
    x-on:open-modal.window="$event.detail === '{{ $name }}' ? show = true : null"
    x-on:close-modal.window="$event.detail === '{{ $name }}' ? show = false : null"
    x-on:keydown.escape.window="show = false"
    x-show="show"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
    style="display: none;"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @click.self="show = false"
>
    <div 
        class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 {{ $maxWidthClass }} w-full shadow-2xl"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        @click.stop
    >
        <!-- Modal Header -->
        @if(isset($header))
            <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-800">
                <div class="flex-1">
                    {{ $header }}
                </div>
                <button 
                    @click="show = false"
                    class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                    aria-label="{{ __('ui.aria.close_modal') }}"
                >
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
        @endif
        
        <!-- Modal Body -->
        <div class="p-6">
            {{ $slot }}
        </div>
        
        <!-- Modal Footer -->
        @if(isset($footer))
            <div class="flex items-center justify-end gap-3 p-6 border-t border-gray-200 dark:border-gray-800">
                {{ $footer }}
            </div>
        @endif
    </div>
</div>
