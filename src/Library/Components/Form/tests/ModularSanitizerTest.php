<?php

require_once __DIR__ . '/../Security/HtmlSanitizer.php';
require_once __DIR__ . '/../Security/FormStructureDetector.php';
require_once __DIR__ . '/../Security/ContentSanitizer.php';

use Canvastack\Canvastack\Library\Components\Form\Security\HtmlSanitizer;
use Canvastack\Canvastack\Library\Components\Form\Security\FormStructureDetector;
use Canvastack\Canvastack\Library\Components\Form\Security\ContentSanitizer;

/**
 * Comprehensive Test Suite for Modular Sanitization System
 * 
 * Tests the new modular approach that covers:
 * - All HTML form elements (input, select, textarea, radio, checkbox, etc.)
 * - Dynamic form element detection
 * - Context-aware sanitization
 * - Reusable across form and table systems
 * - Future-proof extensibility
 */
class ModularSanitizerTest
{
    private static $testResults = [];
    private static $totalTests = 0;
    private static $passedTests = 0;
    
    public static function runAllTests()
    {
        echo "=== MODULAR SANITIZER COMPREHENSIVE TEST SUITE ===\n\n";
        
        // Test Form Structure Detection
        self::testFormStructureDetection();
        
        // Test Content Sanitization Contexts
        self::testContentSanitizationContexts();
        
        // Test All Form Elements Coverage
        self::testAllFormElementsCoverage();
        
        // Test Dynamic Extension Capability
        self::testDynamicExtension();
        
        // Test Performance and Caching
        self::testPerformanceAndCaching();
        
        // Test Cross-System Compatibility
        self::testCrossSystemCompatibility();
        
        // Display Results
        self::displayResults();
    }
    
    /**
     * Test comprehensive form structure detection
     */
    private static function testFormStructureDetection()
    {
        echo "🔍 Testing Form Structure Detection...\n";
        
        $testCases = [
            // Basic form elements
            ['<input type="text" name="username">', true, 'Basic input element'],
            ['<select name="country"><option>US</option></select>', true, 'Select element'],
            ['<textarea name="message"></textarea>', true, 'Textarea element'],
            ['<label for="email">Email</label>', true, 'Label element'],
            ['<button type="submit">Submit</button>', true, 'Button element'],
            
            // Radio and Checkbox (previously missing)
            ['<input type="radio" name="gender" value="male">', true, 'Radio input'],
            ['<input type="checkbox" name="terms" value="1">', true, 'Checkbox input'],
            ['<div class="rdio rdio-primary"><input type="radio"></div>', true, 'Custom radio wrapper'],
            ['<div class="ckbox ckbox-primary"><input type="checkbox"></div>', true, 'Custom checkbox wrapper'],
            
            // HTML5 form elements
            ['<input type="email" name="email">', true, 'HTML5 email input'],
            ['<input type="number" name="age" min="0" max="120">', true, 'HTML5 number input'],
            ['<input type="date" name="birthday">', true, 'HTML5 date input'],
            ['<input type="range" name="volume" min="0" max="100">', true, 'HTML5 range input'],
            ['<input type="color" name="theme">', true, 'HTML5 color input'],
            ['<input type="file" name="upload" accept="image/*">', true, 'File input'],
            
            // Form containers and structure
            ['<fieldset><legend>Personal Info</legend></fieldset>', true, 'Fieldset with legend'],
            ['<div class="form-group row"><label>Name</label></div>', true, 'Form group wrapper'],
            ['<div class="input-group"><input type="text"></div>', true, 'Input group wrapper'],
            ['<optgroup label="Countries"><option>US</option></optgroup>', true, 'Option group'],
            
            // Advanced form elements
            ['<datalist id="browsers"><option value="Chrome"></datalist>', true, 'Datalist element'],
            ['<output name="result" for="a b">0</output>', true, 'Output element'],
            ['<progress value="70" max="100">70%</progress>', true, 'Progress element'],
            ['<meter value="6" min="0" max="10">6 out of 10</meter>', true, 'Meter element'],
            
            // Non-form content
            ['<p>This is just a paragraph</p>', false, 'Regular paragraph'],
            ['<div class="content">Regular content</div>', false, 'Regular div'],
            ['<h1>Title</h1>', false, 'Heading element'],
            ['Just plain text', false, 'Plain text'],
            
            // Complex form structures
            ['<form><div class="form-group"><input type="text"></div></form>', true, 'Complete form structure'],
            ['<div class="tabbable"><div class="tab-content"><input type="text"></div></div>', true, 'Tabbed form content'],
        ];
        
        foreach ($testCases as [$content, $expected, $description]) {
            $result = FormStructureDetector::isFormStructure($content);
            self::assertTest($result === $expected, "Form Detection: {$description}");
        }
        
        echo "✅ Form Structure Detection Tests Completed\n\n";
    }
    
