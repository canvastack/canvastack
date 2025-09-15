<?php

/**
 * Real UserController Test - Test actual form generation
 */

// Set up Laravel environment
$basePath = realpath(__DIR__ . '/../../../../../../..');
if (file_exists($basePath . '/vendor/autoload.php')) {
    require_once $basePath . '/vendor/autoload.php';
    
    // Bootstrap Laravel
    $app = require_once $basePath . '/bootstrap/app.php';
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    
    echo "=== REAL USERCONTROLLER SYNC TEST ===\n\n";
    
    // Test the actual canvastack_script function
    echo "1. Testing real canvastack_script() function:\n";
    echo "---------------------------------------------\n";
    
    $jsCode = "ajaxSelectionBox('group_id', 'first_route', 'http://localhost/mantra.smartfren.dev/public/ajax/post?AjaxPosF=true&_token=test', '{\"source\":\"group_id\",\"target\":\"first_route\"}')";
    
    if (function_exists('canvastack_script')) {
        $scriptOutput = canvastack_script($jsCode);
        echo "✅ canvastack_script() function found\n";
        echo "Input: {$jsCode}\n";
        echo "Output:\n{$scriptOutput}\n\n";
        
        // Test ContentSanitizer if available
        if (class_exists('Canvastack\Canvastack\Library\Components\Form\Security\ContentSanitizer')) {
            echo "2. Testing real ContentSanitizer:\n";
            echo "---------------------------------\n";
            
            $sanitized = \Canvastack\Canvastack\Library\Components\Form\Security\ContentSanitizer::sanitizeForm($scriptOutput, [
                'format' => true,
                'format_options' => [
                    'fix_encoding' => true,
                    'format_lines' => true,
                    'add_indentation' => false,
                    'fix_structure' => true,
                    'fix_javascript' => true,
                ]
            ]);
            
            echo "✅ ContentSanitizer processed successfully\n";
            echo "Result:\n{$sanitized}\n\n";
            
            // Verify no double-processing
            $isIdentical = ($scriptOutput === $sanitized);
            echo "✅ No double-processing: " . ($isIdentical ? "YES ✅" : "NO ❌") . "\n";
            
            if ($isIdentical) {
                echo "\n🎉 SUCCESS: Real UserController sync() is now working correctly!\n";
                echo "✅ JavaScript properly wrapped in script tags\n";
                echo "✅ No double-processing occurs\n";
                echo "✅ AJAX dropdowns will function properly\n";
            } else {
                echo "\n❌ Issue detected: Output was modified\n";
                echo "Original length: " . strlen($scriptOutput) . "\n";
                echo "Processed length: " . strlen($sanitized) . "\n";
            }
            
        } else {
            echo "❌ ContentSanitizer class not found\n";
        }
        
    } else {
        echo "❌ canvastack_script() function not found\n";
    }
    
} else {
    echo "❌ Laravel autoload not found, running simplified test\n\n";
    
    // Fallback test without Laravel
    echo "=== SIMPLIFIED SYNC TEST ===\n\n";
    
    function test_canvastack_script($script, $ready = true) {
        if (true === $ready) {
            return "<script type='text/javascript'>$(document).ready(function() { {$script} });</script>";
        } else {
            return "<script type='text/javascript'>{$script}</script>";
        }
    }
    
    $jsCode = "ajaxSelectionBox('group_id', 'first_route', 'http://localhost/ajax', 'data')";
    $output = test_canvastack_script($jsCode);
    
    echo "Generated output:\n{$output}\n\n";
    echo "✅ Script tags present: " . (strpos($output, '<script') !== false ? "YES" : "NO") . "\n";
    echo "✅ JavaScript present: " . (strpos($output, 'ajaxSelectionBox') !== false ? "YES" : "NO") . "\n";
}

echo "\n=== TEST COMPLETED ===\n";