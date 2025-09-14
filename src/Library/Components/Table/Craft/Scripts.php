<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft;

/**
 * Created on 22 May 2021
 * Time Created : 00:29:19
 *
 * @filesource Scripts.php
 *
 * @author    wisnuwidi@canvastack.com - 2021
 * @copyright wisnuwidi
 *
 * @email     wisnuwidi@canvastack.com
 */
trait Scripts
{
    private $datatablesMode = 'GET';

    private $strictGetUrls = true;

    private $strictColumns = true;

    /**
     * Javascript Config for Rendering Datatables
     *
     * created @Oct 11, 2018
     * author: wisnuwidi
     *
     * @param  string  $attr_id
     * @param  string  $columns
     * @param  string  $data_info
     * @param  bool  $server_side
     * @param  bool  $filters
     * @param  bool|string|array  $custom_link
     * @return string
     */
    protected function datatables($attr_id, $columns, $data_info = [], $server_side = false, $filters = false, $custom_link = false)
    {
        $varTableID = explode('-', $attr_id);
        $varTableID = implode('', $varTableID);
        $current_url = url(canvastack_current_route()->uri);

        $buttonConfig = 'exportOptions:{columns:":visible:not(:last-child)"}';
        $buttonset = $this->setButtons($attr_id, [
            'excel|text:"<i class=\"fa fa-external-link\" aria-hidden=\"true\"></i> <u>E</u>xcel"|key:{key:"e",altKey:true}',
            'csv|'.$buttonConfig,
            'pdf|'.$buttonConfig,
            'copy|'.$buttonConfig,
            'print|'.$buttonConfig,
        ]);

        $initComplete = null;
        $_fixedColumn = '';
        if (! empty($data_info['fixed_columns'])) {
            $fixedColumnData = json_encode($data_info['fixed_columns']);
            $_fixedColumn = 'scrollY:300,scrollX:true,scrollCollapse:true,fixedColumns:'.$fixedColumnData.',';
        }
        //	$_scroller     = '"scroller"     :true,';
        $_searching = '"searching"    :true,';
        $_processing = '"processing"   :true,';
        $_retrieve = '"retrieve"     :false,';
        $_paginate = '"paginate"     :true,';
        $_searchDelay = '"searchDelay"  :1000,';
        $_bDeferRender = '"bDeferRender" :true,';
        $_responsive = '"responsive"   :false,';
        $_autoWidth = '"autoWidth"    :false,';
        $_dom = '"dom"          :"lBfrtip",';

        $allLimitRows = 9999999999;
        $limitRowsData = [10, 25, 50, 100, 250, 500, 1000, $allLimitRows];
        $onloadRowsLimit = [10];
        if (! empty($data_info['onload_limit_rows'])) {
            if (is_string($data_info['onload_limit_rows'])) {
                if (in_array(strtolower($data_info['onload_limit_rows']), ['*', 'all'])) {
                    unset($limitRowsData[array_search(end($limitRowsData), $limitRowsData)]);
                    $onloadRowsLimit = [$allLimitRows];
                }
            } else {
                unset($limitRowsData[array_search($data_info['onload_limit_rows'], $limitRowsData)]);
                $onloadRowsLimit = [intval($data_info['onload_limit_rows'])];
            }

            $limitRowsData = array_merge_recursive($onloadRowsLimit, $limitRowsData);
        }

        $limitRowsDataString = [];
        foreach ($limitRowsData as $row => $limit) {
            if ($allLimitRows == $limit) {
                $limitRowsDataString[$row] = 'Show All';
            } else {
                $limitRowsDataString[$row] = (string) $limit.' Rows';
            }
        }
        $lengthMenu = json_encode([$limitRowsData, $limitRowsDataString]);
        $_lengthMenu = "lengthMenu :{$lengthMenu}, ";

        $_buttons = '"buttons"  :'.$buttonset.',';
        $responsive = "rowReorder :{selector:'td:nth-child(2)'},responsive: false,";
        $default_set = $_fixedColumn.$_searching.$_processing.$_retrieve.$_paginate.$_searchDelay.$_bDeferRender.$_responsive.$_autoWidth.$_dom.$_lengthMenu.$_buttons;

        $js_conditional = null;
        if (! empty($data_info['conditions']['columns'])) {
            $js_conditional = $this->conditionalColumns("cody_{$varTableID}_dt", $data_info['conditions']['columns'], $data_info['columns']);
        }

        $filter_button = false;
        $filter_js = false;
        $js = '<script type="text/javascript">jQuery(function($) {';
        if (false !== $server_side) {
            $diftaURI = "&difta[name]={$data_info['name']}&difta[source]=dynamics";
            
            // Add route_path to difta for POST requests to determine correct action URLs
            if (isset($this->route_page) && !empty($this->route_page)) {
                $diftaURI .= "&difta[route_path]={$this->route_page}";
            }
            
            $link_url = "renderDataTables=true{$diftaURI}";

            if (false !== $custom_link) {
                if (is_array($custom_link)) {
                    $link_url = "{$custom_link[0]}={$custom_link[1]}";
                } else {
                    $link_url = "{$custom_link}=true";
                }
            }

            $scriptURI = "{$current_url}?{$link_url}";
            $colDefs = ",columnDefs:[{target:[1],visible:false,searchable:false,className:'control hidden-column'}";
            $orderColumn = ",order:[[1,'desc']]{$colDefs}]";
            
            // Handle columns configuration differently for POST method
            if ('POST' === $this->datatablesMode) {
                $columns = ",columns:{$columns}{$orderColumn}";
            } else {
                $columns = ",columns:{$columns}{$orderColumn}";
            }
            
            $url_path = url(canvastack_current_route()->uri);
            $hash = hash_code_id();
            $clickAction = ".on('click','td.clickable', function(){ var getRLP = $(this).parent('tr').attr('rlp'); if(getRLP != false) { var _rlp = parseInt(getRLP.replace('{$hash}','')-8*800/80); window.location='{$url_path}/'+_rlp+'/edit'; } });";
            $initComplete = ','.$this->initComplete($attr_id, false);

            if (false !== $filters) {
                if (is_array($filters) && empty($filters)) {
                    $filters = null;
                }
                $filter_button = "$('div#{$attr_id}_wrapper>.dt-buttons').append('<span class=\"cody_{$attr_id}_diy-dt-filter-box\"></span>')";
                $filter_js = $this->filter($attr_id, $scriptURI);
                $exportURI = route('ajax.export')."?exportDataTables=true{$diftaURI}";
                $connection = null;
                if (! empty($this->connection)) {
                    $connection = "::{$this->connection}";
                }
                $filter_js .= $this->export($attr_id.$connection, $exportURI);
            }

            $jsOrder = null;
            if (true === $this->strictGetUrls) {
                //	$jsOrder   = "drawDatatableOnClickColumnOrder('{$attr_id}', '{$scriptURI}{$filters}', cody_{$varTableID}_dt);";
            }
            //	$hiddenColumn = "$('#{$attr_id}').DataTable().columns([1]).visible(false);";
            $hiddenColumn = ''; //"cody_{$varTableID}_dt.column(1).visible(false).columns.adjust().responsive.recalc();";
            $fixedColumn = "$('.dtfc-fixed-left').last().addClass('last-of-scrool-column-table');";
            $documentLoad = "$(document).ready(function() { $('#{$attr_id}').wrap('<div class=\"diy-wrapper-table\"></div>'); {$filter_js}; {$jsOrder} {$hiddenColumn} {$fixedColumn} });";

            if (! empty($this->method)) {
                $this->datatablesMode = $this->method;
            }
            if ('POST' === $this->datatablesMode) {
                $token = csrf_token();
                $idString = str_replace('-', '', $attr_id);
                
                // Build difta object for JavaScript
                $diftaJS = json_encode([
                    'name' => $data_info['name'] ?? '',
                    'source' => 'dynamics'
                ]);
                
                // Build POST data function that includes all necessary parameters
                $postDataFunction = "data: function (data) {
                    // Standard datatables parameters
                    var postData = {
                        draw: data.draw,
                        start: data.start,
                        length: data.length,
                        search: data.search,
                        order: data.order,
                        columns: data.columns,
                        _token: '{$token}'
                    };
                    
                    // Add difta parameters
                    postData.difta = {$diftaJS};
                    
                    // Add datatables configuration data if available
                    if (typeof window.canvastack_datatables_config !== 'undefined' && window.canvastack_datatables_config['{$attr_id}']) {
                        postData.datatables_data = window.canvastack_datatables_config['{$attr_id}'];
                    }
                    
                    // Clean unnecessary components if strict mode enabled
                    if ({$this->strictColumns}) {
                        deleteUnnecessaryDatatableComponents(postData, {$this->strictColumns});
                    }
                    
                    return postData;
                }";
                
                $ajax = "ajax:{url:'{$scriptURI}',type:'POST',headers:{'X-CSRF-TOKEN': '{$token}'},{$postDataFunction} }";
            } else {
                // FIX THE UNNECESARY @https://stackoverflow.com/a/46805503/20802728
                $idString = str_replace('-', '', $attr_id);
                $ajaxLimitGetURLs = null;
                if (true === $this->strictGetUrls) {
                    $ajaxLimitGetURLs = ",data: function (data) {var diyDUDC{$idString} = data; deleteUnnecessaryDatatableComponents(diyDUDC{$idString}, {$this->strictColumns})}";
                }

                $ajax = "ajax:{ url:'{$scriptURI}{$filters}'{$ajaxLimitGetURLs} }";
            }

            $js .= "cody_{$varTableID}_dt = $('#{$attr_id}').DataTable({ {$responsive} {$default_set} 'serverSide':true,{$ajax}{$columns}{$initComplete}{$js_conditional} }){$clickAction}{$filter_button}";
        } else {
            $js .= "cody_{$varTableID}_dt = $('#{$attr_id}').DataTable({ {$default_set}columns:{$columns} });";
        }
        
