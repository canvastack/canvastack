<?php

namespace Tests\Unit\Controllers;

use Tests\TestCase;
use Mockery;

/**
 * Test script load order preservation in Scripts trait
 * 
 * This test verifies that scripts are loaded in the correct order:
 * 1. Scripts maintain their insertion order within each position group
 * 2. Position prefixes (top:, last:) are respected
 * 3. Dependencies are preserved (e.g., jQuery before plugins)
 * 4. Deduplication doesn't break load order
 */
class ScriptLoadOrderTest extends TestCase
{
    /**
     * Clean up Mockery after each test
     */
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test that scripts maintain insertion order within same position
     */
    public function test_scripts_maintain_insertion_order_within_position()
    {
        // Create a mock template that records script calls
        $scriptCalls = [];
        $mockTemplate = Mockery::mock();
        $mockTemplate->shouldReceive('js')
            ->andReturnUsing(function ($script, $position) use (&$scriptCalls) {
                $scriptCalls[] = ['script' => $script, 'position' => $position];
                return true;
            });

        // Create controller with Scripts trait
        $controller = new class($mockTemplate) {
            use \Canvastack\Canvastack\Controllers\Core\Craft\Scripts;
            
            private $scriptNode = 'canvastackScriptNode::';
            public $template;
            
            public function __construct($template)
            {
                $this->template = $template;
            }
            
            // Expose private method for testing
            public function testSetScriptUnique(string $type, array $scripts): void
            {
                $this->setScriptUnique($type, $scripts);
            }
            
            // Expose deduplicateScripts for testing
            public function testDeduplicateScripts(string $type, array $scripts): array
            {
                return $this->deduplicateScripts($type, $scripts);
            }
        };

        // Test scripts in specific order (jQuery must load before plugins)
        $scripts = [
            'js' => [
                'vendor/jquery.js',
                'vendor/bootstrap.js',
                'vendor/select2.js',
                'app.js',
            ]
        ];

        $controller->testSetScriptUnique('js', $scripts);

        // Verify scripts were added in correct order
        $this->assertCount(4, $scriptCalls);
        $this->assertEquals('vendor/jquery.js', $scriptCalls[0]['script']);
        $this->assertEquals('vendor/bootstrap.js', $scriptCalls[1]['script']);
        $this->assertEquals('vendor/select2.js', $scriptCalls[2]['script']);
        $this->assertEquals('app.js', $scriptCalls[3]['script']);
        
        // All should be bottom position (default)
        foreach ($scriptCalls as $call) {
            $this->assertEquals('bottom', $call['position']);
        }
    }

    /**
     * Test that position prefixes are respected with correct grouping
     * 
     * Scripts should be grouped by position: top → bottom → last
     * Within each group, insertion order is preserved
     */
    public function test_position_prefixes_respected_with_order()
    {
        $scriptCalls = [];
        $mockTemplate = Mockery::mock();
        $mockTemplate->shouldReceive('js')
            ->andReturnUsing(function ($script, $position) use (&$scriptCalls) {
                $scriptCalls[] = ['script' => $script, 'position' => $position];
                return true;
            });

        $controller = new class($mockTemplate) {
            use \Canvastack\Canvastack\Controllers\Core\Craft\Scripts;
            
            private $scriptNode = 'canvastackScriptNode::';
            public $template;
            
            public function __construct($template)
            {
                $this->template = $template;
            }
            
            public function testSetScriptUnique(string $type, array $scripts): void
            {
                $this->setScriptUnique($type, $scripts);
            }
        };

        // Test scripts with different positions
        $scripts = [
            'js' => [
                'top:vendor/config.js',      // Top group, position 1
                'vendor/jquery.js',           // Bottom group, position 1
                'top:vendor/polyfill.js',     // Top group, position 2
                'vendor/bootstrap.js',        // Bottom group, position 2
                'last:analytics.js',          // Last group, position 1
            ]
        ];

        $controller->testSetScriptUnique('js', $scripts);

        // Verify correct order: all top scripts → all bottom scripts → all last scripts
        $this->assertCount(5, $scriptCalls);
        
        // Top scripts should come first (in their insertion order)
        $this->assertEquals('vendor/config.js', $scriptCalls[0]['script']);
        $this->assertEquals('top', $scriptCalls[0]['position']);
        
        $this->assertEquals('vendor/polyfill.js', $scriptCalls[1]['script']);
        $this->assertEquals('top', $scriptCalls[1]['position']);
        
        // Bottom scripts should come next (in their insertion order)
        $this->assertEquals('vendor/jquery.js', $scriptCalls[2]['script']);
        $this->assertEquals('bottom', $scriptCalls[2]['position']);
        
        $this->assertEquals('vendor/bootstrap.js', $scriptCalls[3]['script']);
        $this->assertEquals('bottom', $scriptCalls[3]['position']);
        
        // Last scripts should come at the end
        $this->assertEquals('analytics.js', $scriptCalls[4]['script']);
        $this->assertEquals('last', $scriptCalls[4]['position']);
    }

