/**
 * Delete Handler JavaScript
 * Handles delete confirmation modals and AJAX delete requests
 * Enhanced with dynamic table name detection and improved messaging
 */

$(document).ready(function() {
    // Handle modal show event to update content with accurate data
    $(document).on('show.bs.modal', '[id^="deleteModal_"]', function(e) {
        var modal = $(this);
        var button = $(e.relatedTarget);
        
        // Ensure proper z-index
        modal.css('z-index', 1070);
        $('.modal-backdrop').css('z-index', 1060);
        
        // Get data from button attributes
        var recordId = button.data('record-id');
        var tableName = button.data('table-name');
        var deleteType = button.data('delete-type');
        var modelClass = button.data('model-class');
        var formId = button.data('form-id');
        var controllerInfo = button.data('controller-info');
        
        console.log('Dynamic Modal Data:', {
            recordId: recordId, 
            tableName: tableName, 
            deleteType: deleteType,
            modelClass: modelClass,
            formId: formId,
            controllerInfo: controllerInfo
        });
        
        // Get accurate table name from various sources
        var actualTableName = getActualTableName(tableName, modelClass, controllerInfo);
        
        // Update modal content with dynamic information
        if (recordId && actualTableName) {
            var alertElement = modal.find('.modal-body .alert');
            
            // Create dynamic message based on delete type with improved wording
            var newMessage;
            if (deleteType === 'soft') {
                newMessage = 'Anda akan menghapus record data dari tabel <strong>\'' + actualTableName + '\'</strong> dengan ID <strong>' + recordId + '</strong>. Data akan dipindahkan ke recycle bin dan dapat dipulihkan kembali. Apakah Anda yakin?';
            } else {
                newMessage = 'Anda akan menghapus permanen record data dari tabel <strong>\'' + actualTableName + '\'</strong> dengan ID <strong>' + recordId + '</strong>. Tindakan ini tidak dapat dibatalkan. Apakah Anda yakin?';
            }
            
            // Update alert content
            alertElement.html('<i class="fa fa-exclamation-triangle"></i> ' + newMessage);
            
            // Update modal title based on delete type
            var titleElement = modal.find('.modal-title');
            if (deleteType === 'soft') {
                titleElement.html('<i class="fa fa-trash-o"></i> &nbsp; Confirm Soft Delete');
            } else {
                titleElement.html('<i class="fa fa-trash"></i> &nbsp; Confirm Permanent Delete');
            }
            
            // Update button text based on delete type
            var confirmButton = modal.find('.btn-danger, .btn-warning');
            if (deleteType === 'soft') {
                confirmButton.removeClass('btn-danger').addClass('btn-warning');
                confirmButton.html('<i class="fa fa-trash-o"></i> Yes, Move to Trash');
            } else {
                confirmButton.removeClass('btn-warning').addClass('btn-danger');
                confirmButton.html('<i class="fa fa-trash"></i> Yes, Delete Permanently');
            }
        }
        
        // Store form ID and other data for submission
        modal.data('form-id', formId);
        modal.data('delete-type', deleteType);
        modal.data('model-class', modelClass);
        modal.data('table-name', actualTableName);
    });
    
    // Handle delete confirmation button click
    $(document).on('click', '[id^="deleteModal_"] .btn-danger[onclick], [id^="deleteModal_"] .btn-warning[onclick]', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var modal = button.closest('[id^="deleteModal_"]');
        var formId = modal.data('form-id');
        var form = $('#' + formId);
        
        if (form.length === 0) {
            console.error('Form not found:', formId);
            showNotification('error', 'Form not found');
            return;
        }
        
        // Show loading state
        var originalText = button.html();
        var deleteType = modal.data('delete-type');
        var loadingText = deleteType === 'soft' ? 
            '<i class="fa fa-spinner fa-spin"></i> Moving to Trash...' : 
            '<i class="fa fa-spinner fa-spin"></i> Deleting...';
            
        button.html(loadingText).prop('disabled', true);
        
        // Submit form via AJAX for better UX
        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: form.serialize(),
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            success: function(response) {
                // Hide modal
                modal.modal('hide');
                
                // Show success message
                var successMessage = response.message;
                if (!successMessage) {
                    var tableName = modal.data('table-name') || 'record';
                    successMessage = deleteType === 'soft' ? 
                        'Record from table \'' + tableName + '\' has been moved to trash successfully' :
                        'Record from table \'' + tableName + '\' has been deleted permanently';
                }
                
                if (response.success !== false) {
                    showNotification('success', successMessage);
                    
                    // Refresh DataTable if available
                    if (typeof window.dataTable !== 'undefined' && window.dataTable) {
                        window.dataTable.ajax.reload(null, false);
                    } else if ($('.dataTable').length > 0) {
                        // Try to reload any DataTable on the page
                        $('.dataTable').each(function() {
                            var table = $(this).DataTable();
                            if (table && typeof table.ajax !== 'undefined') {
                                table.ajax.reload(null, false);
                            }
                        });
                    } else {
                        // Fallback: reload page after short delay
                        setTimeout(function() {
                            window.location.reload();
                        }, 1500);
                    }
                } else {
                    showNotification('error', response.message || 'Operation failed');
                }
            },
            error: function(xhr) {
                // Hide modal
                modal.modal('hide');
                
                var errorMessage = 'An error occurred';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.status === 404) {
                    errorMessage = 'Record not found';
                } else if (xhr.status === 403) {
                    errorMessage = 'You do not have permission to perform this action';
                } else if (xhr.status === 422) {
                    errorMessage = 'Validation error occurred';
                } else if (xhr.status === 500) {
                    errorMessage = 'Server error occurred';
                }
                
                showNotification('error', errorMessage);
            },
            complete: function() {
                // Restore button state
                button.html(originalText).prop('disabled', false);
            }
        });
    });
    
    // Handle direct button click (fallback for non-AJAX)
    $(document).on('click', 'button[onclick*="submit()"]', function(e) {
        var button = $(this);
        var onclick = button.attr('onclick');
        
        // Extract form ID from onclick attribute
        var formIdMatch = onclick.match(/getElementById\('([^']+)'\)/);
        if (formIdMatch) {
            var formId = formIdMatch[1];
            var form = $('#' + formId);
            
            if (form.length) {
                e.preventDefault();
                form.submit();
            }
        }
    });
});

