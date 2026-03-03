@extends('canvastack::layouts.admin')

@section('title', $themeData['display_name'] . ' Theme')

@push('head')
    {{-- Meta Tags --}}
    {!! $meta->tags() !!}
@endpush

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-4">
            <a 
                href="{{ route('admin.themes.index') }}"
                class="btn btn-ghost btn-circle"
            >
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                    {{ $themeData['display_name'] }}
                </h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">
                    {{ $themeData['description'] }}
                </p>
            </div>
        </div>
        
        <div class="flex items-center gap-3">
            @if(!$isActive)
                <form action="{{ route('admin.themes.activate', $themeData['name']) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary gap-2">
                        <i data-lucide="check-circle" class="w-5 h-5"></i>
                        Activate Theme
                    </button>
                </form>
            @else
                <span class="badge badge-lg badge-success gap-2">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    Active Theme
                </span>
            @endif
            
            <a 
                href="{{ route('admin.themes.export', [$themeData['name'], 'json']) }}"
                class="btn btn-outline gap-2"
                download
            >
                <i data-lucide="download" class="w-5 h-5"></i>
                Export
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left Column: Theme Info --}}
        <div class="lg:col-span-1 space-y-6">
            {{-- Theme Preview --}}
            <div class="card bg-white dark:bg-gray-900 shadow-lg">
                <div class="card-body">
                    <h2 class="card-title text-gray-900 dark:text-gray-100 mb-4">
                        Theme Preview
                    </h2>
                    
                    <x-canvastack::ui.theme-thumbnail 
                        :theme="$theme"
                        variant="gradient"
                        :width="400"
                        :height="225"
                        class="rounded-xl overflow-hidden mb-4"
                    />
                    
                    <x-canvastack::ui.theme-thumbnail 
                        :theme="$theme"
                        variant="palette"
                        :width="400"
                        :height="150"
                        class="rounded-xl overflow-hidden"
                    />
                </div>
            </div>

            {{-- Theme Metadata --}}
            <div class="card bg-white dark:bg-gray-900 shadow-lg">
                <div class="card-body">
                    <h2 class="card-title text-gray-900 dark:text-gray-100 mb-4">
                        Information
                    </h2>
                    
                    <div class="space-y-3">
                        <div>
                            <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Version</label>
                            <p class="text-gray-900 dark:text-gray-100 font-semibold">
                                v{{ $themeData['version'] }}
                            </p>
                        </div>
                        
                        <div>
                            <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Author</label>
                            <p class="text-gray-900 dark:text-gray-100 font-semibold">
                                {{ $themeData['author'] }}
                            </p>
                        </div>
                        
                        <div>
                            <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Dark Mode</label>
                            <p class="text-gray-900 dark:text-gray-100 font-semibold">
                                @if($themeData['dark_mode'])
                                    <span class="badge badge-success gap-1">
                                        <i data-lucide="moon" class="w-3 h-3"></i>
                                        Supported
                                    </span>
                                @else
                                    <span class="badge badge-ghost gap-1">
                                        <i data-lucide="sun" class="w-3 h-3"></i>
                                        Not Supported
                                    </span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Column: Theme Configuration --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Color Palette --}}
            <div class="card bg-white dark:bg-gray-900 shadow-lg">
                <div class="card-body">
                    <h2 class="card-title text-gray-900 dark:text-gray-100 mb-4">
                        Color Palette
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @foreach(['primary', 'secondary', 'accent', 'success', 'warning', 'error', 'info'] as $colorName)
                            @if(isset($themeData['colors'][$colorName]))
                                @php
                                    $colorData = $themeData['colors'][$colorName];
                                    $isArray = is_array($colorData);
                                @endphp
                                
                                <div>
                                    <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100 mb-2 capitalize">
                                        {{ $colorName }}
                                    </h3>
                                    
                                    @if($isArray)
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($colorData as $shade => $color)
                                                <div class="flex flex-col items-center">
                                                    <div 
                                                        class="w-12 h-12 rounded-lg border-2 border-gray-300 dark:border-gray-700 cursor-pointer hover:scale-110 transition-transform"
                                                        style="background-color: {{ $color }}"
                                                        title="{{ $colorName }}-{{ $shade }}: {{ $color }}"
                                                        onclick="copyToClipboard('{{ $color }}')"
                                                    ></div>
                                                    <span class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                                        {{ $shade }}
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="flex items-center gap-3">
                                            <div 
                                                class="w-16 h-16 rounded-lg border-2 border-gray-300 dark:border-gray-700 cursor-pointer hover:scale-110 transition-transform"
                                                style="background-color: {{ $colorData }}"
                                                title="{{ $colorName }}: {{ $colorData }}"
                                                onclick="copyToClipboard('{{ $colorData }}')"
                                            ></div>
                                            <div>
                                                <code class="text-sm text-gray-700 dark:text-gray-300">
                                                    {{ $colorData }}
                                                </code>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Gradients --}}
            @if(!empty($themeData['gradient']))
                <div class="card bg-white dark:bg-gray-900 shadow-lg">
                    <div class="card-body">
                        <h2 class="card-title text-gray-900 dark:text-gray-100 mb-4">
                            Gradients
                        </h2>
                        
                        <div class="space-y-4">
                            @foreach($themeData['gradient'] as $gradientName => $gradientValue)
                                <div>
                                    <label class="text-sm font-medium text-gray-600 dark:text-gray-400 capitalize mb-2 block">
                                        {{ str_replace('_', ' ', $gradientName) }}
                                    </label>
                                    <div 
                                        class="h-24 rounded-xl cursor-pointer hover:scale-[1.02] transition-transform"
                                        style="background: {{ $gradientValue }}"
                                        title="{{ $gradientValue }}"
                                        onclick="copyToClipboard('{{ $gradientValue }}')"
                                    ></div>
                                    <code class="text-xs text-gray-600 dark:text-gray-400 mt-2 block">
                                        {{ $gradientValue }}
                                    </code>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            {{-- Typography --}}
            @if(!empty($themeData['fonts']))
                <div class="card bg-white dark:bg-gray-900 shadow-lg">
                    <div class="card-body">
                        <h2 class="card-title text-gray-900 dark:text-gray-100 mb-4">
                            Typography
                        </h2>
                        
                        <div class="space-y-4">
                            @foreach($themeData['fonts'] as $fontName => $fontValue)
                                <div>
                                    <label class="text-sm font-medium text-gray-600 dark:text-gray-400 capitalize mb-2 block">
                                        {{ $fontName }} Font
                                    </label>
                                    <div 
                                        class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg"
                                        style="font-family: {{ $fontValue }}"
                                    >
                                        <p class="text-2xl text-gray-900 dark:text-gray-100">
                                            The quick brown fox jumps over the lazy dog
                                        </p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                                            ABCDEFGHIJKLMNOPQRSTUVWXYZ<br>
                                            abcdefghijklmnopqrstuvwxyz<br>
                                            0123456789
                                        </p>
                                    </div>
                                    <code class="text-xs text-gray-600 dark:text-gray-400 mt-2 block">
                                        {{ $fontValue }}
                                    </code>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            {{-- Layout Configuration --}}
            @if(!empty($themeData['layout']))
                <div class="card bg-white dark:bg-gray-900 shadow-lg">
                    <div class="card-body">
                        <h2 class="card-title text-gray-900 dark:text-gray-100 mb-4">
                            Layout Configuration
                        </h2>
                        
                        <div class="overflow-x-auto">
                            <table class="table table-zebra w-full">
                                <thead>
                                    <tr>
                                        <th>Property</th>
                                        <th>Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($themeData['layout'] as $key => $value)
                                        <tr>
                                            <td class="font-medium text-gray-900 dark:text-gray-100">
                                                {{ str_replace('_', ' ', ucfirst($key)) }}
                                            </td>
                                            <td class="text-gray-700 dark:text-gray-300">
                                                @if(is_array($value))
                                                    <code class="text-sm">{{ json_encode($value) }}</code>
                                                @else
                                                    <code class="text-sm">{{ $value }}</code>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Initialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
    
    /**
     * Copy text to clipboard
     */
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            // Show toast notification
            const toast = document.createElement('div');
            toast.className = 'alert alert-success fixed bottom-4 right-4 w-auto z-50';
            toast.innerHTML = `
                <i data-lucide="check" class="w-5 h-5"></i>
                <span>Copied to clipboard: ${text}</span>
            `;
            document.body.appendChild(toast);
            
            // Reinitialize icons
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
            
            // Remove after 3 seconds
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }).catch(err => {
            console.error('Failed to copy:', err);
            alert('Failed to copy to clipboard');
        });
    }
</script>
@endpush
@endsection

