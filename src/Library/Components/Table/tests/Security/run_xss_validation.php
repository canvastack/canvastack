<?php
/**
 * XSS Prevention Validation Runner
 * 
 * Quick validation script to test XSS prevention measures
 * Run this to verify all security fixes are working properly
 */

require_once __DIR__ . '/../../Craft/Security/JavaScriptSecurityHelper.php';

use Canvastack\Canvastack\Library\Components\Table\Craft\Security\JavaScriptSecurityHelper;

echo "🧪 XSS PREVENTION VALIDATION TESTS\n";
echo "==========================================\n\n";

$testsPassed = 0;
$testsTotal = 0;

/**
 * Test helper function
 */
function runTest($testName, $callback) {
    global $testsPassed, $testsTotal;
    $testsTotal++;
    
    echo "🔍 Testing: {$testName}...\n";
    
    try {
        $result = $callback();
        if ($result === true) {
            echo "   ✅ PASSED\n\n";
            $testsPassed++;
            return true;
        } else {
            echo "   ❌ FAILED: {$result}\n\n";
            return false;
        }
    } catch (Exception $e) {
        echo "   ❌ ERROR: " . $e->getMessage() . "\n\n";
        return false;
    }
}

// Test 1: CSRF Token Encoding
runTest("CSRF Token Encoding", function() {
    $maliciousToken = '</script><script>alert("XSS")</script>';
    $secureToken = JavaScriptSecurityHelper::encodeString($maliciousToken);
    
    if (strpos($secureToken, '<script') !== false) {
        return "Script tag not properly encoded";
    }
    if (strpos($secureToken, 'alert(') !== false) {
        return "Alert function not properly encoded";
    }
    
    return true;
});

// Test 2: JSON Security Flags
runTest("JSON Security Flags", function() {
    $testData = [
        'payload' => '</script><script>alert("XSS")</script>',
        'callback' => 'javascript:alert("XSS")'
    ];
    
    $secureJSON = JavaScriptSecurityHelper::encodeForJS($testData);
    
    if (strpos($secureJSON, '<script') !== false) {
        return "Script tag found in JSON output";
    }
    if (strpos($secureJSON, 'javascript:') !== false) {
        return "JavaScript URL found in JSON output";
    }
    
    return true;
});

// Test 3: ID Validation
runTest("ID Validation", function() {
    // Valid ID should work
    try {
        $result = JavaScriptSecurityHelper::encodeId('valid-table-id');
        if (!is_string($result)) {
            return "Valid ID encoding failed";
        }
    } catch (Exception $e) {
        return "Valid ID rejected: " . $e->getMessage();
    }
    
    // Invalid ID should throw exception
    try {
        JavaScriptSecurityHelper::encodeId('<script>alert("XSS")</script>');
        return "Invalid ID was accepted";
    } catch (InvalidArgumentException $e) {
        // Expected behavior
        return true;
    }
    
    return "Exception not thrown for invalid ID";
});

// Test 4: Configuration Sanitization
runTest("Configuration Sanitization", function() {
    $maliciousConfig = [
        'ajax' => [
            'url' => 'javascript:alert("XSS")'
        ],
        'columns' => [
            ['data' => 'normal'],
            ['data' => '</script><script>alert("XSS")</script>']
        ]
    ];
    
    $sanitized = JavaScriptSecurityHelper::sanitizeDataTableConfig($maliciousConfig);
    $serialized = serialize($sanitized);
    
    if (strpos($serialized, 'javascript:') !== false) {
        return "JavaScript URL not sanitized";
    }
    if (strpos($serialized, '<script') !== false) {
        return "Script tag not sanitized";
    }
    
    return true;
});

// Test 5: Secure AJAX Function Generation
runTest("Secure AJAX Function Generation", function() {
    $maliciousToken = '</script><script>alert("XSS")</script>';
    $function = JavaScriptSecurityHelper::createSecureAjaxDataFunction($maliciousToken);
    
    if (strpos($function, '<script') !== false) {
        return "Script tag found in AJAX function";
    }
    if (!strpos($function, 'function(data)')) {
        return "Function structure invalid";
    }
    
    return true;
});

// Test 6: Variable Name Validation
runTest("Variable Name Validation", function() {
    // Valid variable should work
    try {
        $result = JavaScriptSecurityHelper::createSafeVariable('validVar', 'test');
        if (!strpos($result, 'validVar = ')) {
            return "Valid variable creation failed";
        }
    } catch (Exception $e) {
        return "Valid variable rejected: " . $e->getMessage();
    }
    
    // Invalid variable should throw exception
    try {
        JavaScriptSecurityHelper::createSafeVariable('alert("XSS")', 'test');
        return "Invalid variable name was accepted";
    } catch (InvalidArgumentException $e) {
        // Expected behavior
        return true;
    }
    
    return "Exception not thrown for invalid variable name";
});

// Test 7: Console Log Security
runTest("Secure Console Log Generation", function() {
    $maliciousMessage = '</script><script>alert("XSS")</script>';
    $maliciousData = ['payload' => '<img src=x onerror=alert("XSS")>'];
    
    $secureLog = JavaScriptSecurityHelper::createSecureConsoleLog($maliciousMessage, $maliciousData);
    
    if (strpos($secureLog, '<script') !== false) {
        return "Script tag found in console log";
    }
    if (strpos($secureLog, 'onerror=') !== false) {
        return "Event handler found in console log";
    }
    if (!strpos($secureLog, 'console.log(')) {
        return "Console log structure invalid";
    }
    
    return true;
});

// Test 8: Backward Compatibility
runTest("Backward Compatibility", function() {
    $normalData = [
        'columns' => ['name', 'email'],
        'config' => ['paging' => true, 'searching' => true]
    ];
    
    $encoded = JavaScriptSecurityHelper::encodeForJS($normalData);
    $decoded = json_decode($encoded, true);
    
    if ($decoded === null) {
        return "JSON decoding failed";
    }
    if ($decoded['columns'] !== $normalData['columns']) {
        return "Column data corrupted";
    }
    if ($decoded['config'] !== $normalData['config']) {
        return "Config data corrupted";
    }
    
    return true;
});

// Display Results
echo "==========================================\n";
echo "📊 TEST RESULTS:\n";
echo "   ✅ Passed: {$testsPassed}/{$testsTotal}\n";

if ($testsPassed === $testsTotal) {
    echo "   🎉 ALL TESTS PASSED! XSS Prevention is working correctly.\n";
    echo "   🛡️ Security measures are properly implemented.\n";
} else {
    $failed = $testsTotal - $testsPassed;
    echo "   ❌ {$failed} tests failed. Please review implementation.\n";
}

echo "==========================================\n";

// Return exit code for automation
exit($testsPassed === $testsTotal ? 0 : 1);