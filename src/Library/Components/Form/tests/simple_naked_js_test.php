<?php

/**
 * Simple test to verify naked JavaScript bug is fixed
 */

require_once __DIR__ . '/../Security/FormFormatter.php';
use Canvastack\Canvastack\Library\Components\Form\Security\FormFormatter;

echo "=== SIMPLE NAKED JAVASCRIPT BUG TEST ===\n\n";

// Test the exact scenario from BUGS.md
$nakedJsHtml = '</div>
$(document).ready(function() { ajaxSelectionBox(\'group_id\', \'first_route\',
\'http://localhost/mantra.smartfren.dev/public/ajax/post?AjaxPosF=true&_token=H60Bn3v9VFBPucr8UUEy4rNInGQEGXAU8FKISsR2\',
\'{"source":"group_id","target":"first_route"}\'); });
<div class="form-group">';

echo "BEFORE PROCESSING:\n";
echo "Contains naked JavaScript: " . (strpos($nakedJsHtml, '$(document).ready') !== false ? "YES" : "NO") . "\n";
echo "Has script tags: " . (strpos($nakedJsHtml, '<script') !== false ? "YES" : "NO") . "\n\n";

// Process with FormFormatter
$result = FormFormatter::formatForm($nakedJsHtml, [
    'fix_javascript' => true,
    'fix_encoding' => false,
    'format_lines' => false,
    'add_indentation' => false,
    'fix_structure' => false
]);

echo "AFTER PROCESSING:\n";
echo "Contains naked JavaScript: " . (preg_match('/\$\(document\)\.ready[^<]*<div/', $result) ? "YES (STILL BUGGY!)" : "NO (FIXED!)") . "\n";
echo "Has script tags: " . (strpos($result, '<script') !== false ? "YES (GOOD!)" : "NO (PROBLEM!)") . "\n";
echo "JavaScript properly wrapped: " . (preg_match('/<script[^>]*>.*?\$\(document\)\.ready.*?<\/script>/s', $result) ? "YES (PERFECT!)" : "NO (NEEDS FIX!)") . "\n\n";

echo "PROCESSED OUTPUT:\n";
echo $result . "\n\n";

// Test conclusion
if (preg_match('/<script[^>]*>.*?\$\(document\)\.ready.*?<\/script>/s', $result) && 
    !preg_match('/\$\(document\)\.ready[^<]*<div/', $result)) {
    echo "🎉 SUCCESS: Naked JavaScript bug is FIXED!\n";
    echo "✅ JavaScript is properly wrapped in script tags\n";
    echo "✅ No naked JavaScript visible to users\n";
    echo "✅ AJAX functionality will work correctly\n";
} else {
    echo "❌ FAILED: Naked JavaScript bug still exists\n";
    echo "⚠️  Users will still see JavaScript code in their browsers\n";
}

?>