/* ECharts - Dynamic Loading */
(function() {
    if (typeof echarts === 'undefined') {
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/echarts@5.5.0/dist/echarts.min.js';
        script.async = false;
        document.head.appendChild(script);
    }
})();