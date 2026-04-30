/**
 * CanvaStack Core Utilities
 * 
 * Universal utility functions used across all templates and pages.
 * These functions provide basic string manipulation, random generation,
 * and data conversion capabilities.
 * 
 * @author wisnuwidi@canvastack.com
 * @copyright Canvastack
 * @version 1.0.0
 */

(function(window) {
    'use strict';
    
    /**
     * Convert string to title case (capitalize first letter of each word)
     * 
     * @param {string} str - String to convert
     * @param {boolean} force - Force lowercase before capitalizing
     * @returns {string} Title cased string
     * 
     * @example
     * ucwords('hello world') // 'Hello World'
     * ucwords('HELLO WORLD', true) // 'Hello World'
     */
    window.ucwords = function(str, force) {
        str = force ? str.toLowerCase() : str;
        return str.replace(/(\b)([a-zA-Z])/g, function(firstLetter) {
            return firstLetter.toUpperCase();
        });
    };
    
    /**
     * Generate random alphanumeric string
     * 
     * @param {number} length - Length of random string (default: 8)
     * @returns {string} Random string
     * 
     * @example
     * canvastack_random() // 'aB3xY9Zq'
     * canvastack_random(16) // 'aB3xY9ZqMnP4rStU'
     */
    window.canvastack_random = function(length) {
        length = length || 8;
        var result = '';
        var characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        var charactersLength = characters.length;
        
        for (var i = 0; i < length; i++) {
            result += characters.charAt(Math.floor(Math.random() * charactersLength));
        }
        
        return result;
    };
    
    /**
     * Convert array to object
     * 
     * @param {Array} array - Array to convert
     * @returns {Object} Object with array indices as keys
     * 
     * @example
     * canvastack_array_to_object(['a', 'b', 'c']) // {0: 'a', 1: 'b', 2: 'c'}
     */
    window.canvastack_array_to_object = function(array) {
        return Object.assign({}, array);
    };
    
    /**
     * Escape HTML special characters to prevent XSS
     * 
     * @param {string} text - Text to escape
     * @returns {string} Escaped text
     * 
     * @example
     * escapeHtml('<script>alert("xss")</script>') // '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;'
     */
    window.escapeHtml = function(text) {
        if (typeof text !== 'string') return text;
        
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        
        return text.replace(/[&<>"']/g, function(m) {
            return map[m];
        });
    };
    
    /**
     * Debounce function execution
     * 
     * @param {Function} func - Function to debounce
     * @param {number} delay - Delay in milliseconds
     * @returns {Function} Debounced function
     * 
     * @example
     * var debouncedSearch = debounce(function() { search(); }, 300);
     * input.addEventListener('keyup', debouncedSearch);
     */
    window.debounce = function(func, delay) {
        var timeout;
        return function() {
            var context = this;
            var args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                func.apply(context, args);
            }, delay);
        };
    };
    
    /**
     * Get base URL from current location
     * 
     * @returns {string} Base URL (protocol + host + first path segment)
     * 
     * @example
     * handleBaseURL() // 'http://localhost/myapp'
     */
    window.handleBaseURL = function() {
        var getUrl = window.location,
            baseUrl = getUrl.protocol + "//" + getUrl.host + "/" + getUrl.pathname.split('/')[1];
        return baseUrl;
    };
    
    console.log('CanvaStack Core Utilities loaded');
    
})(window);
