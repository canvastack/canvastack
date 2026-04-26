<?php

namespace Tests\Property;

use Eris\Attributes\ErisRepeat;
use Eris\Generators;
use Eris\TestTrait;
use Tests\TestCase;

/**
 * Property-Based Tests for XSS Protection
 * 
 * Uses Eris property-based testing to verify XSS protection properties
 * hold across all possible inputs with special characters.
 * 
 * Each test runs 100+ iterations with randomly generated inputs to
 * discover edge cases and ensure comprehensive XSS protection.
 * 
 * Validates: Requirements 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7
 * 
 * @group property
 * @group security
 * @group xss
 */
class XSSProtectionPropertiesTest extends TestCase
{
    use TestTrait;
    
    /**
     * Property 1: User Input Escaping
     * 
     * **Validates: Requirements 1.1**
     * 
     * For any user-controllable input rendered to HTML output, all special 
     * characters SHALL be escaped using the centralized escape helper function.
     * 
     * This property verifies that:
     * - All HTML special characters are properly escaped
     * - Script tags cannot be injected
     * - Event handlers cannot be injected
     * - The escape function handles all input types safely
     * 
     * @test
     */
    #[ErisRepeat(repeat: 100)]
    public function test_property_1_user_input_escaping()
    {
        $this->forAll(
            Generators::string()
        )
        ->withMaxSize(1000)
        ->then(function ($userInput) {
            // Apply the escape function
            $escaped = canvastack_form_escape_html($userInput);
            
            // Property: Escaped output must not contain unescaped dangerous patterns
            $this->assertStringNotContainsString('<script>', strtolower($escaped), 
                'Escaped output contains unescaped <script> tag');
            $this->assertStringNotContainsString('</script>', strtolower($escaped),
                'Escaped output contains unescaped </script> tag');
            $this->assertStringNotContainsString('onerror=', strtolower($escaped),
                'Escaped output contains unescaped onerror handler');
            $this->assertStringNotContainsString('onload=', strtolower($escaped),
                'Escaped output contains unescaped onload handler');
            
            // Property: If input contains dangerous characters, they must be escaped
            if (str_contains($userInput, '<')) {
                $this->assertStringContainsString('&lt;', $escaped,
                    'Less-than character not properly escaped');
            }
            if (str_contains($userInput, '>')) {
                $this->assertStringContainsString('&gt;', $escaped,
                    'Greater-than character not properly escaped');
            }
            if (str_contains($userInput, '"')) {
                $this->assertStringContainsString('&quot;', $escaped,
                    'Double quote not properly escaped');
            }
            if (str_contains($userInput, "'")) {
                $this->assertTrue(
                    str_contains($escaped, '&#039;') || str_contains($escaped, '&apos;'),
                    'Single quote not properly escaped'
                );
            }
            if (str_contains($userInput, '&')) {
                $this->assertStringContainsString('&amp;', $escaped,
                    'Ampersand not properly escaped');
            }
        });
    }
    
    /**
     * Property 2: Label Escaping Across Elements
     * 
     * **Validates: Requirements 1.2, 1.3, 1.4, 1.5**
     * 
     * For any form element (checkbox, radio, tab, file) that accepts a label 
     * parameter, the label SHALL be escaped before rendering to HTML.
     * 
     * This property verifies that labels containing XSS payloads are properly
     * escaped when rendered in form elements.
     * 
     * @test
     */
    #[ErisRepeat(repeat: 100)]
    public function test_property_2_label_escaping()
    {
        $this->forAll(
            Generators::string()
        )
        ->withMaxSize(500)
        ->then(function ($labelInput) {
            // Test label escaping in checkList function
            $checkboxOutput = canvastack_form_checkList(
                'test_field',
                '1',
                $labelInput,  // User-controllable label
                false,
                'success'
            );
            
            // Property: Label must be escaped in output
            $this->assertStringNotContainsString('<script>', strtolower($checkboxOutput),
                'Checkbox label contains unescaped <script> tag');
            $this->assertStringNotContainsString('onerror=', strtolower($checkboxOutput),
                'Checkbox label contains unescaped onerror handler');
            
            // Property: If label contains dangerous characters, they must be escaped
            if (str_contains($labelInput, '<')) {
                $this->assertStringContainsString('&lt;', $checkboxOutput,
                    'Label less-than character not escaped in checkbox');
            }
            if (str_contains($labelInput, '>')) {
                $this->assertStringContainsString('&gt;', $checkboxOutput,
                    'Label greater-than character not escaped in checkbox');
            }
            
            // Test label escaping in tab header function
            $tabOutput = canvastack_form_create_header_tab(
                $labelInput,  // User-controllable tab label
                'tab-1',
                false,
                false
            );
            
            // Property: Tab label must be escaped
            $this->assertStringNotContainsString('<script>', strtolower($tabOutput),
                'Tab label contains unescaped <script> tag');
        });
    }
    
