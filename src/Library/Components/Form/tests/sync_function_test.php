<?php

/**
 * Test untuk fungsi sync() - memastikan JavaScript tidak double-processed
 */

// Set up basic Laravel environment
$basePath = realpath(__DIR__ . '/../../../../../../..');
require_once $basePath . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once $basePath . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Canvastack\Canvastack\Library\Components\Form\Security\ContentSanitizer;

echo "=== SYNC FUNCTION BUG FIX TEST ===\n\n";

// Test canvastack_script function
echo "1. Testing canvastack_script() function directly:\n";
echo "---------------------------------------------------\n";

$jsCode = "ajaxSelectionBox('group_id', 'first_route', 'http://localhost/ajax', 'data')";
$scriptOutput = canvastack_script($jsCode);

echo "Input JavaScript: {$jsCode}\n";
echo "canvastack_script() Output:\n{$scriptOutput}\n\n";

// Test if this output gets double-processed by FormFormatter
echo "2. Testing FormFormatter processing of script output:\n";
echo "------------------------------------------------------\n";

$formattedOutput = ContentSanitizer::sanitizeForm($scriptOutput, [
    'format' => true,
    'format_options' => [
        'fix_encoding' => true,
        'format_lines' => true,
        'add_indentation' => false,
        'fix_structure' => true,
        'fix_javascript' => true, // This should NOT double-process
    ]
]);

echo "After FormFormatter processing:\n{$formattedOutput}\n\n";

// Verify the output is correct
echo "3. Verification:\n";
echo "----------------\n";

$hasScriptTags = (strpos($formattedOutput, '<script') !== false && strpos($formattedOutput, '</script>') !== false);
$hasJavaScript = (strpos($formattedOutput, 'ajaxSelectionBox') !== false);
$isNotDoubleWrapped = (substr_count($formattedOutput, '<script') === 1);

echo "✓ Has script tags: " . ($hasScriptTags ? "YES" : "NO") . "\n";
echo "✓ Contains JavaScript: " . ($hasJavaScript ? "YES" : "NO") . "\n";
echo "✓ Not double-wrapped: " . ($isNotDoubleWrapped ? "YES" : "NO") . "\n";

if ($hasScriptTags && $hasJavaScript && $isNotDoubleWrapped) {
    echo "\n🎉 SUCCESS: Sync function bug has been FIXED!\n";
    echo "JavaScript is properly wrapped and not double-processed.\n";
} else {
    echo "\n❌ FAILURE: Sync function still has issues.\n";
    echo "JavaScript processing needs further investigation.\n";
}

// Test with naked JavaScript (should be wrapped)
echo "\n4. Testing naked JavaScript processing:\n";
echo "---------------------------------------\n";

$nakedJs = '$(document).ready(function() { ajaxSelectionBox("group_id", "first_route", "url", "data"); });';
$processedNaked = ContentSanitizer::sanitizeForm($nakedJs, [
    'format' => true,
    'format_options' => [
        'fix_javascript' => true,
    ]
]);

echo "Naked JavaScript input:\n{$nakedJs}\n\n";
echo "After processing:\n{$processedNaked}\n\n";

$nakedHasScriptTags = (strpos($processedNaked, '<script') !== false);
echo "✓ Naked JS now has script tags: " . ($nakedHasScriptTags ? "YES" : "NO") . "\n";

echo "\n=== TEST COMPLETED ===\n";