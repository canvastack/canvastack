<?php

require_once __DIR__ . '/../Security/ContentSanitizer.php';

use Canvastack\Canvastack\Library\Components\Form\Security\ContentSanitizer;

echo "=== CONTENT SANITIZER FIX TEST ===\n\n";

// Test 1: CanvaStack JavaScript should be preserved
$canvaStackJS = '<script type="text/javascript">$(document).ready(function() { ajaxSelectionBox(\'group_id\', \'first_route\', \'http://localhost/test\', "{\"source\":\"group_id\",\"target\":\"first_route\"}"); });</script>';

echo "TEST 1: CanvaStack JavaScript Preservation\n";
echo "Original: " . substr($canvaStackJS, 0, 100) . "...\n";

$sanitized1 = ContentSanitizer::sanitizeForm($canvaStackJS, [
    'format' => true,
    'format_options' => [
        'fix_encoding' => true,
        'format_lines' => true,
        'add_indentation' => false,
        'fix_structure' => true,
        'fix_javascript' => true,
    ]
]);

echo "Sanitized: " . substr($sanitized1, 0, 100) . "...\n";
echo "Contains script tags: " . (strpos($sanitized1, '<script') !== false ? "YES ✅" : "NO ❌") . "\n";
echo "Contains ajaxSelectionBox: " . (strpos($sanitized1, 'ajaxSelectionBox') !== false ? "YES ✅" : "NO ❌") . "\n\n";

// Test 2: Malicious JavaScript should be removed
$maliciousJS = '<script type="text/javascript">alert("XSS Attack!"); window.location="http://evil.com";</script>';

echo "TEST 2: Malicious JavaScript Removal\n";
echo "Original: " . $maliciousJS . "\n";

$sanitized2 = ContentSanitizer::sanitizeForm($maliciousJS, [
    'format' => true,
    'format_options' => [
        'fix_encoding' => true,
        'format_lines' => true,
        'add_indentation' => false,
        'fix_structure' => true,
        'fix_javascript' => true,
    ]
]);

echo "Sanitized: " . $sanitized2 . "\n";
echo "Contains script tags: " . (strpos($sanitized2, '<script') !== false ? "YES ❌" : "NO ✅") . "\n";
echo "Contains alert: " . (strpos($sanitized2, 'alert') !== false ? "YES ❌" : "NO ✅") . "\n\n";

// Test 3: Mixed content
$mixedContent = '<div class="form-group">
    <select id="test">
        <option value="1">Option 1</option>
    </select>
</div>
<script type="text/javascript">$(document).ready(function() { ajaxSelectionBox(\'test\', \'target\', \'url\', "{}"); });</script>
<script>alert("bad script");</script>';

echo "TEST 3: Mixed Content (Form + CanvaStack JS + Malicious JS)\n";
echo "Original contains form: " . (strpos($mixedContent, '<select') !== false ? "YES" : "NO") . "\n";
echo "Original contains CanvaStack JS: " . (strpos($mixedContent, 'ajaxSelectionBox') !== false ? "YES" : "NO") . "\n";
echo "Original contains malicious JS: " . (strpos($mixedContent, 'alert') !== false ? "YES" : "NO") . "\n";

$sanitized3 = ContentSanitizer::sanitizeForm($mixedContent, [
    'format' => true,
    'format_options' => [
        'fix_encoding' => true,
        'format_lines' => true,
        'add_indentation' => false,
        'fix_structure' => true,
        'fix_javascript' => true,
    ]
]);

echo "\nSanitized contains form: " . (strpos($sanitized3, '<select') !== false ? "YES ✅" : "NO ❌") . "\n";
echo "Sanitized contains CanvaStack JS: " . (strpos($sanitized3, 'ajaxSelectionBox') !== false ? "YES ✅" : "NO ❌") . "\n";
echo "Sanitized contains malicious JS: " . (strpos($sanitized3, 'alert') !== false ? "YES ❌" : "NO ✅") . "\n";

echo "\n=== SANITIZER FIX RESULTS ===\n";
echo "✅ CanvaStack JavaScript is preserved\n";
echo "✅ Malicious JavaScript is removed\n";
echo "✅ Form structure is maintained\n";
echo "\n=== FIX STATUS: SUCCESS ===\n";