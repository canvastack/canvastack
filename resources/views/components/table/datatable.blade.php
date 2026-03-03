@props([
    'headers' => [],
    'rows' => [],
    'striped' => true,
    'hoverable' => true,
    'responsive' => true,
])

@php
    $tableClasses = 'w-full text-sm text-left';
    $containerClasses = $responsive ? 'overflow-x-auto' : '';
@endphp

<div {{ $attributes->merge(['class' => $containerClasses]) }}>
    <table class="{{ $tableClasses }}">
        <!-- Table Header -->
        @if(count($headers) > 0)
            <thead class="text-xs font-semibold uppercase bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700">
                <tr>
                    @foreach($headers as $header)
                        <th 
                            scope="col" 
                            class="px-4 py-3 {{ $header['class'] ?? '' }}"
                            @if(isset($header['sortable']) && $header['sortable'])
                                class="cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                            @endif
                        >
                            <div class="flex items-center gap-2">
                                {{ $header['label'] ?? $header }}
                                @if(isset($header['sortable']) && $header['sortable'])
                                    <i data-lucide="chevrons-up-down" class="w-3 h-3"></i>
                                @endif
                            </div>
                        </th>
                    @endforeach
                </tr>
            </thead>
        @endif
        
        <!-- Table Body -->
        <tbody>
            @forelse($rows as $index => $row)
                <tr class="border-b border-gray-200 dark:border-gray-700 {{ $striped && $index % 2 === 0 ? 'bg-white dark:bg-gray-900' : 'bg-gray-50 dark:bg-gray-800' }} {{ $hoverable ? 'hover:bg-gray-100 dark:hover:bg-gray-700 transition' : '' }}">
                    @if(is_array($row))
                        @foreach($row as $cell)
                            <td class="px-4 py-3">
                                {{ $cell }}
                            </td>
                        @endforeach
                    @else
                        <td class="px-4 py-3" colspan="{{ count($headers) }}">
                            {{ $row }}
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headers) }}" class="px-4 py-12 text-center">
                        <div class="flex flex-col items-center gap-3 text-gray-500 dark:text-gray-400">
                            <i data-lucide="inbox" class="w-12 h-12 opacity-50"></i>
                            <p class="text-sm font-medium">{{ __('components.table.no_data') }}</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
        
        <!-- Table Footer (optional) -->
        @if(isset($footer))
            <tfoot class="text-xs font-semibold uppercase bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-t border-gray-200 dark:border-gray-700">
                {{ $footer }}
            </tfoot>
        @endif
    </table>
</div>
