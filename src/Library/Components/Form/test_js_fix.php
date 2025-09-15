<?php

require_once __DIR__ . '/Security/FormFormatter.php';

use Canvastack\Canvastack\Library\Components\Form\Security\FormFormatter;

// Read the problematic form from BUGS.md
$bugsFile = __DIR__ . '/docs/.gitignores/BUGS.md';
$problemForm = file_get_contents($bugsFile);

echo "=== TESTING JAVASCRIPT FIX ===\n\n";

echo "Original form length: " . strlen($problemForm) . " characters\n";
echo "Original lines: " . count(explode("\n", $problemForm)) . "\n\n";

// Test the JavaScript fix
$fixedForm = FormFormatter::productionFormat($problemForm);

echo "Fixed form length: " . strlen($fixedForm) . " characters\n";
echo "Fixed lines: " . count(explode("\n", $fixedForm)) . "\n\n";

// Save the fixed form
$outputFile = __DIR__ . '/docs/.gitignores/FIXED_FORM_JS.html';
file_put_contents($outputFile, $fixedForm);

echo "Fixed form saved to: $outputFile\n\n";

// Show a preview of the JavaScript section
$lines = explode("\n", $fixedForm);
$jsStartLine = -1;
$jsEndLine = -1;

for ($i = 0; $i < count($lines); $i++) {
    if (strpos($lines[$i], '<script') !== false) {
        $jsStartLine = $i;
    }
    if (strpos($lines[$i], '</script>') !== false) {
        $jsEndLine = $i;
        break;
    }
}

if ($jsStartLine !== -1 && $jsEndLine !== -1) {
    echo "=== JAVASCRIPT SECTION (Lines " . ($jsStartLine + 1) . "-" . ($jsEndLine + 1) . ") ===\n";
    for ($i = $jsStartLine; $i <= $jsEndLine; $i++) {
        echo sprintf("%3d: %s\n", $i + 1, $lines[$i]);
    }
} else {
    echo "No JavaScript section found - checking for naked JS...\n";
    
    // Look for lines that might contain JavaScript
    for ($i = 0; $i < count($lines); $i++) {
        $line = trim($lines[$i]);
        if (strpos($line, '$(document)') !== false || 
            strpos($line, 'ajaxSelectionBox') !== false) {
            echo sprintf("Line %d: %s\n", $i + 1, substr($line, 0, 100) . "...");
        }
    }
}

echo "\n=== TEST COMPLETED ===\n";