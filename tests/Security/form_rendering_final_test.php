<?php
/**
 * Final Form Rendering Test - Comprehensive test for form rendering after security fixes
 */

require_once 'd:\worksites\mantra.smartfren.dev\packages\canvastack\canvastack\src\Library\Components\Form\Security\HtmlSanitizer.php';

use Canvastack\Canvastack\Library\Components\Form\Security\HtmlSanitizer;

echo "=== FINAL FORM RENDERING TEST ===\n\n";

// Simulate the exact draw method from Objects.php
function simulateDrawMethod($data) {
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

echo "1. Testing Typical Form Elements...\n";

// Test cases that match the UserController output
$testCases = [
    // Text input
    '<div class="form-group row"><label for="username" class="col-sm-3 col-form-label">Username <font class="required"><sup>(</sup><strong>*</strong><sup>)</sup></font></label><div class="input-group col-sm-9"><input name="username" type="text" class="form-control"></div></div>',
    
    // Select box
    '<div class="form-group row"><label for="active" class="col-sm-3 col-form-label">Active</label><div class="input-group col-sm-9"><select name="active" class="chosen-select-deselect chosen-selectbox form-control"><option value="1">Active</option><option value="0">Inactive</option></select></div></div>',
    
    // File input
    '<div class="form-group row"><label for="photo" class="col-sm-3 col-form-label">Photo</label><div class="input-group col-sm-9"><input name="photo" type="file" class="form-control"></div></div>',
    
    // Textarea
    '<div class="form-group row"><label for="address" class="col-sm-3 col-form-label">Address</label><div class="input-group col-sm-9"><textarea name="address" class="form-control ckeditor"></textarea></div></div>'
];

$passCount = 0;
$totalTests = count($testCases);

foreach ($testCases as $index => $testCase) {
    $result = simulateDrawMethod($testCase);
    $output = $result[0] ?? '';
    
    // Check if form structure is preserved
    $hasFormGroup = strpos($output, 'form-group') !== false;
    $hasLabel = strpos($output, '<label') !== false;
    $hasInput = strpos($output, '<input') !== false || strpos($output, '<select') !== false || strpos($output, '<textarea') !== false;
    $hasDiv = strpos($output, '<div') !== false;
    
    if ($hasFormGroup && $hasLabel && $hasInput && $hasDiv) {
        echo "   ✅ PASS: Test case " . ($index + 1) . " - Form structure preserved\n";
        $passCount++;
    } else {
        echo "   ❌ FAIL: Test case " . ($index + 1) . " - Form structure damaged\n";
        echo "   Expected: Complete form structure\n";
        echo "   Got: " . substr($output, 0, 150) . "...\n";
    }
}

echo "\n2. Testing XSS Protection in Form Elements...\n";

// Test malicious form elements
$maliciousTests = [
    '<div class="form-group row"><input type="text" onclick="alert(1)" name="test" class="form-control"></div>',
    '<div class="form-group row"><label onclick="alert(1)">Bad Label</label><input type="text" name="test"></div>',
    '<div class="form-group row"><input type="text" name="test"><script>alert("XSS")</script></div>'
];

$xssPassCount = 0;
$totalXssTests = count($maliciousTests);

foreach ($maliciousTests as $index => $testCase) {
    $result = simulateDrawMethod($testCase);
    $output = $result[0] ?? '';
    
    // Check if XSS is removed but form structure preserved
    $hasNoScript = strpos($output, '<script') === false;
    $hasNoOnclick = strpos($output, 'onclick') === false;
    $hasFormStructure = strpos($output, 'form-group') !== false;
    
    if ($hasNoScript && $hasNoOnclick && $hasFormStructure) {
        echo "   ✅ PASS: Malicious test " . ($index + 1) . " - XSS removed, structure preserved\n";
        $xssPassCount++;
    } else {
        echo "   ❌ FAIL: Malicious test " . ($index + 1) . " - XSS protection failed\n";
        echo "   Output: " . $output . "\n";
    }
}

echo "\n3. Testing Non-Form Content...\n";

// Test regular content (should be sanitized normally)
$regularTests = [
    'Plain text content',
    '<p>Safe paragraph</p>',
    '<script>alert("XSS")</script>Malicious content',
    '<b>Bold text</b>'
];

$regularPassCount = 0;
$totalRegularTests = count($regularTests);

foreach ($regularTests as $index => $testCase) {
    $result = simulateDrawMethod($testCase);
    $output = $result[0] ?? '';
    
    if ($index === 2) { // Malicious content test
        if (strpos($output, '<script') === false && strpos($output, 'Malicious content') !== false) {
            echo "   ✅ PASS: Regular malicious content properly sanitized\n";
            $regularPassCount++;
        } else {
            echo "   ❌ FAIL: Regular malicious content not sanitized\n";
        }
    } else { // Safe content tests
        if ($output === $testCase || (strpos($testCase, '<') !== false && strpos($output, strip_tags($testCase)) !== false)) {
            echo "   ✅ PASS: Regular safe content preserved\n";
            $regularPassCount++;
        } else {
            echo "   ❌ FAIL: Regular safe content modified unexpectedly\n";
            echo "   Expected: " . $testCase . "\n";
            echo "   Got: " . $output . "\n";
        }
    }
}

echo "\n=== FINAL TEST SUMMARY ===\n";
echo "Form Structure Tests: {$passCount}/{$totalTests} passed\n";
echo "XSS Protection Tests: {$xssPassCount}/{$totalXssTests} passed\n";
echo "Regular Content Tests: {$regularPassCount}/{$totalRegularTests} passed\n";

$totalPassed = $passCount + $xssPassCount + $regularPassCount;
$totalTests = $totalTests + $totalXssTests + $totalRegularTests;

echo "\nOverall: {$totalPassed}/{$totalTests} tests passed\n";

if ($totalPassed === $totalTests) {
    echo "\n🎉 ALL TESTS PASSED!\n";
    echo "✅ Form rendering is working correctly\n";
    echo "✅ XSS protection is working\n";
    echo "✅ UserController forms should now render properly\n";
} else {
    echo "\n⚠️  Some tests failed. Please review the implementation.\n";
}

echo "\n=== EXPECTED USERCONTROLLER OUTPUT ===\n";
echo "Forms should now render with proper HTML structure:\n";
echo "- Labels with proper styling\n";
echo "- Input fields with form-control classes\n";
echo "- Proper div structure with form-group classes\n";
echo "- Tab content should be visible\n";
echo "- No more plain text rendering\n";