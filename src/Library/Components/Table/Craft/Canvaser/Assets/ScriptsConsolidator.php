<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Assets;

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
        $css = [];
        $js = [];

        if (! empty($addScripts['css'])) {
            $css = is_array($addScripts['css']) ? $addScripts['css'] : [(string) $addScripts['css']];
        }

        if (! empty($addScripts['js'])) {
            $js = is_array($addScripts['js']) ? $addScripts['js'] : [(string) $addScripts['js']];
        }

        // Legacy path also aggregates 'add_js' into JS stack
        if (! empty($addScripts['add_js'])) {
            $addJs = is_array($addScripts['add_js']) ? $addScripts['add_js'] : [(string) $addScripts['add_js']];
            foreach ($addJs as $script) {
                $js[] = $script;
            }
        }

        return ['css' => $css, 'js' => $js];
    }
}
