<?php

/**
 * Debug test to trace the exact pipeline from sync() to browser
 */

echo "=== DEBUG PIPELINE TEST ===\n\n";

// Step 1: Simulate canvastack_script() output
function canvastack_script($js) {
    return "<script type=\"text/javascript\">\n$(document).ready(function() { $js });\n</script>";
}

$rawJs = "ajaxSelectionBox('group_id', 'first_route', 'http://localhost/mantra.smartfren.dev/public/ajax/post?AjaxPosF=true&_token=H60Bn3v9VFBPucr8UUEy4rNInGQEGXAU8FKISsR2', '{\"source\":\"group_id\",\"target\":\"first_route\",\"values\":\"eyJpdiI6ImFNcUFuTm55U3VIcTRvYWVxcnJmNUE9PSIsInZhbHVlIjoiMGk4TVBQZ...\"}');";

echo "STEP 1: canvastack_script() output\n";
$scriptOutput = canvastack_script($rawJs);
echo "Length: " . strlen($scriptOutput) . " chars\n";
echo "Has <script> tags: " . (strpos($scriptOutput, '<script') !== false ? "YES" : "NO") . "\n";
echo "First 200 chars: " . substr($scriptOutput, 0, 200) . "...\n\n";

// Step 2: Objects.php draw() method
require_once __DIR__ . '/../Security/ContentSanitizer.php';

echo "STEP 2: Objects.php draw() method processing\n";
$drawOptions = [
    'format' => true,
    'format_options' => [
        'fix_encoding' => true,
        'format_lines' => true,
        'add_indentation' => false,
        'fix_structure' => true,
        'fix_javascript' => true,
    ]
];

$sanitized = \Canvastack\Canvastack\Library\Components\Form\Security\ContentSanitizer::sanitizeForm($scriptOutput, $drawOptions);

echo "After ContentSanitizer::sanitizeForm():\n";
echo "Length: " . strlen($sanitized) . " chars\n";
echo "Has <script> tags: " . (strpos($sanitized, '<script') !== false ? "YES" : "NO") . "\n";
echo "Has naked JavaScript: " . (preg_match('/\$\(document\)\.ready[^<]*[^>]/', $sanitized) ? "YES (PROBLEM!)" : "NO (GOOD!)") . "\n";
echo "First 200 chars: " . substr($sanitized, 0, 200) . "...\n\n";

// Step 3: Check if the issue is in the JavaScript content itself
echo "STEP 3: Analyzing JavaScript content\n";
if (preg_match('/\$\(document\)\.ready\(function\(\) \{ (.+?) \}\);/', $sanitized, $matches)) {
    $jsContent = $matches[1];
    echo "Extracted JS content: " . substr($jsContent, 0, 100) . "...\n";
    
    // Check for syntax issues
    $hasUnescapedQuotes = preg_match('/[^\\\\]"[^"]*"[^"]*"/', $jsContent);
    $hasUnescapedJson = preg_match('/\{[^}]*"[^"]*:[^}]*\}/', $jsContent);
    
    echo "Has unescaped quotes: " . ($hasUnescapedQuotes ? "YES (SYNTAX ERROR!)" : "NO") . "\n";
    echo "Has JSON in JS: " . ($hasUnescapedJson ? "YES (POTENTIAL ISSUE!)" : "NO") . "\n";
}

// Step 4: Test with the exact problematic content from browser
echo "\nSTEP 4: Testing with exact browser content\n";
$browserContent = '{"source":"group_id","target":"first_route","values":"eyJpdiI6ImFNcUFuTm55U3VIcTRvYWVxcnJmNUE9PSIsInZhbHVlIjoiMGk4TVBQZ';

echo "Browser content (first 100 chars): " . substr($browserContent, 0, 100) . "\n";
echo "Is this naked JSON?: " . (preg_match('/^\{.*/', $browserContent) ? "YES (PROBLEM!)" : "NO") . "\n";

// This suggests the issue might be in how the JavaScript is being generated or escaped
echo "\n=== DIAGNOSIS ===\n";
if (strpos($sanitized, '<script') !== false) {
    echo "✅ FormFormatter is working - JavaScript is wrapped\n";
    echo "❌ But there might be a syntax error in the JavaScript content\n";
    echo "🔍 The issue is likely in the JSON escaping within the JavaScript\n";
    echo "💡 Need to check how ajaxSelectionBox parameters are escaped\n";
} else {
    echo "❌ FormFormatter is not working - JavaScript is not wrapped\n";
    echo "🔍 Need to debug why FormFormatter::fixJavaScript() is not being called\n";
}

echo "\n=== NEXT STEPS ===\n";
echo "1. Check if the JSON data in ajaxSelectionBox is properly escaped\n";
echo "2. Verify that quotes in the JavaScript parameters are handled correctly\n";
echo "3. Test with simpler JavaScript to isolate the issue\n";

?>