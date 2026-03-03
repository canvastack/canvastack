<?php
use Illuminate\Support\Facades\DB;

/**
 * Created on 13 Apr 2021
 * Time Created : 04:05:22
 *
 * @filesource	Table.php
 *
 * @author    wisnuwidi@canvastack.com - 2021
 * @copyright wisnuwidi
 * @email     wisnuwidi@canvastack.com
 */
 
if (!function_exists('canvas_filter_data_normalizer')) {
	
	/**
	 * Normalizing Data Filters
	 * 
	 * @param array $filters
	 * 
	 * @return array
	 */
	function canvas_filter_data_normalizer($filters = []) {
		$filterData = [];
		
		foreach ($filters as $filter_data) {
			if (is_array($filter_data['value'])) {
				foreach ($filter_data['value'] as $filterValues) {
					$filterData[$filter_data['field_name']]['value'][][] = $filterValues;
				}
			} else {
				$filterData[$filter_data['field_name']]['value'][][] = $filter_data['value'];
			}
		}
		
		$_filters = [];
		foreach ($filterData as $node => $nodeValues) {
			$_filters[$node]['field_name']  = $node;
			$_filters[$node]['operator']    = '=';
			foreach ($nodeValues['value'] as $values) {
				$_filters[$node]['value'][] = $values[0];
			}
		}
		unset($filterData);
		
		foreach ($_filters as $dataFilters) {
			$filterData[] = $dataFilters;
		}
		
		return $filterData;
	}
}

if (!function_exists('canvas_get_model_table')) {
	
	/**
	 * Get Table Name From Data Model
	 *
	 * @param object $model
	 * @param boolean $find
	 *
	 * @return object|array
	 */
	function canvas_get_model_table($model, $find = false) {
		$model = canvas_get_model($model, $find);
		
		return $model->getTable();
	}
}

if (!function_exists('canvas_get_all_tables')) {
	
	/**
	 * Get All Table Lists From Host Connection
	 *
	 * @param string $connection
	 *
	 * @return object|array
	 */
	function canvas_get_all_tables($connection = null) {
		return collect(DB::connection($connection)->select('show tables'))->map(function ($val) {
			foreach ($val as $tbl) return $tbl;
		});
	}
}

if (!function_exists('canvas_set_connection_separator')) {
	
	/**
	 * Set Separator Connection
	 *
	 * @param string $separator
	 *
	 * @return array
	 */
	function canvas_set_connection_separator($separator = '--canvascon--') {
		return $separator;
	}
}

if (!function_exists('canvas_check_table_columns')) {
	
	/**
	 * Check if Table Column(s) Exist
	 *
	 * @param string $field_name
	 *
	 * @return array
	 */
	function canvas_check_table_columns($table_name, $field_name, $db_connection = 'mysql') {
		$connection = DB::connection($db_connection);
		return $connection->getSchemaBuilder()->hasColumn($table_name, $field_name);
	}
}

if (!function_exists('canvas_get_table_columns')) {
	
	/**
	 * Get Table Column(s)
	 *
	 * @param string $table_name
	 *
	 * @return array
	 */
	function canvas_get_table_columns($table_name, $db_connection = 'mysql') {
		$connection = DB::connection($db_connection);
		return $connection->getSchemaBuilder()->getColumnListing($table_name);
	}
}

if (!function_exists('canvas_get_table_column_type')) {
	
	/**
	 * Get Table Column(s)
	 *
	 * @param string $table_name
	 * @param string $field_name
	 *
	 * @return string
	 */
    function canvas_get_table_column_type($table_name, $field_name, $db_connection = 'mysql') {
        $connection = DB::connection($db_connection);
        return $connection->getSchemaBuilder()->getColumnType($table_name, $field_name);
	}
}

