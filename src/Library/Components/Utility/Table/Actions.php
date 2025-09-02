<?php

namespace Canvastack\Canvastack\Library\Components\Utility\Table;

final class Actions
{
    /**
     * Build action button HTML based on row data, current URL, action config, and removed buttons.
     * Mirrors legacy canvastack_table_action_button behavior.
     */
    public static function build($row_data, string $field_target, string $current_url, $action, $removed_button = null): string
    {
        // Privileges from session (safe in non-Laravel test context)
        $privileges = [];
        try {
            if (function_exists('session')) {
                $privileges = session()->all()['privileges']['role'] ?? [];
            }
        } catch (\Throwable $e) {
            $privileges = [];
        }
        $path = [];
        $addActions = [];
        $add_path = [];
        $enabledAction = [
            'read' => true,
            'insert' => true,
            'modify' => true,
            'delete' => true,
        ];

        $actions = [];
        if (in_array(current_route(), $privileges)) {
            foreach ($privileges as $roles) {
                if (canvastack_string_contained($roles, routelists_info()['base_info'])) {
                    if (! in_array(routelists_info($roles)['last_info'], ['index', 'insert', 'update', 'destroy'])) {
                        $actions[routelists_info()['base_info']][] = routelists_info($roles)['last_info'];
                    }
                }
            }
            $actionType = [ 'custom' => [], 'default' => $actions[routelists_info()['base_info']] ?? [] ];
            foreach ((array) $action as $ai => $actval) {
                if (in_array($actval, ['index', 'show', 'view', 'create', 'insert', 'add', 'edit', 'update', 'modify', 'delete', 'destroy'])) {
                    unset($action[$ai]);
                } else {
                    $actionType['custom'][] = $actval;
                }
            }
            $action = array_merge_recursive($actionType['default'], $actionType['custom']);
        }

        // removal logic
        if (! empty($removed_button) && is_array($removed_button)) {
            $actionNode = array_flip((array) $action);
            foreach ($removed_button as $remove) {
                if (in_array($remove, ['index', 'show', 'view', 'read'])) {
                    $enabledAction['read'] = false;
                    foreach (['view', 'index', 'show'] as $k) {
                        if (! empty($actionNode[$k])) unset($action[$actionNode[$k]]);
                    }
                } elseif (in_array($remove, ['create', 'insert', 'add'])) {
                    $enabledAction['insert'] = false;
                    foreach (['create', 'insert', 'add'] as $k) {
                        if (! empty($actionNode[$k])) unset($action[$actionNode[$k]]);
                    }
                } elseif (in_array($remove, ['edit', 'update', 'modify'])) {
                    $enabledAction['modify'] = false;
                    foreach (['edit', 'update', 'modify'] as $k) {
                        if (! empty($actionNode[$k])) unset($action[$actionNode[$k]]);
                    }
                } elseif (in_array($remove, ['delete', 'destroy'])) {
                    $enabledAction['delete'] = false;
                    foreach (['delete', 'destroy'] as $k) {
                        if (! empty($actionNode[$k])) unset($action[$actionNode[$k]]);
                    }
                } else {
                    $enabledAction[$removed_button] = false;
                }
            }
        }

        // additional action buttons
        if (is_array($action)) {
            foreach ($action as $action_data) {
                if (canvastack_string_contained($action_data, '|')) {
                    $action_info = \Canvastack\Canvastack\Library\Components\Utility\Canvatility::addActionButtonByString($action_data);
                    $addActions[key($action_info)] = $action_info[key($action_info)];
                    $enabledAction[key($action_info)] = true;
                } else {
                    $addActions[$action_data] = \Canvastack\Canvastack\Library\Components\Utility\Canvatility::addActionButtonByString("{$action_data}|default|link");
                    $enabledAction[$action_data] = true;
                }
            }
        } elseif (is_string($action)) {
            if (canvastack_string_contained($action, '|')) {
                $addActions = \Canvastack\Canvastack\Library\Components\Utility\Canvatility::addActionButtonByString($action);
            } else {
                $addActions = \Canvastack\Canvastack\Library\Components\Utility\Canvatility::addActionButtonByString("{$action}|default|link");
            }
        }

        // default path
        $urlTarget = $row_data->{$field_target};
        $path['view'] = "{$current_url}/{$urlTarget}";
        $path['edit'] = "{$current_url}/{$urlTarget}/edit";
        $path['delete'] = !empty($row_data->deleted_at)
            ? "{$current_url}/{$urlTarget}/restore_deleted"
            : "{$current_url}/{$urlTarget}/delete";

        if ($enabledAction['read'] === false) $path['view'] = false;
        if ($enabledAction['modify'] === false) $path['edit'] = false;
        if ($enabledAction['delete'] === false) $path['delete'] = false;

        if (count($addActions) >= 1) {
            foreach ($addActions as $action_name => $action_values) {
                if (! in_array($action_name, ['show', 'view', 'create', 'edit', 'delete'])) {
                    $add_path[$action_name]['url'] = "{$current_url}/{$urlTarget}/{$action_name}";
                    if (is_array($action_values)) {
                        foreach ($action_values as $actionKey => $actionValue) {
                            if ($actionKey === $action_name) {
                                $add_path[$action_name] = $actionValue;
                                $add_path[$action_name]['url'] = "{$current_url}/{$urlTarget}/{$action_name}";
                            } else {
                                if ($add_path === false) { $add_path = []; }
                                if (!isset($add_path[$action_name])) { $add_path[$action_name] = []; }
                                $add_path[$action_name][$actionKey] = $actionValue;
                            }
                        }
                        // inject btn-<name> token for class expectations in tests
                        $add_path[$action_name]['btn_name'] = 'btn-'.$action_name;
                    }
                }
            }
        }

        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::createActionButtons($path['view'] ?? false, $path['edit'] ?? false, $path['delete'] ?? false, $add_path, false);
    }
}