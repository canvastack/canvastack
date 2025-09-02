<?php

namespace Canvastack\Canvastack\Library\Components\Utility\Url;

use Canvastack\Canvastack\Library\Components\Utility\Assets\AssetPath;

class PathResolver
{
    /**
     * Resolve a string path to absolute URL-like path used by the app.
     *
     * Mirrors legacy canvastack_script_check_string_path:
     * - If path already starts with http/https, return as-is
     * - Else prefix with AssetPath::assetBasePath()
     * - If $existCheck is true, return only if remote exists (best-effort); otherwise null
     */
    public static function checkStringPath(string $path, bool $existCheck = false): ?string
    {
        $trimmed = trim($path);
        if ($trimmed === '') {
            return null;
        }

        // already absolute URL
        if (str_starts_with($trimmed, 'http://') || str_starts_with($trimmed, 'https://')) {
            $resolved = $trimmed;
        } else {
            $base = AssetPath::assetBasePath();
            if ($base === '') {
                // No config context: return input to keep behavior predictable in tests
                $resolved = $trimmed;
            } else {
                $resolved = rtrim($base, '/').'/'.ltrim($trimmed, '/');
            }
        }

        if ($existCheck) {
            // Try to use legacy helper if available
            if (function_exists('canvastack_exist_url')) {
                try {
                    return \canvastack_exist_url($resolved) ? $resolved : null;
                } catch (\Throwable $e) {
                    return null;
                }
            }
            // Fallback best-effort: HEAD request via get_headers if available and allowed
            try {
                $headers = @get_headers($resolved);
                if ($headers && is_array($headers) && preg_match('/^HTTP\/[0-9\.]+\s+2\d\d/', $headers[0])) {
                    return $resolved;
                }
            } catch (\Throwable $e) {
                // ignore
            }
            return null;
        }

        return $resolved;
    }
}