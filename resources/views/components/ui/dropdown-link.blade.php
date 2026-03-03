@props(['icon' => null])

<a {{ $attributes->merge(['class' => 'flex items-center gap-2 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition']) }}>
    @if($icon)
        <i data-lucide="{{ $icon }}" class="w-4 h-4"></i>
    @endif
    {{ $slot }}
</a>
