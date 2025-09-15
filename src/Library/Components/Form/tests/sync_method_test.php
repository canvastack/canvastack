<?php

// Include the necessary files
require_once __DIR__ . '/../Objects.php';
require_once __DIR__ . '/../../../../Helpers/App.php';

// Mock the necessary functions if they don't exist
if (!function_exists('encrypt')) {
    function encrypt($value) {
        return base64_encode($value);
    }
}

if (!function_exists('canvastack_get_ajax_urli')) {
    function canvastack_get_ajax_urli() {
        return 'http://localhost/mantra.smartfren.dev/public/ajax/post?AjaxPosF=true&_token=test123';
    }
}

if (!function_exists('canvastack_script')) {
    function canvastack_script($script, $ready = true) {
        if (true === $ready) {
            return "<script type='text/javascript'>$(document).ready(function() { {$script} });</script>";
        } else {
            return "<script type='text/javascript'>{$script}</script>";
        }
    }
}

use Canvastack\Canvastack\Library\Components\Form\Objects;

echo "=== SYNC METHOD TEST ===\n\n";

// Create an instance of Objects
$objects = new Objects();

// Test the sync method with sample data
echo "Testing sync() method with sample data...\n";

// Capture output
ob_start();

try {
    $objects->sync(
        'group_id',
        'first_route', 
        'test_values',
        'test_labels',
        'SELECT * FROM routes WHERE group_id = ?',
        'selected_value'
    );
    
    // Get the generated output
    $output = ob_get_clean();
    
    echo "Generated JavaScript:\n";
    echo "Length: " . strlen($output) . " characters\n";
    echo "Contains script tags: " . (strpos($output, '<script') !== false ? "YES ✅" : "NO ❌") . "\n";
    echo "Contains ajaxSelectionBox: " . (strpos($output, 'ajaxSelectionBox') !== false ? "YES ✅" : "NO ❌") . "\n";
    echo "Contains proper JSON: " . (strpos($output, '{"source"') !== false ? "YES ✅" : "NO ❌") . "\n";
    
    // Check for syntax issues
    $hasUnescapedQuotes = preg_match('/[^\\\\]"[^,}]/', $output);
    echo "Has unescaped quotes: " . ($hasUnescapedQuotes ? "YES ❌" : "NO ✅") . "\n";
    
    echo "\nFirst 200 characters of output:\n";
    echo substr($output, 0, 200) . "...\n";
    
    echo "\n=== SYNC METHOD TEST RESULTS ===\n";
    if (strpos($output, '<script') !== false && 
        strpos($output, 'ajaxSelectionBox') !== false && 
        !$hasUnescapedQuotes) {
        echo "✅ SUCCESS: sync() method generates valid JavaScript\n";
    } else {
        echo "❌ FAILURE: sync() method has issues\n";
    }
    
} catch (Exception $e) {
    ob_end_clean();
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== TEST COMPLETE ===\n";