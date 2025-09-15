<?php

require_once __DIR__ . '/../Security/FormAuthorizationService.php';

use Canvastack\Canvastack\Library\Components\Form\Security\FormAuthorizationService;

echo "=== VALIDATE RECORD ID TEST ===\n\n";

echo "Testing FormAuthorizationService::validateRecordId() method...\n\n";

$testCases = [
    // Valid cases
    ['1', 1, 'Valid positive integer'],
    ['123', 123, 'Valid large integer'],
    ['999999', 999999, 'Valid very large integer'],
    [1, 1, 'Valid integer input'],
    [123, 123, 'Valid integer input (large)'],
    
    // Invalid cases
    ['0', null, 'Zero should be invalid'],
    ['-1', null, 'Negative number should be invalid'],
    ['abc', null, 'Non-numeric string should be invalid'],
    ['', null, 'Empty string should be invalid'],
    [null, null, 'Null should be invalid'],
    ['1.5', null, 'Decimal should be invalid'],
    ['1e10', null, 'Scientific notation should be invalid'],
    ['999999999999999999999', null, 'Extremely large number should be invalid'],
    ['1; DROP TABLE users;', null, 'SQL injection attempt should be invalid'],
    ['../../../etc/passwd', null, 'Path traversal attempt should be invalid'],
    ['<script>alert(1)</script>', null, 'XSS attempt should be invalid'],
];

$passed = 0;
$total = count($testCases);

foreach ($testCases as $i => $test) {
    $input = $test[0];
    $expected = $test[1];
    $description = $test[2];
    
    $result = FormAuthorizationService::validateRecordId($input);
    
    $inputStr = is_null($input) ? 'null' : (is_string($input) ? "'{$input}'" : $input);
    $expectedStr = is_null($expected) ? 'null' : $expected;
    $resultStr = is_null($result) ? 'null' : $result;
    
    if ($result === $expected) {
        echo "✅ PASS: Test " . ($i + 1) . " - {$description}\n";
        echo "   Input: {$inputStr} → Output: {$resultStr}\n\n";
        $passed++;
    } else {
        echo "❌ FAIL: Test " . ($i + 1) . " - {$description}\n";
        echo "   Input: {$inputStr}\n";
        echo "   Expected: {$expectedStr}, Got: {$resultStr}\n\n";
    }
}

echo "=== TEST RESULTS ===\n";
echo "Passed: {$passed}/{$total} (" . round(($passed / $total) * 100, 1) . "%)\n\n";

if ($passed === $total) {
    echo "🎉 ALL TESTS PASSED!\n";
    echo "✅ validateRecordId() method is working correctly\n";
    echo "🛡️ Security validation is properly implemented\n\n";
    
    echo "🔒 SECURITY FEATURES VERIFIED:\n";
    echo "• ✅ Positive integer validation\n";
    echo "• ✅ Non-numeric input rejection\n";
    echo "• ✅ Negative number rejection\n";
    echo "• ✅ Zero value rejection\n";
    echo "• ✅ Empty/null input handling\n";
    echo "• ✅ SQL injection prevention\n";
    echo "• ✅ XSS attempt blocking\n";
    echo "• ✅ Path traversal prevention\n";
    echo "• ✅ Integer overflow protection\n";
} else {
    echo "⚠️ SOME TESTS FAILED!\n";
    echo "Please review the validateRecordId() implementation\n";
}

echo "\n=== VALIDATE RECORD ID TEST COMPLETE ===\n";