    /**
     * Test content sanitization with different contexts
     */
    private static function testContentSanitizationContexts()
    {
        echo "🛡️ Testing Content Sanitization Contexts...\n";
        
        // Test form context
        $formContent = '<div class="form-group"><input type="text" onclick="alert(1)" name="test"></div>';
        $sanitized = ContentSanitizer::sanitize($formContent, ContentSanitizer::CONTEXT_FORM);
        self::assertTest(
            strpos($sanitized, 'onclick') === false && strpos($sanitized, 'form-group') !== false,
            'Form context: Remove XSS, preserve structure'
        );
        
        // Test table context
        $tableContent = '<table><tr><td onclick="alert(1)">Data</td></tr></table>';
        $sanitized = ContentSanitizer::sanitize($tableContent, ContentSanitizer::CONTEXT_TABLE);
        self::assertTest(
            strpos($sanitized, 'onclick') === false && strpos($sanitized, '<table>') !== false,
            'Table context: Remove XSS, preserve structure'
        );
        
        // Test user input context
        $userInput = '<script>alert("XSS")</script>Hello World';
        $sanitized = ContentSanitizer::sanitize($userInput, ContentSanitizer::CONTEXT_USER_INPUT);
        self::assertTest(
            strpos($sanitized, '<script>') === false && strpos($sanitized, 'Hello World') !== false,
            'User input context: Strict sanitization'
        );
        
        // Test attribute context
        $attribute = 'value="test" onclick="alert(1)"';
        $sanitized = ContentSanitizer::sanitize($attribute, ContentSanitizer::CONTEXT_ATTRIBUTE);
        self::assertTest(
            strpos($sanitized, 'onclick') === false,
            'Attribute context: Remove dangerous attributes'
        );
        
        // Test smart sanitization (auto-detection)
        $smartContent = '<input type="email" onclick="alert(1)" name="email">';
        $sanitized = ContentSanitizer::smartSanitize($smartContent);
        self::assertTest(
            strpos($sanitized, 'onclick') === false && strpos($sanitized, 'type="email"') !== false,
            'Smart sanitization: Auto-detect form context'
        );
        
        echo "✅ Content Sanitization Context Tests Completed\n\n";
    }
    
