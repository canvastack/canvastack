<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Form\Features\FileUpload;

/**
 * ImagePreview - Render image preview widget for file fields.
 */
class ImagePreview
{
    /**
     * Render image preview widget.
     *
     * @param string $fieldName The name of the file input field
     * @param string|null $currentImage The current image path (if editing)
     * @return string HTML for the image preview widget
     */
    public function render(string $fieldName, ?string $currentImage = null): string
    {
        $previewId = "preview-{$fieldName}";
        $imageUrl = $currentImage ? asset('storage/' . $currentImage) : '';
        $displayStyle = $currentImage ? '' : 'display: none;';
        $placeholderStyle = $currentImage ? 'display: none;' : '';

        return <<<HTML
        <div class="image-preview-container mt-2">
            <img 
                id="{$previewId}" 
                src="{$imageUrl}" 
                alt="Preview" 
                class="rounded-lg shadow-md max-w-xs dark:shadow-gray-700"
                style="{$displayStyle}"
            />
            <div id="{$previewId}-placeholder" class="text-gray-400 dark:text-gray-500" style="{$placeholderStyle}">
                <svg class="w-24 h-24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>
        </div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const input = document.querySelector('input[name="{$fieldName}"]');
            const preview = document.getElementById('{$previewId}');
            const placeholder = document.getElementById('{$previewId}-placeholder');
            
            if (input) {
                input.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    
                    if (file && file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        
                        reader.onload = function(e) {
                            preview.src = e.target.result;
                            preview.style.display = 'block';
                            placeholder.style.display = 'none';
                        };
                        
                        reader.readAsDataURL(file);
                    }
                });
            }
        });
        </script>
        HTML;
    }
}
