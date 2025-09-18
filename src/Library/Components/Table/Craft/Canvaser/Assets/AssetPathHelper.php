<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Assets;

use Canvastack\Canvastack\Library\Components\Utility\Canvatility;
use Canvastack\Canvastack\Core\Craft\Includes\SafeLogger;

final class AssetPathHelper
{
    public static function toPath(string $filePath, bool $http = false, string $publicPath = 'public'): string
    {
        if (app()->environment(['local', 'testing'])) {
            SafeLogger::debug('AssetPathHelper: Starting path resolution', [
                'file_path' => $filePath,
                'http_mode' => $http,
                'public_path' => $publicPath
            ]);
        }

        if ($http === true) {
            // Delegate URL building to Utility facade when available to avoid duplication
            try {
                $resolved = Canvatility::checkStringPath($filePath);
                if (is_string($resolved) && $resolved !== '') {
                    if (app()->environment(['local', 'testing'])) {
                        SafeLogger::debug('AssetPathHelper: Path resolved via Canvatility', [
                            'method' => 'canvatility',
                            'resolved_path' => $resolved
                        ]);
                    }
                    return $resolved;
                }
            } catch (\Throwable $e) {
                if (app()->environment(['local', 'testing'])) {
                    SafeLogger::warning('AssetPathHelper: Canvatility resolution failed', [
                        'error_type' => get_class($e),
                        'fallback_method' => 'legacy_path_join'
                    ]);
                }
                // fall back to legacy path-join below
            }

            // Legacy fallback (kept for BC when Utility not available):
            try {
                $assetsURL = explode('/', url()->asset('assets'));
                $stringURL = explode('/', $filePath);
                $result = implode('/', array_unique(array_merge_recursive($assetsURL, $stringURL)));
                if (app()->environment(['local', 'testing'])) {
                    SafeLogger::debug('AssetPathHelper: Path resolved via legacy method', [
                        'method' => 'legacy_url_merge',
                        'resolved_path' => $result
                    ]);
                }
                return $result;
            } catch (\Throwable $e) {
                if (app()->environment(['local', 'testing'])) {
                    SafeLogger::warning('AssetPathHelper: Using final fallback for HTTP path', [
                        'error_type' => get_class($e),
                        'fallback_result' => ltrim($filePath)
                    ]);
                }
                // As a last resort, return trimmed input
                return ltrim($filePath);
            }
        }

        // Filesystem path conversion: fallback safely when Laravel helpers unavailable
        try {
            $result = str_replace($publicPath.'/', public_path('\\'), $filePath);
            if (app()->environment(['local', 'testing'])) {
                SafeLogger::debug('AssetPathHelper: Filesystem path conversion completed', [
                    'method' => 'filesystem_conversion',
                    'original_path' => $filePath,
                    'converted_path' => $result
                ]);
            }
            return $result;
        } catch (\Throwable $e) {
            if (app()->environment(['local', 'testing'])) {
                SafeLogger::warning('AssetPathHelper: Filesystem conversion failed', [
                    'error_type' => get_class($e),
                    'fallback_result' => $filePath
                ]);
            }
            return $filePath;
        }
    }
}
