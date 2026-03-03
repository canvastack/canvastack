@props([
    'name',
    'label' => null,
    'value' => '1',
    'checked' => false,
    'disabled' => false,
    'error' => null,
    'hint' => null,
])

@php
    $checkboxClasses = 'w-4 h-4 text-indigo-600 bg-gray-50 dark:bg-gray-800 border-gray-300 dark:border-gray-700 rounded focus:ring-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed';
    
    $errorMessage = $error ?? $errors->first($name);
    $isChecked = old($name, $checked);
@endphp

<div {{ $attributes->only('class') }}>
    <div class="flex items-start gap-3">
        <!-- Checkbox -->
        <input
            type="checkbox"
            id="{{ $name }}"
            name="{{ $name }}"
            value="{{ $value }}"
            {{ $attributes->except(['class', 'name', 'value', 'checked'])->merge([
                'class' => $checkboxClasses,
                'disabled' => $disabled,
            ]) }}
            {{ $isChecked ? 'checked' : '' }}
        >
        
        <!-- Label & Description -->
        @if($label || $slot->isNotEmpty())
            <div class="flex-1">
                <label for="{{ $name }}" class="text-sm font-medium cursor-pointer">
                    {{ $label ?? $slot }}
                </label>
                
                @if($hint)
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $hint }}</p>
                @endif
                
                @if($errorMessage)
                    <p class="mt-0.5 text-xs text-red-600 dark:text-red-400 flex items-center gap-1">
                        <i data-lucide="alert-circle" class="w-3 h-3"></i>
                        {{ $errorMessage }}
                    </p>
                @endif
            </div>
        @endif
    </div>
</div>