if (!function_exists('canvas_temp_table')) {
	
	/**
	 * Create Temporary Table
	 *
	 * @param string $table_name
	 * @param string $sql
	 * @param boolean $strict
	 * @param string $conn
	 */
	function canvas_temp_table($table_name, $sql, $strict = true, $conn = 'mysql') {
		$strictConfig = config("database.connections.{$conn}.strict");
		$table_name   = str_replace('temp_', '', $table_name);
		
		if (Illuminate\Support\Facades\Schema::hasTable("temp_{$table_name}")) {
			Illuminate\Support\Facades\Schema::dropIfExists("temp_{$table_name}");
		}
		
		if (false === $strict) {
			Illuminate\Support\Facades\DB::purge($conn);
			config()->set("database.connections.{$conn}.strict", $strict);
			Illuminate\Support\Facades\DB::reconnect();
		}
	//	dump(microtime(true));
		
		canvas_query($sql, 'SELECT');
		Illuminate\Support\Facades\DB::unprepared("CREATE TABLE temp_{$table_name} {$sql}");
		
		if (false === $strict) {
			Illuminate\Support\Facades\DB::purge($conn);
			config()->set("database.connections.{$conn}.strict", $strictConfig);
			Illuminate\Support\Facades\DB::reconnect();
		}
	}
}

if (!function_exists('canvas_model_processing_table')) {
	
	/**
	 * Call Model Process Data Table
	 *
	 * @param array $data
	 *
	 * @return object
	 */
	function canvas_model_processing_table($data, $name) {
		if (!empty($data[$name])) {
			$model = $data[$name]['model'];
			
			if (false === $data[$name]['strict']) {
				canvas_db('purge', $data[$name]['connection']);
				config()->set("database.connections.{$data[$name]['connection']}.strict", $data[$name]['strict']);
				canvas_db('reconnect');
			}
			
			$model->{$data[$name]['function']}();
		}
	}
}

if (!function_exists('canvas_set_formula_columns')) {
	
	function canvas_set_formula_columns($columns, $data) {
		arsort($data);
		
		$key_columns = array_flip($columns);
		$f_node      = [];
		
		$c_action    = false;
		if (!empty($key_columns['action'])) $c_action = true;
		
		$c_lists     = false;
		if (isset($key_columns['number_lists'])) $c_lists = true;
		
		foreach ($data as $formula_data) {
			$for_node = $formula_data['node_location'];
			$f_node[$formula_data['name']]['field_label'] = $formula_data['label'];
			
			if (empty($for_node)) {
				$f_node[$formula_data['name']]['field_name'] = end($formula_data['field_lists']);
			} else {
				if ('first' === $for_node) {
					$f_node[$formula_data['name']]['field_name'] = $columns[0];
				} elseif ('last' === $for_node) {
					$f_node[$formula_data['name']]['field_name'] = $columns[array_key_last($columns)];
				} else {
					$f_node[$formula_data['name']]['field_name'] = $for_node;
				}
			}
			
			$f_node[$formula_data['name']]['field_key']     = $key_columns[$f_node[$formula_data['name']]['field_name']];
			$f_node[$formula_data['name']]['node_after']    = $formula_data['node_after'];
			$f_node[$formula_data['name']]['node_location'] = $formula_data['node_location'];
		}
		
		foreach ($f_node as $key => $fdata) {
			if ('first' === $fdata['node_location']) {
				if (true === $c_lists) {
					canvas_array_insert($columns, intval($fdata['field_key'])+1, $key);
				} else {
					canvas_array_insert($columns, intval($fdata['field_key']), $key);
				}
			} elseif ('last' === $fdata['node_location']) {
				if (true === $fdata['node_after']) {
					if (true === $c_action) {
						canvas_array_insert($columns, intval($fdata['field_key']), $key);
					} else {
						array_push($columns, $key);
					}
				} else {
					canvas_array_insert($columns, intval($fdata['field_key']), $key);
				}
			} else {
				if (true === $fdata['node_after']) {
					canvas_array_insert($columns, intval($fdata['field_key'])+1, $key);
				} else {
					canvas_array_insert($columns, intval($fdata['field_key']), $key);
				}
			}
		}
		
		return $columns;
	}
}

if (!function_exists('canvas_modal_content_html')) {
	
	function canvas_modal_content_html($name, $title, $elements) {
		$buttonID = str_replace('_cdyFILTERmodalBOX', '_submitFilterButton', $name);
		
		$html  = '<div class="modal-body">';
			$html .= '<div id="' . $name . '">';
				$html .= implode('', $elements);
			$html .= '</div>';
		$html .= '</div>';
		$html .= '<div class="modal-footer">';
			$html .= '<div class="canvas-action-box">';
				$html .= '<button type="reset" id="' . $name . '-cancel" class="btn btn-danger btn-slideright pull-right" data-dismiss="modal">Cancel</button>';
				$html .= '<button id="' . $buttonID . '" class="btn btn-primary btn-slideright pull-right" type="submit">';
					$html .= '<i class="fa fa-filter"></i> &nbsp; Filter Data ' . $title;
				$html .= '</button>';
				$html .= '<button id="exportFilterButton' . $name . '" class="btn btn-info btn-slideright pull-right btn-export-csv hide" type="button">Export to CSV</button>';
			$html .= '</div>';
		$html .= '</div>';
		
		return $html;
	}
}

