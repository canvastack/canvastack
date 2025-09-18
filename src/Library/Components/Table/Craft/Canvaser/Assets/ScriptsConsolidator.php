<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Assets;

use Canvastack\Canvastack\Core\Craft\Includes\SafeLogger;

/**
 * ScriptsConsolidator
 *
 * Normalizes legacy add_scripts structure into a consistent shape:
 *   [ 'css' => string[], 'js' => string[] ]
 *
 * Behavior parity:
 * - If 'add_js' exists, it will be appended after 'js' entries (preserve order).
 * - If 'css' or 'js' missing, treated as empty arrays.
 * - No de-duplication; mirrors legacy merging.
 */
final class ScriptsConsolidator
{
    /**
     * @param  array  $addScripts Incoming structure possibly with keys: css, js, add_js
     * @return array{css: array<int,string>, js: array<int,string>}
     */
    public static function normalize(array $addScripts): array
    {
        if (app()->environment(['local', 'testing'])) {
            SafeLogger::debug('ScriptsConsolidator: Starting scripts normalization', [
                'input_keys' => array_keys($addScripts),
                'has_css' => !empty($addScripts['css']),
                'has_js' => !empty($addScripts['js']),
                'has_add_js' => !empty($addScripts['add_js'])
            ]);
        }

        $css = [];
        $js = [];

        if (! empty($addScripts['css'])) {
            $css = is_array($addScripts['css']) ? $addScripts['css'] : [(string) $addScripts['css']];
            if (app()->environment(['local', 'testing'])) {
                SafeLogger::debug('ScriptsConsolidator: CSS scripts processed', [
                    'css_count' => count($css),
                    'css_type' => is_array($addScripts['css']) ? 'array' : 'string'
                ]);
            }
        }

        if (! empty($addScripts['js'])) {
            $js = is_array($addScripts['js']) ? $addScripts['js'] : [(string) $addScripts['js']];
            if (app()->environment(['local', 'testing'])) {
                SafeLogger::debug('ScriptsConsolidator: JS scripts processed', [
                    'js_count' => count($js),
                    'js_type' => is_array($addScripts['js']) ? 'array' : 'string'
                ]);
            }
        }

        // Legacy path also aggregates 'add_js' into JS stack
        if (! empty($addScripts['add_js'])) {
            $addJs = is_array($addScripts['add_js']) ? $addScripts['add_js'] : [(string) $addScripts['add_js']];
            foreach ($addJs as $script) {
                $js[] = $script;
            }
            if (app()->environment(['local', 'testing'])) {
                SafeLogger::debug('ScriptsConsolidator: Additional JS scripts merged', [
                    'add_js_count' => count($addJs),
                    'total_js_count' => count($js)
                ]);
            }
        }

        $result = ['css' => $css, 'js' => $js];
        
        if (app()->environment(['local', 'testing'])) {
            SafeLogger::debug('ScriptsConsolidator: Scripts normalization completed', [
                'final_css_count' => count($result['css']),
                'final_js_count' => count($result['js'])
            ]);
        }

        return $result;
    }
}
