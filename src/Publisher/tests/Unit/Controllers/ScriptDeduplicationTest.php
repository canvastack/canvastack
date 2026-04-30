<?php

namespace Tests\Unit\Controllers;

use Tests\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Test script deduplication improvements in Scripts trait
 * 
 * This test uses reflection to test the private deduplicateScripts method
 * without requiring full Controller initialization.
 */
class ScriptDeduplicationTest extends TestCase
{
    /**
     * Get a mock controller instance with Scripts trait
     */
    private function getMockController()
    {
        // Create anonymous class that uses Scripts trait
        return new class {
            use \Canvastack\Canvastack\Controllers\Core\Craft\Scripts;
            
            private $scriptNode = 'canvastackScriptNode::';
            
            // Expose the private method for testing
            public function testDeduplicateScripts(string $type, array $scripts): array
            {
                return $this->deduplicateScripts($type, $scripts);
            }
        };
    }

    /**
     * Test basic script deduplication
     */
    public function test_basic_script_deduplication()
    {
        $controller = $this->getMockController();

        $scripts = [
            'vendor/jquery.js',
            'vendor/bootstrap.js',
            'vendor/jquery.js',  // Duplicate
        ];

        $result = $controller->testDeduplicateScripts('js', $scripts);

        $this->assertCount(2, $result);
        $this->assertEquals('vendor/jquery.js', $result[0]);
        $this->assertEquals('vendor/bootstrap.js', $result[1]);
    }

    /**
     * Test deduplication with position prefixes
     */
    public function test_deduplication_with_position_prefixes()
    {
        $controller = $this->getMockController();

        $scripts = [
            'top:vendor/jquery.js',
            'vendor/bootstrap.js',
            'vendor/jquery.js',  // Duplicate (without prefix)
            'last:analytics.js',
            'last:analytics.js',  // Duplicate
        ];

        $result = $controller->testDeduplicateScripts('js', $scripts);

        $this->assertCount(3, $result);
        $this->assertEquals('top:vendor/jquery.js', $result[0]);
        $this->assertEquals('vendor/bootstrap.js', $result[1]);
        $this->assertEquals('last:analytics.js', $result[2]);
    }

    /**
     * Test deduplication with inline script nodes
     */
    public function test_deduplication_with_script_nodes()
    {
        $controller = $this->getMockController();

        $scripts = [
            'canvastackScriptNode::console.log("test");',
            'vendor/jquery.js',
            'canvastackScriptNode::console.log("test");',  // Duplicate
        ];

        $result = $controller->testDeduplicateScripts('js', $scripts);

        $this->assertCount(2, $result);
        $this->assertStringContainsString('canvastackScriptNode::', $result[0]);
        $this->assertEquals('vendor/jquery.js', $result[1]);
    }

    /**
     * Test deduplication with path normalization
     */
    public function test_deduplication_with_path_normalization()
    {
        $controller = $this->getMockController();

        $scripts = [
            'vendor/jquery.js',
            'vendor\\jquery.js',  // Backslash - should be treated as duplicate
            'vendor//jquery.js',  // Double slash - should be treated as duplicate
        ];

        $result = $controller->testDeduplicateScripts('js', $scripts);

        $this->assertCount(1, $result);
        $this->assertEquals('vendor/jquery.js', $result[0]);
    }

    /**
     * Test deduplication filters empty scripts
     */
    public function test_deduplication_filters_empty_scripts()
    {
        $controller = $this->getMockController();

        $scripts = [
            'vendor/jquery.js',
            '',  // Empty
            '   ',  // Whitespace only
            'vendor/bootstrap.js',
        ];

        $result = $controller->testDeduplicateScripts('js', $scripts);

        $this->assertCount(2, $result);
        $this->assertEquals('vendor/jquery.js', $result[0]);
        $this->assertEquals('vendor/bootstrap.js', $result[1]);
    }

    /**
     * Test deduplication with whitespace trimming
     */
    public function test_deduplication_with_whitespace_trimming()
    {
        $controller = $this->getMockController();

        $scripts = [
            'vendor/jquery.js',
            '  vendor/jquery.js  ',  // With whitespace - should be treated as duplicate
            'vendor/bootstrap.js',
        ];

        $result = $controller->testDeduplicateScripts('js', $scripts);

        $this->assertCount(2, $result);
        $this->assertEquals('vendor/jquery.js', $result[0]);
        $this->assertEquals('vendor/bootstrap.js', $result[1]);
    }

    /**
     * Test deduplication maintains insertion order
     */
    public function test_deduplication_maintains_insertion_order()
    {
        $controller = $this->getMockController();

        $scripts = [
            'vendor/jquery.js',
            'vendor/bootstrap.js',
            'vendor/select2.js',
            'vendor/jquery.js',  // Duplicate - should not change order
        ];

        $result = $controller->testDeduplicateScripts('js', $scripts);

        $this->assertCount(3, $result);
        $this->assertEquals('vendor/jquery.js', $result[0]);
        $this->assertEquals('vendor/bootstrap.js', $result[1]);
        $this->assertEquals('vendor/select2.js', $result[2]);
    }
    
    /**
     * Test deduplication with non-string values
     */
    public function test_deduplication_filters_non_string_values()
    {
        $controller = $this->getMockController();

        $scripts = [
            'vendor/jquery.js',
            null,  // Non-string
            123,   // Non-string
            'vendor/bootstrap.js',
        ];

        $result = $controller->testDeduplicateScripts('js', $scripts);

        $this->assertCount(2, $result);
        $this->assertEquals('vendor/jquery.js', $result[0]);
        $this->assertEquals('vendor/bootstrap.js', $result[1]);
    }
}
