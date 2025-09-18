<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Support;

use Canvastack\Canvastack\Core\Craft\Includes\SafeLogger;

/**
 * ActionButtonsRenderer
 * - Safe rendering for action buttons with minimal dependency on session/privileges.
 * - If legacy helper exists and environment looks sane, delegate to it.
 * - Otherwise, render a minimal set based on datatables config and removed buttons.
 */
class ActionButtonsRenderer
{
    /**
     * Render action buttons HTML for a given row.
     *
     * @param  array  $row Row data as associative array
     * @param  object  $dt Datatables config object
     * @param  string|null  $table Table name
     * @return string HTML
     */
    public static function render(array $row, object $dt, ?string $table): string
    {
        if (app()->environment(['local', 'testing'])) {
            SafeLogger::debug('ActionButtonsRenderer: Rendering action buttons', [
                'table' => $table,
                'row_keys' => array_keys($row),
                'has_canvastack_current_url' => function_exists('canvastack_current_url')
            ]);
        }

        $currentUrl = '/';
        if (function_exists('canvastack_current_url')) {
            try {
                $currentUrl = \canvastack_current_url();
            } catch (\Throwable $e) {
                $currentUrl = '/';
            }
        }
        $fieldTarget = isset($dt->useFieldTargetURL) ? (string) $dt->useFieldTargetURL : 'id';
        $idVal = $row[$fieldTarget] ?? ($row['id'] ?? null);

        // Resolve actions from config
        $actions = [];
        try {
            $cfg = $table && isset($dt->columns[$table]) ? $dt->columns[$table] : [];
            if (! empty($cfg['actions'])) {
                if ($cfg['actions'] === true) {
                    $actions = ['view', 'insert', 'edit', 'delete'];
                } elseif (is_array($cfg['actions'])) {
                    $defaults = ['view', 'insert', 'edit', 'delete'];
                    // Simple merge unique preserving order
                    $actions = array_values(array_unique(array_merge($defaults, $cfg['actions'])));
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // Apply removed buttons if provided on config
        $removed = [];
        try {
            $removed = is_array($dt->button_removed ?? null) ? $dt->button_removed : [];
        } catch (\Throwable $e) {
            $removed = [];
        }
        if (! empty($removed) && ! empty($actions)) {
            $actions = array_values(array_filter($actions, function ($a) use ($removed) {
                return ! in_array($a, $removed, true);
            }));
        }

        // If helper exists, try delegate
        if (function_exists('canvastack_table_action_button')) {
            try {
                return \canvastack_table_action_button((object) $row, $fieldTarget, $currentUrl, $actions, $removed);
            } catch (\Throwable $e) {
                // fallthrough to minimal renderer
            }
        }

        // Minimal safe renderer (no session), only when we have an id
        if ($idVal === null) {
            return '';
        }

        $html = [];
        foreach ($actions as $action) {
            $label = ucfirst($action);
            $href = rtrim($currentUrl, '/').'/'.$action.'/'.rawurlencode((string) $idVal);
            $html[] = '<a class="btn btn-xs btn-action btn-'.htmlspecialchars($action, ENT_QUOTES, 'UTF-8').'" href="'.htmlspecialchars($href, ENT_QUOTES, 'UTF-8').'">'.htmlspecialchars($label, ENT_QUOTES, 'UTF-8').'</a>';
        }

        return implode(' ', $html);
    }
}
