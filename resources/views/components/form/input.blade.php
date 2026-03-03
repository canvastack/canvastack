@props([
    'type' => 'text',
    'name',
    'label' => null,
    'value' => null,
    'placeholder' => null,
    'icon' => null,
    'iconPosition' => 'left',
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'error' => null,
    'hint' => null,
])

@php
    $inputClasses = 'w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition disabled:opacity-50 disabled:cursor-not-allowed';
    
    if ($icon) {
        $inputClasses .= $iconPosition === 'left' ? ' pl-10' : ' pr-10';
    }
    
    if ($error || $errors->has($name)) {
        $inputClasses .= ' border-red-500 focus:ring-red-500';
    }
    
    $errorMessage = $error ?? $errors->first($name);
@endphp

<div {{ $attributes->only('class') }}>
    <!-- Label -->
    @if($label)
        <label for="{{ $name }}" class="block text-sm font-medium mb-2">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif
    
    <!-- Input Container -->
    <div class="relative">
        <!-- Icon -->
        @if($icon)
            <div class="absolute {{ $iconPosition === 'left' ? 'left-3' : 'right-3' }} top-1/2 -translate-y-1/2 text-gray-400">
                <i data-lucide="{{ $icon }}" class="w-4 h-4"></i>
            </div>
        @endif
        
        <!-- Input -->
        <input
            type="{{ $type }}"
            id="{{ $name }}"
            name="{{ $name }}"
            value="{{ old($name, $value) }}"
            placeholder="{{ $placeholder }}"
            {{ $attributes->except(['class', 'type', 'name', 'value', 'placeholder'])->merge([
                'class' => $inputClasses,
                'required' => $required,
                'disabled' => $disabled,
                'readonly' => $readonly,
            ]) }}
        >
    </div>
    
    <!-- Hint -->
    @if($hint)
        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">{{ $hint }}</p>
    @endif
    
    <!-- Error Message -->
    @if($errorMessage)
        <p class="mt-1.5 text-xs text-red-600 dark:text-red-400 flex items-center gap-1">
            <i data-lucide="alert-circle" class="w-3 h-3"></i>
            {{ $errorMessage }}
        </p>
    @endif
</div>
