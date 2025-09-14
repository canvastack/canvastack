<?php

namespace Canvastack\Canvastack\Library\Components\Utility\Html;

final class TableUi
{
    /**
     * Build modal content HTML exactly like legacy helper.
     */
    public static function modalContentHtml(string $name, string $title, array $elements): string
    {
        $buttonID = str_replace('_cdyFILTERmodalBOX', '_submitFilterButton', $name);

        $html  = '<div class="modal-body">';
        $innerDivAttr = ' '.\Canvastack\Canvastack\Library\Components\Utility\Canvatility::attributesToString(['id' => $name]);
        $html .= '<div'.$innerDivAttr.'>';
        $html .= implode('', $elements);
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="modal-footer">';
        $html .= '<div class="diy-action-box">';
        $html .= '<button type="reset" id="'.$name.'-cancel" class="btn btn-danger btn-slideright pull-right" data-dismiss="modal">Cancel</button>';
        $html .= '<button id="'.$buttonID.'" class="btn btn-primary btn-slideright pull-right" type="submit">';
        $html .= '<i class="fa fa-filter"></i> &nbsp; Filter Data '.$title;
        $html .= '</button>';
        $html .= '<button id="exportFilterButton'.$name.'" class="btn btn-info btn-slideright pull-right btn-export-csv hide" type="button">Export to CSV</button>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Parse action string/boolean to attribute map.
     * Mirrors legacy canvastack_add_action_button_by_string.
     */
    public static function addActionButtonByString($action, bool $is_array = false): array
    {
        $addActions = [];
        if (is_bool($action)) {
            if (true === $action) {
                $addActions['view']['color'] = 'success';
                $addActions['view']['icon'] = 'eye';

                $addActions['edit']['color'] = 'primary';
                $addActions['edit']['icon'] = 'pencil';

                $addActions['delete']['color'] = 'danger';
                $addActions['delete']['icon'] = 'times';
            }
        } else {
            if (function_exists('canvastack_string_contained') ? \canvastack_string_contained($action, '|') : (is_string($action) && str_contains($action, '|'))) {
                $str_action = explode('|', (string) $action);
                $str_name = reset($str_action);
            } else {
                $str_action = $action;
                $str_name = false;
            }

            $actionAttr = [];

            if (is_array($str_action) && count($str_action) >= 2) {
                $actionAttr['color'] = false;
                if (isset($str_action[1])) {
                    $actionAttr['color'] = $str_action[1];
                }

                $actionAttr['icon'] = false;
                if (isset($str_action[2])) {
                    $actionAttr['icon'] = $str_action[2];
                }
                $addActions[$str_name] = $actionAttr;
            } else {
                $addActions[$action] = $action;
            }
        }

        return $addActions;
    }

    /**
     * Build action buttons HTML identical to legacy create_action_buttons output.
     * Relies on Laravel helpers (csrf_field, action, canvastack_current_route).
     */
    public static function createActionButtons($view = false, $edit = false, $delete = false, $add_action = [], $as_root = false): string
    {
        $deleteURL = false;
        $delete_id = false;
        $buttonDelete = false;
        $buttonDeleteMobile = false;
        $restoreDeleted = false;

        if (false !== $delete) {
            $deletePath = explode('/', (string) $delete);
            $deleteFlag = end($deletePath);
            $delete_id = intval($deletePath[count($deletePath) - 2] ?? 0);
            $buttonDeleteAttribute = \Canvastack\Canvastack\Library\Components\Utility\Canvatility::attributesToString([
                'class' => 'btn btn-danger btn-xs',
                'data-toggle' => 'tooltip',
                'data-placement' => 'top',
                'data-original-title' => 'Delete',
            ]);
            $iconDeleteAttribute = 'fa fa-times';
            $currentRoute = function_exists('canvastack_current_route') ? \canvastack_current_route() : null;
            $deleteURL = null;
            if (! empty($currentRoute)) {
                $deleteURL = str_replace('@index', '@destroy', $currentRoute->getActionName());
            }

            if ('restore_deleted' === $deleteFlag) {
                $restoreDeleted = true;
                $buttonDeleteAttribute = \Canvastack\Canvastack\Library\Components\Utility\Canvatility::attributesToString([
                    'class' => 'btn btn-warning btn-xs',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'top',
                    'data-original-title' => 'Restore',
                ]);
                $iconDeleteAttribute = 'fa fa-recycle';
            }

            if (!empty($deleteURL)) {
                // Generate unique modal ID for this delete action
                $modalId = 'deleteModal_' . md5($deleteURL . $delete_id);
                $formId = 'deleteForm_' . md5($deleteURL . $delete_id);
                
                // Create hidden form for actual deletion
                $delete_action = '<form id="'.$formId.'" action="'.action($deleteURL, $delete_id).'" method="post" style="display:none;">'.csrf_field().'<input name="_method" type="hidden" value="DELETE"></form>';
                
                // Create button that triggers modal instead of direct submission
                $buttonDeleteAttribute = \Canvastack\Canvastack\Library\Components\Utility\Canvatility::attributesToString([
                    'class' => 'btn btn-danger btn-xs btn_delete_modal',
                    'data-toggle' => 'modal',
                    'data-target' => '#'.$modalId,
                    'data-form-id' => $formId,
                    'data-record-id' => $delete_id,
                    'data-table-name' => $table ?? 'record',
                    'title' => $restoreDeleted ? 'Restore' : 'Delete',
                ]);
                
                $buttonDelete = $delete_action.'<button '.$buttonDeleteAttribute.' type="button"><i class="'.$iconDeleteAttribute.'"></i></button>';
            } else {
                $buttonDelete = '<button '.$buttonDeleteAttribute.' type="button" disabled><i class="'.$iconDeleteAttribute.'"></i></button>';
            }
            $buttonDeleteMobile = '<li><a href="'.$delete.'" class="tooltip-error btn_delete" data-rel="tooltip" title="Delete"><span class="red"><i class="fa fa-trash-o bigger-120"></i></span></a></li>';
        }

        $buttonView = false;
        $buttonViewMobile = false;
        if (false != $view) {
            if (true === $restoreDeleted) {
                $attrs = [
                    'readonly' => true,
                    'disabled' => true,
                    'class' => 'btn btn-default btn-xs btn_view',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'top',
                    'data-original-title' => 'View detail',
                ];
                $viewVisibilityAttr = \Canvastack\Canvastack\Library\Components\Utility\Canvatility::attributesToString($attrs);
            } else {
                $attrs = [
                    'href' => $view,
                    'class' => 'btn btn-success btn-xs btn_view',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'top',
                    'data-original-title' => 'View detail',
                ];
                $viewVisibilityAttr = \Canvastack\Canvastack\Library\Components\Utility\Canvatility::attributesToString($attrs);
            }
            $buttonView = '<a '.$viewVisibilityAttr.'><i class="fa fa-eye"></i></a>';
            $buttonViewMobile = '<li class="btn_view"><a href="'.$view.'" class="tooltip-info" data-rel="tooltip" title="View"><span class="blue"><i class="fa fa-search-plus bigger-120"></i></span></a></li>';
        }

        $buttonEdit = false;
        $buttonEditMobile = false;
        if (false != $edit) {
            if (true === $restoreDeleted) {
                $attrs = [
                    'readonly' => true,
                    'disabled' => true,
                    'class' => 'btn btn-default btn-xs btn_edit',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'top',
                    'data-original-title' => 'Edit',
                ];
                $editVisibilityAttr = \Canvastack\Canvastack\Library\Components\Utility\Canvatility::attributesToString($attrs);
            } else {
                $attrs = [
                    'href' => $edit,
                    'class' => 'btn btn-primary btn-xs btn_edit',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'top',
                    'data-original-title' => 'Edit',
                ];
                $editVisibilityAttr = \Canvastack\Canvastack\Library\Components\Utility\Canvatility::attributesToString($attrs);
            }
            $buttonEdit = '<a  '.$editVisibilityAttr.'><i class="fa fa-pencil"></i></a>';
            $buttonEditMobile = '<li class="btn_edit"><a href="'.$edit.'" class="tooltip-success" data-rel="tooltip" title="Edit"><span class="green"><i class="fa fa-pencil-square-o bigger-120"></i></span></a></li>';
        }

        $buttonNew = '';
        $buttonNewMobile = '';
        if (true === is_array($add_action)) {
            if (count($add_action) >= 1) {
                foreach ($add_action as $new_action_name => $new_action_values) {
                    $btn_name = $new_action_values['btn_name'] ?? $new_action_name; // allow Actions::build to inject extra token 'btn-xxx'
                    $row_name = function_exists('camel_case') ? camel_case($new_action_name) : $new_action_name;
                    // Normalize to lowercase for title attributes to match legacy snapshots for single-word actions
                    $row_title = strtolower($row_name);
                    $row_url = $new_action_values['url'] ?? '';
                    $row_color = null;
                    $row_icon = null;
                    if (! empty($new_action_values['color'])) {
                        $row_color = $new_action_values['color'];
                    }
                    if (! empty($new_action_values['icon'])) {
                        $row_icon = $new_action_values['icon'];
                    }

                    if (true === $restoreDeleted) {
                        $attrs = [
                            'readonly' => true,
                            'disabled' => true,
                            'class' => 'btn btn-default btn-xs',
                            'data-toggle' => 'tooltip',
                            'data-placement' => 'top',
                            'data-original-title' => $row_title,
                        ];
                        $actionVisibilityAttr = \Canvastack\Canvastack\Library\Components\Utility\Canvatility::attributesToString($attrs);
                    } else {
                        // Follow legacy exactly: include btn_name and btn-{$row_color} as-is
                        $attrs = [
                            'href' => $row_url,
                            // Keep legacy: include raw btn name token (e.g., 'approve') along with Bootstrap btn classes
                            'class' => 'btn '.$btn_name.' btn-'.$row_color.' btn-xs',
                            'data-toggle' => 'tooltip',
                            'data-placement' => 'top',
                            'data-original-title' => $row_title,
                        ];
                        $actionVisibilityAttr = \Canvastack\Canvastack\Library\Components\Utility\Canvatility::attributesToString($attrs);
                    }
                    $buttonNew .= '<a '.$actionVisibilityAttr.'><i class="fa fa-'.$row_icon.'"></i></a>';
                    $buttonNewMobile .= '<li><a href="'.$row_url.'" class="tooltip-error" data-rel="tooltip" title="'.$row_title.'"><span class="red"><i class="fa fa-'.$row_icon.' bigger-120"></i></span></a></li>';
                }
            }
        }

        $buttons = $buttonView.$buttonEdit.$buttonDelete.$buttonNew;
        $buttonsMobile = $buttonViewMobile.$buttonEditMobile.$buttonDeleteMobile.$buttonNewMobile;

        // Generate delete confirmation modal if delete button exists
        $modalHtml = '';
        if ($buttonDelete && !empty($deleteURL) && $delete_id) {
            $modalId = 'deleteModal_' . md5($deleteURL . $delete_id);
            $formId = 'deleteForm_' . md5($deleteURL . $delete_id);
            
            // Try to get table name from current route or use default
            $tableName = 'record';
            try {
                $currentRoute = function_exists('canvastack_current_route') ? \canvastack_current_route() : null;
                if ($currentRoute && isset($currentRoute->uri)) {
                    $pathParts = explode('/', trim($currentRoute->uri, '/'));
                    if (count($pathParts) >= 2) {
                        $tableName = end($pathParts) === 'index' ? prev($pathParts) : end($pathParts);
                    }
                }
            } catch (\Throwable $e) {
                $tableName = 'record';
            }
            
            $modalHtml = self::generateDeleteConfirmationModal($modalId, $formId, $tableName, (string)$delete_id, $restoreDeleted);
        }

        // Follow legacy wrapper exactly + append modal
        return '<div class="action-buttons-box"><div class="hidden-sm hidden-xs action-buttons">'.$buttons.'</div><div class="hidden-md hidden-lg"><div class="inline pos-rel"><button class="btn btn-minier btn-yellow dropdown-toggle" data-toggle="dropdown" data-position="auto"><i class="fa fa-caret-down icon-only bigger-120"></i></button><ul class="dropdown-menu dropdown-only-icon dropdown-yellow dropdown-menu-right dropdown-caret dropdown-close">'.$buttonsMobile.'</ul></div></div></div>' . $modalHtml;
    }

    /**
     * Build "VALUE{:}attr=.." format identical to legacy canvastack_table_row_attr.
     */
    public static function tableRowAttr(string $value, $attributes): string
    {
        // Use centralized attribute builder to keep formatting consistent
        $attr = is_array($attributes)
            ? \Canvastack\Canvastack\Library\Components\Utility\Canvatility::attributesToString($attributes)
            : (string) $attributes;
        return $value.'{:}'.$attr;
    }

    /**
     * Render <th> for a single header slot, following legacy tableColumn rules.
     * Minimal parity for tests: label override via array form [ 'field' => 'Label' ].
     */
    public static function tableColumn(array $header, int $hIndex, $hList): string
    {
        $hNumber = false; // index to mark number column
        $hCheck = false;  // index to mark checkbox
        $hEmpty = false;  // index to mark empty header
        $_header = '';

        // Normalize key/value from array header definition
        $HKEY = false;
        $HVAL = false;
        if (is_array($hList)) {
            $keyList = array_keys($hList);
            $HKEY = $keyList[0];
            $HVAL = $hList[$HKEY];
        } else {
            $HKEY = (string) $hList;
            $HVAL = trim(ucwords(str_replace('_', ' ', $HKEY)));
        }
        $hListStr = (string) $HKEY;
        $hLabel = (string) $HVAL;

        $hListFields = $hListStr;
        if (str_contains($hListStr, '|')) {
            $newHList = explode('|', $hListStr);
            $hListStr = $newHList[1];
            $hListFields = $hListStr;
        }
        if (str_contains($hListStr, '.')) {
            $newHList = explode('.', $hListStr);
            $hListStr = $newHList[0];
        }

        // Determine the idHeader from original header array
        $idHeader = $header[$hIndex];
        if (is_array($idHeader)) {
            $fHead = array_keys($idHeader);
            $idHeader = $fHead[0];
        }
        if (in_array(strtolower((string) $idHeader), ['no', 'id', 'nik'], true)) {
            $hNumber = $hIndex;
        }

        if (is_string($hListStr) && strpos($hListStr, '<input type="checkbox"') !== false) {
            $hCheck = $hIndex;
        }
        if ($hListStr === '' || $hListStr === null) {
            $hEmpty = $hIndex;
        }

        $display = trim(ucwords(str_replace('_', ' ', (string) $hListStr)));

        if ($hNumber === $hIndex) {
            $_header .= '<th class="center" width="50">'.$display.'</th>';
        } elseif (str_contains($display, ':changeHeaderName:')) {
            $newHList = explode(':changeHeaderName:', $display);
            $display = ucwords($newHList[1]);
            $hListFields = $display;
            $_header .= '<th class="center" width="120">'.$hListFields.'</th>';
        } elseif ($hCheck === $hIndex) {
            $_header .= '<th width="50">'.$display.'</th>';
        } elseif ($hEmpty === $hIndex) {
            $_header .= '<th class="center" width="120">'.$display.'</th>';
        } elseif ($display === 'Action') {
            $_header .= '<th class="center" width="120">'.$display.'</th>';
        } elseif ($display === 'Active') {
            $_header .= '<th class="center" width="120">'.$display.'</th>';
        } elseif ($display === 'Flag Status') {
            $_header .= '<th class="center" width="120">'.$display.'</th>';
        } else {
            if (strtolower((string) $idHeader) === 'number_lists') {
                // Special: output double headers No and ID
                $_header .= '<th class="center" width="30">No</th><th class="center" width="30">ID</th>';
            } else {
                $rowAttr = '';
                if (str_contains((string) $display, '{:}')) {
                    $reList = explode('{:}', (string) $display);
                    $label = $reList[0];
                    if (isset($reList[1])) {
                        $attrParts = explode('|', $reList[1]);
                        $rowAttr = ' '.implode(' ', $attrParts);
                    }
                    $_header .= '<th'.$rowAttr.'>'.$label.'</th>';
                } else {
                    $_header .= '<th>'.$hLabel.'</th>';
                }
            }
        }

        return $_header;
    }

    /**
     * Generate simple table HTML to match legacy expectations used in tests.
     */
    public static function generateTable($title = false, $title_id = false, array $header = [], array $body = [], array $attributes = [], $numbering = false, $containers = true, $server_side = false, $server_side_custom_url = false): string
    {
        // attributes
        $datatableClass = 'IncoDIY-table table animated fadeIn table-striped table-default table-bordered table-hover dataTable repeater display responsive nowrap';
        $_attributes = $attributes ?: [];
        $_attributes['id'] = $_attributes['id'] ?? 'datatable-'.$title_id;
        $_attributes['class'] = $_attributes['class'] ?? $datatableClass;
        // Pass server-side hints as data-* attributes for preview auto-init
        if ($server_side !== false) {
            $_attributes['data-server-side'] = '1';
            if (is_string($server_side_custom_url) && $server_side_custom_url !== '') {
                $_attributes['data-ajax-url'] = $server_side_custom_url;
            }
        }
        $attrString = '';
        $attr = \Canvastack\Canvastack\Library\Components\Utility\Canvatility::attributesToString($_attributes);
        if ($attr !== '') {
            $attrString = ' '.$attr;
        }

        // header
        $_header = '';
        if ($numbering === true) {
            $header = array_merge(['number_lists'], $header);
        }
        if ($header !== false) {
            $_header .= '<thead><tr>';
            $merge = [];
            foreach ($header as $hIndex => $hList) {
                if (is_array($hList) && isset($hList['merge'])) {
                    $merge[$hIndex] = $hList['merge'];
                }
                $_header .= self::tableColumn($header, $hIndex, $hList);
            }
            $_header .= '</tr>';
            if (!empty($merge)) {
                foreach ($merge as $mergedata) {
                    foreach ($mergedata as $idx => $mdList) {
                        $_header .= self::tableColumn($mergedata, $idx, $mdList);
                    }
                }
            }
            $_header .= '</thead>';
        }

        // body (client-side only)
        $_body = '';
        if ($server_side === false) {
            $_body .= '<tbody>';
            $keys = array_keys($body);
            $firstKey = reset($keys);
            foreach ($body as $rowIndex => $row) {
                $rowClickAction = '';
                if (!empty($row['row_data_url'])) {
                    $rowClickAction = ' onclick="location.href=\''.$row['row_data_url'].'\'" class="row-list-url"';
                    unset($row['row_data_url']);
                }
                $_body .= '<tr'.$rowClickAction.'>';
                if ($numbering === true) {
                    $numLists = ($firstKey <= 0) ? ($rowIndex + 1) : $rowIndex;
                    $_body .= '<td class="center">'.$numLists.'</td>';
                }
                foreach ($row as $cell) {
                    if (is_string($cell) && strpos($cell, '{:}') !== false) {
                        [$val, $attr] = explode('{:}', $cell);
                        $attrParts = explode('|', $attr);
                        $attrStr = count($attrParts) ? ' '.implode(' ', $attrParts) : '';
                        $_body .= '<td'.$attrStr.'>'.$val.'</td>';
                    } else {
                        $_body .= '<td>'.$cell.'</td>';
                    }
                }
                $_body .= '</tr>';
            }
            $_body .= '</tbody>';
        }

        return '<table'.$attrString.'>'.$_header.$_body.'</table>';
    }

    /**
     * Generate Delete Confirmation Modal HTML
     * FIXED: Direct HTML approach with proper z-index and body append via JavaScript
     */
    public static function generateDeleteConfirmationModal(string $modalId, string $formId, string $tableName, string $recordId, bool $isRestore = false): string
    {
        $action = $isRestore ? 'restore' : 'delete';
        $actionText = $isRestore ? 'Restore' : 'Delete';
        $actionIcon = $isRestore ? 'fa-recycle' : 'fa-trash-o';
        $actionColor = $isRestore ? 'btn-warning' : 'btn-danger';
        $actionMessage = $isRestore 
            ? "Anda akan memulihkan data dari tabel <strong>{$tableName}</strong> dengan ID <strong>{$recordId}</strong>. Apakah Anda yakin ingin memulihkannya?"
            : "Anda akan menghapus data dari tabel <strong>{$tableName}</strong> dengan ID <strong>{$recordId}</strong>. Apakah Anda yakin ingin menghapusnya?";

        // CRITICAL FIX: Generate modal HTML but append to body via JavaScript to fix z-index
        $modalHtml = '<div id="' . $modalId . '" class="modal fade" role="dialog" tabindex="-1" ' .
                'aria-hidden="true" data-backdrop="static" data-keyboard="true" style="z-index: 1060;">' .
                '<div class="modal-dialog modal-md" role="document">' .
                    '<div class="modal-content">' .
                        '<div class="modal-header">' .
                            '<h5 class="modal-title">' .
                                '<i class="fa ' . $actionIcon . '"></i> &nbsp; Confirm ' . $actionText .
                            '</h5>' .
                            '<button type="button" class="close" data-dismiss="modal" aria-label="Close">' .
                                '<span aria-hidden="true">×</span>' .
                            '</button>' .
                        '</div>' .
                        '<div class="modal-body">' .
                            '<div class="alert alert-warning">' .
                                '<i class="fa fa-exclamation-triangle"></i> ' .
                                $actionMessage .
                            '</div>' .
                        '</div>' .
                        '<div class="modal-footer">' .
                            '<button type="button" class="btn btn-secondary" data-dismiss="modal">' .
                                '<i class="fa fa-times"></i> No, Cancel' .
                            '</button>' .
                            '<button type="button" class="btn ' . $actionColor . '" onclick="document.getElementById(\'' . $formId . '\').submit(); $(\'#' . $modalId . '\').modal(\'hide\');">' .
                                '<i class="fa ' . $actionIcon . '"></i> Yes, ' . $actionText .
                            '</button>' .
                        '</div>' .
                    '</div>' .
                '</div>' .
            '</div>';

        // JavaScript to append modal to body and handle z-index properly
        $script = '<script type="text/javascript">
            $(document).ready(function() {
                // Remove existing modal if exists
                $("#' . $modalId . '").remove();
                
                // Append modal to body to fix z-index issues
                $("body").append(\'' . addslashes($modalHtml) . '\');
                
                console.log("Delete modal appended to body: ' . $modalId . '");
            });
        </script>';

        return $script;
    }
}