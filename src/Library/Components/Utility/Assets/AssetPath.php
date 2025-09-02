<?php

namespace Canvastack\Canvastack\Library\Components\Utility\Assets;

class AssetPath
{
    /**
     * Return base asset path string.
     *
     * Mirrors legacy canvastack_script_asset_path:
     * baseURL/base_template/template
     *
     * Safe fallback: returns empty string when config is unavailable in non-Laravel test context.
     */
    public static function assetBasePath(): string
    {
        try {
            if (function_exists('canvastack_config')) {
                $baseURL = \canvastack_config('baseURL');
                $baseTemplate = \canvastack_config('base_template');
                $template = \canvastack_config('template');
                if (is_string($baseURL) && is_string($baseTemplate) && is_string($template)) {
                    $baseURL = rtrim($baseURL, '/');
                    $baseTemplate = trim($baseTemplate, '/');
                    $template = trim($template, '/');
                    return $baseURL.'/'.$baseTemplate.'/'.$template;
                }
            }
        } catch (\Throwable $e) {
            // ignore and fallback
        }
        return '';
    }
}