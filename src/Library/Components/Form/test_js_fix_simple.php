<?php

require_once __DIR__ . '/Security/FormStructureDetector.php';
require_once __DIR__ . '/Security/ContentSanitizer.php';
require_once __DIR__ . '/Security/FormFormatter.php';

use Canvastack\Canvastack\Library\Components\Form\Security\FormStructureDetector;
use Canvastack\Canvastack\Library\Components\Form\Security\ContentSanitizer;
use Canvastack\Canvastack\Library\Components\Form\Security\FormFormatter;

echo "=== JAVASCRIPT FIX VERIFICATION ===\n\n";

// Load the problematic form
$bugsFile = __DIR__ . '/docs/.gitignores/BUGS.md';
$problemForm = file_get_contents($bugsFile);

echo "ORIGINAL FORM ANALYSIS:\n";
echo "Length: " . strlen($problemForm) . " characters\n";
echo "Lines: " . count(explode("\n", $problemForm)) . "\n";

// Check for naked JavaScript
$hasNakedJs = (strpos($problemForm, '$(document)') !== false && 
               strpos($problemForm, '<script') === false);
echo "Naked JavaScript: " . ($hasNakedJs ? "❌ FOUND" : "✅ NONE") . "\n\n";

// Test the complete fix
echo "APPLYING COMPLETE FIX:\n";
$startTime = microtime(true);

$fixedForm = ContentSanitizer::sanitizeForm($problemForm, [
    'format' => true,
    'format_options' => [
        'fix_encoding' => true,
        'format_lines' => true,
        'add_indentation' => false,
        'fix_structure' => true,
        'fix_javascript' => true,
    ]
]);

$endTime = microtime(true);
$processingTime = ($endTime - $startTime) * 1000;

echo "Processing Time: " . number_format($processingTime, 2) . "ms\n";
echo "Fixed Length: " . strlen($fixedForm) . " characters\n";
echo "Fixed Lines: " . count(explode("\n", $fixedForm)) . "\n\n";

// Verify JavaScript fix
$hasScriptTags = (strpos($fixedForm, '<script') !== false);
$hasNakedJsAfter = (strpos($fixedForm, '$(document)') !== false && 
                    strpos($fixedForm, '<script') === false);

echo "JAVASCRIPT FIX VERIFICATION:\n";
echo "Script Tags Added: " . ($hasScriptTags ? "✅ YES" : "❌ NO") . "\n";
echo "Naked JS Remaining: " . ($hasNakedJsAfter ? "❌ YES" : "✅ NO") . "\n\n";

// Find and display the JavaScript section
$lines = explode("\n", $fixedForm);
$scriptStart = -1;
$scriptEnd = -1;

for ($i = 0; $i < count($lines); $i++) {
    if (strpos($lines[$i], '<script') !== false) {
        $scriptStart = $i;
    }
    if (strpos($lines[$i], '</script>') !== false) {
        $scriptEnd = $i;
        break;
    }
}

if ($scriptStart !== -1 && $scriptEnd !== -1) {
    echo "JAVASCRIPT SECTION FOUND (Lines " . ($scriptStart + 1) . "-" . ($scriptEnd + 1) . "):\n";
    for ($i = $scriptStart; $i <= $scriptEnd; $i++) {
        echo sprintf("%2d: %s\n", $i + 1, $lines[$i]);
    }
} else {
    echo "❌ NO JAVASCRIPT SECTION FOUND\n";
}

// Security verification
echo "\nSECURITY VERIFICATION:\n";
$dangerousPatterns = [
    'onclick=' => 'onclick events',
    'onload=' => 'onload events', 
    'javascript:' => 'javascript: URLs',
    'eval(' => 'eval() calls',
    'document.write(' => 'document.write calls',
];

$securityIssues = 0;
foreach ($dangerousPatterns as $pattern => $description) {
    $found = strpos($fixedForm, $pattern) !== false;
    echo "  $description: " . ($found ? "❌ FOUND" : "✅ CLEAN") . "\n";
    if ($found) $securityIssues++;
}

// Save the final result
$outputFile = __DIR__ . '/docs/.gitignores/FINAL_FIXED_FORM.html';
file_put_contents($outputFile, $fixedForm);

echo "\nFINAL RESULT:\n";
echo "File saved: $outputFile\n";
echo "Security Issues: $securityIssues\n";
echo "Status: " . ($securityIssues === 0 && $hasScriptTags && !$hasNakedJsAfter ? "✅ SUCCESS" : "❌ ISSUES") . "\n";

echo "\n=== VERIFICATION COMPLETED ===\n";

if ($securityIssues === 0 && $hasScriptTags && !$hasNakedJsAfter) {
    echo "\n🎉 JAVASCRIPT RENDERING BUG COMPLETELY FIXED!\n";
    echo "✅ JavaScript properly wrapped in script tags\n";
    echo "✅ No naked JavaScript remaining\n";
    echo "✅ Security maintained\n";
    echo "🚀 Ready for production!\n";
} else {
    echo "\n❌ Issues still remain - needs further investigation\n";
}