if (!function_exists('canvas_clear_json')) {
	
	function canvas_clear_json($data) {
		$json = str_replace('"data"', "data", $data);
		$json = str_replace('"name"', "name", $json);
		$json = str_replace('"', "'", $json);
		
		return $json;
	}
}

if (!function_exists('canvas_table_action_button')) {
	
	/**
	 * Set Action Button URL Used For create_action_buttons() Function
	 *
	 * created @Sep 6, 2018
	 * author: wisnuwidi
	 *
	 * @param array $row_data
	 * @param string $current_url
	 * @param bool|array $action
	 * 	: true, 
	 * 	: false, 
	 * 	: index|insert|update|delete, 
	 * 	: show|create|modify|destroy, 
	 * 	: [index, insert, update, delete], 
	 * 	: [show, create, modify, destroy]
	 *
	 * @return string
	 */
	function canvas_table_action_button($row_data, $field_target = 'id', $current_url, $action, $removed_button = null) {
		$privileges              = session()->all()['privileges']['role'];
		$path                    = [];
		$addActions              = [];
		$add_path                = false;
		$enabledAction           = [];
		$enabledAction['read']   = true;
		$enabledAction['insert'] = true;
		$enabledAction['modify'] = true;
		$enabledAction['delete'] = true;
		
		$actions = [];
		if (in_array(current_route(), $privileges)) {
			foreach ($privileges as $roles) {
				if (canvas_string_contained($roles, routelists_info()['base_info'])) {
					if (!in_array(routelists_info($roles)['last_info'], ['index', 'insert', 'update', 'destroy'])) {
						$actions[routelists_info()['base_info']][] = routelists_info($roles)['last_info'];
					}
				}
			}
			
			$actionType            = [];
			$actionType['custom']  = [];
			$actionType['default'] = $actions[routelists_info()['base_info']];
			
			foreach ($action as $ai => $actval) {
				if (in_array($actval, ['index', 'show', 'view', 'create', 'insert', 'add', 'edit', 'update', 'modify', 'delete', 'destroy'])) {
					unset($action[$ai]);
				} else {
					$actionType['custom'][] = $actval;
				}
			}
			$action = array_merge_recursive($actionType['default'], $actionType['custom']);
		}
		
		if (!empty($removed_button)) {
			if (is_array($removed_button)) {
				$actionNode = array_flip($action);
				foreach ($removed_button as $remove) {
					if (in_array($remove, ['index', 'show', 'view', 'read'])) {
						$enabledAction['read']   = false;
						
						if (!empty($actionNode['view']))  unset($action[$actionNode['view']]);
						if (!empty($actionNode['index'])) unset($action[$actionNode['index']]);
						if (!empty($actionNode['show']))  unset($action[$actionNode['show']]);
						
					} elseif (in_array($remove, ['create', 'insert', 'add'])) {
						$enabledAction['insert'] = false;
						
						if (!empty($actionNode['create'])) unset($action[$actionNode['create']]);
						if (!empty($actionNode['insert'])) unset($action[$actionNode['insert']]);
						if (!empty($actionNode['add']))    unset($action[$actionNode['add']]);
						
					} elseif (in_array($remove, ['edit', 'update', 'modify'])) {
						$enabledAction['modify'] = false;
						
						if (!empty($actionNode['edit']))   unset($action[$actionNode['edit']]);
						if (!empty($actionNode['update'])) unset($action[$actionNode['update']]);
						if (!empty($actionNode['modify'])) unset($action[$actionNode['modify']]);
						
					} elseif (in_array($remove, ['delete', 'destroy'])) {
						$enabledAction['delete'] = false;
						
						if (!empty($actionNode['delete']))  unset($action[$actionNode['delete']]);
						if (!empty($actionNode['destroy'])) unset($action[$actionNode['destroy']]);
						
					} else {
						$enabledAction[$removed_button] = false;
					}
				}
			}
		}
		
		// Add Action Button if the $action parameter above set with array
		if (is_array($action)) {
			foreach ($action as $action_data) {
				if (canvas_string_contained($action_data, '|')) {
					$action_info                      = canvas_add_action_button_by_string($action_data);
					$addActions[key($action_info)]    = $action_info[key($action_info)];
					$enabledAction[key($action_info)] = true;
				} else {
					$addActions[$action_data]         = canvas_add_action_button_by_string("{$action_data}|default|link");
					$enabledAction[$action_data]      = true;
				}
			}
		} else {			
			if (is_string($action)) {
				if (canvas_string_contained($action, '|')) {
					$addActions = canvas_add_action_button_by_string($action);
				} else {
					$addActions = canvas_add_action_button_by_string("{$action}|default|link");
				}
			}
		}
		
		// Default Action
		$urlTarget = $row_data->{$field_target};
		$path['view'] = "{$current_url}/{$urlTarget}";
		$path['edit'] = "{$current_url}/{$urlTarget}/edit";
		if (!empty($row_data->deleted_at)) {
			$path['delete'] = "{$current_url}/{$urlTarget}/restore_deleted";
		} else {
			$path['delete'] = "{$current_url}/{$urlTarget}/delete";
		}
		
		if (false === $enabledAction['read'])   $path['view']   = false;
		if (false === $enabledAction['modify']) $path['edit']   = false;
		if (false === $enabledAction['delete']) $path['delete'] = false;
		
		if (count($addActions) >= 1) {
			foreach ($addActions as $action_name => $action_values) {
				if (!in_array($action_name, ['show', 'view', 'create', 'edit', 'delete'])) {
					$add_path[$action_name]['url'] = "{$current_url}/{$urlTarget}/{$action_name}";
					if (is_array($action_values)) {
						foreach ($action_values as $actionKey => $actionValue) {
							if ($actionKey === $action_name) {
								$add_path[$action_name]        = $actionValue;
								$add_path[$action_name]['url'] = "{$current_url}/{$urlTarget}/{$action_name}";
							} else {
								$add_path[$action_name][$actionKey] = $actionValue;
							}
						}
					}
				}
			}
		}
		
		return create_action_buttons($path['view'], $path['edit'], $path['delete'], $add_path);
	}
}