    /**
     * Test that deduplication preserves first occurrence (load order)
     */
    public function test_deduplication_preserves_first_occurrence()
    {
        $scriptCalls = [];
        $mockTemplate = Mockery::mock();
        $mockTemplate->shouldReceive('js')
            ->andReturnUsing(function ($script, $position) use (&$scriptCalls) {
                $scriptCalls[] = ['script' => $script, 'position' => $position];
                return true;
            });

        $controller = new class($mockTemplate) {
            use \Canvastack\Canvastack\Controllers\Core\Craft\Scripts;
            
            private $scriptNode = 'canvastackScriptNode::';
            public $template;
            
            public function __construct($template)
            {
                $this->template = $template;
            }
            
            public function testSetScriptUnique(string $type, array $scripts): void
            {
                $this->setScriptUnique($type, $scripts);
            }
        };

        // Test with duplicates - first occurrence should be kept
        $scripts = [
            'js' => [
                'vendor/jquery.js',
                'vendor/bootstrap.js',
                'vendor/jquery.js',      // Duplicate - should be removed
                'vendor/select2.js',
                'vendor/bootstrap.js',   // Duplicate - should be removed
            ]
        ];

        $controller->testSetScriptUnique('js', $scripts);

        // Should only have 3 unique scripts in original order
        $this->assertCount(3, $scriptCalls);
        $this->assertEquals('vendor/jquery.js', $scriptCalls[0]['script']);
        $this->assertEquals('vendor/bootstrap.js', $scriptCalls[1]['script']);
        $this->assertEquals('vendor/select2.js', $scriptCalls[2]['script']);
    }

    /**
     * Test that inline script nodes maintain order
     */
    public function test_inline_script_nodes_maintain_order()
    {
        $scriptCalls = [];
        $mockTemplate = Mockery::mock();
        $mockTemplate->shouldReceive('js')
            ->andReturnUsing(function ($script, $position, $asCode = false) use (&$scriptCalls) {
                $scriptCalls[] = [
                    'script' => $script,
                    'position' => $position,
                    'asCode' => $asCode
                ];
                return true;
            });

        $controller = new class($mockTemplate) {
            use \Canvastack\Canvastack\Controllers\Core\Craft\Scripts;
            
            private $scriptNode = 'canvastackScriptNode::';
            public $template;
            
            public function __construct($template)
            {
                $this->template = $template;
            }
            
            public function testSetScriptUnique(string $type, array $scripts): void
            {
                $this->setScriptUnique($type, $scripts);
            }
        };

        // Test with inline scripts
        $scripts = [
            'js' => [
                'vendor/jquery.js',
                'canvastackScriptNode::console.log("init");',
                'vendor/bootstrap.js',
                'canvastackScriptNode::console.log("ready");',
            ]
        ];

        $controller->testSetScriptUnique('js', $scripts);

        // Verify order and inline script handling
        $this->assertCount(4, $scriptCalls);
        
        $this->assertEquals('vendor/jquery.js', $scriptCalls[0]['script']);
        $this->assertFalse($scriptCalls[0]['asCode']);
        
        $this->assertEquals('console.log("init");', $scriptCalls[1]['script']);
        $this->assertTrue($scriptCalls[1]['asCode']);
        
        $this->assertEquals('vendor/bootstrap.js', $scriptCalls[2]['script']);
        $this->assertFalse($scriptCalls[2]['asCode']);
        
        $this->assertEquals('console.log("ready");', $scriptCalls[3]['script']);
        $this->assertTrue($scriptCalls[3]['asCode']);
    }