/**
 * Get actual table name from various sources
 */
function getActualTableName(tableName, modelClass, controllerInfo) {
    // Priority order: controllerInfo > modelClass > tableName > fallback
    
    // Try to get from controller info
    if (controllerInfo && typeof controllerInfo === 'object') {
        if (controllerInfo.table_name && controllerInfo.table_name !== 'records') {
            return controllerInfo.table_name;
        }
    } else if (typeof controllerInfo === 'string') {
        try {
            var parsed = JSON.parse(controllerInfo);
            if (parsed.table_name && parsed.table_name !== 'records') {
                return parsed.table_name;
            }
        } catch (e) {
            // Ignore parsing errors
        }
    }
    
    // Try to extract from model class
    if (modelClass && typeof modelClass === 'string') {
        // Extract table name from model class (e.g., App\Models\User -> users)
        var modelName = modelClass.split('\\').pop(); // Get last part
        if (modelName && modelName !== 'Model') {
            // Convert PascalCase to snake_case and pluralize
            var tableName = modelName
                .replace(/([A-Z])/g, '_$1')
                .toLowerCase()
                .replace(/^_/, '');
            
            // Simple pluralization
            if (!tableName.endsWith('s')) {
                tableName += 's';
            }
            
            return tableName;
        }
    }
    
    // Use provided table name if it's not generic
    if (tableName && tableName !== 'records' && tableName !== 'record') {
        return tableName;
    }
    
    // Try to get from current URL
    var currentPath = window.location.pathname;
    var pathParts = currentPath.split('/').filter(function(part) {
        return part.length > 0;
    });
    
    if (pathParts.length >= 2) {
        var potentialTableName = pathParts[pathParts.length - 2];
        if (potentialTableName && potentialTableName !== 'admin') {
            return potentialTableName;
        }
    }
    
    // Fallback
    return tableName || 'records';
}

/**
 * Show notification message
 */
function showNotification(type, message) {
    // Try to use existing notification system
    if (typeof toastr !== 'undefined') {
        toastr[type](message);
    } else if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: type === 'success' ? 'success' : 'error',
            title: type === 'success' ? 'Success' : 'Error',
            text: message,
            timer: 3000,
            showConfirmButton: false
        });
    } else if (typeof $ !== 'undefined' && $.notify) {
        $.notify({
            message: message
        }, {
            type: type === 'success' ? 'success' : 'danger',
            delay: 3000
        });
    } else {
        // Fallback: simple alert
        alert(message);
    }
}

/**
 * Initialize delete handlers for dynamically loaded content
 */
function initializeDeleteHandlers() {
    // This function can be called after DataTable reloads
    console.log('Delete handlers initialized');
}

// Export for global access
window.initializeDeleteHandlers = initializeDeleteHandlers;
window.getActualTableName = getActualTableName;