    /**
     * Property 3: Attribute Value Escaping
     * 
     * **Validates: Requirements 1.6**
     * 
     * For any attributes array containing user data, all attribute values 
     * SHALL be escaped before rendering to HTML.
     * 
     * This property verifies that attribute values are properly escaped to
     * prevent attribute-based XSS attacks.
     * 
     * @test
     */
    #[ErisRepeat(repeat: 100)]
    public function test_property_3_attribute_value_escaping()
    {
        $this->forAll(
            Generators::string(),
            Generators::string()
        )
        ->withMaxSize(500)
        ->then(function ($attrValue, $dataValue) {
            // Skip dangerous attribute names (they should be blocked by validation)
            $safeAttributes = [
                'data-value' => $attrValue,
                'data-info' => $dataValue,
                'title' => $attrValue,
                'placeholder' => $dataValue,
            ];
            
            try {
                // Validate and escape attributes
                $validated = canvastack_form_validate_attributes($safeAttributes);
                
                // Property: All attribute values must be escaped
                foreach ($validated as $key => $value) {
                    if (is_string($value)) {
                        $this->assertStringNotContainsString('<script>', strtolower($value),
                            "Attribute '{$key}' contains unescaped <script> tag");
                        
                        // Check that dangerous characters are escaped
                        $originalValue = $safeAttributes[$key];
                        if (str_contains($originalValue, '<')) {
                            $this->assertStringContainsString('&lt;', $value,
                                "Attribute '{$key}' less-than not escaped");
                        }
                        if (str_contains($originalValue, '>')) {
                            $this->assertStringContainsString('&gt;', $value,
                                "Attribute '{$key}' greater-than not escaped");
                        }
                        if (str_contains($originalValue, '"')) {
                            $this->assertStringContainsString('&quot;', $value,
                                "Attribute '{$key}' double quote not escaped");
                        }
                    }
                }
            } catch (\InvalidArgumentException $e) {
                // If validation throws exception, that's acceptable (dangerous input blocked)
                $this->assertTrue(true, 'Dangerous attribute blocked by validation');
            }
        });
    }
    
    /**
     * Property 4: Dynamic Class and ID Escaping
     * 
     * **Validates: Requirements 1.7**
     * 
     * For any class names or IDs generated from user input, the values 
     * SHALL be escaped before rendering.
     * 
     * This property verifies that dynamically generated class names and IDs
     * from user input are properly escaped to prevent XSS.
     * 
     * @test
     */
    #[ErisRepeat(repeat: 100)]
    public function test_property_4_dynamic_class_and_id_escaping()
    {
        $this->forAll(
            Generators::string(),
            Generators::string()
        )
        ->withMaxSize(300)
        ->then(function ($className, $idName) {
            // Test with attributes containing user-generated class and ID
            $attributes = [
                'class' => $className,
                'id' => $idName,
            ];
            
            try {
                // Validate and escape attributes
                $validated = canvastack_form_validate_attributes($attributes);
                
                // Property: Class and ID values must be escaped
                if (isset($validated['class']) && is_string($validated['class'])) {
                    $this->assertStringNotContainsString('<script>', strtolower($validated['class']),
                        'Class attribute contains unescaped <script> tag');
                    
                    // Check escaping of dangerous characters in class
                    if (str_contains($className, '<')) {
                        $this->assertStringContainsString('&lt;', $validated['class'],
                            'Class less-than character not escaped');
                    }
                    if (str_contains($className, '>')) {
                        $this->assertStringContainsString('&gt;', $validated['class'],
                            'Class greater-than character not escaped');
                    }
                }
                
                if (isset($validated['id']) && is_string($validated['id'])) {
                    $this->assertStringNotContainsString('<script>', strtolower($validated['id']),
                        'ID attribute contains unescaped <script> tag');
                    $this->assertStringNotContainsString('onerror=', strtolower($validated['id']),
                        'ID attribute contains unescaped onerror handler');
                    
                    // Check escaping of dangerous characters in ID
                    if (str_contains($idName, '<')) {
                        $this->assertStringContainsString('&lt;', $validated['id'],
                            'ID less-than character not escaped');
                    }
                    if (str_contains($idName, '>')) {
                        $this->assertStringContainsString('&gt;', $validated['id'],
                            'ID greater-than character not escaped');
                    }
                }
            } catch (\InvalidArgumentException $e) {
                // If validation throws exception, that's acceptable (dangerous input blocked)
                $this->assertTrue(true, 'Dangerous class/ID blocked by validation');
            }
        });
    }
    
