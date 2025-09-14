<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Method;

use Canvastack\Canvastack\Library\Components\Table\Craft\Elements;

/**
 * Created on Dec 28, 2022
 *
 * Time Created : 3:02:03 PM
 *
 * @filesource	Post.php
 *
 * @author     wisnuwidi@canvastack.com - 2022
 * @copyright  wisnuwidi
 *
 * @email      wisnuwidi@canvastack.com
 */
class Post
{
    use Elements;

    private $id;

    private $columns;

    private $data;

    private $server_side;

    private $filters;

    private $custom_url;

    private $config = [];

    private $configName = [
        'searching',
        'processing',
        'retrieve',
        'paginate',
        'searchDelay',
        'bDeferRender',
        'responsive',
        'lengthMenu',
        'buttons',
        'orders',
        'rowReorder',
        'dom',
    ];

    public function __construct($attr_id, $columns, $data = [], $server_side = false, $filters = false, $custom_url = false)
    {
        $this->id = $attr_id;
        $this->columns = $columns;
        $this->data = $data;
        $this->server_side = $server_side;
        $this->filters = $filters;
        $this->custom_url = $custom_url;

        $this->config();
    }

    private function setConfig($name, $value = true)
    {
        $this->config[$name] = $value;
    }

    private $buttonConfig = 'exportOptions:{columns:":visible:not(:last-child)"}';

    private function config()
    {
        foreach ($this->configName as $config) {
            $this->setConfig($config);
        }

        $this->setConfig('searchDelay', 1000);
        $this->setConfig('responsive', false);
        $this->setConfig('autoWidth', false);
        $this->setConfig('dom', 'lBfrtip');
        $this->setConfig('rowReorder', "{selector:'td:nth-child(2)'}");
        $this->setConfig('lengthMenu', [[10, 25, 50, 100, 250, 500, 1000, -1], ['10', '25', '50', '100', '250', '500', '1000', 'Show All']]);
        $this->setConfig('buttons', $this->setButtons($this->id, [
            'excel|text:"<i class=\"fa fa-external-link\" aria-hidden=\"true\"></i> <u>E</u>xcel"|key:{key:"e",altKey:true}',
            'csv|'.$this->buttonConfig,
            'pdf|'.$this->buttonConfig,
            'copy|'.$this->buttonConfig,
            'print|'.$this->buttonConfig,
        ]));
    }

