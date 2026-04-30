/**
 * Canvasign ECharts Integration
 *
 * Provides a lightweight wrapper around Apache ECharts that:
 *  - Reads CSS custom properties from the active canvasign theme
 *  - Re-renders all registered charts when the `themechange` event fires
 *  - Resizes all charts on window resize (debounced)
 *  - Exposes a simple public API: CanvasignCharts.register() / .init()
 *
 * Usage:
 *   // Register a chart with a builder function that receives a color palette:
 *   CanvasignCharts.register('my-chart-id', function(colors) {
 *       return { /* ECharts option object *\/ };
 *   });
 *
 * Depends on:
 *   - Apache ECharts (https://cdn.jsdelivr.net/npm/echarts@5.5.0/dist/echarts.min.js)
 *   - theme.js (sets data-bs-theme on <html> and dispatches `themechange`)
 *
 * @author  wisnuwidi@canvastack.com
 * @copyright Canvastack
 */

(function () {
    'use strict';

    /* ------------------------------------------------------------------ */
    /* 1. Color palette — reads CSS custom properties from theme.css       */
    /* ------------------------------------------------------------------ */

    /**
     * Returns a color palette object derived from the active CSS custom
     * properties defined in theme.css.
     *
     * @returns {object}
     */
    function getColors() {
        var css = getComputedStyle(document.documentElement);

        function v(name) {
            return css.getPropertyValue(name).trim();
        }

        return {
            primary:    v('--primary')   || '#6366f1',
            secondary:  v('--secondary') || '#8b5cf6',
            success:    v('--success')   || '#22c55e',
            warning:    v('--warning')   || '#f59e0b',
            danger:     v('--danger')    || '#ef4444',
            info:       v('--info')      || '#06b6d4',
            text:       v('--text')      || '#f1f5f9',
            muted:      v('--text-muted')|| '#94a3b8',
            border:     v('--border')    || '#2a2d3a',
            surface:    v('--surface')   || '#1a1d27',
            bg:         v('--bg')        || '#0f1117',
        };
    }

    /* ------------------------------------------------------------------ */
    /* 2. Registry                                                         */
    /* ------------------------------------------------------------------ */

    /** @type {Array<{id: string, instance: object, builder: function}>} */
    var registry = [];

    /* ------------------------------------------------------------------ */
    /* 3. Core helpers                                                     */
    /* ------------------------------------------------------------------ */

    /**
     * Initialise (or re-render) a single chart entry.
     *
     * @param {object} entry  Registry entry
     */
    function renderEntry(entry) {
        if (!entry.instance) return;
        try {
            entry.instance.setOption(entry.builder(getColors()), true);
        } catch (e) {
            console.warn('CanvasignCharts: render failed for #' + entry.id, e);
        }
    }

    /**
     * Re-render all registered charts (called on theme change).
     */
    function renderAll() {
        registry.forEach(renderEntry);
    }

    /**
     * Resize all registered charts (called on window resize).
     */
    function resizeAll() {
        registry.forEach(function (entry) {
            if (entry.instance) {
                try { entry.instance.resize(); } catch (e) { /* ignore */ }
            }
        });
    }

    /* ------------------------------------------------------------------ */
    /* 4. Public API                                                       */
    /* ------------------------------------------------------------------ */

    /**
     * Register and immediately render a chart.
     *
     * @param {string}   elementId  ID of the container element
     * @param {function} builder    Function(colors) → ECharts option object
     * @param {object}   [opts]     Optional echarts.init() options (e.g. { renderer: 'svg' })
     * @returns {object|null}       ECharts instance, or null if echarts not loaded / element missing
     */
    function register(elementId, builder, opts) {
        if (typeof echarts === 'undefined') {
            console.warn('CanvasignCharts: echarts library not loaded.');
            return null;
        }

        var el = document.getElementById(elementId);
        if (!el) {
            console.warn('CanvasignCharts: element #' + elementId + ' not found.');
            return null;
        }

        /* Dispose existing instance if re-registering */
        var existing = registry.find(function (e) { return e.id === elementId; });
        if (existing && existing.instance) {
            existing.instance.dispose();
            registry = registry.filter(function (e) { return e.id !== elementId; });
        }

        var instance = echarts.init(el, null, opts || {});
        var entry = { id: elementId, instance: instance, builder: builder };
        registry.push(entry);
        renderEntry(entry);

        return instance;
    }

    /**
     * Dispose a registered chart and remove it from the registry.
     *
     * @param {string} elementId
     */
    function dispose(elementId) {
        registry = registry.filter(function (entry) {
            if (entry.id === elementId) {
                if (entry.instance) entry.instance.dispose();
                return false;
            }
            return true;
        });
    }

    /* ------------------------------------------------------------------ */
    /* 5. Event listeners                                                  */
    /* ------------------------------------------------------------------ */

    /* Re-render on theme change (fired by theme.js) */
    window.addEventListener('themechange', function () {
        /* Small delay to let CSS custom properties update first */
        setTimeout(renderAll, 50);
    });

    /* Debounced resize */
    var resizeTimer;
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(resizeAll, 150);
    });

    /* ------------------------------------------------------------------ */
    /* 6. Auto-init charts marked with data-canvasign-chart               */
    /* ------------------------------------------------------------------ */

    /**
     * Elements with `data-canvasign-chart` are auto-initialised with a
     * simple bar/line/pie chart based on the `data-chart-type` attribute
     * and inline JSON data in `data-chart-data`.
     *
     * Example:
     *   <div id="my-chart" style="height:300px"
     *        data-canvasign-chart
     *        data-chart-type="bar"
     *        data-chart-data='{"labels":["A","B"],"values":[10,20]}'></div>
     */
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof echarts === 'undefined') return;

        document.querySelectorAll('[data-canvasign-chart]').forEach(function (el) {
            if (!el.id) return;

            var type   = el.getAttribute('data-chart-type') || 'bar';
            var raw    = el.getAttribute('data-chart-data');
            var data   = {};
            if (raw) {
                try { data = JSON.parse(raw); } catch (e) { /* ignore */ }
            }

            register(el.id, function (colors) {
                var labels = data.labels || [];
                var values = data.values || [];

                if (type === 'pie' || type === 'donut') {
                    return {
                        tooltip: { trigger: 'item', backgroundColor: colors.surface, borderColor: colors.border, textStyle: { color: colors.text } },
                        series: [{
                            type: 'pie',
                            radius: type === 'donut' ? ['50%', '70%'] : '60%',
                            data: labels.map(function (l, i) { return { name: l, value: values[i] || 0 }; }),
                            itemStyle: { borderColor: colors.surface, borderWidth: 2 },
                            label: { color: colors.muted },
                        }],
                    };
                }

                return {
                    tooltip: { trigger: 'axis', backgroundColor: colors.surface, borderColor: colors.border, textStyle: { color: colors.text } },
                    grid:    { left: 30, right: 20, top: 20, bottom: 30 },
                    xAxis:   { type: 'category', data: labels, axisLabel: { color: colors.muted }, axisLine: { lineStyle: { color: colors.border } }, splitLine: { show: false } },
                    yAxis:   { type: 'value', axisLabel: { color: colors.muted }, axisLine: { lineStyle: { color: colors.border } }, splitLine: { lineStyle: { color: colors.border, type: 'dashed' } } },
                    series: [{
                        type:      type === 'line' ? 'line' : 'bar',
                        data:      values,
                        smooth:    type === 'line',
                        itemStyle: { color: colors.primary },
                        lineStyle: type === 'line' ? { color: colors.primary, width: 2 } : undefined,
                        areaStyle: type === 'line' ? {
                            color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                                { offset: 0, color: colors.primary + '55' },
                                { offset: 1, color: colors.primary + '00' },
                            ]),
                        } : undefined,
                    }],
                };
            });
        });
    });

    /* ------------------------------------------------------------------ */
    /* 7. Expose public API                                                */
    /* ------------------------------------------------------------------ */

    window.CanvasignCharts = {
        register:   register,
        dispose:    dispose,
        renderAll:  renderAll,
        resizeAll:  resizeAll,
        getColors:  getColors,
    };

})();
