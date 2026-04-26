<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Test canvastack_form_escape_html() helper function
 * 
 * Validates that the escape function handles various input types correctly
 * including strings, arrays, null, false, and objects.
 */
class FormEscapeHelperTest extends TestCase
{
    /**
     * Test escaping simple strings
     * 
     * @test
     */
    public function test_escapes_simple_strings()
    {
        $input = '<script>alert("XSS")</script>';
        $output = canvastack_form_escape_html($input);
        
        $this->assertStringContainsString('&lt;script&gt;', $output);
        $this->assertStringNotContainsString('<script>', $output);
    }
    
    /**
     * Test handling null values
     * 
     * @test
     */
    public function test_handles_null_values()
    {
        $output = canvastack_form_escape_html(null);
        $this->assertEquals('', $output);
    }
    
    /**
     * Test handling false values
     * 
     * @test
     */
    public function test_handles_false_values()
    {
        $output = canvastack_form_escape_html(false);
        $this->assertEquals('', $output);
    }
    
    /**
     * Test escaping arrays recursively
     * 
     * @test
     */
    public function test_escapes_arrays_recursively()
    {
        $input = [
            '<div>Test</div>',
            '<script>alert(1)</script>',
            'Normal text'
        ];
        
        $output = canvastack_form_escape_html($input);
        
        $this->assertIsArray($output);
        $this->assertCount(3, $output);
        $this->assertStringContainsString('&lt;div&gt;', $output[0]);
        $this->assertStringContainsString('&lt;script&gt;', $output[1]);
        $this->assertEquals('Normal text', $output[2]);
    }
    
    /**
     * Test escaping nested arrays
     * 
     * @test
     */
    public function test_escapes_nested_arrays()
    {
        $input = [
            'level1' => '<div>Level 1</div>',
            'nested' => [
                'level2' => '<script>Level 2</script>'
            ]
        ];
        
        $output = canvastack_form_escape_html($input);
        
        $this->assertIsArray($output);
        $this->assertStringContainsString('&lt;div&gt;', $output['level1']);
        $this->assertIsArray($output['nested']);
        $this->assertStringContainsString('&lt;script&gt;', $output['nested']['level2']);
    }
    
    /**
     * Test handling objects with __toString
     * 
     * @test
     */
    public function test_handles_objects_with_tostring()
    {
        $object = new class {
            public function __toString() {
                return '<div>Object content</div>';
            }
        };
        
        $output = canvastack_form_escape_html($object);
        
        $this->assertIsString($output);
        $this->assertStringContainsString('&lt;div&gt;', $output);
    }
    
    /**
     * Test handling objects without __toString
     * 
     * @test
     */
    public function test_handles_objects_without_tostring()
    {
        $object = new \stdClass();
        $object->property = 'value';
        
        $output = canvastack_form_escape_html($object);
        
        // Should return empty string for objects without __toString
        $this->assertEquals('', $output);
    }
    
    /**
     * Test escaping special characters
     * 
     * @test
     */
    public function test_escapes_special_characters()
    {
        $input = '< > & " \'';
        $output = canvastack_form_escape_html($input);
        
        $this->assertStringContainsString('&lt;', $output);
        $this->assertStringContainsString('&gt;', $output);
        $this->assertStringContainsString('&amp;', $output);
        $this->assertStringContainsString('&quot;', $output);
        // PHP may use either &#039; or &apos; for single quotes
        $this->assertTrue(
            str_contains($output, '&#039;') || str_contains($output, '&apos;'),
            'Single quote should be escaped'
        );
    }
    
    /**
     * Test that already escaped content is double-escaped
     * (This is expected behavior - SafeHtml system prevents this)
     * 
     * @test
     */
    public function test_double_escaping_behavior()
    {
        $input = '&lt;div&gt;';
        $output = canvastack_form_escape_html($input);
        
        // Should double-escape (SafeHtml marker system prevents this in practice)
        $this->assertStringContainsString('&amp;lt;', $output);
    }
}
