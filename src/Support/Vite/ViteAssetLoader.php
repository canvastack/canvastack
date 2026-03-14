<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Vite;

use Illuminate\Support\Facades\File;

/**
 * Vite Asset Loader
 * 
 * Helper class to load Vite assets in Laravel.
 * Handles both development (Vite dev server) and production (built assets).
 */
class ViteAssetLoader
{
    /**
     * Base path for Vite assets.
     */
    protected string $basePath;
    
    /**
     * Build directory.
     */
    protected string $buildDirectory = 'build';
    
    /**
     * Vite dev server URL.
     */
    protected string $devServerUrl = 'http://localhost:5173';
    
    /**
     * Manifest cache.
     */
    protected ?array $manifest = null;
    
    /**
     * Constructor.
     */
    public function __construct(string $basePath = null)
    {
        $this->basePath = $basePath ?? base_path('packages/canvastack/canvastack');
        
        // Check if using symlinked build directory
        $symlinkBuildPath = public_path('vendor/canvastack/build');
        if (is_link($symlinkBuildPath) || is_dir($symlinkBuildPath)) {
            $this->buildDirectory = 'vendor/canvastack/build';
        }
    }
    
    /**
     * Check if Vite dev server is running.
     */
    public function isDevServerRunning(): bool
    {
        if (!app()->environment('local')) {
            return false;
        }
        
        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 1,
                    'ignore_errors' => true,
                ],
            ]);
            
            $result = @file_get_contents($this->devServerUrl, false, $context);
            
            return $result !== false;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Get asset URL.
     */
    public function asset(string $path): string
    {
        // Remove leading slash
        $path = ltrim($path, '/');
        
        // Development: Use Vite dev server
        if ($this->isDevServerRunning()) {
            return $this->devServerUrl . '/' . $path;
        }
        
        // Production: Use built assets
        $manifest = $this->getManifest();
        
        if (isset($manifest[$path])) {
            $file = $manifest[$path]['file'];
            return asset($this->buildDirectory . '/' . $file);
        }
        
        // Fallback: Return path as-is
        return asset($this->buildDirectory . '/' . $path);
    }
    
    /**
     * Get manifest.
     */
    protected function getManifest(): array
    {
        if ($this->manifest !== null) {
            return $this->manifest;
        }
        
        $manifestPath = public_path($this->buildDirectory . '/manifest.json');
        
        if (!File::exists($manifestPath)) {
            return [];
        }
        
        $this->manifest = json_decode(File::get($manifestPath), true);
        
        return $this->manifest;
    }
    
    /**
     * Generate script tags for entry point.
     */
    public function scripts(string $entry = 'resources/js/app.js'): string
    {
        // Development: Use Vite dev server
        if ($this->isDevServerRunning()) {
            return $this->devScripts($entry);
        }
        
        // Production: Use built assets
        return $this->prodScripts($entry);
    }
    
    /**
     * Generate script tags for development.
     */
    protected function devScripts(string $entry): string
    {
        $html = '';
        
        // Vite client
        $html .= sprintf(
            '<script type="module" src="%s/@vite/client"></script>' . PHP_EOL,
            $this->devServerUrl
        );
        
        // Entry point
        $html .= sprintf(
            '<script type="module" src="%s/%s"></script>' . PHP_EOL,
            $this->devServerUrl,
            $entry
        );
        
        return $html;
    }
    
    /**
     * Generate script tags for production.
     */
    protected function prodScripts(string $entry): string
    {
        $manifest = $this->getManifest();
        
        if (!isset($manifest[$entry])) {
            return '';
        }
        
        $html = '';
        $entryData = $manifest[$entry];
        
        // Preload CSS
        if (isset($entryData['css'])) {
            foreach ($entryData['css'] as $css) {
                $html .= sprintf(
                    '<link rel="stylesheet" href="%s">' . PHP_EOL,
                    asset($this->buildDirectory . '/' . $css)
                );
            }
        }
        
        // Preload imports
        if (isset($entryData['imports'])) {
            foreach ($entryData['imports'] as $import) {
                if (isset($manifest[$import])) {
                    $importData = $manifest[$import];
                    $html .= sprintf(
                        '<link rel="modulepreload" href="%s">' . PHP_EOL,
                        asset($this->buildDirectory . '/' . $importData['file'])
                    );
                }
            }
        }
        
        // Main script
        $html .= sprintf(
            '<script type="module" src="%s"></script>' . PHP_EOL,
            asset($this->buildDirectory . '/' . $entryData['file'])
        );
        
        return $html;
    }
    
    /**
     * Get CSS files for entry point.
     */
    public function css(string $entry = 'resources/js/app.js'): array
    {
        $manifest = $this->getManifest();
        
        if (!isset($manifest[$entry]['css'])) {
            return [];
        }
        
        return array_map(function ($css) {
            return asset($this->buildDirectory . '/' . $css);
        }, $manifest[$entry]['css']);
    }
    
    /**
     * Get JavaScript files for entry point.
     */
    public function js(string $entry = 'resources/js/app.js'): array
    {
        $manifest = $this->getManifest();
        
        if (!isset($manifest[$entry])) {
            return [];
        }
        
        $files = [
            asset($this->buildDirectory . '/' . $manifest[$entry]['file'])
        ];
        
        // Add imports
        if (isset($manifest[$entry]['imports'])) {
            foreach ($manifest[$entry]['imports'] as $import) {
                if (isset($manifest[$import])) {
                    $files[] = asset($this->buildDirectory . '/' . $manifest[$import]['file']);
                }
            }
        }
        
        return $files;
    }
}
