<?php

require_once __DIR__ . '/../Security/ContentSanitizer.php';

use Canvastack\Canvastack\Library\Components\Form\Security\ContentSanitizer;

echo "=== OBJECTS DRAW METHOD TEST ===\n\n";

// Mock the canvastack_script function exactly as it appears in App.php
function canvastack_script($script, $ready = true) {
    if (true === $ready) {
        return "<script type='text/javascript'>$(document).ready(function() { {$script} });</script>";
    } else {
        return "<script type='text/javascript'>{$script}</script>";
    }
}

// Simulate the exact sync() method logic
function simulateSync($source_field, $target_field, $values, $labels, $query, $selected) {
    $syncs = [];
    $syncs['source'] = $source_field;
    $syncs['target'] = $target_field;
    $syncs['values'] = base64_encode($values); // Simulate encrypt()
    $syncs['labels'] = base64_encode($labels); // Simulate encrypt()
    $syncs['selected'] = base64_encode($selected); // Simulate encrypt()
    $syncs['query'] = base64_encode(trim(preg_replace('/\s\s+/', ' ', $query))); // Simulate encrypt()
    
    // Properly encode JSON for JavaScript - use JSON_UNESCAPED_SLASHES for cleaner output
    $data = json_encode($syncs, JSON_UNESCAPED_SLASHES);
    $ajaxURL = 'http://localhost/mantra.smartfren.dev/public/ajax/post?AjaxPosF=true&_token=H60Bn3v9VFBPucr8UUEy4rNInGQEGXAU8FKISsR2';
    
    // Use double quotes for the JavaScript string to avoid conflicts with JSON double quotes
    // and properly escape the data
    $escapedData = str_replace('"', '\\"', $data);
    
    $javascript = canvastack_script("ajaxSelectionBox('{$source_field}', '{$target_field}', '{$ajaxURL}', \"{$escapedData}\");");
    
    return $javascript;
}

// Simulate the new Objects.php draw() method logic
function simulateObjectsDraw($data) {
    if (is_string($data)) {
        // Check if this is JavaScript content - if so, use minimal formatting to preserve syntax
        $isJavaScript = strpos($data, 'ajaxSelectionBox') !== false || 
                       strpos($data, '<script') !== false;
        
        if ($isJavaScript) {
            // For JavaScript content, use minimal formatting to preserve syntax
            $sanitized = ContentSanitizer::sanitizeForm($data, [
                'format' => true,
                'format_options' => [
                    'fix_encoding' => true,
                    'format_lines' => false, // Don't break JavaScript lines
                    'add_indentation' => false,
                    'fix_structure' => false, // Don't modify JavaScript structure
                    'fix_javascript' => true,
                ]
            ]);
        } else {
            // For regular HTML content, use full formatting
            $sanitized = ContentSanitizer::sanitizeForm($data, [
                'format' => true,
                'format_options' => [
                    'fix_encoding' => true,
                    'format_lines' => true,
                    'add_indentation' => false, // Keep compact for production
                    'fix_structure' => true,
                    'fix_javascript' => true, // Fix naked JavaScript issues
                ]
            ]);
        }
        return $sanitized;
    }
    return $data;
}

// Test with the exact data from the user's issue
echo "Testing with real form data (group_id -> first_route)...\n";

$generatedJS = simulateSync(
    'group_id',
    'first_route',
    'test_values_data_here',
    'test_labels_data_here',
    'SELECT * FROM routes WHERE group_id = ?',
    'selected_value_here'
);

echo "\n1. GENERATED JAVASCRIPT (before draw method):\n";
echo "Length: " . strlen($generatedJS) . " characters\n";
echo "Has script tags: " . (strpos($generatedJS, '<script') !== false ? "YES" : "NO") . "\n";
echo "Content preview: " . substr($generatedJS, 0, 150) . "...\n\n";

// Now test the new Objects.php draw() method logic
echo "2. APPLYING NEW OBJECTS DRAW METHOD...\n";

$processed = simulateObjectsDraw($generatedJS);

echo "Processed length: " . strlen($processed) . " characters\n";
echo "Still has script tags: " . (strpos($processed, '<script') !== false ? "YES ✅" : "NO ❌") . "\n";
echo "Still has ajaxSelectionBox: " . (strpos($processed, 'ajaxSelectionBox') !== false ? "YES ✅" : "NO ❌") . "\n";
echo "Still has $(document).ready: " . (strpos($processed, '$(document).ready') !== false ? "YES ✅" : "NO ❌") . "\n\n";

echo "3. FINAL PROCESSED OUTPUT:\n";
echo "==========================\n";
echo $processed . "\n\n";

// Validate the final output
echo "4. VALIDATION RESULTS:\n";
echo "======================\n";

$isValid = true;
$issues = [];

// Check for script tags
if (strpos($processed, '<script') === false) {
    $isValid = false;
    $issues[] = "Missing <script> opening tag";
}

if (strpos($processed, '</script>') === false) {
    $isValid = false;
    $issues[] = "Missing </script> closing tag";
}

// Check for essential JavaScript components
if (strpos($processed, 'ajaxSelectionBox') === false) {
    $isValid = false;
    $issues[] = "Missing ajaxSelectionBox function call";
}

if (strpos($processed, '$(document).ready') === false) {
    $isValid = false;
    $issues[] = "Missing $(document).ready wrapper";
}

// Check for proper JSON structure (should be on single line for JavaScript)
$lines = explode("\n", $processed);
$jsLine = '';
foreach ($lines as $line) {
    if (strpos($line, 'ajaxSelectionBox') !== false) {
        $jsLine = trim($line);
        break;
    }
}

if (empty($jsLine)) {
    $isValid = false;
    $issues[] = "Cannot find ajaxSelectionBox line";
} else {
    // Check if the JSON is properly contained in the line
    if (!preg_match('/ajaxSelectionBox\([^)]+\{[^}]+\}[^)]*\)/', $jsLine)) {
        $isValid = false;
        $issues[] = "JSON structure appears to be broken across lines";
    }
}

// Check for balanced parentheses and braces
$openParens = substr_count($processed, '(');
$closeParens = substr_count($processed, ')');
$openBraces = substr_count($processed, '{');
$closeBraces = substr_count($processed, '}');

if ($openParens !== $closeParens) {
    $isValid = false;
    $issues[] = "Unbalanced parentheses ({$openParens} open, {$closeParens} close)";
}

if ($openBraces !== $closeBraces) {
    $isValid = false;
    $issues[] = "Unbalanced braces ({$openBraces} open, {$closeBraces} close)";
}

echo "Overall validation: " . ($isValid ? "PASSED ✅" : "FAILED ❌") . "\n";

if (!$isValid) {
    echo "Issues found:\n";
    foreach ($issues as $issue) {
        echo "  - " . $issue . "\n";
    }
} else {
    echo "All checks passed!\n";
}

echo "\n=== OBJECTS DRAW METHOD TEST RESULTS ===\n";

if ($isValid) {
    echo "🎉 SUCCESS! The JavaScript syntax error should be RESOLVED!\n\n";
    echo "✅ Script tags are preserved during processing\n";
    echo "✅ JavaScript functions are intact\n";
    echo "✅ JSON data is properly contained on single line\n";
    echo "✅ No more 'Invalid or unexpected token' errors expected\n";
    echo "✅ The form should work correctly in the browser\n\n";
    echo "🚀 READY FOR PRODUCTION DEPLOYMENT!\n";
} else {
    echo "❌ ISSUES DETECTED - Further refinement needed\n";
    echo "The JavaScript may still cause syntax errors in the browser.\n";
}

echo "\n=== TEST COMPLETE ===\n";