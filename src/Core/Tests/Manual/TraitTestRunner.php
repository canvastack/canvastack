<?php

namespace Canvastack\Canvastack\Core\Tests\Manual;

use Canvastack\Canvastack\Core\Craft\Components\Chart;
use Canvastack\Canvastack\Core\Craft\Components\Email;

/**
 * Manual Trait Test Runner
 * 
 * Manual test untuk memastikan traits bekerja dengan fallback mechanism
 * 
 * @author CanvaStack Dev Team
 * @created 2024-12-19
 * @version 1.0
 */
class TraitTestRunner
{
    public function runTests()
    {
        echo "=== MANUAL TRAIT TESTING ===\n\n";
        
        $this->testChartTrait();
        $this->testEmailTrait();
        
        echo "=== ALL TESTS COMPLETED ===\n";
    }
    
    private function testChartTrait()
    {
        echo "Testing Chart Trait...\n";
        
        $testClass = new class {
            use Chart;
            
            public $plugins = [];
            
            public function init() {
                $this->initChart();
            }
        };
        
        try {
            $testClass->init();
            
            if ($testClass->chart !== null) {
                echo "✅ Chart trait initialized successfully\n";
                echo "   - Chart instance: " . get_class($testClass->chart) . "\n";
                echo "   - Plugin registered: " . (isset($testClass->plugins['chart']) ? 'Yes' : 'No') . "\n";
            } else {
                echo "❌ Chart trait failed to initialize\n";
            }
        } catch (\Exception $e) {
            echo "❌ Chart trait error: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    private function testEmailTrait()
    {
        echo "Testing Email Trait...\n";
        
        $testClass = new class {
            use Email;
            
            public $plugins = [];
            
            public function init() {
                $this->initEmail();
            }
        };
        
        try {
            $testClass->init();
            
            if ($testClass->email !== null) {
                echo "✅ Email trait initialized successfully\n";
                echo "   - Email instance: " . get_class($testClass->email) . "\n";
                echo "   - Plugin registered: " . (isset($testClass->plugins['email']) ? 'Yes' : 'No') . "\n";
            } else {
                echo "❌ Email trait failed to initialize\n";
            }
        } catch (\Exception $e) {
            echo "❌ Email trait error: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
}

// Run the tests
if (php_sapi_name() === 'cli') {
    $runner = new TraitTestRunner();
    $runner->runTests();
}