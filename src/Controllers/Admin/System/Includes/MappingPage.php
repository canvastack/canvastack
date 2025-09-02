<?php

namespace Canvastack\Canvastack\Controllers\Admin\System\Includes;

use Canvastack\Canvastack\Models\Admin\System\MappingPage as MappingData;

/**
 * Created on Sep 6, 2022
 *
 * Time Created : 1:52:26 PM
 *
 * @filesource	MappingPage.php
 *
 * @author     wisnuwidi@canvastack.com - 2022
 * @copyright  wisnuwidi
 *
 * @email      wisnuwidi@canvastack.com
 */
trait MappingPage
{
    public $mapping_page = [];

    private $mapRoute;

    private $mapTable;

    private $model_class_info;

    private $ajaxUrli = null;

    private $nodeID = '__node__';

    private $nodeActionButton = '__btnact__';

    private function map()
    {
        return new MappingData();
    }

    public $filter_page_maps = [];

    public function get_data_mapping_page($user_id)
    {
        $currentRoute = canvastack_current_baseroute();
        $dataPageMaps = [];

        if (! empty($this->map()->getUserDataMapping($user_id))) {
            $sessionID = intval(session()->all()['id']);
            $dataPageMaps = $this->map()->getUserDataMapping($user_id);

            if (! empty($dataPageMaps[$sessionID][$currentRoute])) {
                foreach ($dataPageMaps[$sessionID][$currentRoute] as $dataTable) {
                    $this->filter_page_maps = $dataTable['filter_data'];
                }
            }
        }

        return $this->filter_page_maps;
    }

    public function rolepage($data, $usein)
    {
        return $this->map()::getData($data, $usein, $this->nodeID);
    }

    public function mapping_before_insert($requests, $group)
    {
        $role = [];
        $reqs = $requests->all();

        if (isset($reqs[$this->map()::$prefixNode])) {
            $request = $reqs[$this->map()::$prefixNode];

            foreach ($request['field_name'] as $mname => $mdata) {
                foreach ($mdata as $tname => $tdata) {
                    foreach ($tdata as $fieldTarget) {
                        if (isset($request['field_value'][$mname][$tname][$fieldTarget])) {
                            $role[$group->id][$mname][$tname][$fieldTarget] = $request['field_value'][$mname][$tname][$fieldTarget];
                        } else {
                            $role[$group->id][$mname][$tname][$fieldTarget] = null;
                        }
                    }
                }
            }

            $roles = [];
            foreach ($role as $group_id => $data) {
                foreach ($data as $route_path => $tableData) {

                    if (! empty($request['module'][$route_path])) {
                        $module_id = intval($request['module'][$route_path]);
                        foreach ($tableData as $table_name => $field_data) {

                            foreach ($field_data as $field_name => $field_values) {
                                $target_field_values = null;
                                if (! empty($field_values)) {
                                    $target_field_values = implode('::', $field_values);
                                }

                                $roles[$route_path][$table_name][$field_name]['group_id'] = $group_id;
                                $roles[$route_path][$table_name][$field_name]['module_id'] = $module_id;
                                $roles[$route_path][$table_name][$field_name]['target_table'] = $table_name;
                                $roles[$route_path][$table_name][$field_name]['target_field_name'] = $field_name;
                                $roles[$route_path][$table_name][$field_name]['target_field_values'] = $target_field_values;
                            }
                        }
                    }
                }
            }

            // CLEARING MAPPING PAGE REQUESTS
            request()->offsetUnset($this->map()::$prefixNode);

            $this->map()->insert_process($roles, $group);
        }
    }

    private function mapping()
    {
        $title_id = 'page_privileges_'.canvastack_random_strings(50, false).' role-priv';
        $headerData = [];
        $headerData['module_id'] = [canvastack_table_row_attr('Module Name', ['style' => 'text-align:center', 'rowspan' => 2])];
        $headerData['target_table'] = [canvastack_table_row_attr('Table Name', ['style' => 'text-align:center', 'rowspan' => 2])];
        $headerData['target_roles'] = [
            [
                'column' => canvastack_table_row_attr('Role Query', ['style' => 'text-align:center;min-width:420px;', 'colspan' => 2]),
                'merge' => [
                    canvastack_table_row_attr('Field Name', ['style' => 'text-align:center']),
                    canvastack_table_row_attr('Field Value', ['style' => 'text-align:center']),
                ],
            ],
        ];
        $headerData['action_button'] = [canvastack_table_row_attr('Action', ['style' => 'text-align:center', 'rowspan' => 2])];

        $header = array_merge_recursive($headerData['module_id'], $headerData['target_table'], $headerData['target_roles'], $headerData['action_button']);
        $row_table = $this->mapping_box();

        return canvastack_generate_table('Set Role Module Page', $title_id, $header, $row_table, false, false, false);
    }