if (!function_exists('canvas_add_action_button_by_string')) {
	
	function canvas_add_action_button_by_string($action, $is_array = false) {
		$addActions = [];
		if (is_bool($action)) {
			if (true === $action) {
				$addActions['view']['color']   = 'success';
				$addActions['view']['icon']    = 'eye';
				
				$addActions['edit']['color']   = 'primary';
				$addActions['edit']['icon']    = 'pencil';
				
				$addActions['delete']['color'] = 'danger';
				$addActions['delete']['icon']  = 'times';
			}
		} else {
			if (canvas_string_contained($action, '|')) {
				$str_action = explode('|', $action);
				$str_name	= reset($str_action);
			} else {
				$str_action = $action;
				$str_name   = false;
			}
			
			$actionAttr = [];
			
			if (count($str_action) >= 2) {
				$actionAttr['color'] = false;
				if (isset($str_action[1])) {
					$actionAttr['color'] = $str_action[1];
				}
				
				$actionAttr['icon'] = false;
				if (isset($str_action[2])) {
					$actionAttr['icon'] = $str_action[2];
				}
				$addActions[$str_name]  = $actionAttr;
			} else {
				$addActions[$action]    = $action;
			}
		}
		
		return $addActions;
	}
}

if (!function_exists('create_action_buttons')) {
	
	/**
	 * Action Button(s) Builder
	 *
	 * created @Sep 6, 2018
	 * author: wisnuwidi
	 *
	 * @param string $view
	 * @param string $edit
	 * @param string $delete
	 * @param string $add_action
	 * @param string $as_root
	 *
	 * @return string
	 */
	function create_action_buttons($view = false, $edit = false, $delete = false, $add_action = [], $as_root = false) {
		$deleteURL          = false;
		$delete_id          = false;
		$buttonDelete       = false;
		$buttonDeleteMobile = false;
		$restoreDeleted     = false;
		
		if (false !== $delete) {
			$deletePath            = explode('/', $delete);
			$deleteFlag            = end($deletePath);
			$delete_id             = intval($deletePath[count($deletePath)-2]);
			$deleteURL             = str_replace('@index', '@destroy', canvas_current_route()->getActionName());
			$buttonDeleteAttribute = 'class="btn btn-danger btn-xs" data-toggle="tooltip" data-placement="top" data-original-title="Delete"';
			$iconDeleteAttribute   = 'fa fa-times';
			
			if ('restore_deleted' === $deleteFlag) {
				$restoreDeleted        = true;
				$buttonDeleteAttribute = 'class="btn btn-warning btn-xs" data-toggle="tooltip" data-placement="top" data-original-title="Restore"';
				$iconDeleteAttribute   = 'fa fa-recycle';
			}
			
			$delete_action      = '<form action="' . action($deleteURL, $delete_id) . '" method="post" class="btn btn_delete" style="padding:0 !important">' . csrf_field() . '<input name="_method" type="hidden" value="DELETE">';
			$buttonDelete       = $delete_action . '<button ' . $buttonDeleteAttribute . ' type="submit"><i class="' . $iconDeleteAttribute . '"></i></button></form>';
			$buttonDeleteMobile = '<li><a href="' . $delete . '" class="tooltip-error btn_delete" data-rel="tooltip" title="Delete"><span class="red"><i class="fa fa-trash-o bigger-120"></i></span></a></li>';
		}
		
		$buttonView       = false;
		$buttonViewMobile = false;
		if (false != $view) {
			if (true === $restoreDeleted) {
				$viewVisibilityAttr = 'readonly disabled class="btn btn-default btn-xs btn_view" data-toggle="tooltip" data-placement="top" data-original-title="View detail"';
			} else {
				$viewVisibilityAttr = 'href="' . $view . '" class="btn btn-success btn-xs btn_view" data-toggle="tooltip" data-placement="top" data-original-title="View detail"';
			}
			$buttonView       = '<a ' . $viewVisibilityAttr . '><i class="fa fa-eye"></i></a>';
			$buttonViewMobile = '<li class="btn_view"><a href="' . $view . '" class="tooltip-info" data-rel="tooltip" title="View"><span class="blue"><i class="fa fa-search-plus bigger-120"></i></span></a></li>';
		}
		
		$buttonEdit       = false;
		$buttonEditMobile = false;
		if (false != $edit) {
			if (true === $restoreDeleted) {
				$editVisibilityAttr = ' readonly disabled class="btn btn-default btn-xs btn_edit" data-toggle="tooltip" data-placement="top" data-original-title="Edit"';
			} else {
				$editVisibilityAttr = ' href="' . $edit . '" class="btn btn-primary btn-xs btn_edit" data-toggle="tooltip" data-placement="top" data-original-title="Edit"';
			}
			$buttonEdit       = '<a ' . $editVisibilityAttr . '><i class="fa fa-pencil"></i></a>';
			$buttonEditMobile = '<li class="btn_edit"><a href="' . $edit . '" class="tooltip-success" data-rel="tooltip" title="Edit"><span class="green"><i class="fa fa-pencil-square-o bigger-120"></i></span></a></li>';
		}
		
		$buttonNew       = '';
		$buttonNewMobile = '';
		if (true === is_array($add_action)) {
			if (count($add_action) >= 1) {
				foreach ($add_action as $new_action_name => $new_action_values) {
					$btn_name  = $new_action_name;
					$row_name  = camel_case($new_action_name);
					$row_url   = $new_action_values['url'];
					$row_color = null;
					$row_icon  = null;
					if (!empty($new_action_values['color'])) $row_color = $new_action_values['color'];
					if (!empty($new_action_values['icon']))  $row_icon  = $new_action_values['icon'];
					
					if (true === $restoreDeleted) {
						$actionVisibilityAttr = ' readonly disabled class="btn btn-default btn-xs" data-toggle="tooltip" data-placement="top" data-original-title="' . $row_name . '"';
					} else {
						$actionVisibilityAttr = ' href="' . $row_url . '" class="btn ' . $btn_name . ' btn-' . $row_color. ' btn-xs" data-toggle="tooltip" data-placement="top" data-original-title="' . $row_name . '"';
					}
					$buttonNew       .= '<a' . $actionVisibilityAttr . '><i class="fa fa-' . $row_icon . '"></i></a>';
					$buttonNewMobile .= '<li><a href="' . $row_url . '" class="tooltip-error" data-rel="tooltip" title="' . $row_name . '"><span class="red"><i class="fa fa-' . $row_icon . ' bigger-120"></i></span></a></li>';
				}
			}
		}
		
		$buttons       = $buttonView       . $buttonEdit       . $buttonDelete       . $buttonNew;
		$buttonsMobile = $buttonViewMobile . $buttonEditMobile . $buttonDeleteMobile . $buttonNewMobile;
		
		return '<div class="action-buttons-box"><div class="hidden-sm hidden-xs action-buttons">' . $buttons . '</div><div class="hidden-md hidden-lg"><div class="inline pos-rel"><button class="btn btn-minier btn-yellow dropdown-toggle" data-toggle="dropdown" data-position="auto"><i class="fa fa-caret-down icon-only bigger-120"></i></button><ul class="dropdown-menu dropdown-only-icon dropdown-yellow dropdown-menu-right dropdown-caret dropdown-close">' . $buttonsMobile . '</ul></div></div></div>';
	}
}