    public function script()
    {
        $varTableID = explode('-', $this->id);
        $varTableID = implode('', $varTableID);
        
        // Build AJAX configuration for POST method - route to AjaxController
        $token = csrf_token();
        $routePrefix = config('canvastack.routes.prefix', 'canvastack');
        $scriptURI = url("{$routePrefix}/ajax/post?renderDataTables=true");
        
        // Build difta parameters
        $diftaJS = json_encode([
            'name' => $this->data['name'] ?? '',
            'source' => 'dynamics'
        ]);
        
        // Enhanced configuration for POST method
        $postConfig = $this->config;
        $postConfig['serverSide'] = $this->server_side;
        
        if ($this->server_side) {
            // Build proper columns configuration for DataTables
            $columnsConfig = [];
            if (!empty($this->columns)) {
                // Handle both string and array column formats
                $columnsList = is_string($this->columns) ? json_decode($this->columns, true) : $this->columns;
                
                if (is_array($columnsList)) {
                    foreach ($columnsList as $column) {
                        if (is_string($column)) {
                            // CRITICAL: Skip 'id' column to match GET method behavior
                            if ($column !== 'id') {
                                $columnsConfig[] = $this->handlePseudoColumn($column);
                            }
                        } elseif (is_array($column) && isset($column['data'])) {
                            // CRITICAL: Skip 'id' column to match GET method behavior
                            if ($column['data'] !== 'id') {
                                $columnsConfig[] = $this->handlePseudoColumn($column);
                            }
                        }
                    }
                }
            }
            
            // Set columns configuration - CRITICAL: DataTables POST method requires explicit columns
            if (!empty($columnsConfig)) {
                $postConfig['columns'] = $this->addHiddenIdColumn($columnsConfig);
            } else {
                // Fallback: If no columns config, try to extract from data
                if (isset($this->data['columns']) && is_array($this->data['columns'])) {
                    $fallbackColumns = [];
                    foreach ($this->data['columns'] as $col) {
                        // CRITICAL: Skip 'id' column to match GET method behavior
                        if (is_string($col) && $col !== 'id') {
                            $fallbackColumns[] = $this->handlePseudoColumn($col);
                        } elseif (is_array($col) && isset($col['data']) && $col['data'] !== 'id') {
                            $fallbackColumns[] = $this->handlePseudoColumn($col);
                        }
                    }
                    if (!empty($fallbackColumns)) {
                        $postConfig['columns'] = $this->addHiddenIdColumn($fallbackColumns);
                    }
                } else {
                    // Last resort: Create basic columns configuration if none exists
                    // This prevents DataTables from failing due to missing columns
                    $postConfig['columns'] = [
                        ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'className' => 'center un-clickable', 'orderable' => false, 'searchable' => false],
                        ['data' => 'id', 'name' => 'id', 'visible' => false, 'searchable' => false, 'className' => 'control hidden-column'],
                        ['data' => 'name', 'name' => 'name', 'className' => 'auto-cut-text clickable', 'defaultContent' => ''],
                        ['data' => 'action', 'name' => 'action', 'className' => 'auto-cut-text', 'orderable' => false, 'searchable' => false]
                    ];
                }
            }
            
            // CRITICAL: Set default order to hidden ID column (index 1) to match GET method
            $postConfig['order'] = [[1, 'desc']];
            
            // Build AJAX configuration
            $postConfig['ajax'] = [
                'url' => $scriptURI,
                'type' => 'POST',
                'headers' => [
                    'X-CSRF-TOKEN' => $token
                ],
                'contentType' => 'application/x-www-form-urlencoded; charset=UTF-8',
                'dataType' => 'json'
            ];
        }
        
        // Build configuration JSON
        $configurations = json_encode($postConfig, JSON_UNESCAPED_SLASHES);
        
        // Add the data function for AJAX POST - Fix JSON syntax issue
        if ($this->server_side) {
            // Build the data function as a proper JavaScript function
            $dataFunctionStr = "function(data) {
                var postData = {
                    draw: data.draw,
                    start: data.start,
                    length: data.length,
                    search: data.search,
                    order: data.order,
                    columns: data.columns,
                    _token: '{$token}'
                };
                
                postData.difta = {$diftaJS};
                
                if (typeof window.canvastack_datatables_config !== 'undefined' && window.canvastack_datatables_config['{$this->id}']) {
                    postData.datatables_data = window.canvastack_datatables_config['{$this->id}'];
                }
                
                return postData;
            }";
            