    private $group_id;

    private function get_data_map()
    {
        $urli = explode('/', url()->current());
        if ('edit' === last($urli)) {
            unset($urli[count($urli) - 1]);
            $this->group_id = intval(last($urli));
        }

        $current_rolemaps = [];
        if (! empty($this->map()->current_data($this->group_id))) {
            foreach ($this->map()->current_data($this->group_id) as $current_module => $current_module_data) {
                $module_data = canvastack_query('base_module')->where('id', intval($current_module))->first();
                $current_rolemaps[$module_data->route_path]['model']['page_roles'] = $current_module_data;
            }
        }

        $this->model_class_info = canvastack_get_model_controllers_info($current_rolemaps);
    }

    private function setID($string, $node = null)
    {
        if (empty($node)) {
            $node = $this->nodeID;
        }

        return canvastack_random_strings(8, false, $string, '__node__');
    }

    private function mapping_box()
    {
        $this->get_data_map();

        $row_table = [];
        $icon = '<i class="fa fa-caret-right"></i> &nbsp; ';
        $roleData = null;

        foreach ($this->menu_privileges as $parent => $childs) {
            $parent_title = ucwords(str_replace('_', ' ', $parent));
            if (! empty($childs->name)) {
                $parent_title = $childs->name;
            }
            $row_table[] = [canvastack_table_row_attr($icon."{$parent_title}", ['style' => 'font-weight:500;text-indent:5pt;color:black', 'colspan' => 5])];

            foreach ($childs as $child_name => $data_module) {
                if (! isset($data_module->id)) {
                    $child_title = ucwords(str_replace('_', ' ', $child_name));
                    if (! empty($data_module->name)) {
                        $child_title = $data_module->name;
                    }

                    $row_table[] = [canvastack_table_row_attr($icon.$child_title, ['style' => 'font-weight:500;text-indent:12pt;color:green', 'colspan' => 5])];
                    foreach ($data_module as $module_name => $module_data) {
                        if (! empty($this->model_class_info[$module_data->route])) {
                            $roleData = $this->model_class_info[$module_data->route];
                        }

                        if (! empty($module_data->id)) {
                            if (! empty($roleData)) {
                                $row_table[] = $this->buildRoleBox($roleData, $module_name, $module_data, $icon);
                            }
                        } else {
                            $module_title = ucwords(str_replace('_', ' ', $module_name));
                            if (! empty($module_data->name)) {
                                $module_title = $module_data->name;
                            }

                            $row_table[] = [canvastack_table_row_attr($icon.$module_title, ['style' => 'font-weight:500;text-indent:19pt', 'colspan' => 4])];
                            foreach ($module_data as $third_name => $third_data) {
                                if (! empty($this->model_class_info[$third_data->route])) {
                                    $roleData = $this->model_class_info[$third_data->route];
                                }

                                $third_title = ucwords(str_replace('_', ' ', $third_name));
                                if (! empty($third_data->name)) {
                                    $third_title = $third_data->name;
                                }

                                $row_table[] = $this->buildRoleBox($roleData, $third_title, $third_data, $icon);
                            }
                        }
                    }

                } else {
                    $child_title = ucwords(str_replace('_', ' ', $child_name));
                    if (! empty($data_module->name)) {
                        $child_title = $data_module->name;
                    }

                    if (! empty($this->model_class_info[$data_module->route])) {
                        $roleData = $this->model_class_info[$data_module->route];
                    }

                    if (! empty($roleData)) {
                        $row_table[] = $this->buildRoleBox($roleData, $child_name, $data_module, $icon, 'text-indent:15pt');
                    }
                }
            }

        }

        return $row_table;
    }

    private $roleNode;

    private function rolename($basename, $identify = [])
    {
        $rolename = [];
        $this->roleNode = $this->map()::$prefixNode;

        $basename = "{$this->roleNode}[{$basename}]";
        if (! empty($identify)) {
            if (is_array($identify)) {
                if (! empty($identify[2])) {
                    return $rolename[$basename] = "{$basename}[{$identify[0]}][{$identify[1]}][{$identify[2]}][]";
                } else {
                    return $rolename[$basename] = "{$basename}[{$identify[0]}][{$identify[1]}][]";
                }
            } else {
                return $rolename[$basename] = "{$basename}[$identify][]";
            }
        } else {
            return $rolename[$basename] = "{$basename}[]";
        }
    }

    private function getFieldTable($table_name, $func, $connection = null)
    {
        $result = [];
        $data = $this->map()::{$func}($table_name, $connection);

        foreach ((array) json_decode($data) as $label => $value) {
            $result[$value] = ucwords(str_replace('-', ' ', canvastack_clean_strings($label)));
        }

        return $result;
    }

