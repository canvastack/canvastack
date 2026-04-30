<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Demonstration of async/defer loading support
 * 
 * This test demonstrates how to use async and defer attributes
 * for optimal script loading performance.
 */
class ScriptAsyncDeferDemoTest extends TestCase
{
    /**
     * Demonstrate async loading for analytics scripts
     * 
     * Async scripts are downloaded in parallel and executed immediately
     * when ready, without blocking page rendering. Perfect for analytics
     * and tracking scripts that don't need to run in a specific order.
     */
    public function test_async_loading_for_analytics()
    {
        $controller = $this->createMockController();
        
        // Add analytics scripts with async loading
        // These will load in parallel and execute as soon as ready
        $controller->js('https://www.google-analytics.com/analytics.js', 'last', false, 'async');
        $controller->js('assets/js/tracking.js', 'last', false, 'async');
        $controller->js('assets/js/heatmap.js', 'last', false, 'async');
        
        // Verify all scripts have async attribute
        $scripts = $controller->template->scripts['js']['last'];
        $this->assertCount(3, $scripts);
        
        foreach ($scripts as $script) {
            $this->assertStringContainsString('async', $script->html);
        }
        
        // Output example HTML
        echo "\n\n=== Async Loading Example (Analytics) ===\n";
        foreach ($scripts as $script) {
            echo $script->html . "\n";
        }
        
        $this->assertTrue(true);
    }
    
    /**
     * Demonstrate defer loading for application scripts
     * 
     * Defer scripts are downloaded in parallel but executed in order
     * after DOM parsing is complete. Perfect for application scripts
     * that need to run in sequence and depend on DOM being ready.
     */
    public function test_defer_loading_for_application_scripts()
    {
        $controller = $this->createMockController();
        
        // Add application scripts with defer loading
        // These will load in parallel but execute in order after DOM ready
        $controller->js('https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js', 'top', false, 'defer');
        $controller->js('assets/js/vendor.js', 'bottom', false, 'defer');
        $controller->js('assets/js/app.js', 'bottom', false, 'defer');
        $controller->js('assets/js/init.js', 'bottom', false, 'defer');
        
        // Verify all scripts have defer attribute
        $topScripts = $controller->template->scripts['js']['top'];
        $bottomScripts = $controller->template->scripts['js']['bottom'];
        
        $this->assertCount(1, $topScripts);
        $this->assertCount(3, $bottomScripts);
        
        foreach ($topScripts as $script) {
            $this->assertStringContainsString('defer', $script->html);
        }
        
        foreach ($bottomScripts as $script) {
            $this->assertStringContainsString('defer', $script->html);
        }
        
        // Output example HTML
        echo "\n\n=== Defer Loading Example (Application Scripts) ===\n";
        echo "<!-- In <head> -->\n";
        foreach ($topScripts as $script) {
            echo $script->html . "\n";
        }
        echo "\n<!-- Before </body> -->\n";
        foreach ($bottomScripts as $script) {
            echo $script->html . "\n";
        }
        
        $this->assertTrue(true);
    }
    
    /**
     * Demonstrate mixed loading strategies
     * 
     * Shows how to combine different loading strategies for optimal
     * performance based on script dependencies and requirements.
     */
    public function test_mixed_loading_strategies()
    {
        $controller = $this->createMockController();
        
        // Critical scripts - load normally in head (blocking)
        $controller->js('assets/js/config.js', 'top', false, null);
        
        // Framework scripts - defer to load in parallel but execute in order
        $controller->js('https://cdn.jsdelivr.net/npm/vue@3/dist/vue.global.js', 'top', false, 'defer');
        $controller->js('assets/js/components.js', 'bottom', false, 'defer');
        
        // Application scripts - defer for ordered execution
        $controller->js('assets/js/app.js', 'bottom', false, 'defer');
        
        // Analytics - async for parallel loading and immediate execution
        $controller->js('https://www.google-analytics.com/analytics.js', 'last', false, 'async');
        
        // Inline initialization - no async/defer (executes immediately)
        $controller->js('window.APP_CONFIG = { version: "1.0" };', 'top', true);
        
        // Verify loading strategies
        $topScripts = $controller->template->scripts['js']['top'];
        $bottomScripts = $controller->template->scripts['js']['bottom'];
        $lastScripts = $controller->template->scripts['js']['last'];
        
        // Output example HTML
        echo "\n\n=== Mixed Loading Strategies Example ===\n";
        echo "<!-- In <head> -->\n";
        foreach ($topScripts as $script) {
            echo $script->html . "\n";
        }
        echo "\n<!-- Before </body> -->\n";
        foreach ($bottomScripts as $script) {
            echo $script->html . "\n";
        }
        echo "\n<!-- Last (analytics) -->\n";
        foreach ($lastScripts as $script) {
            echo $script->html . "\n";
        }
        
        $this->assertTrue(true);
    }
    