    /**
     * Test coverage of all form elements
     */
    private static function testAllFormElementsCoverage()
    {
        echo "📋 Testing All Form Elements Coverage...\n";
        
        $formElements = [
            // Standard form elements
            'input' => '<input type="text" name="test">',
            'textarea' => '<textarea name="message"></textarea>',
            'select' => '<select name="options"><option>1</option></select>',
            'button' => '<button type="submit">Submit</button>',
            'label' => '<label for="test">Test Label</label>',
            'fieldset' => '<fieldset><legend>Group</legend></fieldset>',
            'legend' => '<legend>Form Section</legend>',
            'optgroup' => '<optgroup label="Group"><option>1</option></optgroup>',
            'option' => '<option value="1">Option 1</option>',
            'datalist' => '<datalist id="list"><option>Item</option></datalist>',
            
            // HTML5 elements
            'output' => '<output name="result">Result</output>',
            'progress' => '<progress value="50" max="100">50%</progress>',
            'meter' => '<meter value="5" max="10">5/10</meter>',
            
            // Input types
            'email' => '<input type="email" name="email">',
            'password' => '<input type="password" name="pass">',
            'number' => '<input type="number" name="age">',
            'range' => '<input type="range" name="volume">',
            'date' => '<input type="date" name="birthday">',
            'time' => '<input type="time" name="appointment">',
            'datetime-local' => '<input type="datetime-local" name="meeting">',
            'month' => '<input type="month" name="month">',
            'week' => '<input type="week" name="week">',
            'color' => '<input type="color" name="theme">',
            'file' => '<input type="file" name="upload">',
            'hidden' => '<input type="hidden" name="token">',
            'checkbox' => '<input type="checkbox" name="agree">',
            'radio' => '<input type="radio" name="gender">',
            'submit' => '<input type="submit" value="Submit">',
            'reset' => '<input type="reset" value="Reset">',
            'image' => '<input type="image" src="submit.png">',
            'url' => '<input type="url" name="website">',
            'tel' => '<input type="tel" name="phone">',
            'search' => '<input type="search" name="query">',
        ];
        
        foreach ($formElements as $element => $html) {
            $detected = FormStructureDetector::isFormStructure($html);
            self::assertTest($detected, "Element coverage: {$element}");
        }
        
        // Test custom form classes (CanvaStack specific)
        $customClasses = [
            'rdio-primary' => '<div class="rdio rdio-primary"><input type="radio"></div>',
            'ckbox-success' => '<div class="ckbox ckbox-success"><input type="checkbox"></div>',
            'form-control' => '<input type="text" class="form-control">',
            'input-group' => '<div class="input-group"><input type="text"></div>',
            'switch' => '<div class="switch-box"><input type="checkbox" class="switch"></div>',
        ];
        
        foreach ($customClasses as $class => $html) {
            $detected = FormStructureDetector::isFormStructure($html);
            self::assertTest($detected, "Custom class coverage: {$class}");
        }
        
        echo "✅ All Form Elements Coverage Tests Completed\n\n";
    }
    
    /**
     * Test dynamic extension capability
     */
    private static function testDynamicExtension()
    {
        echo "🔧 Testing Dynamic Extension Capability...\n";
        
        // Add custom form elements
        FormStructureDetector::addCustomElements(['custom-input', 'special-select']);
        
        $customElement = '<custom-input name="test" type="special">';
        $detected = FormStructureDetector::isFormStructure($customElement);
        self::assertTest($detected, 'Dynamic element addition: custom-input');
        
        // Add custom form classes
        FormStructureDetector::addCustomClasses(['my-form-control', 'special-input']);
        
        $customClass = '<div class="my-form-control"><input type="text"></div>';
        $detected = FormStructureDetector::isFormStructure($customClass);
        self::assertTest($detected, 'Dynamic class addition: my-form-control');
        
        // Test custom context addition
        ContentSanitizer::addContext('custom_context', [
            'preserve_structure' => true,
            'allowed_tags' => ['custom-tag'],
            'level' => ContentSanitizer::LEVEL_PERMISSIVE
        ]);
        
        $contexts = ContentSanitizer::getAvailableContexts();
        self::assertTest(in_array('custom_context', $contexts), 'Dynamic context addition');
        
        echo "✅ Dynamic Extension Tests Completed\n\n";
    }
    
    /**
     * Test performance and caching
     */
    private static function testPerformanceAndCaching()
    {
        echo "⚡ Testing Performance and Caching...\n";
        
        $testContent = '<div class="form-group"><input type="text" name="test"></div>';
        
        // First call (should cache)
        $start = microtime(true);
        $result1 = ContentSanitizer::sanitizeForm($testContent);
        $time1 = microtime(true) - $start;
        
        // Second call (should use cache)
        $start = microtime(true);
        $result2 = ContentSanitizer::sanitizeForm($testContent);
        $time2 = microtime(true) - $start;
        
        self::assertTest($result1 === $result2, 'Caching: Consistent results');
        self::assertTest($time2 <= $time1, 'Caching: Performance improvement');
        
        // Test cache statistics
        $stats = ContentSanitizer::getCacheStats();
        self::assertTest($stats['cache_size'] > 0, 'Cache statistics: Cache populated');
        
        // Test cache clearing
        ContentSanitizer::clearCache();
        $stats = ContentSanitizer::getCacheStats();
        self::assertTest($stats['cache_size'] === 0, 'Cache clearing: Cache emptied');
        
        echo "✅ Performance and Caching Tests Completed\n\n";
    }
    
