<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Actions;

use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Contracts\TableContext;

/**
 * Compose action column and removed privileges per legacy logic.
 */
final class ActionButtonsResolver
{
    /**
     * @param  \Yajra\DataTables\DataTableAbstract  $datatables
     * @param  array  $privileges Legacy privileges (role_group, role, etc.)
     * @param  array  $actionList Merged action list (view/insert/edit/delete + custom)
     * @param  array  $buttonsRemoval From columns config button_removed (optional)
     * @param  array  $removedPrivileges Precomputed removed privileges (optional)
     */
    public static function apply($datatables, TableContext $ctx, array $privileges, array $actionList, array $buttonsRemoval = [], array $removedPrivileges = []): void
    {
        $data = $ctx->data;
        $table = $ctx->tableName;
        $model = $ctx->method['difta']['name'] ?? 'unknown';

        // Build action_data structure as legacy
        $action_data = [];
        $action_data['model'] = $model; // kept for parity though legacy passes $model instance; not used directly in renderer
        $action_data['current_url'] = canvastack_current_url();
        $action_data['action']['data'] = $actionList;
        if (($privileges['role_group'] ?? 0) > 1) {
            if (! empty($removedPrivileges)) {
                $action_data['action']['removed'] = $removedPrivileges;
            } else {
                $action_data['action']['removed'] = $data->datatables->button_removed ?? [];
            }
        } else {
            $action_data['action']['removed'] = $data->datatables->button_removed ?? [];
        }

        if (! empty($buttonsRemoval)) {
            $removeActions = $action_data['action']['removed'];
            unset($action_data['action']['removed']);
            $action_data['action']['removed'] = array_merge_recursive_distinct($buttonsRemoval, $removeActions);
        }

        $urlTarget = $data->datatables->useFieldTargetURL ?? 'id';

        $datatables->addColumn('action', function ($row) use ($action_data, $urlTarget, $data, $table) {
            // Try legacy helper first; fallback to safe renderer when helper not available
            if (function_exists('canvastack_table_action_button')) {
                return canvastack_table_action_button($row, $urlTarget, $action_data['current_url'], $action_data['action']['data'], $action_data['action']['removed']);
            }
            // Normalize row to array
            $arr = method_exists($row, 'getAttributes') ? $row->getAttributes() : (array) $row;
            return \Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Support\ActionButtonsRenderer::render($arr, $data->datatables, $table);
        });
    }
}