    /**
     * Test complex scenario with all features combined
     * 
     * This test verifies that scripts are grouped by position correctly:
     * - All 'top:' scripts load first (in their insertion order)
     * - All bottom scripts load next (in their insertion order)
     * - All 'last:' scripts load at the end (in their insertion order)
     */
    public function test_complex_load_order_scenario()
    {
        $scriptCalls = [];
        $mockTemplate = Mockery::mock();
        $mockTemplate->shouldReceive('js')
            ->andReturnUsing(function ($script, $position, $asCode = false) use (&$scriptCalls) {
                $scriptCalls[] = [
                    'script' => $script,
                    'position' => $position,
                    'asCode' => $asCode
                ];
                return true;
            });

        $controller = new class($mockTemplate) {
            use \Canvastack\Canvastack\Controllers\Core\Craft\Scripts;
            
            private $scriptNode = 'canvastackScriptNode::';
            public $template;
            
            public function __construct($template)
            {
                $this->template = $template;
            }
            
            public function testSetScriptUnique(string $type, array $scripts): void
            {
                $this->setScriptUnique($type, $scripts);
            }
        };

        // Complex scenario: positions, duplicates, inline scripts
        $scripts = [
            'js' => [
                'top:vendor/config.js',
                'vendor/jquery.js',
                'top:vendor/polyfill.js',
                'vendor/bootstrap.js',
                'vendor/jquery.js',                              // Duplicate
                'canvastackScriptNode::console.log("init");',
                'vendor/select2.js',
                'last:analytics.js',
                'canvastackScriptNode::console.log("ready");',
                'last:tracking.js',
            ]
        ];

        $controller->testSetScriptUnique('js', $scripts);

        // Should have 9 unique scripts (1 duplicate removed: vendor/jquery.js)
        $this->assertCount(9, $scriptCalls);
        
        // Verify the exact order - grouped by position: top → bottom → last
        // Within each group, insertion order is preserved
        $expectedOrder = [
            // Top scripts first (in insertion order)
            ['script' => 'vendor/config.js', 'position' => 'top', 'asCode' => false],
            ['script' => 'vendor/polyfill.js', 'position' => 'top', 'asCode' => false],
            
            // Bottom scripts next (in insertion order)
            ['script' => 'vendor/jquery.js', 'position' => 'bottom', 'asCode' => false],
            ['script' => 'vendor/bootstrap.js', 'position' => 'bottom', 'asCode' => false],
            ['script' => 'console.log("init");', 'position' => 'bottom', 'asCode' => true],
            ['script' => 'vendor/select2.js', 'position' => 'bottom', 'asCode' => false],
            ['script' => 'console.log("ready");', 'position' => 'bottom', 'asCode' => true],
            
            // Last scripts at the end (in insertion order)
            ['script' => 'analytics.js', 'position' => 'last', 'asCode' => false],
            ['script' => 'tracking.js', 'position' => 'last', 'asCode' => false],
        ];
        
        foreach ($expectedOrder as $index => $expected) {
            $this->assertEquals($expected['script'], $scriptCalls[$index]['script'], "Script at index {$index} doesn't match");
            $this->assertEquals($expected['position'], $scriptCalls[$index]['position'], "Position at index {$index} doesn't match");
            $this->assertEquals($expected['asCode'], $scriptCalls[$index]['asCode'], "AsCode flag at index {$index} doesn't match");
        }
    }

    /**
     * Test that dependency order is preserved (jQuery before plugins)
     */
    public function test_dependency_order_preserved()
    {
        $scriptCalls = [];
        $mockTemplate = Mockery::mock();
        $mockTemplate->shouldReceive('js')
            ->andReturnUsing(function ($script, $position) use (&$scriptCalls) {
                $scriptCalls[] = ['script' => $script, 'position' => $position];
                return true;
            });

        $controller = new class($mockTemplate) {
            use \Canvastack\Canvastack\Controllers\Core\Craft\Scripts;
            
            private $scriptNode = 'canvastackScriptNode::';
            public $template;
            
            public function __construct($template)
            {
                $this->template = $template;
            }
            
            public function testSetScriptUnique(string $type, array $scripts): void
            {
                $this->setScriptUnique($type, $scripts);
            }
        };

        // jQuery must load before plugins that depend on it
        $scripts = [
            'js' => [
                'vendor/jquery.js',
                'vendor/jquery.select2.js',    // Depends on jQuery
                'vendor/jquery.datepicker.js', // Depends on jQuery
                'vendor/bootstrap.js',          // Depends on jQuery
            ]
        ];

        $controller->testSetScriptUnique('js', $scripts);

        // Verify jQuery loads first
        $this->assertEquals('vendor/jquery.js', $scriptCalls[0]['script']);
        
        // Verify plugins load after jQuery in order
        $this->assertEquals('vendor/jquery.select2.js', $scriptCalls[1]['script']);
        $this->assertEquals('vendor/jquery.datepicker.js', $scriptCalls[2]['script']);
        $this->assertEquals('vendor/bootstrap.js', $scriptCalls[3]['script']);
    }
}
