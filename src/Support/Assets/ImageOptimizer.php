<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Assets;

/**
 * Image Optimizer
 *
 * Provides runtime image optimization utilities.
 */
class ImageOptimizer
{
    /**
     * Generate responsive image srcset.
     *
     * @param string $imagePath Original image path
     * @param array<int> $sizes Array of widths (e.g., [320, 640, 1024, 1920])
     * @return string Srcset attribute value
     */
    public function generateSrcset(string $imagePath, array $sizes = [320, 640, 1024, 1920]): string
    {
        $srcset = [];

        foreach ($sizes as $width) {
            $srcset[] = $this->getResizedImageUrl($imagePath, $width) . " {$width}w";
        }

        return implode(', ', $srcset);
    }

    /**
     * Generate sizes attribute for responsive images.
     *
     * @param array<string> $breakpoints Array of media queries and sizes
     * @return string Sizes attribute value
     */
    public function generateSizes(array $breakpoints = []): string
    {
        if (empty($breakpoints)) {
            // Default responsive sizes
            $breakpoints = [
                '(max-width: 640px) 100vw',
                '(max-width: 1024px) 50vw',
                '33vw',
            ];
        }

        return implode(', ', $breakpoints);
    }

    /**
     * Get WebP version of image if available.
     *
     * @param string $imagePath Original image path
     * @return string WebP image path or original if WebP not available
     */
    public function getWebpVersion(string $imagePath): string
    {
        $pathInfo = pathinfo($imagePath);
        $webpPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.webp';

        // Check if WebP version exists
        if (file_exists(public_path($webpPath))) {
            return $webpPath;
        }

        return $imagePath;
    }

    /**
     * Generate picture element with WebP fallback.
     *
     * @param string $imagePath Original image path
     * @param string $alt Alt text
     * @param array<string> $attributes Additional attributes
     * @return string HTML picture element
     */
    public function generatePicture(string $imagePath, string $alt = '', array $attributes = []): string
    {
        $webpPath = $this->getWebpVersion($imagePath);
        $srcset = $this->generateSrcset($imagePath);
        $sizes = $this->generateSizes();

        $attrs = $this->buildAttributes($attributes);

        $html = '<picture>';

        // WebP source
        if ($webpPath !== $imagePath) {
            $html .= sprintf(
                '<source type="image/webp" srcset="%s" sizes="%s">',
                htmlspecialchars($this->generateSrcset($webpPath)),
                htmlspecialchars($sizes)
            );
        }

        // Original format source
        $html .= sprintf(
            '<source srcset="%s" sizes="%s">',
            htmlspecialchars($srcset),
            htmlspecialchars($sizes)
        );

        // Fallback img
        $html .= sprintf(
            '<img src="%s" alt="%s"%s loading="lazy">',
            htmlspecialchars($imagePath),
            htmlspecialchars($alt),
            $attrs
        );

        $html .= '</picture>';

        return $html;
    }

    /**
     * Get resized image URL (placeholder for actual implementation).
     *
     * @param string $imagePath Original image path
     * @param int $width Target width
     * @return string Resized image URL
     */
    protected function getResizedImageUrl(string $imagePath, int $width): string
    {
        // In a real implementation, this would:
        // 1. Check if resized version exists
        // 2. Generate resized version if needed
        // 3. Return URL to resized version
        //
        // For now, return original path
        // This can be integrated with Laravel's image manipulation packages
        // like Intervention Image or Glide

        return $imagePath;
    }

    /**
     * Build HTML attributes string.
     *
     * @param array<string, string> $attributes Attributes array
     * @return string HTML attributes string
     */
    protected function buildAttributes(array $attributes): string
    {
        if (empty($attributes)) {
            return '';
        }

        $attrs = [];
        foreach ($attributes as $key => $value) {
            $attrs[] = sprintf('%s="%s"', $key, htmlspecialchars($value));
        }

        return ' ' . implode(' ', $attrs);
    }

    /**
     * Add lazy loading attribute to image tag.
     *
     * @param string $html Image HTML
     * @return string Image HTML with lazy loading
     */
    public function addLazyLoading(string $html): string
    {
        // Add loading="lazy" if not already present
        if (strpos($html, 'loading=') === false) {
            $html = str_replace('<img ', '<img loading="lazy" ', $html);
        }

        return $html;
    }

    /**
     * Get optimized image attributes for Blade.
     *
     * @param string $imagePath Image path
     * @param string $alt Alt text
     * @param array<string> $options Options (width, height, class, etc.)
     * @return array<string, string> Attributes array
     */
    public function getImageAttributes(string $imagePath, string $alt = '', array $options = []): array
    {
        $attributes = [
            'src' => $imagePath,
            'alt' => $alt,
            'loading' => 'lazy',
        ];

        // Add srcset if responsive
        if ($options['responsive'] ?? true) {
            $attributes['srcset'] = $this->generateSrcset($imagePath);
            $attributes['sizes'] = $this->generateSizes();
        }

        // Add dimensions if provided
        if (isset($options['width'])) {
            $attributes['width'] = (string) $options['width'];
        }

        if (isset($options['height'])) {
            $attributes['height'] = (string) $options['height'];
        }

        // Add class if provided
        if (isset($options['class'])) {
            $attributes['class'] = $options['class'];
        }

        return $attributes;
    }
}
