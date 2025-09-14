# ⚡ **JAVASCRIPT INTEGRATION & EVENT HANDLING**

## 📋 **TABLE OF CONTENTS**
1. [Feature Overview](#feature-overview)
2. [Core JavaScript Architecture](#core-javascript-architecture)
3. [Event Handling System](#event-handling-system)
4. [DataTables Integration](#datatables-integration)
5. [Modal Management](#modal-management)
6. [AJAX Operations](#ajax-operations)
7. [Performance Optimization](#performance-optimization)
8. [Debugging & Troubleshooting](#debugging--troubleshooting)

---

## 🎯 **FEATURE OVERVIEW**

The JavaScript Integration System provides comprehensive client-side functionality for the Canvastack Table System. It handles event delegation, modal management, AJAX operations, and DataTables integration with a focus on performance and reliability.

### **Key Features:**
✅ **Event Delegation** - Efficient handling of dynamic content  
✅ **Modal Management** - Centralized modal lifecycle management  
✅ **DataTables Integration** - Advanced table functionality  
✅ **AJAX Operations** - Seamless server communication  
✅ **Error Handling** - Robust error management and fallbacks  
✅ **Performance Optimization** - Debouncing, caching, lazy loading  
✅ **Debug Support** - Comprehensive logging and debugging tools  
✅ **Cross-browser Compatibility** - Works across modern browsers  

---

## 🏗️ **CORE JAVASCRIPT ARCHITECTURE**

### **File Structure:**
```
Library/Components/Table/Craft/Scripts.php
├── Configuration Management
├── Event Delegation System
├── Modal Handlers
├── DataTables Integration
├── AJAX Utilities
└── Debug & Logging
```

### **Main Script Generation:**
**File**: `Library/Components/Table/Craft/Scripts.php`

```php
public function generateTableScript(string $attr_id, array $config): string
{
    $js = '';
    $documentLoad = '';
    
    // jQuery Document Ready Wrapper
    $js .= '$(document).ready(function() {';
    
    // Configuration Storage
    if (!empty($config)) {
        $configData = json_encode($config, JSON_HEX_APOS | JSON_HEX_QUOT);
        $js .= "
        // Store table configuration globally
        if (typeof window.canvastack_datatables_config === 'undefined') {
            window.canvastack_datatables_config = {};
        }
        window.canvastack_datatables_config['{$attr_id}'] = {$configData};
        ";
    }
    
    // Event Handlers
    $js .= $this->generateEventHandlers($attr_id);
    
    // Modal Handlers
    $js .= $this->generateModalHandlers($attr_id);
    
    // DataTables Integration
    $js .= $this->generateDataTablesIntegration($attr_id);
    
    // Close Document Ready
    $js .= '});' . $documentLoad;
    
    return '<script type="text/javascript">' . $js . '</script>';
}
```

---

## 🎮 **EVENT HANDLING SYSTEM**

### **1. Event Delegation Pattern:**
```javascript
// Centralized event delegation for dynamic content
$(document).on('click', '.btn_filter_modal', function(e) {
    e.preventDefault();
    var $btn = $(this);
    var modalTarget = $btn.data('target');
    
    console.log('Filter button clicked, target modal:', modalTarget);
    $(modalTarget).modal('show');
});

$(document).on('click', '.btn_delete_modal', function(e) {
    e.preventDefault();
    var $btn = $(this);
    var modalTarget = $btn.data('target');
    
    console.log('Delete button clicked, target modal:', modalTarget);
    
    // Show the modal that was already appended to body
    if ($(modalTarget).length > 0) {
        $(modalTarget).modal('show');
        console.log('Modal shown:', modalTarget);
    } else {
        console.error('Modal not found:', modalTarget);
        // Fallback: show browser confirm dialog
        var recordId = $btn.data('record-id');
        var tableName = $btn.data('table-name');
        if (confirm('Anda akan menghapus data dari tabel ' + tableName + ' dengan ID ' + recordId + '. Apakah Anda yakin?')) {
            var formId = $btn.data('form-id');
            var form = document.getElementById(formId);
            if (form) {
                form.submit();
            }
        }
    }
});
```

### **2. Custom Event System:**
```javascript
// Custom event dispatcher
function triggerTableEvent(eventName, data) {
    $(document).trigger('canvastack:table:' + eventName, data);
}

// Event listeners
$(document).on('canvastack:table:filter:applied', function(e, data) {
    console.log('Filter applied:', data);
    updateTableStats(data);
});

$(document).on('canvastack:table:record:deleted', function(e, data) {
    console.log('Record deleted:', data);
    refreshTableData();
});

$(document).on('canvastack:table:modal:opened', function(e, data) {
    console.log('Modal opened:', data);
    trackModalUsage(data);
});
```

### **3. Event Handler Registration:**
```javascript
// Dynamic event handler registration
function registerTableEvents(tableId) {
    var eventHandlers = {
        'click .btn-export': handleExportClick,
        'click .btn-refresh': handleRefreshClick,
        'change .bulk-select': handleBulkSelectChange,
        'submit .filter-form': handleFilterSubmit,
    };
    
    Object.keys(eventHandlers).forEach(function(event) {
        var parts = event.split(' ');
        var eventType = parts[0];
        var selector = parts.slice(1).join(' ');
        
        $('#' + tableId).on(eventType, selector, eventHandlers[event]);
    });
}
```

---

## 📊 **DATATABLES INTEGRATION**

### **1. DataTables Configuration:**
```javascript
// Advanced DataTables setup
function initializeDataTable(tableId, config) {
    var defaultConfig = {
        processing: true,
        serverSide: true,
        responsive: true,
        stateSave: true,
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        dom: '<"row"<"col-sm-6"l><"col-sm-6"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-5"i><"col-sm-7"p>>',
        language: {
            processing: '<i class="fa fa-spinner fa-spin"></i> Loading...',
            emptyTable: 'No data available',
            zeroRecords: 'No matching records found',
            info: 'Showing _START_ to _END_ of _TOTAL_ entries',
            infoEmpty: 'Showing 0 to 0 of 0 entries',
            infoFiltered: '(filtered from _MAX_ total entries)',
            search: 'Search:',
            paginate: {
                first: 'First',
                last: 'Last',
                next: 'Next',
                previous: 'Previous'
            }
        },
        ajax: {
            url: config.ajax_url || '/api/table/data',
            type: 'POST',
            data: function(d) {
                // Add custom parameters
                d._token = $('meta[name="csrf-token"]').attr('content');
                d.table_id = tableId;
                
                // Add filter parameters
                if (window.canvastack_table_filters && window.canvastack_table_filters[tableId]) {
                    d.filters = window.canvastack_table_filters[tableId];
                }
                
                return d;
            },
            error: function(xhr, error, thrown) {
                console.error('DataTables AJAX error:', error, thrown);
                showErrorMessage('Failed to load table data. Please try again.');
            }
        },
        columns: config.columns || [],
        order: config.order || [[0, 'asc']],
        columnDefs: config.columnDefs || [],
    };
    
    // Merge with custom configuration
    var finalConfig = $.extend(true, defaultConfig, config);
    
    // Initialize DataTable
    var table = $('#' + tableId).DataTable(finalConfig);
    
    // Store table instance globally
    if (typeof window.canvastack_datatables === 'undefined') {
        window.canvastack_datatables = {};
    }
    window.canvastack_datatables[tableId] = table;
    
    return table;
}
```

### **2. Server-Side Processing:**
```javascript
// Enhanced server-side processing
function setupServerSideProcessing(tableId, config) {
    return {
        ajax: {
            url: config.ajax_url,
            type: 'POST',
            data: function(d) {
                // Standard DataTables parameters
                var params = {
                    draw: d.draw,
                    start: d.start,
                    length: d.length,
                    search: d.search.value,
                    order_column: d.columns[d.order[0].column].data,
                    order_direction: d.order[0].dir,
                    _token: $('meta[name="csrf-token"]').attr('content')
                };
                
                // Add column-specific search
                d.columns.forEach(function(column, index) {
                    if (column.search.value) {
                        params['columns[' + index + '][search]'] = column.search.value;
                    }
                });
                
                // Add custom filters
                if (window.canvastack_table_filters && window.canvastack_table_filters[tableId]) {
                    params.filters = window.canvastack_table_filters[tableId];
                }
                
                return params;
            },
            dataSrc: function(json) {
                // Process server response
                if (json.error) {
                    showErrorMessage(json.error);
                    return [];
                }
                
                // Update table info
                updateTableInfo(tableId, json);
                
                return json.data;
            }
        }
    };
}
```

### **3. Column Definitions:**
```javascript
// Dynamic column configuration
function generateColumnDefs(columns) {
    var columnDefs = [];
    
    columns.forEach(function(column, index) {
        var def = {
            targets: index,
            data: column.data,
            name: column.name,
            searchable: column.searchable !== false,
            orderable: column.orderable !== false,
        };
        
        // Custom rendering
        if (column.render) {
            def.render = column.render;
        }
        
        // Column-specific classes
        if (column.className) {
            def.className = column.className;
        }
        
        // Width settings
        if (column.width) {
            def.width = column.width;
        }
        
        columnDefs.push(def);
    });
    
    return columnDefs;
}
```

---

## 🎭 **MODAL MANAGEMENT**

### **1. Modal Lifecycle Management:**
```javascript
// Centralized modal management
var ModalManager = {
    activeModals: {},
    
    create: function(modalId, modalHtml) {
        // Remove existing modal
        this.destroy(modalId);
        
        // Append to body
        $('body').append(modalHtml);
        
        // Store reference
        this.activeModals[modalId] = {
            element: $('#' + modalId),
            created: new Date(),
            interactions: 0
        };
        
        console.log('Modal created:', modalId);
    },
    
    show: function(modalId) {
        if (this.activeModals[modalId]) {
            this.activeModals[modalId].element.modal('show');
            this.activeModals[modalId].interactions++;
            
            // Trigger custom event
            $(document).trigger('canvastack:modal:shown', { modalId: modalId });
            
            console.log('Modal shown:', modalId);
        } else {
            console.error('Modal not found:', modalId);
        }
    },
    
    hide: function(modalId) {
        if (this.activeModals[modalId]) {
            this.activeModals[modalId].element.modal('hide');
            console.log('Modal hidden:', modalId);
        }
    },
    
    destroy: function(modalId) {
        if (this.activeModals[modalId]) {
            this.activeModals[modalId].element.remove();
            delete this.activeModals[modalId];
            console.log('Modal destroyed:', modalId);
        }
    },
    
    cleanup: function() {
        // Clean up old modals
        var now = new Date();
        Object.keys(this.activeModals).forEach(function(modalId) {
            var modal = this.activeModals[modalId];
            var age = now - modal.created;
            
            // Remove modals older than 1 hour with no interactions
            if (age > 3600000 && modal.interactions === 0) {
                this.destroy(modalId);
            }
        }.bind(this));
    }
};

// Auto cleanup every 10 minutes
setInterval(function() {
    ModalManager.cleanup();
}, 600000);
```

### **2. Modal Event Handlers:**
```javascript
// Modal event delegation
$(document).on('shown.bs.modal', '[id^="filterModal_"], [id^="deleteModal_"]', function() {
    var modalId = $(this).attr('id');
    console.log('Modal shown event:', modalId);
    
    // Focus first input
    $(this).find('input:visible:first').focus();
    
    // Track modal usage
    if (typeof gtag !== 'undefined') {
        gtag('event', 'modal_opened', {
            modal_id: modalId,
            modal_type: modalId.includes('filter') ? 'filter' : 'delete'
        });
    }
});

$(document).on('hidden.bs.modal', '[id^="filterModal_"], [id^="deleteModal_"]', function() {
    var modalId = $(this).attr('id');
    console.log('Modal hidden event:', modalId);
    
    // Clean up event handlers
    $(this).find('button, input, select').off('.modal');
    
    // Remove backdrop if stuck
    $('.modal-backdrop').remove();
    $('body').removeClass('modal-open');
});

$(document).on('hide.bs.modal', '[id^="filterModal_"], [id^="deleteModal_"]', function(e) {
    var modalId = $(this).attr('id');
    
    // Prevent accidental closure for delete modals
    if (modalId.includes('deleteModal_') && e.target === this) {
        var hasUnsavedChanges = $(this).find('form').data('changed');
        if (hasUnsavedChanges) {
            if (!confirm('You have unsaved changes. Are you sure you want to close?')) {
                e.preventDefault();
                return false;
            }
        }
    }
});
```

### **3. Modal Z-Index Management:**
```javascript
// Dynamic z-index management
function fixModalZIndex() {
    var zIndex = 1050;
    $('.modal:visible').each(function() {
        $(this).css('z-index', zIndex);
        zIndex += 10;
    });
    
    $('.modal-backdrop').each(function(index) {
        $(this).css('z-index', 1040 + (index * 10));
    });
}

// Apply z-index fix when modals are shown
$(document).on('shown.bs.modal', '.modal', function() {
    fixModalZIndex();
});
```

---

## 🌐 **AJAX OPERATIONS**

### **1. AJAX Utility Functions:**
```javascript
// Centralized AJAX handler
var AjaxManager = {
    defaults: {
        type: 'POST',
        dataType: 'json',
        timeout: 30000,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'X-Requested-With': 'XMLHttpRequest'
        }
    },
    
    request: function(options) {
        var settings = $.extend({}, this.defaults, options);
        
        // Show loading indicator
        if (settings.showLoading !== false) {
            this.showLoading();
        }
        
        return $.ajax(settings)
            .done(function(response) {
                console.log('AJAX success:', response);
                
                if (response.success === false) {
                    throw new Error(response.message || 'Request failed');
                }
                
                if (settings.success) {
                    settings.success(response);
                }
            })
            .fail(function(xhr, status, error) {
                console.error('AJAX error:', status, error);
                
                var message = 'Request failed';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                } else if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    message = Object.values(xhr.responseJSON.errors).flat().join(', ');
                }
                
                if (settings.error) {
                    settings.error(xhr, status, error, message);
                } else {
                    showErrorMessage(message);
                }
            })
            .always(function() {
                if (settings.showLoading !== false) {
                    AjaxManager.hideLoading();
                }
                
                if (settings.complete) {
                    settings.complete();
                }
            });
    },
    
    showLoading: function() {
        if ($('#ajax-loading').length === 0) {
            $('body').append('<div id="ajax-loading" class="ajax-loading">' +
                           '<div class="spinner">' +
                           '<i class="fa fa-spinner fa-spin"></i> Loading...' +
                           '</div></div>');
        }
        $('#ajax-loading').show();
    },
    
    hideLoading: function() {
        $('#ajax-loading').hide();
    }
};
```

### **2. Filter AJAX Operations:**
```javascript
// Filter form submission
function submitFilterForm(tableId, formData) {
    return AjaxManager.request({
        url: '/api/table/filter',
        data: {
            table_id: tableId,
            filters: formData,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            // Store filters globally
            if (typeof window.canvastack_table_filters === 'undefined') {
                window.canvastack_table_filters = {};
            }
            window.canvastack_table_filters[tableId] = formData;
            
            // Reload DataTable
            if (window.canvastack_datatables && window.canvastack_datatables[tableId]) {
                window.canvastack_datatables[tableId].ajax.reload();
            }
            
            // Show success message
            showSuccessMessage('Filters applied successfully');
            
            // Trigger custom event
            $(document).trigger('canvastack:table:filter:applied', {
                tableId: tableId,
                filters: formData,
                results: response
            });
        },
        error: function(xhr, status, error, message) {
            showErrorMessage('Failed to apply filters: ' + message);
        }
    });
}
```

### **3. Export Operations:**
```javascript
// Table export functionality
function exportTable(tableId, format, options) {
    options = options || {};
    
    return AjaxManager.request({
        url: '/api/table/export',
        data: {
            table_id: tableId,
            format: format,
            filters: window.canvastack_table_filters ? window.canvastack_table_filters[tableId] : {},
            columns: options.columns,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.download_url) {
                // Trigger download
                var link = document.createElement('a');
                link.href = response.download_url;
                link.download = response.filename || 'export.' + format;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                showSuccessMessage('Export completed successfully');
            }
        },
        error: function(xhr, status, error, message) {
            showErrorMessage('Export failed: ' + message);
        }
    });
}
```

---

## ⚡ **PERFORMANCE OPTIMIZATION**

### **1. Debouncing & Throttling:**
```javascript
// Utility functions for performance
function debounce(func, wait, immediate) {
    var timeout;
    return function() {
        var context = this, args = arguments;
        var later = function() {
            timeout = null;
            if (!immediate) func.apply(context, args);
        };
        var callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) func.apply(context, args);
    };
}

function throttle(func, limit) {
    var inThrottle;
    return function() {
        var args = arguments;
        var context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(function() { inThrottle = false; }, limit);
        }
    };
}

// Apply debouncing to search inputs
$(document).on('input', '.dataTables_filter input', debounce(function() {
    var tableId = $(this).closest('.dataTables_wrapper').find('table').attr('id');
    if (window.canvastack_datatables && window.canvastack_datatables[tableId]) {
        window.canvastack_datatables[tableId].search(this.value).draw();
    }
}, 300));
```

### **2. Lazy Loading:**
```javascript
// Lazy load table components
function lazyLoadTable(tableId) {
    var $table = $('#' + tableId);
    
    if ($table.length && !$table.hasClass('loaded')) {
        // Check if table is in viewport
        if (isInViewport($table[0])) {
            initializeDataTable(tableId, window.canvastack_datatables_config[tableId]);
            $table.addClass('loaded');
        }
    }
}

function isInViewport(element) {
    var rect = element.getBoundingClientRect();
    return (
        rect.top >= 0 &&
        rect.left >= 0 &&
        rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
        rect.right <= (window.innerWidth || document.documentElement.clientWidth)
    );
}

// Apply lazy loading on scroll
$(window).on('scroll', throttle(function() {
    $('.canvastack-table:not(.loaded)').each(function() {
        lazyLoadTable($(this).attr('id'));
    });
}, 100));
```

### **3. Memory Management:**
```javascript
// Memory cleanup utilities
function cleanupTable(tableId) {
    // Destroy DataTable instance
    if (window.canvastack_datatables && window.canvastack_datatables[tableId]) {
        window.canvastack_datatables[tableId].destroy();
        delete window.canvastack_datatables[tableId];
    }
    
    // Remove event handlers
    $('#' + tableId).off();
    
    // Clear configuration
    if (window.canvastack_datatables_config && window.canvastack_datatables_config[tableId]) {
        delete window.canvastack_datatables_config[tableId];
    }
    
    // Clear filters
    if (window.canvastack_table_filters && window.canvastack_table_filters[tableId]) {
        delete window.canvastack_table_filters[tableId];
    }
    
    console.log('Table cleanup completed:', tableId);
}

// Auto cleanup on page unload
$(window).on('beforeunload', function() {
    if (window.canvastack_datatables) {
        Object.keys(window.canvastack_datatables).forEach(function(tableId) {
            cleanupTable(tableId);
        });
    }
});
```

---

## 🐛 **DEBUGGING & TROUBLESHOOTING**

### **1. Debug Console:**
```javascript
// Debug utility for development
var CanvastackDebug = {
    enabled: false,
    
    enable: function() {
        this.enabled = true;
        console.log('Canvastack Debug Mode Enabled');
        this.showDebugInfo();
    },
    
    disable: function() {
        this.enabled = false;
        console.log('Canvastack Debug Mode Disabled');
    },
    
    log: function(message, data) {
        if (this.enabled) {
            console.log('[Canvastack Debug]', message, data || '');
        }
    },
    
    showDebugInfo: function() {
        console.group('Canvastack Debug Info');
        console.log('DataTables Config:', window.canvastack_datatables_config);
        console.log('DataTables Instances:', window.canvastack_datatables);
        console.log('Active Filters:', window.canvastack_table_filters);
        console.log('Active Modals:', ModalManager.activeModals);
        console.groupEnd();
    },
    
    testModal: function(modalId) {
        if (ModalManager.activeModals[modalId]) {
            console.log('Modal test - showing:', modalId);
            ModalManager.show(modalId);
        } else {
            console.error('Modal not found for testing:', modalId);
        }
    },
    
    testAjax: function(url, data) {
        console.log('Testing AJAX request to:', url);
        return AjaxManager.request({
            url: url,
            data: data || {},
            success: function(response) {
                console.log('AJAX test successful:', response);
            },
            error: function(xhr, status, error) {
                console.error('AJAX test failed:', status, error);
            }
        });
    }
};

// Enable debug mode in development
if (window.location.hostname === 'localhost' || window.location.hostname.includes('dev')) {
    CanvastackDebug.enable();
}
```

### **2. Error Reporting:**
```javascript
// Centralized error reporting
function reportError(error, context) {
    var errorInfo = {
        message: error.message || error,
        stack: error.stack,
        context: context,
        url: window.location.href,
        userAgent: navigator.userAgent,
        timestamp: new Date().toISOString()
    };
    
    console.error('Canvastack Error:', errorInfo);
    
    // Send to error reporting service (if configured)
    if (window.errorReportingUrl) {
        $.ajax({
            url: window.errorReportingUrl,
            method: 'POST',
            data: errorInfo,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        }).fail(function() {
            console.error('Failed to report error to server');
        });
    }
}

// Global error handler
window.addEventListener('error', function(e) {
    reportError(e.error, 'Global Error Handler');
});

// Unhandled promise rejection handler
window.addEventListener('unhandledrejection', function(e) {
    reportError(e.reason, 'Unhandled Promise Rejection');
});
```

### **3. Performance Monitoring:**
```javascript
// Performance monitoring utilities
var PerformanceMonitor = {
    timers: {},
    
    start: function(name) {
        this.timers[name] = performance.now();
    },
    
    end: function(name) {
        if (this.timers[name]) {
            var duration = performance.now() - this.timers[name];
            console.log('Performance [' + name + ']:', duration.toFixed(2) + 'ms');
            delete this.timers[name];
            return duration;
        }
    },
    
    measure: function(name, fn) {
        this.start(name);
        var result = fn();
        this.end(name);
        return result;
    },
    
    measureAsync: function(name, promise) {
        this.start(name);
        return promise.finally(function() {
            PerformanceMonitor.end(name);
        });
    }
};

// Monitor table initialization
function monitoredInitializeDataTable(tableId, config) {
    return PerformanceMonitor.measure('DataTable Init: ' + tableId, function() {
        return initializeDataTable(tableId, config);
    });
}
```

---

## 🔧 **CONFIGURATION & CUSTOMIZATION**

### **1. Global Configuration:**
```javascript
// Global Canvastack configuration
window.CanvastackConfig = {
    debug: false,
    ajax: {
        timeout: 30000,
        retries: 3,
        retryDelay: 1000
    },
    modals: {
        backdrop: 'static',
        keyboard: true,
        focus: true,
        autoCleanup: true
    },
    datatables: {
        pageLength: 25,
        responsive: true,
        stateSave: true,
        processing: true
    },
    performance: {
        debounceDelay: 300,
        throttleLimit: 100,
        lazyLoading: true
    }
};
```

### **2. Plugin System:**
```javascript
// Plugin registration system
var CanvastackPlugins = {
    plugins: {},
    
    register: function(name, plugin) {
        this.plugins[name] = plugin;
        console.log('Plugin registered:', name);
        
        // Initialize if auto-init is enabled
        if (plugin.autoInit) {
            this.init(name);
        }
    },
    
    init: function(name) {
        if (this.plugins[name] && this.plugins[name].init) {
            this.plugins[name].init();
            console.log('Plugin initialized:', name);
        }
    },
    
    get: function(name) {
        return this.plugins[name];
    }
};

// Example plugin
CanvastackPlugins.register('tableStats', {
    autoInit: true,
    
    init: function() {
        $(document).on('canvastack:table:loaded', this.updateStats);
    },
    
    updateStats: function(e, data) {
        console.log('Updating table stats:', data);
        // Update statistics display
    }
});
```

---

*This documentation covers the complete JavaScript Integration & Event Handling system. The system provides robust client-side functionality with comprehensive error handling, performance optimization, and debugging capabilities.*