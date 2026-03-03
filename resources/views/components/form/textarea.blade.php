@props([
    'name',
    'label' => null,
    'value' => null,
    'placeholder' => null,
    'rows' => 4,
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'error' => null,
    'hint' => null,
    'maxlength' => null,
    'showCount' => false,
])

@php
    $textareaClasses = 'w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition disabled:opacity-50 disabled:cursor-not-allowed resize-y';
    
    if ($error || $errors->has($name)) {
        $textareaClasses .= ' border-red-500 focus:ring-red-500';
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
    
    <!-- Textarea -->
    <textarea
        id="{{ $name }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        placeholder="{{ $placeholder }}"
        {{ $attributes->except(['class', 'name', 'value', 'placeholder', 'rows'])->merge([
            'class' => $textareaClasses,
            'required' => $required,
            'disabled' => $disabled,
            'readonly' => $readonly,
            'maxlength' => $maxlength,
        ]) }}
        @if($showCount && $maxlength)
            x-data="{ count: {{ strlen(old($name, $value) ?? '') }} }"
            x-on:input="count = $el.value.length"
        @endif
    >{{ old($name, $value) }}</textarea>
    
    <!-- Character Count -->
    @if($showCount && $maxlength)
        <div class="flex items-center justify-between mt-1.5">
            @if($hint)
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $hint }}</p>
            @else
                <div></div>
            @endif
            <p class="text-xs text-gray-500 dark:text-gray-400" x-text="`${count}/${maxlength}`"></p>
        </div>
    @elseif($hint)
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