            // Properly insert the data function into the ajax configuration
            // Use a more robust approach to handle nested JSON objects
            $ajaxPattern = '/"ajax":\{([^{}]*(?:\{[^{}]*\}[^{}]*)*)\}/';
            if (preg_match($ajaxPattern, $configurations, $matches)) {
                $ajaxContent = $matches[1];
                $newAjaxContent = $ajaxContent . ',"data":' . $dataFunctionStr;
                $configurations = str_replace(
                    '"ajax":{' . $ajaxContent . '}',
                    '"ajax":{' . $newAjaxContent . '}',
                    $configurations
                );
            } else {
                // Fallback: If regex fails, manually reconstruct the ajax configuration
                $ajaxConfig = $postConfig['ajax'];
                $ajaxConfig['data'] = '__DATA_FUNCTION_PLACEHOLDER__';
                $postConfig['ajax'] = $ajaxConfig;
                $configurations = json_encode($postConfig, JSON_UNESCAPED_SLASHES);
                $configurations = str_replace('"__DATA_FUNCTION_PLACEHOLDER__"', $dataFunctionStr, $configurations);
            }
        }
        
        // CRITICAL: Replace row number function placeholder with actual JavaScript function
        $rowNumberFunction = 'function(data, type, row, meta) {
            return meta.row + meta.settings._iDisplayStart + 1;
        }';
        $configurations = str_replace('"__ROW_NUMBER_FUNCTION__"', $rowNumberFunction, $configurations);
        
        // CRITICAL: Replace group info function placeholder with actual JavaScript function
        // SUPER DYNAMIC: Support multiple field names for maximum flexibility
        $groupInfoFunction = 'function(data, type, row, meta) {
            // Return group information with comprehensive fallback chain
            return row.group_info ||      // Priority 1: group_info
                   row.group_name ||      // Priority 2: group_name
                   row.group_alias ||     // Priority 3: group_alias
                   row.department ||      // Priority 4: department
                   row.department_name || // Priority 5: department_name
                   row.role ||            // Priority 6: role
                   row.role_name ||       // Priority 7: role_name
                   row.team ||            // Priority 8: team
                   row.team_name ||       // Priority 9: team_name
                   row.division ||        // Priority 10: division
                   row.unit ||            // Priority 11: unit
                   "N/A";                 // Default: N/A
        }';
        $configurations = str_replace('"__GROUP_INFO_FUNCTION__"', $groupInfoFunction, $configurations);
        
        // CRITICAL: Replace action function placeholder with actual JavaScript function
        $actionFunction = 'function(data, type, row, meta) {
            // Return action buttons or empty string if not available
            return row.action || "";
        }';
        $configurations = str_replace('"__ACTION_FUNCTION__"', $actionFunction, $configurations);

        $script = '<script type="text/javascript">';
        
        // Store configuration for POST requests
        if ($this->server_side) {
            $configData = json_encode([
                'columns' => $this->data['columns'] ?? [],
                'records' => $this->data['records'] ?? [],
                'modelProcessing' => $this->data['modelProcessing'] ?? [],
                'labels' => $this->data['labels'] ?? [],
                'relations' => $this->data['relations'] ?? [],
                'tableID' => $this->data['tableID'] ?? [],
                'model' => $this->data['model'] ?? []
            ]);
            
            $script .= "
            if (typeof window.canvastack_datatables_config === 'undefined') {
                window.canvastack_datatables_config = {};
            }
            window.canvastack_datatables_config['{$this->id}'] = {$configData};
            ";
        }
        
        $script .= "
jQuery(function($) {
    try {
        // Debug: Log configuration before initialization
        console.log('DataTable POST Configuration for {$this->id}:', {$configurations});
        
        // Create global DataTable variable (same as GET method)
        cody_{$varTableID}_dt = $('#{$this->id}').DataTable(
            {$configurations}
        );
        
        // Add comprehensive error handling for AJAX requests
        cody_{$varTableID}_dt.on('error.dt', function(e, settings, techNote, message) {
            console.error('DataTable AJAX error for {$this->id}: ', message);
            console.error('Technical note: ', techNote);
            console.error('Settings: ', settings);
        });
        
        // Add success handler to debug data reception
        cody_{$varTableID}_dt.on('xhr.dt', function(e, settings, json, xhr) {
            if (json) {
                console.log('DataTable POST received data for {$this->id}:', json);
                console.log('Records total: ', json.recordsTotal);
                console.log('Records filtered: ', json.recordsFiltered);
                console.log('Data length: ', json.data ? json.data.length : 0);
            } else {
                console.warn('DataTable POST received empty response for {$this->id}');
            }
        });
        
        // Add draw event handler to fix clickable functionality on every render
        cody_{$varTableID}_dt.on('draw.dt', function() {
            console.log('DataTable POST draw event fired for {$this->id}');
            
            // Fix clickable functionality: move from TR to TD (same as GET method)
            $('#{$this->id} tbody tr').each(function() {
                var \$tr = $(this);
                if (\$tr.hasClass('clickable')) {
                    \$tr.removeClass('clickable');
                    \$tr.find('td').each(function() {
                        var \$td = $(this);
                        if (!\$td.hasClass('un-clickable') && !\$td.find('.action-buttons-box').length) {
                            \$td.addClass('auto-cut-text clickable');
                        } else if (\$td.find('.action-buttons-box').length) {
                            \$td.addClass('auto-cut-text');
                        }
                    });
                }
            });
            
            // Add click handler for clickable TDs (same as Scripts.php)
            $('#{$this->id}').off('click', 'td.clickable').on('click', 'td.clickable', function() {
                var getRLP = $(this).parent('tr').attr('rlp');
                if (getRLP != false && getRLP != undefined) {
                    var hash = '" . hash_code_id() . "';
                    var _rlp = parseInt(getRLP.replace(hash, '') - 8*800/80);
                    var url_path = '" . url(canvastack_current_route()->uri ?? '') . "';
                    window.location = url_path + '/' + _rlp;
                }
            });
        });
        
    } catch (error) {
        console.error('DataTable POST initialization error for {$this->id}: ', error);
        console.error('Configuration was: ', {$configurations});
    }
});

