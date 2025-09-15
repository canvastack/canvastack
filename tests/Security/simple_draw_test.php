<?php
/**
 * Simple Draw Method Test
 */

require_once 'd:\worksites\mantra.smartfren.dev\packages\canvastack\canvastack\src\Library\Components\Form\Security\HtmlSanitizer.php';

use Canvastack\Canvastack\Library\Components\Form\Security\HtmlSanitizer;

echo "=== SIMPLE DRAW METHOD TEST ===\n\n";

// Simulate the draw method logic
function testDraw($data) {
    $elements = [];
    
    if ($data) {
        // Only sanitize user input, not form structure HTML
        if (is_string($data)) {
            // Check if this is form structure HTML (contains form elements)
            $isFormStructure = preg_match('/<div[^>]*class="[^"]*form-group[^"]*"[^>]*>/', $data) ||
                             preg_match('/<input[^>]*>/', $data) ||
                             preg_match('/<select[^>]*>/', $data) ||
                             preg_match('/<textarea[^>]*>/', $data) ||
                             preg_match('/<label[^>]*>/', $data);
            
            if ($isFormStructure) {
                // For form structure, only sanitize dangerous scripts but preserve form HTML
                if (preg_match('/<script[^>]*>|javascript:|on\w+\s*=/i', $data)) {
                    HtmlSanitizer::logXSSAttempt($data, 'form_structure');
                    $sanitized = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $data);
                    $sanitized = preg_replace('/javascript:/i', '', $sanitized);
                    $sanitized = preg_replace('/on\w+\s*=[^>]*/i', '', $sanitized);
                    $elements[] = $sanitized;
                } else {
                    // Safe form structure, add as-is
                    $elements[] = $data;
                }
            } else {
                // Regular content, apply full XSS protection
                if (HtmlSanitizer::containsXSS($data)) {
                    HtmlSanitizer::logXSSAttempt($data, 'form_draw');
                    $sanitized = HtmlSanitizer::clean($data);
                    $elements[] = $sanitized;
                } else {
                    $elements[] = $data;
                }
            }
        } else {
            // For non-strings, add as-is
            $elements[] = $data;
        }
    }
    
    return $elements;
}

// Test 1: Form structure HTML
echo "1. Testing Form Structure HTML...\n";
$formHtml = '<div class="form-group row"><label for="username">Username</label><input type="text" name="username" class="form-control"></div>';
$result = testDraw($formHtml);
$output = $result[0] ?? '';

if (strpos($output, '<div class="form-group') !== false && strpos($output, '<input') !== false) {
    echo "   ✅ PASS: Form structure preserved\n";
    echo "   Output: " . substr($output, 0, 100) . "...\n";
} else {
    echo "   ❌ FAIL: Form structure not preserved\n";
    echo "   Output: " . $output . "\n";
}

// Test 2: Form structure with XSS
echo "\n2. Testing Form Structure with XSS...\n";
$maliciousFormHtml = '<div class="form-group row"><input type="text" onclick="alert(1)" name="test"><script>alert("XSS")</script></div>';
$result = testDraw($maliciousFormHtml);
$output = $result[0] ?? '';

if (strpos($output, '<script') === false && strpos($output, 'onclick') === false && strpos($output, '<input') !== false) {
    echo "   ✅ PASS: XSS removed, form structure preserved\n";
    echo "   Output: " . $output . "\n";
} else {
    echo "   ❌ FAIL: XSS not properly handled\n";
    echo "   Output: " . $output . "\n";
}

// Test 3: Regular content
echo "\n3. Testing Regular Content...\n";
$regularContent = 'Just plain text';
$result = testDraw($regularContent);
$output = $result[0] ?? '';

if ($output === $regularContent) {
    echo "   ✅ PASS: Regular content preserved\n";
} else {
    echo "   ❌ FAIL: Regular content modified\n";
    echo "   Expected: " . $regularContent . "\n";
    echo "   Got: " . $output . "\n";
}

echo "\n=== TEST SUMMARY ===\n";
echo "The draw method should now properly handle form structure HTML\n";
echo "while still providing XSS protection for malicious content.\n";