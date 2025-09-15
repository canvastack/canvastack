<?php
/**
 * Form Rendering Test - Ensure forms render correctly after security hardening
 */

require_once 'd:\worksites\mantra.smartfren.dev\vendor\autoload.php';

use Canvastack\Canvastack\Library\Components\Form\Objects;

echo "=== FORM RENDERING TEST ===\n\n";

// Test 1: Form structure HTML should be preserved
echo "1. Testing Form Structure Preservation...\n";
try {
    $form = new Objects();
    
    // Test form group HTML (typical form structure)
    $formGroupHtml = '<div class="form-group row"><label for="test">Test Label</label><input type="text" name="test" class="form-control"></div>';
    
    $form->draw($formGroupHtml);
    $elements = $form->elements ?? [];
    $lastElement = end($elements);
    
    if (strpos($lastElement, '<div class="form-group') !== false && 
        strpos($lastElement, '<input') !== false) {
        echo "   ✅ PASS: Form structure HTML preserved\n";
    } else {
        echo "   ❌ FAIL: Form structure HTML was sanitized\n";
        echo "   Expected: Contains form elements\n";
        echo "   Got: " . substr($lastElement, 0, 100) . "...\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ FAIL: " . $e->getMessage() . "\n";
}

// Test 2: Malicious scripts in form structure should be removed
echo "\n2. Testing XSS Protection in Form Structure...\n";
try {
    $form = new Objects();
    
    // Test malicious form HTML
    $maliciousFormHtml = '<div class="form-group row"><label for="test">Test</label><input type="text" name="test" onclick="alert(1)" class="form-control"><script>alert("XSS")</script></div>';
    
    $form->draw($maliciousFormHtml);
    $elements = $form->elements ?? [];
    $lastElement = end($elements);
    
    if (strpos($lastElement, '<script') === false && 
        strpos($lastElement, 'onclick') === false &&
        strpos($lastElement, '<input') !== false) {
        echo "   ✅ PASS: Malicious scripts removed, form structure preserved\n";
    } else {
        echo "   ❌ FAIL: XSS protection not working properly\n";
        echo "   Got: " . substr($lastElement, 0, 200) . "...\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ FAIL: " . $e->getMessage() . "\n";
}

// Test 3: Regular content should still be sanitized
echo "\n3. Testing Regular Content Sanitization...\n";
try {
    $form = new Objects();
    
    // Test regular malicious content (not form structure)
    $maliciousContent = '<script>alert("XSS")</script>Hello World';
    
    $form->draw($maliciousContent);
    $elements = $form->elements ?? [];
    $lastElement = end($elements);
    
    if (strpos($lastElement, '<script') === false && 
        strpos($lastElement, 'Hello World') !== false) {
        echo "   ✅ PASS: Regular content properly sanitized\n";
    } else {
        echo "   ❌ FAIL: Regular content sanitization not working\n";
        echo "   Got: " . $lastElement . "\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ FAIL: " . $e->getMessage() . "\n";
}

// Test 4: Safe content should be preserved
echo "\n4. Testing Safe Content Preservation...\n";
try {
    $form = new Objects();
    
    // Test safe content
    $safeContent = '<p>This is safe content</p>';
    
    $form->draw($safeContent);
    $elements = $form->elements ?? [];
    $lastElement = end($elements);
    
    if ($lastElement === $safeContent) {
        echo "   ✅ PASS: Safe content preserved\n";
    } else {
        echo "   ❌ FAIL: Safe content was modified\n";
        echo "   Expected: " . $safeContent . "\n";
        echo "   Got: " . $lastElement . "\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ FAIL: " . $e->getMessage() . "\n";
}

echo "\n=== FORM RENDERING TEST SUMMARY ===\n";
echo "✅ Form structure HTML should be preserved\n";
echo "✅ XSS protection should work on form elements\n";
echo "✅ Regular content sanitization should work\n";
echo "✅ Safe content should be preserved\n";

echo "\n=== EXPECTED RESULT ===\n";
echo "🎯 Forms should render correctly with proper HTML structure\n";
echo "🔒 Security protection should still work for malicious content\n";
echo "⚖️  Balance between security and functionality achieved\n";