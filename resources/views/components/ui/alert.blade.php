@props([
    'type' => 'info',
    'message' => null,
    'dismissible' => true,
    'icon' => null,
])

@php
    $typeConfig = [
        'success' => [
            'bg' => 'bg-emerald-50 dark:bg-emerald-900/20',
            'border' => 'border-emerald-200 dark:border-emerald-800',
            'text' => 'text-emerald-800 dark:text-emerald-200',
            'icon' => $icon ?? 'check-circle',
        ],
        'error' => [
            'bg' => 'bg-red-50 dark:bg-red-900/20',
            'border' => 'border-red-200 dark:border-red-800',
            'text' => 'text-red-800 dark:text-red-200',
            'icon' => $icon ?? 'alert-circle',
        ],
        'warning' => [
            'bg' => 'bg-amber-50 dark:bg-amber-900/20',
            'border' => 'border-amber-200 dark:border-amber-800',
            'text' => 'text-amber-800 dark:text-amber-200',
            'icon' => $icon ?? 'alert-triangle',
        ],
        'info' => [
            'bg' => 'bg-blue-50 dark:bg-blue-900/20',
            'border' => 'border-blue-200 dark:border-blue-800',
            'text' => 'text-blue-800 dark:text-blue-200',
            'icon' => $icon ?? 'info',
        ],
    ];
    
    $config = $typeConfig[$type] ?? $typeConfig['info'];
    $classes = "flex items-start gap-3 p-4 rounded-xl border {$config['bg']} {$config['border']} {$config['text']}";
@endphp

<div 
    {{ $attributes->merge(['class' => $classes]) }}
    @if($dismissible)
        x-data="{ show: true }"
        x-show="show"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    @endif
>
    <!-- Icon -->
    <div class="shrink-0 mt-0.5">
        <i data-lucide="{{ $config['icon'] }}" class="w-5 h-5"></i>
    </div>
    
    <!-- Content -->
    <div class="flex-1 min-w-0">
        @if($message)
            <p class="text-sm font-medium">{{ $message }}</p>
        @else
            {{ $slot }}
        @endif
    </div>
    
    <!-- Dismiss Button -->
    @if($dismissible)
        <button 
            @click="show = false"
            class="shrink-0 p-1 rounded-lg hover:bg-black/5 dark:hover:bg-white/5 transition"
            aria-label="{{ __('ui.aria.dismiss') }}"
        >
            <i data-lucide="x" class="w-4 h-4"></i>
        </button>
    @endif
</div>
