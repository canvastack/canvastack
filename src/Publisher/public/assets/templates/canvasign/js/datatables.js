/* DataTables Bootstrap 5 JS - Dynamic Loading */
(function() {
    if (typeof $.fn.dataTable === 'undefined') {
        // Load DataTables scripts in sequence
        const scripts = [
            'https://cdn.datatables.net/v/bs5/jszip-2.5.0/dt-1.13.4/b-2.3.6/b-colvis-2.3.6/b-html5-2.3.6/b-print-2.3.6/r-2.4.1/datatables.min.js',
            'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js',
            'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js'
        ];
        
        let loadedCount = 0;
        scripts.forEach((src) => {
            const script = document.createElement('script');
            script.src = src;
            script.async = false;
            script.onload = () => {
                loadedCount++;
                if (loadedCount === scripts.length) {
                    document.dispatchEvent(new CustomEvent('datatables-loaded'));
                }
            };
            document.head.appendChild(script);
        });
    }
})();