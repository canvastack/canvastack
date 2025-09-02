<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Assets;

use Canvastack\Canvastack\Library\Components\Utility\Canvatility;

final class AssetPathHelper
{
    public static function toPath(string $filePath, bool $http = false, string $publicPath = 'public'): string
    {
        if ($http === true) {
            // Delegate URL building to Utility facade when available to avoid duplication
            try {
                $resolved = Canvatility::checkStringPath($filePath);
                if (is_string($resolved) && $resolved !== '') {
                    return $resolved;
                }
            } catch (\Throwable $e) {
                // fall back to legacy path-join below
            }

            // Legacy fallback (kept for BC when Utility not available):
            try {
                $assetsURL = explode('/', url()->asset('assets'));
                $stringURL = explode('/', $filePath);
                return implode('/', array_unique(array_merge_recursive($assetsURL, $stringURL)));
            } catch (\Throwable $e) {
                // As a last resort, return trimmed input
                return ltrim($filePath);
            }
        }

        // Filesystem path conversion: fallback safely when Laravel helpers unavailable
        try {
            return str_replace($publicPath.'/', public_path('\\'), $filePath);
        } catch (\Throwable $e) {
            return $filePath;
        }
    }
}
