/* jQuery 3.7.1 - Inline Loading */
// Create and append jQuery script tag immediately
(function() {
    // Only load if jQuery is not already available
    if (typeof window.jQuery === 'undefined') {
        var script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js';
        script.type = 'text/javascript';
        script.async = false; // Ensure synchronous loading
        
        // Insert before any existing scripts to ensure it loads first
        var firstScript = document.getElementsByTagName('script')[0];
        if (firstScript) {
            firstScript.parentNode.insertBefore(script, firstScript);
        } else {
            document.head.appendChild(script);
        }
        
        // Wait for jQuery to be available
        var checkJQuery = function() {
            if (typeof window.jQuery !== 'undefined') {
                // Make sure $ is also available
                window.$ = window.jQuery;
                console.log('jQuery loaded successfully');
            } else {
                // Keep checking until jQuery is available
                setTimeout(checkJQuery, 10);
            }
        };
        
        // Start checking after a brief delay
        setTimeout(checkJQuery, 50);
    }
})();