    /**
     * Property Test: XSS Payload Resistance
     * 
     * Tests the escape function against known XSS attack vectors to ensure
     * comprehensive protection across all common attack patterns.
     * 
     * This test uses a generator that produces strings containing common
     * XSS payloads mixed with normal text.
     * 
     * @test
     */
    #[ErisRepeat(repeat: 150)]
    public function test_xss_payload_resistance()
    {
        // Generator for XSS payloads mixed with normal text
        $xssPayloadGenerator = Generators::oneOf(
            Generators::constant('<script>alert(1)</script>'),
            Generators::constant('<img src=x onerror=alert(1)>'),
            Generators::constant('<svg onload=alert(1)>'),
            Generators::constant('javascript:alert(1)'),
            Generators::constant('<iframe src="javascript:alert(1)">'),
            Generators::constant('<body onload=alert(1)>'),
            Generators::constant('<input onfocus=alert(1) autofocus>'),
            Generators::constant('<select onfocus=alert(1) autofocus>'),
            Generators::constant('<textarea onfocus=alert(1) autofocus>'),
            Generators::constant('<marquee onstart=alert(1)>'),
            Generators::constant('<div style="background:url(javascript:alert(1))">'),
            Generators::constant('"><script>alert(1)</script>'),
            Generators::constant("'><script>alert(1)</script>"),
            Generators::constant('<scr<script>ipt>alert(1)</scr</script>ipt>'),
            Generators::string()
        );
        
        $this->forAll($xssPayloadGenerator)
            ->withMaxSize(1000)
            ->then(function ($payload) {
                // Apply escape function
                $escaped = canvastack_form_escape_html($payload);
                
                // Property: No unescaped HTML tags should survive escaping
                // The key is that < and > must be escaped, making any tags harmless
                $this->assertStringNotContainsString('<script>', strtolower($escaped),
                    'XSS payload: <script> tag not escaped');
                $this->assertStringNotContainsString('</script>', strtolower($escaped),
                    'XSS payload: </script> tag not escaped');
                $this->assertStringNotContainsString('<img ', strtolower($escaped),
                    'XSS payload: <img tag not escaped');
                $this->assertStringNotContainsString('<svg ', strtolower($escaped),
                    'XSS payload: <svg tag not escaped');
                $this->assertStringNotContainsString('<iframe ', strtolower($escaped),
                    'XSS payload: <iframe tag not escaped');
                $this->assertStringNotContainsString('<body ', strtolower($escaped),
                    'XSS payload: <body tag not escaped');
                $this->assertStringNotContainsString('<input ', strtolower($escaped),
                    'XSS payload: <input tag not escaped');
                $this->assertStringNotContainsString('<select ', strtolower($escaped),
                    'XSS payload: <select tag not escaped');
                $this->assertStringNotContainsString('<textarea ', strtolower($escaped),
                    'XSS payload: <textarea tag not escaped');
                
                // Property: Dangerous characters must be escaped
                if (str_contains($payload, '<')) {
                    $this->assertStringContainsString('&lt;', $escaped,
                        'XSS payload: < character not escaped');
                }
                if (str_contains($payload, '>')) {
                    $this->assertStringContainsString('&gt;', $escaped,
                        'XSS payload: > character not escaped');
                }
            });
    }
}
