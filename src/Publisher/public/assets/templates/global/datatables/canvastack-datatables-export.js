/**
 * CanvaStack DataTables Export
 * 
 * Universal export functionality for DataTables.
 * Handles export to Excel, CSV, PDF with filter support.
 * 
 * @author wisnuwidi@canvastack.com
 * @copyright Canvastack
 * @version 1.0.0
 */

(function(window, $) {
    'use strict';
    
    /**
     * Export data from modal with filters
     * 
     * @param {string} modalID - Modal element ID
     * @param {string} exportID - Export identifier
     * @param {string} filterID - Filter identifier
     * @param {string} token - CSRF token
     * @param {string} url - Export endpoint URL
     * @param {string} link - Additional link parameter
     * @param {Array} filter - Filter parameters
     */
    window.exportFromModal = function(modalID, exportID, filterID, token, url, link, filter) {
        filter = filter || [];
        
        $('#exportFilterButton' + modalID).on('click', function(event) {
            $(this).css({
                'position': 'relative',
                'width': '138px',
                'text-align': 'left'
            }).append('<span id="loader_' + modalID + '" class="inputloader loader" style="right:8px;width:20px;height:20px;top:7px;background-size:20px"></span>');
            
            var inputFilters = $('#' + modalID + ' > .form-group.row > .input-group.col-sm-9 > select.' + exportID);
            var inputData = [];
            inputData['exportData'] = true;
            inputData['_token'] = token;
            
            inputFilters.each(function(x, y) {
                inputData[y.name] = y.value;
            });
            
            // Only add lurExp if link is not null and not empty string
            if (link != null && link !== '') {
                inputData['lurExp'] = link;
            }
            
            if (filter != null) {
                inputData['ftrExp'] = filter;
            }
            
            // Debug logging
            if (window.APP_DEBUG) {
                console.log('Export request:', {
                    url: url,
                    link: link,
                    hasLurExp: inputData.hasOwnProperty('lurExp'),
                    inputData: inputData
                });
            }
            
            $.ajax({
                type: 'POST',
                data: canvastack_array_to_object(inputData),
                dataType: 'JSON',
                url: url,
                success: function(n) {
                    if (window.APP_DEBUG) {
                        console.log('Export response:', n);
                    }
                    
                    // Check if response is valid and has export path
                    if (n && n.canvastackExportStreamPath) {
                        window.location.href = n.canvastackExportStreamPath;
                    } else if (n && n.error) {
                        // Handle error response
                        alert('Export failed: ' + (n.message || 'Unknown error'));
                        if (window.APP_DEBUG) {
                            console.error('Export error:', n);
                        }
                    } else {
                        // Handle unexpected response
                        alert('Export failed: Invalid response from server');
                        if (window.APP_DEBUG) {
                            console.error('Invalid export response:', n);
                        }
                    }
                },
                error: function(xhr, status, error) {
                    alert('Export failed: ' + error);
                    if (window.APP_DEBUG) {
                        console.error('Export AJAX error:', xhr, status, error);
                    }
                },
                complete: function() {
                    $('#exportFilterButton' + modalID).removeAttr('style');
                    $('#loader_' + modalID).remove();
                    CanvaStackModal.hide(filterID);
                }
            });
        });
    };
    
    /**
     * Export DataTable to Excel
     * 
     * @param {string} tableId - Table ID
     * @param {string} filename - Export filename
     */
    window.exportDataTableToExcel = function(tableId, filename) {
        filename = filename || 'export';
        
        var table = $('#' + tableId).DataTable();
        if (!table) {
            console.error('DataTable not found:', tableId);
            return;
        }
        
        // Trigger Excel export button if available
        var excelButton = table.button('.buttons-excel');
        if (excelButton.length > 0) {
            excelButton.trigger();
        } else {
            console.warn('Excel export button not found for table:', tableId);
        }
    };
    
    /**
     * Export DataTable to CSV
     * 
     * @param {string} tableId - Table ID
     * @param {string} filename - Export filename
     */
    window.exportDataTableToCSV = function(tableId, filename) {
        filename = filename || 'export';
        
        var table = $('#' + tableId).DataTable();
        if (!table) {
            console.error('DataTable not found:', tableId);
            return;
        }
        
        // Trigger CSV export button if available
        var csvButton = table.button('.buttons-csv');
        if (csvButton.length > 0) {
            csvButton.trigger();
        } else {
            console.warn('CSV export button not found for table:', tableId);
        }
    };
    
    /**
     * Export DataTable to PDF
     * 
     * @param {string} tableId - Table ID
     * @param {string} filename - Export filename
     */
    window.exportDataTableToPDF = function(tableId, filename) {
        filename = filename || 'export';
        
        var table = $('#' + tableId).DataTable();
        if (!table) {
            console.error('DataTable not found:', tableId);
            return;
        }
        
        // Trigger PDF export button if available
        var pdfButton = table.button('.buttons-pdf');
        if (pdfButton.length > 0) {
            pdfButton.trigger();
        } else {
            console.warn('PDF export button not found for table:', tableId);
        }
    };
    
    /**
     * Print DataTable
     * 
     * @param {string} tableId - Table ID
     */
    window.printDataTable = function(tableId) {
        var table = $('#' + tableId).DataTable();
        if (!table) {
            console.error('DataTable not found:', tableId);
            return;
        }
        
        // Trigger print button if available
        var printButton = table.button('.buttons-print');
        if (printButton.length > 0) {
            printButton.trigger();
        } else {
            console.warn('Print button not found for table:', tableId);
        }
    };
    
    console.log('CanvaStack DataTables Export loaded');
    
})(window, jQuery);
