<?php

namespace Tests\Unit;

use Tests\TestCase;
use Canvastack\Canvastack\Library\Components\Form\Objects;
use Canvastack\Canvastack\Library\Constants\SafeHtml;

/**
 * Test SafeHtml Integration in Form Objects
 * 
 * Validates: Task 4.1 - SafeHtml marker integration in Objects.php
 * 
 * Tests that form generation methods properly mark their output with SafeHtml
 * and that double-encoding is prevented.
 */
class FormObjectsSafeHtmlTest extends TestCase
{
    private Objects $form;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->form = new Objects();
    }
    
    /**
     * Test that label() method marks output with SafeHtml
     * 
     * @test
     */
    public function test_label_marks_output_as_safe_html()
    {
        $label = $this->form->label('email', 'Email Address');
        
        // Label should be marked with SafeHtml marker
        $this->assertTrue(SafeHtml::isMarked($label), 'Label output should be marked with SafeHtml');
        
        // When unmarked, should contain proper HTML
        $unmarked = SafeHtml::unmark($label);
        $this->assertStringContainsString('<label', $unmarked);
        $this->assertStringContainsString('for="email"', $unmarked);
        $this->assertStringContainsString('Email Address', $unmarked);
    }
    
    /**
     * Test that SafeHtml prevents double-encoding
     * 
     * @test
     */
    public function test_safehtml_prevents_double_encoding()
    {
        // Create a label with special characters
        $label = $this->form->label('test', 'Test & Verify');
        
        // The label should be marked as safe
        $this->assertTrue(SafeHtml::isMarked($label));
        
        // When unmarked, the label should contain the text
        $unmarked = SafeHtml::unmark($label);
        $this->assertStringContainsString('Test & Verify', $unmarked);
        
        // The marker should prevent double-encoding when processed
        // If we process marked content, it should be unmarked (not escaped again)
        $processed = SafeHtml::process($label);
        $this->assertStringContainsString('Test & Verify', $processed);
        $this->assertStringNotContainsString(SafeHtml::MARKER, $processed);
    }
    
    /**
     * Test that SafeHtml::process() handles marked content correctly
     * 
     * @test
     */
    public function test_safehtml_process_unmarked_safe_content()
    {
        $label = $this->form->label('name', 'Full Name');
        
        // Process should unmark safe HTML
        $processed = SafeHtml::process($label);
        
        // Should not contain the marker
        $this->assertStringNotContainsString(SafeHtml::MARKER, $processed);
        
        // Should contain the actual HTML
        $this->assertStringContainsString('<label', $processed);
        $this->assertStringContainsString('Full Name', $processed);
    }
    
    /**
     * Test that SafeHtml::process() escapes unmarked content
     * 
     * @test
     */
    public function test_safehtml_process_escapes_unsafe_content()
    {
        $unsafeContent = '<script>alert("XSS")</script>';
        
        // Process should escape unsafe content
        $processed = SafeHtml::process($unsafeContent);
        
        // Should be escaped
        $this->assertStringContainsString('&lt;script&gt;', $processed);
        $this->assertStringNotContainsString('<script>', $processed);
    }
    
    /**
     * Test that combining marked HTML elements works correctly
     * 
     * @test
     */
    public function test_combining_marked_html_elements()
    {
        $label1 = $this->form->label('field1', 'Field 1');
        $label2 = $this->form->label('field2', 'Field 2');
        
        // Both should be marked
        $this->assertTrue(SafeHtml::isMarked($label1));
        $this->assertTrue(SafeHtml::isMarked($label2));
        
        // Unmark before combining (as done in inputDraw)
        $unmarked1 = SafeHtml::unmark($label1);
        $unmarked2 = SafeHtml::unmark($label2);
        
        $combined = $unmarked1 . $unmarked2;
        
        // Combined should not have markers
        $this->assertStringNotContainsString(SafeHtml::MARKER, $combined);
        
        // Should contain both labels
        $this->assertStringContainsString('Field 1', $combined);
        $this->assertStringContainsString('Field 2', $combined);
    }
    
    /**
     * Test that SafeHtml::mark() doesn't double-mark
     * 
     * @test
     */
    public function test_safehtml_mark_prevents_double_marking()
    {
        $html = '<div>Test</div>';
        
        // Mark once
        $marked = SafeHtml::mark($html);
        $this->assertTrue(SafeHtml::isMarked($marked));
        
        // Mark again - should not double-mark
        $doubleMarked = SafeHtml::mark($marked);
        
        // Should still have only one marker
        $markerCount = substr_count($doubleMarked, SafeHtml::MARKER);
        $this->assertEquals(1, $markerCount, 'Should have exactly one marker, not double-marked');
    }
    
    /**
     * Test that render() removes SafeHtml markers from output
     * 
     * @test
     */
    public function test_render_removes_safehtml_markers()
    {
        // Create marked HTML elements
        $element1 = SafeHtml::mark('<div>Element 1</div>');
        $element2 = SafeHtml::mark('<div>Element 2</div>');
        
        // Verify they are marked
        $this->assertTrue(SafeHtml::isMarked($element1));
        $this->assertTrue(SafeHtml::isMarked($element2));
        
        // Render the elements
        $rendered = $this->form->render([$element1, $element2]);
        
        // Convert to string if array
        $renderedString = is_array($rendered) ? implode('', $rendered) : $rendered;
        
        // Markers should be removed
        $this->assertStringNotContainsString(SafeHtml::MARKER, $renderedString, 
            'SafeHtml markers should not appear in rendered output');
        
        // But the actual HTML should still be there
        $this->assertStringContainsString('Element 1', $renderedString);
        $this->assertStringContainsString('Element 2', $renderedString);
    }
    
    /**
     * Test that render() handles string input correctly
     * 
     * @test
     */
    public function test_render_removes_markers_from_string_input()
    {
        $markedHtml = SafeHtml::mark('<form>Test Form</form>');
        
        $rendered = $this->form->render($markedHtml);
        
        // Marker should be removed
        $this->assertStringNotContainsString(SafeHtml::MARKER, $rendered);
        
        // HTML should be preserved
        $this->assertStringContainsString('<form>Test Form</form>', $rendered);
    }
    
    /**
     * Test that empty content is handled correctly
     * 
     * @test
     */
    public function test_safehtml_handles_empty_content()
    {
        $empty = '';
        
        // Marking empty content should return empty string
        $marked = SafeHtml::mark($empty);
        $this->assertEquals('', $marked);
        
        // Processing empty content should return empty string
        $processed = SafeHtml::process($empty);
        $this->assertEquals('', $processed);
    }
}
