<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Method;

use Canvastack\Canvastack\Library\Components\Table\Craft\Elements;
use Canvastack\Canvastack\Library\Components\Table\Exceptions\SecurityException;

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
        // SECURITY: Sanitize DOM ID to prevent XSS
        $sanitizedId = $this->sanitizeDomId($this->id);
        $varTableID = explode('-', $sanitizedId);
        $varTableID = implode('', $varTableID);
        
        // Build AJAX configuration for POST method - route to AjaxController
        // SECURITY: Sanitize CSRF token before JavaScript output
        $token = $this->sanitizeJavaScript(csrf_token());
        $routePrefix = config('canvastack.routes.prefix', 'canvastack');
        // SECURITY: Sanitize URL before JavaScript output
        $scriptURI = $this->sanitizeUrl(url("{$routePrefix}/ajax/post?renderDataTables=true"));
        
        // Build difta parameters - SECURITY: Use secure JSON encoding
        $diftaJS = $this->sanitizeJsonForJavaScript([
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
        
        // Build configuration JSON - SECURITY: Use secure JSON encoding to prevent XSS
        $configurations = $this->sanitizeJsonForJavaScript($postConfig);
        
        // Add the data function for AJAX POST - Fix JSON syntax issue
        if ($this->server_side) {
            // Build the data function as a proper JavaScript function - SECURITY: All variables pre-sanitized
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
                
                if (typeof window.canvastack_datatables_config !== 'undefined' && window.canvastack_datatables_config['{$sanitizedId}']) {
                    postData.datatables_data = window.canvastack_datatables_config['{$sanitizedId}'];
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
            // SECURITY: Use secure JSON encoding to prevent XSS in configuration data
            $configData = $this->sanitizeJsonForJavaScript([
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
            window.canvastack_datatables_config['{$sanitizedId}'] = {$configData};
            ";
        }
        
        $script .= "
jQuery(function($) {
    try {
        // Debug: Log configuration before initialization - SECURITY: Using sanitized ID
        console.log('DataTable POST Configuration for {$sanitizedId}:', {$configurations});
        
        // Create global DataTable variable (same as GET method) - SECURITY: Using sanitized ID
        cody_{$varTableID}_dt = $('#{$sanitizedId}').DataTable(
            {$configurations}
        );
        
        // Add comprehensive error handling for AJAX requests - SECURITY: Using sanitized ID
        cody_{$varTableID}_dt.on('error.dt', function(e, settings, techNote, message) {
            console.error('DataTable AJAX error for {$sanitizedId}: ', message);
            console.error('Technical note: ', techNote);
            console.error('Settings: ', settings);
        });
        
        // Add success handler to debug data reception - SECURITY: Using sanitized ID
        cody_{$varTableID}_dt.on('xhr.dt', function(e, settings, json, xhr) {
            if (json) {
                console.log('DataTable POST received data for {$sanitizedId}:', json);
                console.log('Records total: ', json.recordsTotal);
                console.log('Records filtered: ', json.recordsFiltered);
                console.log('Data length: ', json.data ? json.data.length : 0);
            } else {
                console.warn('DataTable POST received empty response for {$sanitizedId}');
            }
        });
        
        // Add draw event handler to fix clickable functionality on every render - SECURITY: Using sanitized ID
        cody_{$varTableID}_dt.on('draw.dt', function() {
            console.log('DataTable POST draw event fired for {$sanitizedId}');
            
            // Fix clickable functionality: move from TR to TD (same as GET method) - SECURITY: Using sanitized ID
            $('#{$sanitizedId} tbody tr').each(function() {
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
            
            // Add click handler for clickable TDs (same as Scripts.php) - SECURITY: Using sanitized ID and URLs
            $('#{$sanitizedId}').off('click', 'td.clickable').on('click', 'td.clickable', function() {
                var getRLP = $(this).parent('tr').attr('rlp');
                if (getRLP != false && getRLP != undefined) {
                    var hash = '" . $this->sanitizeJavaScript(hash_code_id()) . "';
                    var _rlp = parseInt(getRLP.replace(hash, '') - 8*800/80);
                    var url_path = '" . $this->sanitizeUrl(url(canvastack_current_route()->uri ?? '')) . "';
                    window.location = url_path + '/' + _rlp;
                }
            });
        });
        
    } catch (error) {
        console.error('DataTable POST initialization error for {$sanitizedId}: ', error);
        console.error('Configuration was: ', {$configurations});
    }
});

// Add filter button container AFTER DataTable initialization (same as GET method) - SECURITY: Using sanitized ID  
jQuery(function($) {
    // Wait for DataTable to be fully initialized before adding filter container
    setTimeout(function() {
        $('div#{$sanitizedId}_wrapper>.dt-buttons').append('<span class=\"cody_{$sanitizedId}_diy-dt-filter-box\"></span>');
        
        // Call filter functionality AFTER container is created
        " . $this->generateFilterScript() . "
        
        // Fix clickable functionality: move from TR to TD (same as GET method) - SECURITY: Using sanitized ID
        $('#{$sanitizedId} tbody tr').each(function() {
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
        
        // Add click handler for clickable TDs (same as Scripts.php) - SECURITY: Using sanitized ID and URLs
        $('#{$sanitizedId}').off('click', 'td.clickable').on('click', 'td.clickable', function() {
            var getRLP = $(this).parent('tr').attr('rlp');
            if (getRLP != false && getRLP != undefined) {
                var hash = '" . $this->sanitizeJavaScript(hash_code_id()) . "';
                var _rlp = parseInt(getRLP.replace(hash, '') - 8*800/80);
                var url_path = '" . $this->sanitizeUrl(url(canvastack_current_route()->uri ?? '')) . "';
                window.location = url_path + '/' + _rlp;
            }
        });
    }, 200); // Increased timeout to ensure DataTable is fully ready
});

// Add document ready wrapper for table wrapping only - SECURITY: Using sanitized ID
$(document).ready(function() { 
    $('#{$sanitizedId}').wrap('<div class=\"diy-wrapper-table\"></div>'); 
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
        
        // SECURITY: Sanitize DOM ID before using in JavaScript
        $sanitizedId = $this->sanitizeDomId($this->id);
        $varTableID = explode('-', $sanitizedId);
        $varTableID = implode('', $varTableID);
        
        // Build script URI for POST method - SECURITY: Sanitize URL
        $routePrefix = config('canvastack.routes.prefix', 'canvastack');
        $scriptURI = $this->sanitizeUrl(url("{$routePrefix}/ajax/post?renderDataTables=true"));
        
        // Generate filter JavaScript call (similar to Scripts trait filter method) - SECURITY: Using sanitized values
        $filterScript = "diyDataTableFilters('{$sanitizedId}', '{$scriptURI}', cody_{$varTableID}_dt);";
        
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
        // SECURITY: Sanitize DOM ID before using in JavaScript
        $sanitizedId = $this->sanitizeDomId($this->id);
        $modalID = "{$sanitizedId}_cdyFILTERmodalBOX";
        $filterID = "{$sanitizedId}_cdyFILTER";
        $exportID = 'export_'.str_replace('-', '_', $sanitizedId).'_cdyFILTERField';
        // SECURITY: Sanitize CSRF token
        $token = $this->sanitizeJavaScript(csrf_token());
        
        // Build export URL for POST method - SECURITY: Sanitize URL and parameters
        $routePrefix = config('canvastack.routes.prefix', 'canvastack');
        $exportURL = $this->sanitizeUrl(url("{$routePrefix}/ajax/export?exportDataTables=true"));
        
        // Add difta parameters to export URL - SECURITY: Sanitize data values
        if (!empty($this->data['name'])) {
            $safeName = $this->sanitizeHtmlAttribute($this->data['name']);
            $exportURL .= "&difta[name]={$safeName}&difta[source]=dynamics";
        }
        
        $connection = '';
        if (!empty($this->data['connection'])) {
            $connection = "::" . $this->sanitizeJavaScript($this->data['connection']);
        }
        
        $filters = [];
        if (!empty($this->filters) && is_array($this->filters)) {
            $filters = $this->filters;
        }
        // SECURITY: Use secure JSON encoding
        $filter = $this->sanitizeJsonForJavaScript($filters);
        
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

    /**
     * Sanitize JavaScript string to prevent XSS attacks
     * @param string $input
     * @return string
     */
    private function sanitizeJavaScript(string $input): string
    {
        if (empty($input)) {
            return '';
        }

        // Remove dangerous characters and patterns for JavaScript context
        $dangerous = [
            '<script', '</script>', '<iframe', '</iframe>', 'javascript:', 'vbscript:',
            'onload=', 'onerror=', 'onclick=', 'onmouseover=', 'onfocus=', 'onblur=',
            'eval(', 'setTimeout(', 'setInterval(', 'Function(', 'document.write(',
            'alert(', 'confirm(', 'prompt(', 'console.log('
        ];

        $cleaned = str_ireplace($dangerous, '', $input);
        
        // Additional character escaping for JavaScript context
        $cleaned = addslashes($cleaned);
        $cleaned = str_replace(["\r", "\n", "\t"], ['\\r', '\\n', '\\t'], $cleaned);
        
        // Validate result doesn't contain malicious patterns
        if ($cleaned !== $input) {
            throw new SecurityException("Potentially malicious JavaScript detected", [
                'original_input' => $input,
                'sanitized_output' => $cleaned,
                'context' => 'javascript_sanitization'
            ]);
        }

        return $cleaned;
    }

    /**
     * Sanitize HTML attribute to prevent XSS attacks
     * @param string $input
     * @return string
     */
    private function sanitizeHtmlAttribute(string $input): string
    {
        if (empty($input)) {
            return '';
        }

        // Remove dangerous patterns for HTML attributes
        $dangerous = [
            'javascript:', 'vbscript:', 'data:', 'onload=', 'onerror=', 'onclick=',
            'onmouseover=', 'onfocus=', 'onblur=', '<script', '</script>'
        ];

        $cleaned = str_ireplace($dangerous, '', $input);
        $cleaned = htmlspecialchars($cleaned, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Validate sanitization effectiveness
        if (strpos(strtolower($cleaned), 'script') !== false || 
            strpos(strtolower($cleaned), 'javascript') !== false) {
            throw new SecurityException("XSS attempt detected in HTML attribute", [
                'original_input' => $input,
                'sanitized_output' => $cleaned,
                'context' => 'html_attribute_sanitization'
            ]);
        }

        return $cleaned;
    }

    /**
     * Sanitize JSON data for safe JavaScript embedding
     * @param mixed $data
     * @return string
     */
    private function sanitizeJsonForJavaScript($data): string
    {
        // Use Laravel's json encoding with proper escaping
        $json = json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES);
        
        if ($json === false) {
            throw new SecurityException("JSON encoding failed during sanitization", [
                'data_type' => gettype($data),
                'json_error' => json_last_error_msg(),
                'context' => 'json_sanitization'
            ]);
        }

        // Additional XSS protection for script injection
        $json = str_replace(['</', '<!--', '-->', '<script'], ['<\/', '\\u003c!--', '--\\u003e', '\\u003cscript'], $json);
        
        return $json;
    }

    /**
     * Validate and sanitize DOM ID for safe usage
     * @param string $id
     * @return string
     */
    private function sanitizeDomId(string $id): string
    {
        if (empty($id)) {
            throw new SecurityException("Empty DOM ID not allowed", [
                'context' => 'dom_id_validation'
            ]);
        }

        // Remove any characters that could break JavaScript selectors
        $cleaned = preg_replace('/[^a-zA-Z0-9_-]/', '', $id);
        
        if ($cleaned !== $id) {
            throw new SecurityException("Invalid characters in DOM ID", [
                'original_id' => $id,
                'sanitized_id' => $cleaned,
                'context' => 'dom_id_sanitization'
            ]);
        }

        // Ensure ID starts with letter (HTML5 requirement)
        if (!preg_match('/^[a-zA-Z]/', $cleaned)) {
            throw new SecurityException("DOM ID must start with a letter", [
                'invalid_id' => $cleaned,
                'context' => 'dom_id_format_validation'
            ]);
        }

        return $cleaned;
    }

    /**
     * Sanitize URL for safe JavaScript usage
     * @param string $url
     * @return string
     */
    private function sanitizeUrl(string $url): string
    {
        if (empty($url)) {
            return '';
        }

        // Parse and validate URL
        $parsed = parse_url($url);
        if ($parsed === false) {
            throw new SecurityException("Invalid URL format detected", [
                'invalid_url' => $url,
                'context' => 'url_sanitization'
            ]);
        }

        // Block dangerous protocols
        $dangerousProtocols = ['javascript', 'vbscript', 'data', 'file'];
        if (isset($parsed['scheme']) && in_array(strtolower($parsed['scheme']), $dangerousProtocols)) {
            throw new SecurityException("Dangerous URL protocol detected", [
                'url' => $url,
                'protocol' => $parsed['scheme'],
                'context' => 'url_protocol_validation'
            ]);
        }

        // Escape URL for JavaScript context
        return addslashes($url);
    }
}