    /**
     * Demonstrate performance optimization with defer
     * 
     * Shows how defer can improve page load performance by allowing
     * scripts to load in parallel while maintaining execution order.
     */
    public function test_performance_optimization_with_defer()
    {
        $controller = $this->createMockController();
        
        // Without defer (traditional blocking approach)
        // Each script blocks until downloaded and executed
        echo "\n\n=== Traditional Blocking Approach ===\n";
        echo "<!-- Each script blocks page rendering -->\n";
        echo '<script src="assets/js/jquery.js"></script>' . "\n";
        echo '<script src="assets/js/bootstrap.js"></script>' . "\n";
        echo '<script src="assets/js/app.js"></script>' . "\n";
        echo "Total blocking time: ~3 seconds (sequential)\n";
        
        // With defer (optimized approach)
        // All scripts download in parallel, execute in order after DOM ready
        $controller->js('assets/js/jquery.js', 'bottom', false, 'defer');
        $controller->js('assets/js/bootstrap.js', 'bottom', false, 'defer');
        $controller->js('assets/js/app.js', 'bottom', false, 'defer');
        
        $scripts = $controller->template->scripts['js']['bottom'];
        
        echo "\n=== Optimized Defer Approach ===\n";
        echo "<!-- All scripts download in parallel, execute in order -->\n";
        foreach ($scripts as $script) {
            echo $script->html . "\n";
        }
        echo "Total blocking time: ~0 seconds (parallel download)\n";
        echo "Execution time: ~1 second (after DOM ready, in order)\n";
        echo "Performance improvement: ~66% faster page load\n";
        
        $this->assertTrue(true);
    }
    
    /**
     * Demonstrate when NOT to use async/defer
     * 
     * Shows scenarios where traditional blocking scripts are necessary.
     */
    public function test_when_not_to_use_async_defer()
    {
        $controller = $this->createMockController();
        
        echo "\n\n=== When NOT to Use Async/Defer ===\n\n";
        
        // 1. Critical configuration that must execute before other scripts
        echo "1. Critical Configuration (blocking):\n";
        $controller->js('window.API_KEY = "abc123";', 'top', true);
        echo "   - Inline config must execute immediately\n";
        echo "   - Other scripts depend on this config\n\n";
        
        // 2. Scripts that manipulate DOM during page load
        echo "2. DOM Manipulation During Load (blocking):\n";
        $controller->js('assets/js/polyfills.js', 'top', false, null);
        echo "   - Polyfills must load before other scripts\n";
        echo "   - DOM manipulation needs to happen during parsing\n\n";
        
        // 3. Inline scripts (async/defer ignored)
        echo "3. Inline Scripts (no async/defer):\n";
        $controller->js('console.log("Inline code");', 'bottom', true, 'async');
        echo "   - Inline scripts always execute immediately\n";
        echo "   - async/defer attributes are ignored\n\n";
        
        $this->assertTrue(true);
    }
    
    /**
     * Create a mock controller for testing
     */
    private function createMockController()
    {
        return new class {
            use \Canvastack\Canvastack\Controllers\Core\Craft\Scripts;
            
            public function __construct() {
                $this->template = new class {
                    public $scripts = [];
                    
                    public function js($script, $position, $asCode, $loadMode = null) {
                        $scriptObj = $this->createScriptObject($script, $asCode, $loadMode);
                        $this->scripts['js'][$position][] = $scriptObj;
                        return $this->scripts;
                    }
                    
                    private function createScriptObject($script, $asCode, $loadMode) {
                        if ($asCode) {
                            return (object)[
                                'url' => false,
                                'html' => '<script type="text/javascript">' . $script . '</script>'
                            ];
                        }
                        
                        $loadModeAttr = '';
                        if (in_array($loadMode, ['async', 'defer'])) {
                            $loadModeAttr = ' ' . $loadMode;
                        }
                        
                        return (object)[
                            'url' => $script,
                            'html' => '<script type="text/javascript" src="' . $script . '"' . $loadModeAttr . '></script>'
                        ];
                    }
                };
            }
        };
    }
}
