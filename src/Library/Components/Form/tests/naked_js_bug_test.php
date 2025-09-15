<?php

/**
 * Test for Naked JavaScript Bug Fix
 * 
 * This test simulates the exact scenario from BUGS.md where JavaScript
 * appears as naked text in the browser instead of being wrapped in script tags.
 */

require_once __DIR__ . '/../Security/FormFormatter.php';

use Canvastack\Canvastack\Library\Components\Form\Security\FormFormatter;

echo "=== NAKED JAVASCRIPT BUG FIX TEST ===\n\n";

// Test case 1: Simulate the exact content from BUGS.md
$buggyHtml = '<div id="user-group" class="tab-pane fade active show" role="tabpanel"><div class="form-group row"><label for="group_id" class="col-sm-3 control-label">
User Group <font class="required" title="This Required Field cannot be Leave Empty!"><sup>(</sup><strong>*</strong><sup>
)</sup></font></label><div class="input-group col-sm-9"><select required="" class="chosen-select-deselect chosen-selectbox form-control" id="group_id" name="group_id" style="display: none;"><option value="" selected="selected"></option><option value="1">Super Admin</option></select></div></div>

<div class="form-group row"><label for="first_route" class="col-sm-3 control-label">
First Redirect
</label><div class="input-group col-sm-9"><select class="chosen-select-deselect chosen-selectbox form-control" id="first_route" name="first_route" style="display: none;"><option value="" selected="selected"></option></select></div></div>
$(document).ready(function() { ajaxSelectionBox(\'group_id\', \'first_route\',
\'http://localhost/mantra.smartfren.dev/public/ajax/post?AjaxPosF=true&_token=H60Bn3v9VFBPucr8UUEy4rNInGQEGXAU8FKISsR2\',
\'{"source":"group_id","target":"first_route","values":"eyJpdiI6Ill0SHJNSndDZjdzTDBkK1NhazJ3N1E9PSIsInZhbHVlIjoibGxZRGs4Y..."}\'
); });
<div class="form-group row"><label for="alias" class="col-sm-3 control-label">
Alias
</label><div class="input-group col-sm-9"><input class="form-control" name="alias" type="text" value="" id="alias"></div></div>';

echo "TEST 1: Processing HTML with naked JavaScript (from BUGS.md)\n";
echo "Input contains naked JavaScript: " . (strpos($buggyHtml, '$(document).ready') !== false ? "YES" : "NO") . "\n";
echo "Input has script tags: " . (strpos($buggyHtml, '<script') !== false ? "YES" : "NO") . "\n\n";

$result1 = FormFormatter::formatForm($buggyHtml, ['fix_javascript' => true]);

echo "After processing:\n";
echo "Output contains naked JavaScript: " . (preg_match('/\$\(document\)\.ready[^<]*<div/', $result1) ? "YES (BUG!)" : "NO (FIXED!)") . "\n";
echo "Output has script tags: " . (strpos($result1, '<script') !== false ? "YES (GOOD!)" : "NO (PROBLEM!)") . "\n";
echo "JavaScript properly wrapped: " . (preg_match('/<script[^>]*>.*?\$\(document\)\.ready.*?<\/script>/s', $result1) ? "YES (PERFECT!)" : "NO (NEEDS FIX!)") . "\n\n";

// Show the relevant part of the output
if (preg_match('/(<script[^>]*>.*?<\/script>)/s', $result1, $matches)) {
    echo "Extracted JavaScript block:\n";
    echo $matches[1] . "\n\n";
} else {
    echo "No script block found in output!\n\n";
}

// Test case 2: Already wrapped JavaScript (should not be double-processed)
$alreadyWrappedHtml = '<div class="form-group">
<script type="text/javascript">
$(document).ready(function() { 
    ajaxSelectionBox(\'group_id\', \'first_route\', \'http://example.com/ajax\', \'data\'); 
});
</script>
</div>';

echo "TEST 2: Processing HTML with already wrapped JavaScript\n";
echo "Input has proper script tags: YES\n";

$result2 = FormFormatter::formatForm($alreadyWrappedHtml, ['fix_javascript' => true]);

echo "After processing:\n";
echo "Output unchanged: " . ($result2 === $alreadyWrappedHtml ? "YES (GOOD!)" : "NO (DOUBLE-PROCESSED!)") . "\n";
echo "Script tags count - Input: " . substr_count($alreadyWrappedHtml, '<script') . ", Output: " . substr_count($result2, '<script') . "\n\n";

// Test case 3: Mixed content (some wrapped, some naked)
$mixedHtml = '<div>
<script>alert("existing");</script>
<p>Some content</p>
$(document).ready(function() { console.log("naked js"); });
<div>More content</div>
</div>';

echo "TEST 3: Processing mixed content (wrapped + naked JavaScript)\n";
$result3 = FormFormatter::formatForm($mixedHtml, ['fix_javascript' => true]);

echo "Naked JavaScript wrapped: " . (preg_match('/<script[^>]*>.*?\$\(document\)\.ready.*?<\/script>/s', $result3) ? "YES" : "NO") . "\n";
echo "Existing script preserved: " . (strpos($result3, 'alert("existing")') !== false ? "YES" : "NO") . "\n";
echo "Total script blocks: " . substr_count($result3, '<script') . "\n\n";

// Performance test
echo "PERFORMANCE TEST:\n";
$start = microtime(true);
for ($i = 0; $i < 100; $i++) {
    FormFormatter::formatForm($buggyHtml, ['fix_javascript' => true]);
}
$end = microtime(true);
$avgTime = ($end - $start) / 100 * 1000; // Convert to milliseconds

echo "Average processing time: " . number_format($avgTime, 2) . "ms\n";
echo "Performance: " . ($avgTime < 5 ? "EXCELLENT" : ($avgTime < 10 ? "GOOD" : "NEEDS OPTIMIZATION")) . "\n\n";

echo "=== TEST SUMMARY ===\n";
echo "Test 1 (Naked JS Bug): " . (preg_match('/<script[^>]*>.*?\$\(document\)\.ready.*?<\/script>/s', $result1) ? "PASSED ✅" : "FAILED ❌") . "\n";
echo "Test 2 (No Double-Processing): " . ($result2 === $alreadyWrappedHtml ? "PASSED ✅" : "FAILED ❌") . "\n";
echo "Test 3 (Mixed Content): " . (substr_count($result3, '<script') >= 2 ? "PASSED ✅" : "FAILED ❌") . "\n";
echo "Performance: " . ($avgTime < 10 ? "PASSED ✅" : "FAILED ❌") . "\n\n";

echo "=== DETAILED OUTPUT FOR DEBUGGING ===\n";
echo "Result 1 (first 500 chars):\n";
echo substr($result1, 0, 500) . "...\n\n";

if (preg_match('/\$\(document\)\.ready[^<]*<div/', $result1)) {
    echo "⚠️  WARNING: Naked JavaScript still detected in output!\n";
    echo "This indicates the fix needs further refinement.\n";
} else {
    echo "✅ SUCCESS: No naked JavaScript detected in output!\n";
    echo "The fix is working correctly.\n";
}

?>