        // Add configuration storage for POST method
        if ('POST' === $this->datatablesMode && false !== $server_side) {
            $configData = json_encode([
                'columns' => $data_info['columns'] ?? [],
                'records' => $data_info['records'] ?? [],
                'modelProcessing' => $data_info['modelProcessing'] ?? [],
                'labels' => $data_info['labels'] ?? [],
                'relations' => $data_info['relations'] ?? [],
                'tableID' => $data_info['tableID'] ?? [],
                'model' => $data_info['model'] ?? []
            ]);
            
            $js .= "
            // Store datatables configuration for POST requests
            if (typeof window.canvastack_datatables_config === 'undefined') {
                window.canvastack_datatables_config = {};
            }
            window.canvastack_datatables_config['{$attr_id}'] = {$configData};
            ";
        }
        
        
        // Add Delete Confirmation Modal JavaScript - SIMPLIFIED VERSION
        $js .= "
        // Simple Delete Confirmation Modal Handler
        $(document).on('click', '.btn_delete_modal', function(e) {
            e.preventDefault();
            var \$btn = $(this);
            var modalTarget = \$btn.data('target');
            
            console.log('Delete button clicked, target modal:', modalTarget);
            
            // Show the modal that was already appended to body
            if ($(modalTarget).length > 0) {
                $(modalTarget).modal('show');
                console.log('Modal shown:', modalTarget);
            } else {
                console.error('Modal not found:', modalTarget);
                // Fallback: show browser confirm dialog
                var recordId = \$btn.data('record-id');
                var tableName = \$btn.data('table-name');
                if (confirm('Anda akan menghapus data dari tabel ' + tableName + ' dengan ID ' + recordId + '. Apakah Anda yakin?')) {
                    var formId = \$btn.data('form-id');
                    var form = document.getElementById(formId);
                    if (form) {
                        form.submit();
                    }
                }
            }
        });
        
