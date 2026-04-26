<?php

namespace Canvastack\Canvastack\Library\Helpers;

/**
 * File Upload Configuration Helper
 * 
 * بِسْمِ ٱللَّٰهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ
 * 
 * This helper provides methods to inject file upload configuration
 * into HTML for client-side validation.
 * 
 * @package Canvastack\Canvastack\Library\Helpers
 * @version 1.0.0
 */
class FileUploadConfig
{
    /**
     * Get file upload configuration meta tags
     * 
     * Generates HTML meta tags containing file upload configuration
     * from config/canvastack.controller.php for use by client-side validator.
     * 
     * @return string HTML meta tags
     * 
     * @example
     * // In your layout file (e.g., resources/views/layouts/app.blade.php):
     * <head>
     *     {!! \Canvastack\Canvastack\Library\Helpers\FileUploadConfig::getMetaTags() !!}
     * </head>
     */
    public static function getMetaTags(): string
    {
        $config = config('canvastack.controller.security', []);
        
        // Get max file size from config, or use PHP's actual limit (whichever is smaller)
        $configMaxSize = $config['max_file_size'] ?? 10485760; // 10MB default
        $phpUploadMax = self::convertToBytes(ini_get('upload_max_filesize'));
        $phpPostMax = self::convertToBytes(ini_get('post_max_size'));
        
        // Use the smallest limit to prevent 413 errors
        $maxFileSize = min($configMaxSize, $phpUploadMax, $phpPostMax);
        
        $allowedExtensions = $config['allowed_file_extensions'] ?? [
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg',
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
            'txt', 'csv', 'zip', 'rar'
        ];
        
        $extensionsString = implode(',', $allowedExtensions);
        
        $html = '<meta name="canvastack-max-file-size" content="' . htmlspecialchars($maxFileSize, ENT_QUOTES, 'UTF-8') . '">' . "\n";
        $html .= '<meta name="canvastack-allowed-extensions" content="' . htmlspecialchars($extensionsString, ENT_QUOTES, 'UTF-8') . '">';
        
        return $html;
    }
    
    /**
     * Get file upload validator script tag
     * 
     * Generates HTML script tag to include the file upload validator JavaScript.
     * 
     * @return string HTML script tag
     * 
     * @example
     * // In your layout file (e.g., resources/views/layouts/app.blade.php):
     * <body>
     *     ...
     *     {!! \Canvastack\Canvastack\Library\Helpers\FileUploadConfig::getScriptTag() !!}
     * </body>
     */
    public static function getScriptTag(): string
    {
        $assetPath = asset('assets/js/file-upload-validator.js');
        return '<script src="' . htmlspecialchars($assetPath, ENT_QUOTES, 'UTF-8') . '"></script>';
    }
    
    /**
     * Get complete file upload configuration HTML
     * 
     * Generates both meta tags and script tag for complete client-side validation setup.
     * 
     * @return array Array with 'meta' and 'script' keys
     * 
     * @example
     * // In your layout file:
     * $fileUploadConfig = \Canvastack\Canvastack\Library\Helpers\FileUploadConfig::getCompleteConfig();
     * 
     * <head>
     *     {!! $fileUploadConfig['meta'] !!}
     * </head>
     * <body>
     *     ...
     *     {!! $fileUploadConfig['script'] !!}
     * </body>
     */
    public static function getCompleteConfig(): array
    {
        return [
            'meta' => self::getMetaTags(),
            'script' => self::getScriptTag()
        ];
    }
    
    /**
     * Get configuration as JSON for JavaScript
     * 
     * Returns file upload configuration as JSON string for use in inline JavaScript.
     * 
     * @return string JSON configuration
     * 
     * @example
     * <script>
     *     const fileUploadConfig = {!! \Canvastack\Canvastack\Library\Helpers\FileUploadConfig::getConfigJson() !!};
     * </script>
     */
    public static function getConfigJson(): string
    {
        $config = config('canvastack.controller.security', []);
        
        $data = [
            'maxFileSize' => $config['max_file_size'] ?? 10485760,
            'allowedExtensions' => $config['allowed_file_extensions'] ?? [
                'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg',
                'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
                'txt', 'csv', 'zip', 'rar'
            ],
            'maxFileSizeFormatted' => self::formatFileSize($config['max_file_size'] ?? 10485760)
        ];
        
        return json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }
    
    /**
     * Format file size for display
     * 
     * @param int $bytes File size in bytes
     * 
     * @return string Formatted file size
     */
    private static function formatFileSize(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 Bytes';
        }
        
        $k = 1024;
        $sizes = ['Bytes', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes) / log($k));
        
        return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
    }
    
    /**
     * Convert PHP ini size format to bytes
     * 
     * @param string $value Size value (e.g., "40M", "2G")
     * 
     * @return int Size in bytes
     */
    private static function convertToBytes(string $value): int
    {
        $value = trim($value);
        $last = strtolower($value[strlen($value)-1]);
        $value = (int) $value;
        
        switch($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }
        
        return $value;
    }
}