    private function buildRoleBox($roleData, $module_name, $module_data, $icon, $indent = false)
    {
        if ($roleData) {

            $connection = $roleData['model']['connection'];
            $identifier = $roleData['model']['table_map'];
            if (! empty($connection)) {
                $identifier = $roleData['model']['table_map'].$this->map()::$diycon.$connection;
            }
            $routeName = strtolower($module_data->route);
            $routeNameAttribute = str_replace('.', '-', $module_data->route);
            $routeToAttribute = 'role__'.$routeNameAttribute.'__'.$roleData['model']['table_map'];

            $roleAttributes = [];
            $roleAttributes['table_name'] = $this->rolename('table_name');
            $roleAttributes['field_name'] = "{$this->roleNode}[field_name]";
            $roleAttributes['field_value'] = $this->rolename('field_value', $identifier);

            $roleValues = [];
            $roleValues['table_checked'] = false;
            $roleValues['table_map'] = $identifier;
            $roleValues['table_connection'] = $roleData['model']['connection'];
            $roleValues['field_name'] = [];
            $roleValues['field_value'] = [];

            $connection = null;
            $buffers = [];
            $buffer_data = [];

            if (isset($roleData['model']['buffers']['page_roles'])) {
                $buffers = $roleData['model']['buffers']['page_roles'];
                $roleValues['table_checked'] = true;

                foreach ($buffers as $buffer_table => $buffer_data) {
                    $roleValues['table_map'] = $buffer_table;

                    $bufferTableGetField = $buffer_table;
                    if (canvastack_string_contained($buffer_table, $this->map()::$diycon)) {
                        $buffer_table_split = explode($this->map()::$diycon, $buffer_table);
                        $bufferTableGetField = $buffer_table_split[0];
                        $connection = $buffer_table_split[1];
                    }

                    if (! empty($buffer_data)) {
                        foreach ($buffer_data as $buffer_field => $buffered) {
                            $roleValues['field_name'][$buffer_table][$buffer_field]['selected'] = [$buffered->target_field_name => $buffered->target_field_name];
                            $roleValues['field_name'][$buffer_table][$buffer_field]['data'] = $this->getFieldTable($bufferTableGetField, 'getTableFields', $connection);

                            $buffered_values = [];
                            foreach (explode('::', $buffered->target_field_values) as $value_buffered) {
                                $buffered_values['selected'][$value_buffered] = $value_buffered;
                                $buffered_values['data'] = $this->getFieldTable([$buffer_table => [$buffer_field]], 'getFieldValues');
                            }

                            $roleValues['field_value'][$buffer_table][$buffer_field] = $buffered_values;
                        }
                    }
                }
            }

            $nodeModel = str_replace('.', '-', $routeName);
            $roleColumns = [];
            $roleColumns['ajax_field_name'] = $this->ajax_urli('field_name', true);
            $roleColumns['identifier'] = canvastack_input('hidden', "qmod-{$identifier}", $nodeModel, null, $module_data->id);
            $tableID = $this->setID($identifier);
            $tableLabel = ucwords(str_replace('_', ' ', str_replace('view_', ' ', str_replace('t_', ' ', $roleData['model']['table_map']))));
            $roleColumns['table_name'] = canvastack_form_checkList($roleAttributes['table_name'], $roleValues['table_map'], $tableLabel, $roleValues['table_checked'], 'success read-select full-width text-left', $tableID, "class='{$tableID}{$this->nodeID}{$nodeModel}'");

            $fieldID = $this->setID($identifier);
            $valueID = $this->setID($identifier);

            $rand = [];
            $fieldbuff = [];

            if (! empty($buffer_data)) {
                $n = 0;
                foreach ($buffer_data as $buffer_field => $buffered) {
                    $n++;

                    $rand['f'] = canvastack_random_strings(8, false, null, null);
                    $rand['v'] = canvastack_random_strings(8, false, null, null);

                    $fieldbuff['field'] = $fieldID.$rand['f'];
                    $fieldbuff['value'] = $valueID.$rand['v'];

                    $fieldbuff['ranid'][$buffer_field] = $fieldID.$rand['f'];
                    $fieldbuff['ranval'][$buffer_field] = $valueID.$rand['v'];

                    if ($n > 1) {
                        $fieldNameAttr = ['id' => $fieldbuff['field'], 'class' => $routeToAttribute."{$fieldID}field_name"];
                        $fieldValueAttr = ['id' => $fieldbuff['value'], 'class' => $routeToAttribute."{$valueID}field_value", 'multiple'];
                    } else {
                        $fieldNameAttr = ['id' => $fieldID, 'class' => $routeToAttribute."{$fieldID}field_name"];
                        $fieldValueAttr = ['id' => $valueID, 'class' => $routeToAttribute."{$valueID}field_value", 'multiple'];
                    }

                    $roleColumns['identifier'] = canvastack_input('hidden', "qmod-{$identifier}", $nodeModel, "{$this->roleNode}[module][{$module_data->route}]", $module_data->id);

                    $fieldNameValues = $roleValues['field_name'][$identifier][$buffer_field];
                    $roleColumns['field_name'][$identifier][$buffer_field] = canvastack_form_selectbox(
                        $this->rolename('field_name', [$routeName, $identifier]),
                        $fieldNameValues['data'],
                        $fieldNameValues['selected'],
                        $fieldNameAttr,
                        false,
                        false
                    );

                    $fieldDataValues = $roleValues['field_value'][$identifier][$buffer_field];
                    $roleColumns['field_value'][$identifier][$buffer_field] = canvastack_form_selectbox(
                        $this->rolename('field_value', [$routeName, $identifier, array_keys($fieldNameValues['selected'])[0]]),
                        $fieldDataValues['data'],
                        $fieldDataValues['selected'],
                        $fieldValueAttr,
                        false,
                        false
                    );
                }
            } else {
                $roleColumns['field_name'] = canvastack_form_selectbox($roleAttributes['field_name'], $roleValues['field_name'], null, ['id' => $fieldID, 'class' => $routeToAttribute."{$fieldID}field_name"], false, false);
                $roleColumns['field_value'] = canvastack_form_selectbox($roleAttributes['field_value'], $roleValues['field_value'], null, ['id' => $valueID, 'class' => $routeToAttribute."{$valueID}field_value", 'multiple'], false, false);
            }

            $module_name_label = ucwords(str_replace('_', ' ', str_replace('view_', ' ', str_replace('t_', ' ', $module_name))));
            $opt = ['align' => 'center', 'id' => strtolower($module_name).'-row', 'colspan' => 2, 'style' => 'padding: 0 !important;'];

            $mergeBox = canvastack_draw_query_map_page_table($routeToAttribute, $fieldID, $valueID, $roleColumns, $buffers, $fieldbuff);

            $resultBox = [];
            $resultBox['head'] = [canvastack_table_row_attr($icon.$module_name_label.$roleColumns['identifier'], ['style' => 'text-indent:19pt', 'id' => strtolower($module_name).'-row'])];
            $resultBox['body'] = [
                canvastack_table_row_attr($roleColumns['table_name'], ['align' => 'left', 'id' => strtolower($module_name).'-row']),
                canvastack_table_row_attr($mergeBox, $opt),
            ];

            $nodebtn = "node_btn_{$tableID}{$this->nodeActionButton}{$fieldID}{$this->nodeActionButton}{$valueID}";
            $resultBox['scripts']['table'] = [
                canvastack_table_row_attr(
                    $this->buttonAdd($nodebtn, $tableID, $fieldID, $valueID).
                    $this->js_rolemap_table($tableID, $fieldID, $valueID, $nodebtn, $nodeModel).
                    $this->js_rolemap_fieldname($fieldID, $valueID),
                    ['align' => 'center', 'id' => strtolower($module_name).'-row', 'width' => 100, 'style' => 'padding:8px']
                ),
            ];

            $o = array_merge_recursive($resultBox['head'], $resultBox['body'], $resultBox['scripts']['table']);

            return $o;
        }
    }