        // Handle modal cleanup on hide
        $(document).on('hidden.bs.modal', '[id^=\"deleteModal_\"]', function() {
            console.log('Delete modal hidden:', $(this).attr('id'));
        });
        ";
        
        $js .= '});'.$documentLoad;
        
        // Add POST method filter integration for datatables
        if ('POST' === $this->datatablesMode && false !== $server_side) {
            $js .= "
            // POST Method Filter Integration
            $(document).ready(function() {
                // Define filter functions if not already defined
                if (typeof window.initializePostFilters === 'undefined') {
                    " . $this->getPostFilterJavaScript() . "
                }
                
                // Hook into DataTables AJAX success to check for filter configuration
                $('#{$attr_id}').on('xhr.dt', function(e, settings, json, xhr) {
                    if (json && json.filterConfig && json.filterConfig.hasFilters) {
                        // Initialize filter functionality
                        setTimeout(function() {
                            initializePostFilters('{$attr_id}', json.filterConfig);
                        }, 100);
                    }
                });
            });
            ";
        }
        
        $js .= '</script>';

        return $js;
    }

    /**
     * Get POST method filter JavaScript code
     * 
     * @return string
     */
    private function getPostFilterJavaScript()
    {
        return "
        // Global storage for filter configurations
        window.postDatatableFilters = window.postDatatableFilters || {};

        /**
         * Initialize filter functionality for a datatable
         */
        window.initializePostFilters = function(tableId, filterConfig) {
            console.log('Initializing POST filters for table:', tableId, filterConfig);
            
            // Store filter config globally
            window.postDatatableFilters[tableId] = filterConfig;
            
            // Add filter button to toolbar
            addFilterButton(tableId, filterConfig);
            
            // Create filter modal
            createFilterModal(tableId, filterConfig);
            
            // Bind events
            bindFilterEvents(tableId, filterConfig);
        };

        /**
         * Add filter button to DataTables toolbar
         */
        function addFilterButton(tableId, filterConfig) {
            var wrapper = $('#' + tableId + '_wrapper');
            var buttonsContainer = wrapper.find('.dt-buttons');
            
            if (buttonsContainer.length === 0) {
                console.warn('DataTables buttons container not found for table:', tableId);
                return;
            }

            // Create filter button HTML
            var filterButtonHtml = '<button type=\"button\" class=\"btn btn-default btn-sm\" ' +
                    'id=\"' + tableId + '_filterButton\" ' +
                    'data-toggle=\"modal\" ' +
                    'data-target=\"#' + tableId + '_filterModal\" ' +
                    'title=\"Filter Data\">' +
                    '<i class=\"fa fa-filter\"></i> Filter' +
                    '</button>';

            // Add button to toolbar
            buttonsContainer.append(filterButtonHtml);
            
            console.log('Filter button added for table:', tableId);
        }

        /**
         * Create filter modal HTML
         */
        function createFilterModal(tableId, filterConfig) {
            var modalId = tableId + '_filterModal';
            var formId = tableId + '_filterForm';
            
            // Generate filter fields HTML
            var filterFieldsHtml = '';
            
            if (filterConfig.filterGroups && Array.isArray(filterConfig.filterGroups)) {
                filterConfig.filterGroups.forEach(function(group, index) {
                    var fieldName = group.column || 'field_' + index;
                    var fieldLabel = fieldName.charAt(0).toUpperCase() + fieldName.slice(1).replace('_', ' ');
                    
                    if (group.type === 'selectbox') {
                        filterFieldsHtml += '<div class=\"form-group row\">' +
                                '<label for=\"' + fieldName + '\" class=\"col-sm-3 control-label\">' + fieldLabel + '</label>' +
                                '<div class=\"input-group col-sm-9\">' +
                                    '<select id=\"' + fieldName + '\" ' +
                                            'class=\"form-control chosen-select-deselect\" ' +
                                            'name=\"' + fieldName + '\">' +
                                        '<option value=\"\" selected=\"selected\">Select ' + fieldLabel + '</option>' +
                                    '</select>' +
                                '</div>' +
                            '</div>';
                    }
                });
            }

            // Create modal HTML
            var modalHtml = '<div id=\"' + modalId + '\" class=\"modal fade\" role=\"dialog\" tabindex=\"-1\" ' +
                     'aria-hidden=\"true\" data-backdrop=\"static\" data-keyboard=\"true\">' +
                    '<div class=\"modal-dialog modal-lg\" role=\"document\">' +
                        '<form action=\"' + filterConfig.baseUrl + '?renderDataTables=true&filters=true\" ' +
                              'method=\"GET\" id=\"' + formId + '\" role=\"form\">' +
                            '<div class=\"modal-content\">' +
                                '<div class=\"modal-header\">' +
                                    '<h5 class=\"modal-title\">' +
                                        '<i class=\"fa fa-filter\"></i> &nbsp; Filter Data ' + filterConfig.tableName +
                                    '</h5>' +
                                    '<button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"Close\">' +
                                        '<span aria-hidden=\"true\">×</span>' +
                                    '</button>' +
                                '</div>' +
                                '<input type=\"hidden\" name=\"_token\" value=\"' + filterConfig.token + '\">' +
                                '<div class=\"modal-body\">' +
                                    '<div id=\"' + modalId + '_modalBOX\">' +
                                        filterFieldsHtml +
                                    '</div>' +
                                '</div>' +
                                '<div class=\"modal-footer\">' +
                                    '<button type=\"button\" class=\"btn btn-secondary\" data-dismiss=\"modal\">Cancel</button>' +
                                    '<button type=\"submit\" class=\"btn btn-primary\">Apply Filter</button>' +
                                    '<button type=\"button\" class=\"btn btn-warning\" id=\"' + tableId + '_clearFilter\">Clear Filter</button>' +
                                '</div>' +
                            '</div>' +
                        '</form>' +
                    '</div>' +
                '</div>';

            // Add modal to page
            $('body').append(modalHtml);
            
            console.log('Filter modal created for table:', tableId);
            
            // Load filter options
            loadFilterOptions(tableId, filterConfig);
        }

        /**
         * Load filter options for selectbox fields
         */
        function loadFilterOptions(tableId, filterConfig) {
            if (!filterConfig.filterGroups || !Array.isArray(filterConfig.filterGroups)) {
                return;
            }

            filterConfig.filterGroups.forEach(function(group, index) {
                if (group.type === 'selectbox' && group.relate) {
                    var fieldName = group.column;
                    var selectElement = $('#' + fieldName);
                    
                    if (selectElement.length === 0) {
                        return;
                    }

                    // Make AJAX request to get filter options
                    var optionsUrl = filterConfig.baseUrl + '?renderDataTables=true&filters=true&getFilterOptions=' + fieldName;
                    
                    $.ajax({
                        url: optionsUrl,
                        method: 'GET',
                        dataType: 'json',
                        success: function(response) {
                            if (response && response.options && Array.isArray(response.options)) {
                                // Clear existing options except the first one
                                selectElement.find('option:not(:first)').remove();
                                
                                // Add new options
                                response.options.forEach(function(option) {
                                    var optionValue = option.value || option;
                                    var optionText = option.text || option.label || option;
                                    selectElement.append('<option value=\"' + optionValue + '\">' + optionText + '</option>');
                                });
                                
                                // Refresh chosen if it's initialized
                                if (selectElement.hasClass('chosen-select-deselect')) {
                                    selectElement.trigger('chosen:updated');
                                }
                            }
                        },
                        error: function(xhr, status, error) {
                            console.warn('Failed to load filter options for', fieldName, ':', error);
                        }
                    });
                }
            });
        }

        /**
         * Bind filter events
         */
        function bindFilterEvents(tableId, filterConfig) {
            var modalId = tableId + '_filterModal';
            var formId = tableId + '_filterForm';
            
            // Handle form submission
            $('#' + formId).on('submit', function(e) {
                e.preventDefault();
                
                var formData = $(this).serializeArray();
                var filters = {};
                
                formData.forEach(function(field) {
                    if (field.value && field.name !== '_token') {
                        filters[field.name] = field.value;
                    }
                });
                
                // Apply filters to datatable
                applyFiltersToDataTable(tableId, filters);
                
                // Close modal
                $('#' + modalId).modal('hide');
            });
            
            // Handle clear filter
            $('#' + tableId + '_clearFilter').on('click', function() {
                // Clear form
                $('#' + formId)[0].reset();
                
                // Clear datatable filters
                applyFiltersToDataTable(tableId, {});
                
                // Close modal
                $('#' + modalId).modal('hide');
            });
        }

        /**
         * Apply filters to DataTable
         */
        function applyFiltersToDataTable(tableId, filters) {
            var table = $('#' + tableId).DataTable();
            
            if (!table) {
                console.warn('DataTable not found for ID:', tableId);
                return;
            }

            // Store filters for next AJAX request
            var settings = table.settings()[0];
            if (settings && settings.ajax && typeof settings.ajax === 'object') {
                // Modify ajax data function to include filters
                var originalData = settings.ajax.data;
                
                settings.ajax.data = function(data) {
                    // Call original data function if it exists
                    if (typeof originalData === 'function') {
                        originalData.call(this, data);
                    }
                    
                    // Add filters to request
                    if (filters && Object.keys(filters).length > 0) {
                        data.filters = filters;
                    }
                    
                    return data;
                };
            }
            
            // Reload table
            table.ajax.reload();
            
            console.log('Filters applied to table:', tableId, filters);
        }
        ";
    }

    private function getJsContainMatch($data, $match_contained = null)
    {
        if ('!=' === $match_contained || '!==' === $match_contained) {
            $match = false;
        }
        if ('==' === $match_contained || '===' === $match_contained) {
            $match = true;
        }

        if (true == $match) {
            return ":contains(\"{$data}\")";
        }
        if (false == $match) {
            return ":not(:contains(\"{$data}\"))";
        }
    }

    private function conditionalColumns($tableIdentity, $data, $columns)
    {
        $icols = [];
        foreach ($columns as $i => $v) {
            $icols[$v] = $i;
        }

        foreach ($data as $idx => $_data) {
            $data[$idx]['node']['field_name'] = $icols[$_data['field_name']];
            if (! empty($icols[$_data['field_target']])) {
                $data[$idx]['node']['field_target'] = $icols[$_data['field_target']];
            } else {
                $data[$idx]['node']['field_target'] = null;
            }
        }

        $js = null;
        if (! empty($data)) {

            $js .= ", 'createdRow': function(row, data, dataIndex, cells) {";

            $jsConds = [];
            $jsCond = '';
            foreach ($data as $condition) {
                if (! empty($condition['logic_operator'])) {
                    $conditionValue = $condition['value'];
                    if (canvastack_string_contained($condition['value'], '|')) {
                        $conditionValue = explode('|', $condition['value']);
                    }
                    if (in_array($condition['logic_operator'], ['=', '==', '===', '<', '<=', '>', '>='])) {
                        $js .= "if (data.{$condition['field_name']} {$condition['logic_operator']} '{$condition['value']}') {";
                    } else {
                        $isNot = '';
                        if (in_array($condition['logic_operator'], ['NOT LIKE'])) {
                            $isNot = '!';
                        }

                        if (is_array($conditionValue)) {
                            foreach ($conditionValue as $condVal) {
                                $jsConds[] = "{$isNot}~data.{$condition['field_name']}.indexOf('{$condVal}')";
                            }
                            $jsCond = implode(' && ', $jsConds);
                        } else {
                            $jsCond = "{$isNot}~data.{$condition['field_name']}.indexOf('{$condition['value']}')";
                        }
                        $js .= "if ({$jsCond}) {";
                    }

                    if ('row' === $condition['field_target']) {
                        $js .= "$(row).children('td').css({'{$condition['rule']}': '{$condition['action']}'});";
                    }

                    if ('cell' === $condition['field_target']) {
                        if ('prefix' !== $condition['rule'] && 'suffix' !== $condition['rule'] && 'prefix&suffix' !== $condition['rule']) {
                            $js .= "$(cells[\"{$condition['node']['field_name']}\"]).css({'{$condition['rule']}': '{$condition['action']}'});";
                        }
                        if ('prefix&suffix' === $condition['rule']) {
                            $js .= "$(cells[\"{$condition['node']['field_name']}\"]).text(\"{$condition['action'][0]}\" + data.{$condition['field_name']} + \"{$condition['action'][1]}\");";
                        }
                        if ('prefix' === $condition['rule']) {
                            $js .= "$(cells[\"{$condition['node']['field_name']}\"]).text(\"{$condition['action']}\" + data.{$condition['field_name']});";
                        }
                        if ('suffix' === $condition['rule']) {
                            $js .= "$(cells[\"{$condition['node']['field_name']}\"]).text(data.{$condition['field_name']} + \"{$condition['action']}\");";
                        }
                        if ('replace' === $condition['rule']) {
                            if ('integer' === $condition['action']) {
                                $js .= "$(cells[\"{$condition['node']['field_name']}\"]).text(parseInt($(cells[\"{$condition['node']['field_name']}\"]).text()));";
                            } elseif ('float' === $condition['action'] || canvastack_string_contained($condition['action'], 'float')) {
                                if (canvastack_string_contained($condition['action'], '|')) {
                                    $condAcFloat = explode('|', $condition['action']);
                                    $js .= "$(cells[\"{$condition['node']['field_name']}\"]).text(parseFloat($(cells[\"{$condition['node']['field_name']}\"]).text()).toFixed({$condAcFloat[1]}));";
                                } else {
                                    $js .= "$(cells[\"{$condition['node']['field_name']}\"]).text(parseFloat($(cells[\"{$condition['node']['field_name']}\"]).text()).toFixed(2));";
                                }
                            } else {
                                $js .= "$(cells[\"{$condition['node']['field_name']}\"]).text('{$condition['action']}');";
                            }
                        }
                    }

                    if ($condition['field_target'] !== 'row' && $condition['field_target'] !== 'cell') {
                        // NEW CODE PLAN HERE
                        if (! empty($condition['node']['field_target'])) {
                            if ('prefix' !== $condition['rule'] && 'suffix' !== $condition['rule'] && 'prefix&suffix' !== $condition['rule']) {
                                $js .= "$(cells[\"{$condition['node']['field_target']}\"]).css({'{$condition['rule']}': '{$condition['action']}'});";
                            }
                        }

                        if ('replace' === $condition['rule']) {
                            if ('integer' === $condition['action']) {
                                $js .= "$(cells[\"{$condition['node']['field_target']}\"]).text(parseInt($(cells[\"{$condition['node']['field_target']}\"]).text()));";
                            } elseif ('float' === $condition['action'] || canvastack_string_contained($condition['action'], 'float')) {
                                if (canvastack_string_contained($condition['action'], '|')) {
                                    $condAcFloat = explode('|', $condition['action']);
                                    $js .= "$(cells[\"{$condition['node']['field_target']}\"]).text(parseFloat($(cells[\"{$condition['node']['field_target']}\"]).text()).toFixed({$condAcFloat[1]}));";
                                } else {
                                    $js .= "$(cells[\"{$condition['node']['field_target']}\"]).text(parseFloat($(cells[\"{$condition['node']['field_target']}\"]).text()).toFixed(2));";
                                }
                            } else {
                                if (canvastack_string_contained($condition['action'], 'url::') || canvastack_string_contained($condition['action'], 'ajax::')) {
                                    $node_table = explode('_', $tableIdentity)[1];
                                    $node_buttons = explode('::', $condition['action']);
                                    $action_buttons = explode('|', $node_buttons[1]);

                                    $button = [];
                                    $button['name'] = $action_buttons[0];
                                    $button['class'] = "btn {$button['name']} btn-{$action_buttons[1]} btn-xs";
                                    $button['icon'] = "fa fa-{$action_buttons[2]}";
                                    $button['token'] = csrf_token();
                                    $js .= "$(cells[\"{$condition['node']['field_target']}\"]).each(function() {";
                                    $js .= "var anchorNode{$node_table} = $(this).children().find('.action-buttons').find('.{$button['name']}');";

                                    if ('ajax' === $node_buttons[0]) {
                                        $js .= "var dataURLi{$node_table} = anchorNode{$node_table}.attr('href').split('/');";
                                        $js .= "var anchorValue{$node_table} = dataURLi{$node_table}[dataURLi{$node_table}.length-2];";
                                        $js .= "var dataValue{$node_table} = {'_token':'{$button['token']}',data:anchorValue{$node_table}};";
                                        $js .= "var anchorUrl{$node_table} = anchorNode{$node_table}.attr('href').replace(anchorValue{$node_table} + '/' + dataURLi{$node_table}[dataURLi{$node_table}.length-1], dataURLi{$node_table}[dataURLi{$node_table}.length-1]);";

                                        $js .= "anchorNode{$node_table}.removeAttr('href');";
                                        $js .= "anchorNode{$node_table}.click(function() {";
                                        $js .= '$.ajax({';
                                        $js .= "url: anchorUrl{$node_table},";
                                        $js .= "type: 'post',";
                                        $js .= "data: dataValue{$node_table},";
                                        $js .= 'success: function (response) {';
                                        $js .= "{$tableIdentity}.draw();";
                                        $js .= '},';
                                        $js .= 'error: function(jqXHR, textStatus, errorThrown) {';
                                        $js .= 'console.log(textStatus, errorThrown);';
                                        $js .= '}';
                                        $js .= '});';
                                        $js .= '});';
                                    }

                                    $js .= "anchorNode{$node_table}.removeClass().addClass('{$button['class']}').find('i.fa').removeClass().addClass('{$button['icon']}');";
                                    $js .= '});';

                                } else {
                                    $js .= "$(cells[\"{$condition['node']['field_target']}\"]).text('{$condition['action']}');";
                                }
                            }
                        }
                        //	$js .= "console.log(data);";
                    }

                    $js .= '}';
                }

                if ('column' === $condition['field_target']) {
                    if ('prefix' !== $condition['rule'] && 'suffix' !== $condition['rule'] && 'prefix&suffix' !== $condition['rule']) {
                        $js .= "$(cells[\"{$condition['node']['field_name']}\"]).css({'{$condition['rule']}': '{$condition['action']}'});";
                    }
                    if ('prefix&suffix' === $condition['rule']) {
                        $js .= "$(cells[\"{$condition['node']['field_name']}\"]).text(\"{$condition['action'][0]}\" + data.{$condition['field_name']} + \"{$condition['action'][1]}\");";
                    }
                    if ('prefix' === $condition['rule']) {
                        $js .= "$(cells[\"{$condition['node']['field_name']}\"]).text(\"{$condition['action']}\" + data.{$condition['field_name']});";
                    }
                    if ('suffix' === $condition['rule']) {
                        $js .= "$(cells[\"{$condition['node']['field_name']}\"]).text(data.{$condition['field_name']} + \"{$condition['action']}\");";
                    }
                    if ('replace' === $condition['rule']) {
                        if ('integer' === $condition['action']) {
                            $js .= "$(cells[\"{$condition['node']['field_name']}\"]).text(parseInt($(cells[\"{$condition['node']['field_name']}\"]).text()));";
                        } elseif ('float' === $condition['action'] || canvastack_string_contained($condition['action'], 'float')) {
                            if (canvastack_string_contained($condition['action'], '|')) {
                                $condAcFloat = explode('|', $condition['action']);
                                $js .= "$(cells[\"{$condition['node']['field_name']}\"]).text(parseFloat($(cells[\"{$condition['node']['field_name']}\"]).text()).toFixed({$condAcFloat[1]}));";
                            } else {
                                $js .= "$(cells[\"{$condition['node']['field_name']}\"]).text(parseFloat($(cells[\"{$condition['node']['field_name']}\"]).text()).toFixed(2));";
                            }
                        } else {
                            $js .= "$(cells[\"{$condition['node']['field_name']}\"]).text('{$condition['action']}');";
                        }
                    }
                }
            }

            $js .= '}';
        }

        return $js;
    }

    protected function filterButton(array $data)
    {
        if (! empty($data['searchable'])) {
            if (! empty($data['searchable']['all::columns'])) {
                if (false === $data['searchable']['all::columns']) {
                    return false;
                }
            }

            if (false !== $data['searchable'] && ! empty($data['class'])) {
                $btn_class = $data['class'];
                if (empty($data['class'])) {
                    $btn_class = 'btn btn-primary btn-flat btn-lg mt-3';
                }

                return '<button type="button" class="'.$btn_class.' '.$data['id'].'" data-toggle="modal" data-target=".'.$data['id'].'">'.$data['button_label'].'</button>';
            }
        }

        return false;
    }

    protected function filterModalbox(array $data)
    {
        $current_route = canvastack_current_route();
        $current_url = $current_route ? url($current_route->uri) : url()->current();
        if (! empty($data['searchable'])) {
            if (! empty($data['searchable']['all::columns'])) {
                if (false === $data['searchable']['all::columns']) {
                    return false;
                }
            }

            if (! empty($data['modal_content']['html'])) {
                $attributes = '';
                if (! empty($data['attributes'])) {
                    foreach ($data['attributes'] as $key => $attr) {
                        $attributes .= " {$key}=\"{$attr}\"";
                    }
                }

                $title = null;
                if (! empty($data['modal_title'])) {
                    $title = $data['modal_title'];
                }
                $name = null;
                if (! empty($data['modal_content']['name'])) {
                    $name = $data['modal_content']['name'];
                }
                $content = null;
                if (! empty($data['modal_content']['html'])) {
                    $content = $data['modal_content']['html'];
                }

                $html = '<div '.$attributes.'>';
                $html .= '<div id="'.$data['id'].'_cdyFILTERFormBox" class="modal-dialog modal-lg" role="document">';
                $html .= '<form action="'.$current_url.'?renderDataTables=true&filters=true" method="GET" id="'.$data['id'].'_cdyFILTERForm" role="form">';
                $html .= '<div class="modal-content">';
                $html .= '<div id="'.$data['id'].'_cdyProcessing" class="dataTables_processing" style="display:none"></div>';
                $html .= '<div class="modal-header">';
                $html .= '<h5 class="modal-title" id="">'.$title.' Data '.$name.'</h5>';
                $html .= '<button type="button" class="close" data-dismiss="modal" aria-label="Close">';
                $html .= '<span aria-hidden="true">&times;</span>';
                $html .= '</button>';
                $html .= '</div>';
                $html .= '<input type="hidden" name="_token" value="'.csrf_token().'" />';
                $html .= $content;
                $html .= '</div>';
                $html .= '</form>';
                $html .= '</div>';
                $html .= '</div>';

                return $html;
            }
        }

    }

    private function export($id, $url, $type = 'csv', $delimeter = '|')
    {
        $connection = null;
        if (canvastack_string_contained($id, '::')) {
            $stringID = explode('::', $id);
            $id = $stringID[0];
            $connection = canvastack_encrypt($stringID[1]);
        }

        $varTableID = explode('-', $id);
        $varTableID = implode('', $varTableID);
        $modalID = "{$id}_cdyFILTERmodalBOX";
        $filterID = "{$id}_cdyFILTER";
        $exportID = 'export_'.str_replace('-', '_', $id).'_cdyFILTERField';
        $token = csrf_token();

        $filters = [];
        if (! empty($this->conditions['where'])) {
            $filters = $this->conditions['where'];
        }
        $filter = json_encode($filters);

        return "exportFromModal('{$modalID}', '{$exportID}', '{$filterID}', '{$token}', '{$url}', '{$connection}', {$filter});";
    }

    private function filter($id, $url)
    {
        $varTableID = explode('-', $id);
        $varTableID = implode('', $varTableID);

        return "diyDataTableFilters('{$id}', '{$url}', cody_{$varTableID}_dt);";
    }

    private function initComplete($id, $location = 'footer')
    {
        if (false === $location) {
            $js = "initComplete: function() {document.getElementById('{$id}').deleteTFoot();}";
        } else {
            if (true === $location) {
                $location = 'footer';
            }

            $js = 'initComplete: function() {';
            $js .= 'this.api().columns().every(function(n) {';
            $js .= 'if (n > 1) {';
            $js .= 'var column = this;';
            $js .= 'var input  = document.createElement("input");';
            $js .= '$(input).attr({';
            $js .= "'class':'form-control',";
            $js .= "'placeholder': 'search'";
            $js .= "}).appendTo($(column.{$location}()).empty()).on('change', function () {";
            $js .= 'column.search($(this).val(), false, false, true).draw();';
            $js .= '});';
            $js .= '}';
            $js .= '});';
            $js .= '}';
        }

        return $js;
    }

    /**
     * Set Buttons
        $buttonset = '[
            {
                extend:"collection",
                exportOptions:{columns:":visible:not(:last-child)"},
                text:"<i class=\"fa fa-external-link\" aria-hidden=\"true\"></i> <u>E</u>xport",
                buttons:[{text:"Excel",buttons:"excel"}, "csv", "pdf"],
                key:{key:"e",altKey:true}
            },
            "copy",
            "print"
        ]';
     */
    private function setButtons($id, $button_sets = [])
    {
        $buttons = [];
        foreach ($button_sets as $button) {

            $button = trim($button);
            $option = null;
            $options[$button] = [];

            if (canvastack_string_contained($button, '|')) {
                $splits = explode('|', $button);
                foreach ($splits as $split) {
                    if (canvastack_string_contained($split, ':')) {
                        $options[$button][] = $split;
                    } else {
                        $button = $split;
                    }
                }
            }

            if (! empty($options[$button])) {
                $option = implode(',', $options[$button]);
            }
            $buttons[] = '{extend:"'.$button.'", '.$option.'}';
        }

        return '['.implode(',', $buttons).']';
    }
}