// Add filter button container AFTER DataTable initialization (same as GET method)  
jQuery(function($) {
    // Wait for DataTable to be fully initialized before adding filter container
    setTimeout(function() {
        $('div#{$this->id}_wrapper>.dt-buttons').append('<span class=\"cody_{$this->id}_diy-dt-filter-box\"></span>');
        
        // Call filter functionality AFTER container is created
        " . $this->generateFilterScript() . "
        
        // Fix clickable functionality: move from TR to TD (same as GET method)
        $('#{$this->id} tbody tr').each(function() {
            var \$tr = $(this);
            if (\$tr.hasClass('clickable')) {
                \$tr.removeClass('clickable');
                \$tr.find('td').each(function() {
                    var \$td = $(this);
                    if (!\$td.hasClass('un-clickable') && !\$td.find('.action-buttons-box').length) {
                        \$td.addClass('auto-cut-text clickable');
                    } else if (\$td.find('.action-buttons-box').length) {
                        \$td.addClass('auto-cut-text');
                    }
                });
            }
        });
        
        // Add click handler for clickable TDs (same as Scripts.php)
        $('#{$this->id}').off('click', 'td.clickable').on('click', 'td.clickable', function() {
            var getRLP = $(this).parent('tr').attr('rlp');
            if (getRLP != false && getRLP != undefined) {
                var hash = '" . hash_code_id() . "';
                var _rlp = parseInt(getRLP.replace(hash, '') - 8*800/80);
                var url_path = '" . url(canvastack_current_route()->uri ?? '') . "';
                window.location = url_path + '/' + _rlp;
            }
        });
    }, 200); // Increased timeout to ensure DataTable is fully ready
});