    private function ajax_urli($usein, $return_data = false)
    {
        $current_url = url(str_replace('.', '/', canvastack_current_baseroute()));
        $urlset = [
            'rolemapage' => 'true',
            'usein' => $usein,
            '_token' => csrf_token(),
        ];

        $uri = [];
        foreach ($urlset as $fieldurl => $urlvalue) {
            $uri[] = "{$fieldurl}={$urlvalue}";
        }

        $this->ajaxUrli = $current_url.'?'.implode('&', $uri);

        if (true === $return_data) {
            return $this->ajaxUrli;
        }
    }

    private function js_rolemap_table($id, $target_id, $second_target, $nodebtn, $nodeModel)
    {
        $this->ajax_urli('table_name');

        return canvastack_script("mappingPageTableFieldname('{$id}', '{$target_id}', '{$this->ajaxUrli}', '{$second_target}', '{$nodebtn}', '{$nodeModel}');");
    }

    private function js_rolemap_fieldname($id, $target_id)
    {
        $this->ajax_urli('field_name');

        return canvastack_script("mappingPageFieldnameValues('{$id}', '{$target_id}', '{$this->ajaxUrli}');");
    }

    private function buttonAdd($node_btn, $id, $target_id, $second_target)
    {
        $this->ajax_urli('field_name');

        return canvastack_mappage_button_add($this->ajaxUrli, $node_btn, $id, $target_id, $second_target);
    }
}
