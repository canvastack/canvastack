@props(['items' => []])

<nav aria-label="{{ __('ui.aria.breadcrumb') }}" {{ $attributes->merge(['class' => 'flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-6']) }}>
    @foreach($items as $index => $item)
        @if($index > 0)
            <i data-lucide="chevron-right" class="w-3 h-3"></i>
        @endif
        
        @if(isset($item['url']) && !$loop->last)
            <a 
                href="{{ $item['url'] }}" 
                class="hover:text-indigo-600 dark:hover:text-indigo-400 transition"
            >
                {{ $item['label'] }}
            </a>
        @else
            <span class="text-gray-900 dark:text-gray-100 font-medium">
                {{ $item['label'] }}
            </span>
        @endif
    @endforeach
</nav>
