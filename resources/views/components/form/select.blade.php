@props([
    'name',
    'label' => null,
    'value' => null,
    'options' => [],
    'placeholder' => null,
    'required' => false,
    'disabled' => false,
    'error' => null,
    'hint' => null,
    'icon' => null,
])

@php
    $selectClasses = 'w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition disabled:opacity-50 disabled:cursor-not-allowed appearance-none';
    
    if ($icon) {
        $selectClasses .= ' pl-10';
    }
    
    if ($error || $errors->has($name)) {
        $selectClasses .= ' border-red-500 focus:ring-red-500';
    }
    
    $errorMessage = $error ?? $errors->first($name);
    $selectedValue = old($name, $value);
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
    
    <!-- Select Container -->
    <div class="relative">
        <!-- Icon -->
        @if($icon)
            <div class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                <i data-lucide="{{ $icon }}" class="w-4 h-4"></i>
            </div>
        @endif
        
        <!-- Select -->
        <select
            id="{{ $name }}"
            name="{{ $name }}"
            {{ $attributes->except(['class', 'name', 'value', 'options'])->merge([
                'class' => $selectClasses,
                'required' => $required,
                'disabled' => $disabled,
            ]) }}
        >
            @if($placeholder)
                <option value="">{{ $placeholder }}</option>
            @endif
            
            @foreach($options as $optionValue => $optionLabel)
                @if(is_array($optionLabel))
                    <!-- Option Group -->
                    <optgroup label="{{ $optionValue }}">
                        @foreach($optionLabel as $groupValue => $groupLabel)
                            <option 
                                value="{{ $groupValue }}" 
                                {{ $selectedValue == $groupValue ? 'selected' : '' }}
                            >
                                {{ $groupLabel }}
                            </option>
                        @endforeach
                    </optgroup>
                @else
                    <!-- Regular Option -->
                    <option 
                        value="{{ $optionValue }}" 
                        {{ $selectedValue == $optionValue ? 'selected' : '' }}
                    >
                        {{ $optionLabel }}
                    </option>
                @endif
            @endforeach
        </select>
        
        <!-- Chevron Icon -->
        <div class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
            <i data-lucide="chevron-down" class="w-4 h-4"></i>
        </div>
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
