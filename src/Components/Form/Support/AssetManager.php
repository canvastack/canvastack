<?php

namespace Canvastack\Canvastack\Components\Form\Support;

/**
 * AssetManager - Manages JavaScript and CSS asset loading.
 *
 * Tracks loaded assets to prevent duplicate loading and provides
 * lazy loading strategy for form component dependencies.
 */
class AssetManager
{
    /**
     * Loaded assets tracking.
     */
    protected array $loadedAssets = [];

    /**
     * Rendered assets tracking (to prevent duplicate rendering).
     */
    protected array $renderedAssets = [];

    /**
     * Load CKEditor assets.
     *
     * @return void
     */
    public function loadCKEditor(): void
    {
        if (!$this->isLoaded('ckeditor')) {
            $this->loadedAssets['ckeditor'] = true;
        }
    }

    /**
     * Load Choices.js assets.
     *
     * @return void
     */
    public function loadChoices(): void
    {
        if (!$this->isLoaded('choices')) {
            $this->loadedAssets['choices'] = true;
        }
    }

    /**
     * Load Flatpickr assets.
     *
     * @return void
     */
    public function loadFlatpickr(): void
    {
        if (!$this->isLoaded('flatpickr')) {
            $this->loadedAssets['flatpickr'] = true;
        }
    }

    /**
     * Load Tagify assets.
     *
     * @return void
     */
    public function loadTagify(): void
    {
        if (!$this->isLoaded('tagify')) {
            $this->loadedAssets['tagify'] = true;
        }
    }

    /**
     * Check if asset is loaded.
     *
     * @param string $asset Asset name
     * @return bool True if asset is loaded
     */
    public function isLoaded(string $asset): bool
    {
        return isset($this->loadedAssets[$asset]);
    }

    /**
     * Get all loaded assets.
     *
     * @return array Array of loaded asset names
     */
    public function getLoadedAssets(): array
    {
        return array_keys($this->loadedAssets);
    }

    /**
     * Render asset loading script.
     *
     * Generates JavaScript module code to lazy load required assets.
     *
     * @return string JavaScript code for asset loading
     */
    public function renderScript(): string
    {
        $assets = $this->getLoadedAssets();

        if (empty($assets)) {
            return '';
        }

        $imports = [];
        foreach ($assets as $asset) {
            $imports[] = "await window.CanvastackForm.load{$this->capitalize($asset)}();";
        }

        $importsHtml = implode("\n            ", $imports);

        return <<<JS
        <script type="module">
        (async function() {
            try {
                {$importsHtml}
            } catch (error) {
                console.error('Failed to load form assets:', error);
            }
        })();
        </script>
        JS;
    }

    /**
     * Capitalize asset name for method naming.
     *
     * @param string $name Asset name
     * @return string Capitalized name
     */
    protected function capitalize(string $name): string
    {
        return ucfirst($name);
    }

    /**
     * Render asset tags for a specific asset (CSS and JS).
     *
     * @param string $asset Asset name (ckeditor, choices, flatpickr, tagify)
     * @param bool $lazy Whether to use lazy loading (defer attribute)
     * @return string HTML tags for loading the asset
     */
    public function renderAssetTags(string $asset = '', bool $lazy = true): string
    {
        // If no specific asset provided, render all loaded assets
        if (empty($asset)) {
            $assets = $this->getLoadedAssets();
            if (empty($assets)) {
                return '';
            }

            $html = '';
            foreach ($assets as $assetName) {
                $html .= $this->renderAssetTags($assetName, $lazy);
            }

            return $html;
        }

        // Check if asset already rendered (prevent duplicate rendering)
        if (isset($this->renderedAssets[$asset])) {
            return '';
        }

        // Mark asset as loaded and rendered
        if (!$this->isLoaded($asset)) {
            $this->loadedAssets[$asset] = true;
        }
        $this->renderedAssets[$asset] = true;

        $html = '';
        $deferAttr = $lazy ? ' defer' : '';

        // Add CSS and JS for specific asset
        if ($asset === 'ckeditor') {
            // CKEditor 4 (classic)
            $html .= '<script src="https://cdn.ckeditor.com/4.22.1/standard-all/ckeditor.js"' . $deferAttr . '></script>' . "\n";
        } elseif ($asset === 'choices') {
            $html .= '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/styles/choices.min.css">' . "\n";
            $html .= '<script src="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/scripts/choices.min.js"' . $deferAttr . '></script>' . "\n";
        } elseif ($asset === 'flatpickr') {
            $html .= '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">' . "\n";
            $html .= '<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"' . $deferAttr . '></script>' . "\n";
        } elseif ($asset === 'tagify') {
            $html .= '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@yaireo/tagify@4.17.9/dist/tagify.css">' . "\n";
            $html .= '<script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify@4.17.9/dist/tagify.min.js"' . $deferAttr . '></script>' . "\n";
        }

        return $html;
    }
}
