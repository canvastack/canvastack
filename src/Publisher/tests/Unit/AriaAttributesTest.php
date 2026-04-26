<?php

namespace Tests\Unit;

use Tests\TestCase;
use Canvastack\Canvastack\Library\Components\Form\Objects;
use Canvastack\Canvastack\Library\Constants\FormConstants;

/**
 * Unit Tests for ARIA Attributes
 * 
 * Tests that ARIA attributes are correctly added to form elements.
 * 
 * Validates: Requirements 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 7.7, 7.8
 */
class AriaAttributesTest extends TestCase
{
    private Objects $form;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->form = new Objects();
    }
    
    /**
     * Test that checkbox has aria-checked attribute when selected
     * 
     * @test
     */
    public function test_checkbox_has_aria_checked_when_selected()
    {
        $this->form->checkbox('terms', [1 => 'I agree'], [1]);
        
        $reflection = new \ReflectionClass($this->form);
        $property = $reflection->getProperty('elements');
        $property->setAccessible(true);
        $html = implode('', $property->getValue($this->form));
        
        $this->assertStringContainsString('aria-checked="true"', $html);
    }
    
    /**
     * Test that checkbox has aria-checked attribute when not selected
     * 
     * @test
     */
    public function test_checkbox_has_aria_checked_when_not_selected()
    {
        $this->form->checkbox('terms', [1 => 'I agree'], []);
        
        $reflection = new \ReflectionClass($this->form);
        $property = $reflection->getProperty('elements');
        $property->setAccessible(true);
        $html = implode('', $property->getValue($this->form));
        
        $this->assertStringContainsString('aria-checked="false"', $html);
    }
    
    /**
     * Test that radio button has aria-checked attribute when selected
     * 
     * @test
     */
    public function test_radio_has_aria_checked_when_selected()
    {
        $this->form->radiobox('gender', [1 => 'Male', 2 => 'Female'], 1);
        
        $reflection = new \ReflectionClass($this->form);
        $property = $reflection->getProperty('elements');
        $property->setAccessible(true);
        $html = implode('', $property->getValue($this->form));
        
        $this->assertStringContainsString('aria-checked="true"', $html);
    }
    
    /**
     * Test that disabled checkbox has aria-disabled attribute
     * 
     * @test
     */
    public function test_disabled_checkbox_has_aria_disabled()
    {
        $this->form->checkbox('terms', [1 => 'I agree'], [], [FormConstants::ATTR_DISABLED => true]);
        
        $reflection = new \ReflectionClass($this->form);
        $property = $reflection->getProperty('elements');
        $property->setAccessible(true);
        $html = implode('', $property->getValue($this->form));
        
        $this->assertStringContainsString('aria-disabled="true"', $html);
    }
    
    /**
     * Test that required checkbox has aria-required attribute
     * 
     * @test
     */
    public function test_required_checkbox_has_aria_required()
    {
        $this->form->checkbox('terms', [1 => 'I agree'], [], [FormConstants::ATTR_REQUIRED => true]);
        
        $reflection = new \ReflectionClass($this->form);
        $property = $reflection->getProperty('elements');
        $property->setAccessible(true);
        $html = implode('', $property->getValue($this->form));
        
        $this->assertStringContainsString('aria-required="true"', $html);
    }
}