if (!function_exists('canvas_table_row_attr')) {
	/**
	 * Set Default Row Attributes for Table
	 *
	 * @param string $str_value
	 * @param string $attributes
	 * 		=> colspan=2|id=idLists OR ['colspan' => 2, 'id' => 'idLists']
	 *
	 * @return string
	 */
	function canvas_table_row_attr($str_value, $attributes) {
		$attr = $attributes;
		if (is_array($attributes)) {
			$attribute = [];
			foreach ($attributes as $key => $value) {
				$attribute[] = "{$key}=\"{$value}\"";
			}
			$attr = implode(' ', $attribute);
		}
		
		return "{$str_value}{:}$attr";
	}
}

if (!function_exists('canvas_generate_table')) {
	
	/**
	 * Table Builder
	 *
	 * @param string $title
	 * @param string $title_id
	 * @param array  $header
	 * @param array  $body
	 * @param array  $attributes
	 * @param string $numbering
	 * @param string $containers
	 * 		: draw <div> container box, defalult true
	 * @param string $server_side
	 * @param boolean|string|array $server_side_custom_url
	 *
	 * @return string
	 */
	function canvas_generate_table($title = false, $title_id = false, $header = array(), $body = array(), $attributes = array(), $numbering = false, $containers = true, $server_side = false, $server_side_custom_url = false) {
		// set attributes
		$datatableClass = 'CanvaStack-table table animated fadeIn table-striped table-default table-bordered table-hover dataTable repeater display responsive nowrap';
		if (false !== $attributes && is_array($attributes)) {
			if (empty($attributes)) {
				$_attributes = array (
					'id'    => "datatable-{$title_id}",
					'class' => $datatableClass
				);
			} else {
				if (empty($attributes['id'])) {
					$_attributes['id'] = "datatable-{$title_id}";
				}
				if (empty($attributes['class'])) {
					$_attributes['class'] = $datatableClass;
				}
				foreach ($attributes as $attrField => $attrValue) {
					$_attributes[$attrField] = $attrValue;
				}
			}
		} else {
			$_attributes = array (
				'id'    => "datatable-{$title_id}",
				'class' => $datatableClass
			);
		}
		
		$attributes = ' ' . rtrim(canvas_attributes_to_string($_attributes));
		
		// set header table
		$hNumber   = false;
		$hEmpty    = false;
		$_header   = false;
		
		if (true === $numbering) {
			$number = ['number_lists'];
			$header = array_merge($number, $header);
		}
		
		if (false !== $header) {
			$_merge   = [];
			$_header  = '<thead><tr>';
			foreach ($header as $hIndex => $hList) {
				if (is_array($hList)) {
					$_merge[$hIndex] = $hList['merge'];
				}
				
				if (is_array($hList)) {
					$_header .= tableColumn($header, $hIndex, $hList['column']);
				} else {
					$_header .= tableColumn($header, $hIndex, $hList);
				}
			}
			$_header .= '</tr>';
			
			if (!empty($_merge)) {
				foreach ($_merge as $_mergedata) {
					foreach ($_mergedata as $idx => $mdList) {
						$_header .= tableColumn($_mergedata, $idx, $mdList);
					}
				}
			}
			$_header .= '</thead>';
		}
		
		// set body list(s) table
		$_body = false;
		$num   = false;
		
		if (false === $server_side) {
			if (false !== $body) {
				$_body = '<tbody>';
				
				$array_keys = array_keys($body);
				$first_key  = reset($array_keys);
				
				foreach ($body as $bIndex => $bLists) {
					$rowClickAction = false;
					if (!empty($bLists['row_data_url']) && false !== $bLists['row_data_url']) {
						$rowClickAction = ' onclick="location.href=\'' . $bLists['row_data_url'] . '\'" class="row-list-url"';
					}
					
					unset($bLists['row_data_url']);
					
					$_body .= '<tr>';
					for ($row = 0; $row <= count($body); $row++) {
						if ($bIndex === $row) {
							
							if (true === $numbering) {
								if ($first_key <= 0)	$numLists = intval($row)+1;
								else                 $numLists = intval($row);
								
								$_body .= "<td class=\"center\">{$numLists}</td>";
							}
							
							foreach ($bLists as $index => $list) {
								$row_attr = false;
								if ('action' === $index) {
									$rowClickAction = false;
								}
								if (true === str_contains($list, '{:}')) {
									$reList = explode('{:}', $list);
									$list   = $reList[0];
									
									if (isset($reList[1])) {
										$rowAttr  = explode('|', $reList[1]);
										$row_attr = ' ' . implode(' ', $rowAttr);
									}
									
									$row_list = "<td{$row_attr}{$rowClickAction}>{$list}</td>";
								} else {
									$row_list = "<td{$rowClickAction}>{$list}</td>";
								}
								
								if ($hNumber === $index) {
									if (is_empty($list)) $num = intval($row)+1;
									else $num = $list;
									
									$_body .= "<td class=\"center\">{$num}</td>";
								} else if ($hEmpty === $index) {
									$_body .= "<td class=\"center\">{$list}</td>";
								} else if ('active' === $index) {
									$_list  = set_active_value($list);
									$_body .= "<td align=\"center\">{$_list}</td>";
								} else if ('flag_status' === $index) {
									$_list  = internal_flag_status($list);
									$_body .= "<td align=\"center\"{$rowClickAction}>{$_list}</td>";
								} else if ('request_status' === $index) {
									$_list  = request_status(true, $list);
									$_body .= "<td align=\"center\">{$_list}</td>";
								} else if ('update_status' === $index) {
									$_list  = active_box();
									$_body .= "<td align=\"center\">{$_list[$list]}</td>";
								} else if ('action' === $index) {
									$_body .= "<td align=\"center\"{$rowClickAction}>{$list}</td>";
								} else {
									$_body .= $row_list;
								}
							}
						}
					}
					
					$_body .= '</tr>';
				}
				
				$_body .= '</tbody>';
			} else {
				$_body = '<tbody><tr><td>Found no data</td></tr></tbody>';
			}
		} else {
			$_body = null;
		}
		
		$table = "<table{$attributes}>{$_header}{$_body}</table>";
		
		return $table;
	}
	
	function tableColumn($header, $hIndex, $hList) {
		$hNumber = false;
		$hCheck  = false;
		$hEmpty  = false;
		$_header = false;
		
		$HKEY    = false;
		$HVAL    = false;
		if (is_array($hList)) {
			$keyList = array_keys($hList);
			$HKEY    = $keyList[0];
			$HVAL    = $hList[$HKEY];
		} else {
			$HKEY    = $hList;
			$HVAL    = trim(ucwords(str_replace('_', ' ', $HKEY)));
		}
		$hList       = $HKEY;
		$hLabel      = $HVAL;
		
		$hListFields = $hList;
		if (true === str_contains($hList, '|')) {
			$newHList    = explode('|', $hList);
			$hList       = $newHList[1];
			$hListFields = $hList;
		}
		if (true === str_contains($hList, '.')) {
			$newHList = explode('.', $hList);
			$hList    = $newHList[0];
		}
		
		// check if header label : no|id|nik
		$idHeader = $header[$hIndex];
		if (is_array($idHeader)) {
			$fHead    = array_keys($idHeader);
			$idHeader = $fHead[0];
		}
		if ('no' === strtolower($idHeader) || 'id' === strtolower($idHeader) || 'nik' === strtolower($idHeader)) $hNumber = $hIndex;
		
		if (true === canvas_string_contained($hList, '<input type="checkbox"')) $hCheck = $hIndex;
		if (is_empty($hList)) $hEmpty = $hIndex;
		
		$hList = trim(ucwords(str_replace('_', ' ', $hList)));
		if ($hNumber === $hIndex) {
			$_header     .= "<th class=\"center\" width=\"50\">{$hList}</th>";
		} else if (true === str_contains($hList, ':changeHeaderName:')) {
			$newHList     = explode(':changeHeaderName:', $hList);
			$hList        = ucwords($newHList[1]);
			$hListFields  = $hList;
			$_header     .= "<th class=\"center\" width=\"120\">{$hListFields}</th>";
		} else if ($hCheck === $hIndex) {
			$_header     .= "<th width=\"50\">{$hList}</th>";
		} else if ($hEmpty === $hIndex) {
			$_header     .= "<th class=\"center\" width=\"120\">{$hList}</th>";
		} else if ('Action' === $hList) {
			$_header    .= "<th class=\"center\" width=\"120\">{$hList}</th>";
		} else if ('Active' === $hList) {
			$_header    .= "<th class=\"center\" width=\"120\">{$hList}</th>";
		} else if ('Flag Status' === $hList) {
			$_header    .= "<th class=\"center\" width=\"120\">{$hList}</th>";
		} else {
			if ('number_lists' === strtolower($idHeader)) {
				$_header    .= "<th class=\"center\" width=\"30\">No</th><th class=\"center\" width=\"30\">ID</th>";
			} else {
				$row_attr = false;
				if (true === str_contains($hList, '{:}')) {
					$reList = explode('{:}', $hList);
					$hList  = $reList[0];
					
					if (isset($reList[1])) {
						$rowAttr  = explode('|', $reList[1]);
						$row_attr = ' ' . implode(' ', $rowAttr);
					}
					
					$row_list = "<th{$row_attr}>{$hList}</th>";
				} else {
					$row_list = "<th>{$hLabel}</th>";
				}
				
				$_header    .= $row_list;
			}
		}
		
		return $_header;
	}
}

