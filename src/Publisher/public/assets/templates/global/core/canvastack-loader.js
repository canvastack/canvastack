/**
 * CanvaStack Loader
 * 
 * Universal loading indicator management for form inputs and elements.
 * Provides visual feedback during AJAX operations.
 * 
 * @author wisnuwidi@canvastack.com
 * @copyright Canvastack
 * @version 1.0.0
 */

(function(window, $) {
    'use strict';
    
    /**
     * Show/hide/remove loading indicator for an element
     * 
     * @param {string} target_id - Target element ID (without '#' prefix)
     * @param {string} view - Action: 'show', 'hide', 'fadeOut', or 'remove'
     * 
     * @example
     * loader('myInput', 'show');      // Show loader
     * loader('myInput', 'fadeOut');   // Fade out and remove
     * loader('myInput', 'remove');    // Remove all loaders
     */
    window.loader = function(target_id, view) {
        view = view || 'hide';
        
        var _loaderTarget = '#' + target_id;
        var _loaderID = 'CanvaStackInpLdr' + target_id;
        
        if (view === 'remove') {
            // Remove all loaders
            $('span.inputloader').remove();
            
        } else if (view === 'fadeOut') {
            // Fade out and remove
            $('span.inputloader').fadeOut(1800, function() {
                $(this).remove();
            });
            
        } else if (view === 'hide') {
            // Hide loader
            $('#' + _loaderID).hide();
            
        } else {
            // Show loader (view can be additional CSS class)
            var loaderClass = 'inputloader loader';
            if (view !== 'show') {
                loaderClass += ' ' + view;
            }
            
            // Check if loader already exists
            if ($('#' + _loaderID).length === 0) {
                $(_loaderTarget).before(
                    '<span class="' + loaderClass + '" id="' + _loaderID + '"></span>'
                );
            } else {
                $('#' + _loaderID).show();
            }
        }
    };
    
    /**
     * Show loader with custom message
     * 
     * @param {string} target_id - Target element ID
     * @param {string} message - Loading message
     * 
     * @example
     * loaderWithMessage('myInput', 'Loading data...');
     */
    window.loaderWithMessage = function(target_id, message) {
        var _loaderTarget = '#' + target_id;
        var _loaderID = 'CanvaStackInpLdr' + target_id;
        
        message = message || 'Loading...';
        
        if ($('#' + _loaderID).length === 0) {
            $(_loaderTarget).before(
                '<span class="inputloader loader" id="' + _loaderID + '">' +
                '<span class="loader-message">' + message + '</span>' +
                '</span>'
            );
        } else {
            $('#' + _loaderID).show().find('.loader-message').text(message);
        }
    };
    
    /**
     * Show global page loader overlay
     * 
     * @param {string} message - Loading message (optional)
     * 
     * @example
     * showPageLoader('Processing...');
     * hidePageLoader();
     */
    window.showPageLoader = function(message) {
        message = message || 'Loading...';
        
        if ($('#canvastack-page-loader').length === 0) {
            $('body').append(
                '<div id="canvastack-page-loader" class="canvastack-page-loader">' +
                '<div class="canvastack-page-loader-content">' +
                '<div class="canvastack-page-loader-spinner"></div>' +
                '<div class="canvastack-page-loader-message">' + message + '</div>' +
                '</div>' +
                '</div>'
            );
        } else {
            $('#canvastack-page-loader').show()
                .find('.canvastack-page-loader-message').text(message);
        }
    };
    
    /**
     * Hide global page loader overlay
     * 
     * @example
     * hidePageLoader();
     */
    window.hidePageLoader = function() {
        $('#canvastack-page-loader').fadeOut(300, function() {
            $(this).remove();
        });
    };
    
    console.log('CanvaStack Loader loaded');
    
})(window, jQuery);
