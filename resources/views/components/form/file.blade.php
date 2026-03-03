@props([
    'name',
    'label' => null,
    'accept' => null,
    'multiple' => false,
    'required' => false,
    'disabled' => false,
    'error' => null,
    'hint' => null,
    'preview' => false,
])

@php
    $errorMessage = $error ?? $errors->first($name);
@endphp

<div {{ $attributes->only('class') }} x-data="{ 
    files: [], 
    preview: @js($preview),
    handleFiles(event) {
        this.files = Array.from(event.target.files);
    }
}">
    <!-- Label -->
    @if($label)
        <label for="{{ $name }}" class="block text-sm font-medium mb-2">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif
    
    <!-- File Input Container -->
    <div class="relative">
        <input
            type="file"
            id="{{ $name }}"
            name="{{ $name }}{{ $multiple ? '[]' : '' }}"
            accept="{{ $accept }}"
            {{ $attributes->except(['class', 'name', 'accept', 'multiple'])->merge([
                'class' => 'hidden',
                'required' => $required,
                'disabled' => $disabled,
                'multiple' => $multiple,
            ]) }}
            @change="handleFiles($event)"
        >
        
        <!-- Custom File Button -->
        <label 
            for="{{ $name }}" 
            class="flex items-center justify-center gap-2 w-full px-4 py-8 bg-gray-50 dark:bg-gray-800 border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-xl text-sm text-gray-600 dark:text-gray-400 hover:border-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-950/20 transition cursor-pointer"
            :class="{ 'opacity-50 cursor-not-allowed': {{ $disabled ? 'true' : 'false' }} }"
        >
            <i data-lucide="upload" class="w-5 h-5"></i>
            <span x-show="files.length === 0">{{ __('canvastack::components.form.file_upload.drag_drop') }} {{ __('canvastack::components.form.file_upload.or') }} {{ __('canvastack::components.form.file_upload.browse') }}</span>
            <span x-show="files.length > 0" x-text="`${files.length} {{ __('canvastack::components.form.file_upload.files_selected') }}`"></span>
        </label>
    </div>
    
    <!-- File Preview -->
    <div x-show="files.length > 0 && preview" class="mt-3 space-y-2">
        <template x-for="(file, index) in files" :key="index">
            <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <i data-lucide="file" class="w-5 h-5 text-gray-400"></i>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium truncate" x-text="file.name"></p>
                    <p class="text-xs text-gray-500" x-text="(file.size / 1024).toFixed(2) + ' KB'"></p>
                </div>
            </div>
        </template>
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