if (!function_exists('canvas_draw_query_map_page_table')) {
	
	function canvas_draw_query_map_page_table($name, $field_id, $value_id, $data, $buffers, $fieldbuff) {		
		$fieldID   = $field_id;
		$trClass   = null;
		
		$o         = "<table class=\"table mapping-table display responsive relative-box {$name}\"><tbody>";
		if (!empty($buffers)) {
			$n      = 0;
			$id     = explode('__node__', $field_id)[0];
			$ico    = 'fa fa-recycle warning';
			$script = null;
			
			foreach ($buffers[$id] as $field_info => $value) {
				$n++;
				
				if ($n > 1) {
					$field_id = $fieldbuff['ranid'][$field_info];
					$value_id = $fieldbuff['ranval'][$field_info];
					$trClass  = " role-add-{$fieldID}";
					$ico      = 'fa fa-minus-circle danger';
					$script   = "<script type='text/javascript'>$(document).ready(function() { rowButtonRemovalMapRoles('{$field_id}', '{$value_id}'); mappingPageFieldnameValues('{$field_id}', '{$value_id}', '{$data['ajax_field_name']}'); });</script>";
				}
				
				$o .= "<tr id=\"row-box-{$field_id}\" class=\"relative-box row-box-{$fieldID}{$trClass}\">";
					$o .= "<td class=\"qmap-box-{$fieldID} field-name-box\">";
						$o .= $data['field_name'][$value->target_table][$value->target_field_name];
					$o .= "</td>";
					$o .= "<td class=\"qmap-box-{$fieldID} relative-box field-value-box\">";
						$o .= $data['field_value'][$value->target_table][$field_info];
						$o .= "<span id=\"remove-row{$field_id}\" class=\"remove-row{$fieldID} multi-chain-buttons\" style=\"\">";
							$o .= "<i class='{$ico}' aria-hidden='true'></i>";
						$o .= "</span>";
						$o .= $script;
					$o .= "</td>";
				$o .= "</tr>";
			}
			
		} else {
			$o .= "<tr id=\"row-box-{$field_id}\" class=\"relative-box row-box-{$field_id}\">";
				$o .= "<td class=\"qmap-box-{$field_id} field-name-box\">";
					$o .= "{$data['field_name']}";
				$o .= "</td>";
				$o .= "<td class=\"qmap-box-{$field_id} relative-box field-value-box\">";
					$o .= "{$data['field_value']}";
					$o .= "<span id=\"remove-row{$field_id}\" class=\"remove-row{$field_id} multi-chain-buttons\" style=\"display:none;\">";
						$o .= "<i class='fa fa-recycle warning' aria-hidden='true'></i>";
					$o .= "</span>";
				$o .= "</td>";
			$o .= "</tr>";
		}
		
		$o .= "</tbody></table>";
		
		return $o;
	}
}