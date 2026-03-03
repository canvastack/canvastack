@props([
    'paginator',
    'simple' => false,
])

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('ui.aria.pagination_navigation') }}" {{ $attributes->merge(['class' => 'flex items-center justify-between']) }}>
        <!-- Mobile Pagination -->
        <div class="flex justify-between flex-1 sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-500 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 cursor-default leading-5 rounded-xl">
                    {{ __('ui.aria.previous') }}
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 leading-5 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                    {{ __('ui.aria.previous') }}
                </a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 leading-5 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                    {{ __('ui.aria.next') }}
                </a>
            @else
                <span class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-gray-500 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 cursor-default leading-5 rounded-xl">
                    {{ __('ui.aria.next') }}
                </span>
            @endif
        </div>

        <!-- Desktop Pagination -->
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <!-- Results Info -->
            <div>
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    {{ __('ui.pagination.showing') }}
                    <span class="font-medium">{{ $paginator->firstItem() }}</span>
                    {{ __('ui.pagination.to') }}
                    <span class="font-medium">{{ $paginator->lastItem() }}</span>
                    {{ __('ui.pagination.of') }}
                    <span class="font-medium">{{ $paginator->total() }}</span>
                    {{ __('ui.pagination.results') }}
                </p>
            </div>

            <!-- Pagination Links -->
            <div>
                @if($simple)
                    <!-- Simple Pagination -->
                    <div class="flex items-center gap-2">
                        @if ($paginator->onFirstPage())
                            <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-500 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 cursor-default leading-5 rounded-xl">
                                {{ __('ui.aria.previous') }}
                            </span>
                        @else
                            <a href="{{ $paginator->previousPageUrl() }}" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 leading-5 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                                {{ __('ui.aria.previous') }}
                            </a>
                        @endif

                        @if ($paginator->hasMorePages())
                            <a href="{{ $paginator->nextPageUrl() }}" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 leading-5 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                                {{ __('ui.aria.next') }}
                            </a>
                        @else
                            <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-500 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 cursor-default leading-5 rounded-xl">
                                {{ __('ui.aria.next') }}
                            </span>
                        @endif
                    </div>
                @else
                    <!-- Full Pagination -->
                    <span class="relative z-0 inline-flex gap-1">
                        {{-- Previous Page Link --}}
                        @if ($paginator->onFirstPage())
                            <span aria-disabled="true" aria-label="{{ __('ui.aria.previous') }}">
                                <span class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-gray-500 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 cursor-default rounded-lg" aria-hidden="true">
                                    <i data-lucide="chevron-left" class="w-4 h-4"></i>
                                </span>
                            </span>
                        @else
                            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition" aria-label="{{ __('ui.aria.previous') }}">
                                <i data-lucide="chevron-left" class="w-4 h-4"></i>
                            </a>
                        @endif

                        {{-- Pagination Elements --}}
                        @foreach ($paginator->links()->elements as $element)
                            {{-- "Three Dots" Separator --}}
                            @if (is_string($element))
                                <span aria-disabled="true">
                                    <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 cursor-default rounded-lg">{{ $element }}</span>
                                </span>
                            @endif

                            {{-- Array Of Links --}}
                            @if (is_array($element))
                                @foreach ($element as $page => $url)
                                    @if ($page == $paginator->currentPage())
                                        <span aria-current="page">
                                            <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-white gradient-bg border border-transparent rounded-lg">{{ $page }}</span>
                                        </span>
                                    @else
                                        <a href="{{ $url }}" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition" aria-label="{{ __('ui.aria.go_to_page', ['page' => $page]) }}">
                                            {{ $page }}
                                        </a>
                                    @endif
                                @endforeach
                            @endif
                        @endforeach

                        {{-- Next Page Link --}}
                        @if ($paginator->hasMorePages())
                            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition" aria-label="{{ __('ui.aria.next') }}">
                                <i data-lucide="chevron-right" class="w-4 h-4"></i>
                            </a>
                        @else
                            <span aria-disabled="true" aria-label="{{ __('ui.aria.next') }}">
                                <span class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-gray-500 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 cursor-default rounded-lg" aria-hidden="true">
                                    <i data-lucide="chevron-right" class="w-4 h-4"></i>
                                </span>
                            </span>
                        @endif
                    </span>
                @endif
            </div>
        </div>
    </nav>
@endif
