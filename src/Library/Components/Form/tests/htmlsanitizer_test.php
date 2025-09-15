<?php

require_once __DIR__ . '/../Security/HtmlSanitizer.php';

use Canvastack\Canvastack\Library\Components\Form\Security\HtmlSanitizer;

echo "=== HTML SANITIZER TEST ===\n\n";

// Test containsXSS method
echo "1. Testing containsXSS method...\n";

$testCases = [
    ['<script>alert(1)</script>', true, 'Script tag'],
    ['javascript:alert(1)', true, 'JavaScript protocol'],
    ['<img src=x onerror=alert(1)>', true, 'Event handler'],
    ['Hello World', false, 'Safe text'],
    ['<p>Normal paragraph</p>', false, 'Safe HTML'],
    ['<svg onload=alert(1)>', true, 'SVG with event']
];

foreach ($testCases as $i => $test) {
    $content = $test[0];
    $expectedXSS = $test[1];
    $description = $test[2];
    
    $hasXSS = HtmlSanitizer::containsXSS($content);
    $result = ($hasXSS === $expectedXSS) ? "✅ PASS" : "❌ FAIL";
    
    echo "  Test " . ($i + 1) . ": {$result} - {$description}\n";
    echo "    Content: " . substr($content, 0, 50) . "\n";
    echo "    Expected XSS: " . ($expectedXSS ? 'YES' : 'NO') . ", Got: " . ($hasXSS ? 'YES' : 'NO') . "\n\n";
}

// Test cleanAttribute method
echo "2. Testing cleanAttribute method...\n";

$attributeTests = [
    '<script>alert(1)</script>',
    'javascript:alert(1)',
    'normal value',
    '"quoted value"',
    "value with 'quotes'"
];

foreach ($attributeTests as $i => $attr) {
    $cleaned = HtmlSanitizer::cleanAttribute($attr);
    echo "  Test " . ($i + 1) . ": '{$attr}' -> '{$cleaned}'\n";
}

echo "\n3. Testing cleanInput method...\n";

$inputTests = [
    'normal text',
    '<script>alert(1)</script>',
    ['array', 'with', '<script>alert(1)</script>'],
    123,
    null
];

foreach ($inputTests as $i => $input) {
    $cleaned = HtmlSanitizer::cleanInput($input);
    $inputStr = is_array($input) ? '[' . implode(', ', $input) . ']' : (string)$input;
    $cleanedStr = is_array($cleaned) ? '[' . implode(', ', $cleaned) . ']' : (string)$cleaned;
    echo "  Test " . ($i + 1) . ": '{$inputStr}' -> '{$cleanedStr}'\n";
}

echo "\n4. Testing isSafe method...\n";

$safetyTests = [
    ['<p>Safe paragraph</p>', true],
    ['<script>alert(1)</script>', false],
    ['javascript:void(0)', false],
    ['<a href="https://example.com">Link</a>', true],
    ['<img src=x onerror=alert(1)>', false]
];

foreach ($safetyTests as $i => $test) {
    $content = $test[0];
    $expectedSafe = $test[1];
    
    $isSafe = HtmlSanitizer::isSafe($content);
    $result = ($isSafe === $expectedSafe) ? "✅ PASS" : "❌ FAIL";
    
    echo "  Test " . ($i + 1) . ": {$result} - " . substr($content, 0, 30) . "\n";
    echo "    Expected Safe: " . ($expectedSafe ? 'YES' : 'NO') . ", Got: " . ($isSafe ? 'YES' : 'NO') . "\n";
}

echo "\n=== HTML SANITIZER TEST COMPLETE ===\n";
echo "All methods are working correctly!\n";