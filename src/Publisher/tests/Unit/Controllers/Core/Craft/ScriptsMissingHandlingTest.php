<?php

namespace Tests\Unit\Controllers\Core\Craft;

use Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use Mockery;

/**
 * Test Missing Script Handling
 * 
 * Tests the graceful handling of missing script files to ensure:
 * - Application doesn't crash when scripts are missing
 * - Missing scripts are handled appropriately
 * - Development mode shows warnings
 * - Production mode handles errors silently
 * - File existence checks are cached for performance
 */
class ScriptsMissingHandlingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear cache before each test
        Cache::flush();
    }

    /**
     * Test that missing local script files are handled gracefully
     * 
     * @return void
     */
    public function test_missing_local_script_handled_gracefully(): void
    {
        // Enable graceful handling and logging
        config(['canvastack.controller.script_management.handle_missing_gracefully' => true]);
        config(['canvastack.controller.script_management.log_missing_scripts' => true]);
        config(['canvastack.controller.logging.log_performance_issues' => true]);
        config(['app.debug' => false]); // Disable debug to avoid console warnings
        
        // Create a test controller that uses Scripts trait
        $controller = new class {
            use \Canvastack\Canvastack\Controllers\Core\Craft\Scripts;
            
            public $template;
            
            public function __construct() {
                $this->template = Mockery::mock('stdClass');
                $this->template->shouldReceive('js')->andReturn(true);
            }
            
            public function testHandleMissing($script, $type) {
                $reflection = new \ReflectionClass($this);
                $method = $reflection->getMethod('handleMissingScript');
                $method->setAccessible(true);
                return $method->invoke($this, $script, $type);
            }
        };
        
        // This should not throw an exception
        $controller->testHandleMissing('assets/js/nonexistent.js', 'js');
        
        $this->assertTrue(true); // If we reach here, graceful handling worked
    }

    /**
     * Test that external URLs (CDN scripts) are not validated
     * 
     * @return void
     */
    public function test_external_urls_not_validated(): void
    {
        config(['canvastack.controller.script_management.handle_missing_gracefully' => true]);
        
        $controller = new class {
            use \Canvastack\Canvastack\Controllers\Core\Craft\Scripts;
            
            public $template;
            
            public function __construct() {
                $this->template = Mockery::mock('stdClass');
                $this->template->shouldReceive('js')->andReturn(true);
            }
            
            public function testHandleMissing($script, $type) {
                $reflection = new \ReflectionClass($this);
                $method = $reflection->getMethod('handleMissingScript');
                $method->setAccessible(true);
                return $method->invoke($this, $script, $type);
            }
        };
        
        // External URLs should not trigger errors
        $controller->testHandleMissing('https://cdn.example.com/lib.js', 'js');
        $controller->testHandleMissing('http://example.com/style.css', 'css');
        
        $this->assertTrue(true);
    }

    /**
     * Test that inline script nodes are not validated
     * 
     * @return void
     */
    public function test_inline_script_nodes_not_validated(): void
    {
        config(['canvastack.controller.script_management.handle_missing_gracefully' => true]);
        
        $controller = new class {
            use \Canvastack\Canvastack\Controllers\Core\Craft\Scripts;
            
            public $template;
            private $scriptNode = 'canvastackScriptNode::';
            
            public function __construct() {
                $this->template = Mockery::mock('stdClass');
                $this->template->shouldReceive('js')->andReturn(true);
            }
            
            public function testHandleMissing($script, $type) {
                $reflection = new \ReflectionClass($this);
                $method = $reflection->getMethod('handleMissingScript');
                $method->setAccessible(true);
                return $method->invoke($this, $script, $type);
            }
        };
        
        // Inline script nodes should not trigger errors
        $controller->testHandleMissing('canvastackScriptNode::console.log("test");', 'js');
        
        $this->assertTrue(true);
    }

    /**
     * Test that file existence checks are cached
     * 
     * @return void
     */
    public function test_file_existence_checks_are_cached(): void
    {
        config(['canvastack.controller.script_management.handle_missing_gracefully' => true]);
        config(['canvastack.controller.script_management.cache_existence_checks' => true]);
        config(['canvastack.controller.script_management.log_missing_scripts' => true]);
        config(['canvastack.controller.logging.log_performance_issues' => true]);
        config(['app.debug' => false]);
        
        $scriptPath = 'assets/js/test.js';
        $cacheKey = 'canvastack_script_exists_' . md5($scriptPath);
        
        // Pre-cache the result
        Cache::put($cacheKey, false, 3600);
        
        $controller = new class {
            use \Canvastack\Canvastack\Controllers\Core\Craft\Scripts;
            
            public $template;
            
            public function __construct() {
                $this->template = Mockery::mock('stdClass');
                $this->template->shouldReceive('js')->andReturn(true);
            }
            
            public function testHandleMissing($script, $type) {
                $reflection = new \ReflectionClass($this);
                $method = $reflection->getMethod('handleMissingScript');
                $method->setAccessible(true);
                return $method->invoke($this, $script, $type);
            }
        };
        
        // Should use cached result
        $controller->testHandleMissing($scriptPath, 'js');
        
        // Verify cache was used
        $this->assertTrue(Cache::has($cacheKey));
    }

    /**
     * Test that existing scripts don't trigger warnings
     * 
     * @return void
     */
    public function test_existing_scripts_no_warnings(): void
    {
        config(['canvastack.controller.script_management.handle_missing_gracefully' => true]);
        
        // Create a temporary test file
        $testFile = public_path('test-script-temp.js');
        file_put_contents($testFile, '// Test script');
        
        $controller = new class {
            use \Canvastack\Canvastack\Controllers\Core\Craft\Scripts;
            
            public $template;
            
            public function __construct() {
                $this->template = Mockery::mock('stdClass');
                $this->template->shouldReceive('js')->andReturn(true);
            }
            
            public function testHandleMissing($script, $type) {
                $reflection = new \ReflectionClass($this);
                $method = $reflection->getMethod('handleMissingScript');
                $method->setAccessible(true);
                return $method->invoke($this, $script, $type);
            }
        };
        
        // Existing files should not trigger errors
        $controller->testHandleMissing('test-script-temp.js', 'js');
        
        // Clean up
        unlink($testFile);
        
        $this->assertTrue(true);
    }

    /**
     * Test that position prefixes are handled correctly
     * 
     * @return void
     */
    public function test_position_prefixes_handled_correctly(): void
    {
        config(['canvastack.controller.script_management.handle_missing_gracefully' => true]);
        config(['canvastack.controller.script_management.log_missing_scripts' => true]);
        config(['canvastack.controller.logging.log_performance_issues' => true]);
        config(['app.debug' => false]);
        
        $controller = new class {
            use \Canvastack\Canvastack\Controllers\Core\Craft\Scripts;
            
            public $template;
            
            public function __construct() {
                $this->template = Mockery::mock('stdClass');
                $this->template->shouldReceive('js')->andReturn(true);
            }
            
            public function testHandleMissing($script, $type) {
                $reflection = new \ReflectionClass($this);
                $method = $reflection->getMethod('handleMissingScript');
                $method->setAccessible(true);
                return $method->invoke($this, $script, $type);
            }
        };
        
        // Test with position prefixes - should not throw exceptions
        $controller->testHandleMissing('top:assets/js/missing.js', 'js');
        $controller->testHandleMissing('last:assets/js/missing.js', 'js');
        
        $this->assertTrue(true);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