// Add document ready wrapper for table wrapping only
$(document).ready(function() { 
    $('#{$this->id}').wrap('<div class=\"diy-wrapper-table\"></div>'); 
    $('.dtfc-fixed-left').last().addClass('last-of-scrool-column-table'); 
});
        ";
        $script .= '</script>';

        return $script;
    }
    
    /**
     * Generate filter script for POST method (similar to Scripts trait)
     * 
     * @return string
     */
    private function generateFilterScript()
    {
        // Always generate filter script for POST method to ensure compatibility
        // The diyDataTableFilters function will handle cases where no filter modal exists
        
        $varTableID = explode('-', $this->id);
        $varTableID = implode('', $varTableID);
        
        // Build script URI for POST method
        $routePrefix = config('canvastack.routes.prefix', 'canvastack');
        $scriptURI = url("{$routePrefix}/ajax/post?renderDataTables=true");
        
        // Generate filter JavaScript call (similar to Scripts trait filter method)
        $filterScript = "diyDataTableFilters('{$this->id}', '{$scriptURI}', cody_{$varTableID}_dt);";
        
        // Generate export script (similar to Scripts trait export method)
        $exportScript = $this->generateExportScript();
        
        return $filterScript . $exportScript;
    }
    
    /**
     * Generate export script for POST method (similar to Scripts trait)
     * 
     * @return string
     */
    private function generateExportScript()
    {
        $modalID = "{$this->id}_cdyFILTERmodalBOX";
        $filterID = "{$this->id}_cdyFILTER";
        $exportID = 'export_'.str_replace('-', '_', $this->id).'_cdyFILTERField';
        $token = csrf_token();
        
        // Build export URL for POST method
        $routePrefix = config('canvastack.routes.prefix', 'canvastack');
        $exportURL = url("{$routePrefix}/ajax/export?exportDataTables=true");
        
        // Add difta parameters to export URL
        if (!empty($this->data['name'])) {
            $exportURL .= "&difta[name]={$this->data['name']}&difta[source]=dynamics";
        }
        
        $connection = '';
        if (!empty($this->data['connection'])) {
            $connection = "::{$this->data['connection']}";
        }
        
        $filters = [];
        if (!empty($this->filters) && is_array($this->filters)) {
            $filters = $this->filters;
        }
        $filter = json_encode($filters);
        
        return "exportFromModal('{$modalID}', '{$exportID}', '{$filterID}', '{$token}', '{$exportURL}', '{$connection}', {$filter});";
    }
    
    /**
     * Check if filter capability is available
     * 
     * @return bool
     */
    private function hasFilterCapability()
    {
        // Always return true if we have any indication of filter functionality
        // This is a more aggressive approach to ensure filters work
        return true;
    }
    
    /**
     * Handle pseudo columns that don't exist in server data
     * 
     * @param string|array $column
     * @return array
     */
    private function handlePseudoColumn($column)
    {
        // Define pseudo columns and their configurations
        // FIXED: Remove 'group_info' from pseudo columns - it's a REAL database column from JOIN query
        $pseudoColumns = [
            'number_lists' => [
                'data' => 'DT_RowIndex',
                'name' => 'DT_RowIndex',
                'defaultContent' => '',
                'orderable' => false,
                'searchable' => false,
                'className' => 'center un-clickable sorting_disabled',
                'render' => '__ROW_NUMBER_FUNCTION__'
            ],
            'action' => [
                'data' => 'action',
                'name' => 'action',
                'defaultContent' => '',
                'orderable' => false,
                'searchable' => false,
                'className' => 'auto-cut-text',
                'render' => '__ACTION_FUNCTION__'
            ],
            'no' => [
                'data' => 'DT_RowIndex',
                'name' => 'DT_RowIndex',
                'defaultContent' => '',
                'orderable' => false,
                'searchable' => false,
                'className' => 'center un-clickable',
                'render' => '__ROW_NUMBER_FUNCTION__'
            ]
        ];
        
        if (is_string($column)) {
            // Handle string column
            if (isset($pseudoColumns[$column])) {
                return $pseudoColumns[$column];
            } else {
                // Regular column - add auto-cut-text and clickable classes (same as Builder.php)
                return [
                    'data' => $column,
                    'name' => $column,
                    'className' => 'auto-cut-text clickable'
                ];
            }
        } elseif (is_array($column) && isset($column['data'])) {
            // Handle array column configuration
            $columnData = $column['data'];
            if (isset($pseudoColumns[$columnData])) {
                // Merge with pseudo column config, preserving any custom settings
                return array_merge($pseudoColumns[$columnData], $column);
            } else {
                // Regular column - ensure it has proper classes (same as Builder.php)
                $columnConfig = $column;
                if (!isset($columnConfig['className'])) {
                    $columnConfig['className'] = 'auto-cut-text clickable';
                }
                if (!isset($columnConfig['name'])) {
                    $columnConfig['name'] = $columnData;
                }
                return $columnConfig;
            }
        }
        
        return ['data' => null, 'defaultContent' => ''];
    }
    
    /**
     * Add hidden ID column to columns configuration (same as Builder.php)
     * 
     * @param array $columns
     * @return array
     */
    private function addHiddenIdColumn($columns)
    {
        // Check if ID column already exists in visible columns
        $hasVisibleId = false;
        foreach ($columns as $column) {
            if (isset($column['data']) && $column['data'] === 'id') {
                $hasVisibleId = true;
                break;
            }
        }
        
        // If ID is not in visible columns, add it as hidden column
        if (!$hasVisibleId) {
            // Find the position after number_lists/DT_RowIndex column
            $insertPosition = 0;
            foreach ($columns as $index => $column) {
                if (isset($column['data']) && ($column['data'] === 'DT_RowIndex' || $column['data'] === 'number_lists')) {
                    $insertPosition = $index + 1;
                    break;
                }
            }
            
            // Insert hidden ID column
            $hiddenIdColumn = [
                'data' => 'id',
                'name' => 'id',
                'visible' => false,
                'searchable' => false,
                'className' => 'control hidden-column'
            ];
            
            array_splice($columns, $insertPosition, 0, [$hiddenIdColumn]);
        }
        
        return $columns;
    }
}
