@props([
    'theme' => null,
    'variant' => 'gradient', // gradient, palette, split, card
    'width' => 320,
    'height' => 180,
    'class' => '',
])

@php
    if (is_string($theme)) {
        $theme = app('canvastack.theme')->get($theme);
    }
    
    if (!$theme) {
        $theme = app('canvastack.theme')->current();
    }
    
    $generator = new \Canvastack\Canvastack\Support\Theme\ThemeThumbnailGenerator();
    $generator->setDimensions($width, $height);
    $svg = $generator->generate($theme, $variant);
    $dataUri = $generator->generateDataUri($theme, $variant);
@endphp

<div {{ $attributes->merge(['class' => "theme-thumbnail {$class}"]) }}>
    <img 
        src="{{ $dataUri }}" 
        alt="{{ __('canvastack::ui.theme.preview_alt', ['name' => $theme->getDisplayName()]) }}"
        width="{{ $width }}"
        height="{{ $height }}"
        class="w-full h-full object-cover"
        loading="lazy"
    />
</div>