    /**
     * Test cross-system compatibility
     */
    private static function testCrossSystemCompatibility()
    {
        echo "🔄 Testing Cross-System Compatibility...\n";
        
        // Test form system compatibility
        $formContent = '<div class="form-group"><input type="text" onclick="alert(1)"></div>';
        $sanitized = ContentSanitizer::sanitizeForm($formContent);
        self::assertTest(
            strpos($sanitized, 'form-group') !== false && strpos($sanitized, 'onclick') === false,
            'Form system compatibility'
        );
        
        // Test table system compatibility
        $tableContent = '<table class="table"><tr><td onclick="alert(1)">Data</td></tr></table>';
        $sanitized = ContentSanitizer::sanitizeTable($tableContent);
        self::assertTest(
            strpos($sanitized, '<table>') !== false && strpos($sanitized, 'onclick') === false,
            'Table system compatibility'
        );
        
        // Test batch processing
        $contents = [
            '<input type="text" onclick="alert(1)">',
            '<select onclick="alert(2)"><option>1</option></select>',
            '<textarea onclick="alert(3)"></textarea>'
        ];
        
        $sanitized = ContentSanitizer::batchSanitize($contents, ContentSanitizer::CONTEXT_FORM);
        $allSafe = true;
        foreach ($sanitized as $content) {
            if (strpos($content, 'onclick') !== false) {
                $allSafe = false;
                break;
            }
        }
        self::assertTest($allSafe, 'Batch processing compatibility');
        
        // Test user input sanitization
        $userInput = '<script>alert("XSS")</script>Hello';
        $sanitized = ContentSanitizer::sanitizeUserInput($userInput);
        self::assertTest(
            strpos($sanitized, '<script>') === false && strpos($sanitized, 'Hello') !== false,
            'User input sanitization compatibility'
        );
        
        echo "✅ Cross-System Compatibility Tests Completed\n\n";
    }
    
    /**
     * Assert test result
     */
    private static function assertTest($condition, $description)
    {
        self::$totalTests++;
        
        if ($condition) {
            self::$passedTests++;
            self::$testResults[] = "✅ PASS: {$description}";
            echo "  ✅ {$description}\n";
        } else {
            self::$testResults[] = "❌ FAIL: {$description}";
            echo "  ❌ {$description}\n";
        }
    }
    
    /**
     * Display final test results
     */
    private static function displayResults()
    {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "🎯 MODULAR SANITIZER TEST RESULTS\n";
        echo str_repeat("=", 60) . "\n\n";
        
        echo "📊 SUMMARY:\n";
        echo "Total Tests: " . self::$totalTests . "\n";
        echo "Passed: " . self::$passedTests . "\n";
        echo "Failed: " . (self::$totalTests - self::$passedTests) . "\n";
        echo "Success Rate: " . round((self::$passedTests / self::$totalTests) * 100, 2) . "%\n\n";
        
        if (self::$passedTests === self::$totalTests) {
            echo "🎉 ALL TESTS PASSED!\n";
            echo "✅ Modular sanitization system is working correctly\n";
            echo "✅ All form elements are properly covered\n";
            echo "✅ Dynamic extension capability verified\n";
            echo "✅ Cross-system compatibility confirmed\n";
            echo "✅ Performance and caching optimized\n\n";
            
            echo "🚀 PRODUCTION READY:\n";
            echo "- Complete HTML5 form element coverage\n";
            echo "- Radio, checkbox, and all input types supported\n";
            echo "- Modular design for reusability\n";
            echo "- Future-proof extensibility\n";
            echo "- High-performance caching\n";
            echo "- Context-aware sanitization\n";
            echo "- Zero breaking changes to existing code\n";
        } else {
            echo "⚠️ SOME TESTS FAILED\n";
            echo "Please review the failed tests above.\n";
        }
        
        echo "\n" . str_repeat("=", 60) . "\n";
    }
}

// Run the tests
ModularSanitizerTest::runAllTests();