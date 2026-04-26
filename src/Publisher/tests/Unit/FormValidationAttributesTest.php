<?php

namespace Tests\Unit;

use Tests\TestCase;
use Canvastack\Canvastack\Library\Components\Form\Objects;
use Canvastack\Canvastack\Library\Constants\FormConstants;

/**
 * Test validation rule propagation to HTML attributes
 * 
 * Validates that Laravel validation rules are correctly converted to HTML5
 * validation attributes for client-side validation.
 */
class FormValidationAttributesTest extends TestCase
{
    protected Objects $form;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->form = new Objects();
    }
    
    /**
     * Test required rule generates required attribute
     */
    public function test_required_rule_generates_required_attribute(): void
    {
        // Set validation rules
        $this->form->setValidations([
            'name' => ['required']
        ]);
        
        // Get validation attributes using reflection
        $reflection = new \ReflectionClass($this->form);
        $property = $reflection->getProperty('validation_attributes');
        $property->setAccessible(true);
        $attributes = $property->getValue();
        
        // Assert required attribute is set
        $this->assertArrayHasKey('name', $attributes);
        $this->assertArrayHasKey('required', $attributes['name']);
        $this->assertEquals('required', $attributes['name']['required']);
        $this->assertArrayHasKey(FormConstants::ARIA_REQUIRED, $attributes['name']);
        $this->assertEquals('true', $attributes['name'][FormConstants::ARIA_REQUIRED]);
    }
    
    /**
     * Test email rule generates type="email" attribute
     */
    public function test_email_rule_generates_email_type(): void
    {
        $this->form->setValidations([
            'email' => ['email']
        ]);
        
        $reflection = new \ReflectionClass($this->form);
        $property = $reflection->getProperty('validation_attributes');
        $property->setAccessible(true);
        $attributes = $property->getValue();
        
        $this->assertArrayHasKey('email', $attributes);
        $this->assertArrayHasKey('type', $attributes['email']);
        $this->assertEquals('email', $attributes['email']['type']);
    }
    
    /**
     * Test max rule generates maxlength attribute
     */
    public function test_max_rule_generates_maxlength(): void
    {
        $this->form->setValidations([
            'username' => ['max:50']
        ]);
        
        $reflection = new \ReflectionClass($this->form);
        $property = $reflection->getProperty('validation_attributes');
        $property->setAccessible(true);
        $attributes = $property->getValue();
        
        $this->assertArrayHasKey('username', $attributes);
        $this->assertArrayHasKey('maxlength', $attributes['username']);
        $this->assertEquals('50', $attributes['username']['maxlength']);
    }
    
    /**
     * Test min rule generates minlength attribute
     */
    public function test_min_rule_generates_minlength(): void
    {
        $this->form->setValidations([
            'password' => ['min:8']
        ]);
        
        $reflection = new \ReflectionClass($this->form);
        $property = $reflection->getProperty('validation_attributes');
        $property->setAccessible(true);
        $attributes = $property->getValue();
        
        $this->assertArrayHasKey('password', $attributes);
        $this->assertArrayHasKey('minlength', $attributes['password']);
        $this->assertEquals('8', $attributes['password']['minlength']);
    }
    
    /**
     * Test numeric rule generates type="number" attribute
     */
    public function test_numeric_rule_generates_number_type(): void
    {
        $this->form->setValidations([
            'age' => ['numeric']
        ]);
        
        $reflection = new \ReflectionClass($this->form);
        $property = $reflection->getProperty('validation_attributes');
        $property->setAccessible(true);
        $attributes = $property->getValue();
        
        $this->assertArrayHasKey('age', $attributes);
        $this->assertArrayHasKey('type', $attributes['age']);
        $this->assertEquals('number', $attributes['age']['type']);
    }
    
    /**
     * Test integer rule generates type="number" with step="1"
     */
    public function test_integer_rule_generates_number_type_with_step(): void
    {
        $this->form->setValidations([
            'quantity' => ['integer']
        ]);
        
        $reflection = new \ReflectionClass($this->form);
        $property = $reflection->getProperty('validation_attributes');
        $property->setAccessible(true);
        $attributes = $property->getValue();
        
        $this->assertArrayHasKey('quantity', $attributes);
        $this->assertArrayHasKey('type', $attributes['quantity']);
        $this->assertEquals('number', $attributes['quantity']['type']);
        $this->assertArrayHasKey('step', $attributes['quantity']);
        $this->assertEquals('1', $attributes['quantity']['step']);
    }
    
    /**
     * Test mimes rule generates accept attribute
     */
    public function test_mimes_rule_generates_accept_attribute(): void
    {
        $this->form->setValidations([
            'avatar' => ['mimes:jpg,png,gif']
        ]);
        
        $reflection = new \ReflectionClass($this->form);
        $property = $reflection->getProperty('validation_attributes');
        $property->setAccessible(true);
        $attributes = $property->getValue();
        
        $this->assertArrayHasKey('avatar', $attributes);
        $this->assertArrayHasKey('accept', $attributes['avatar']);
        $this->assertEquals('.jpg,.png,.gif', $attributes['avatar']['accept']);
    }
    
    /**
     * Test multiple rules are combined correctly
     */
    public function test_multiple_rules_combined(): void
    {
        $this->form->setValidations([
            'email' => ['required', 'email', 'max:255']
        ]);
        
        $reflection = new \ReflectionClass($this->form);
        $property = $reflection->getProperty('validation_attributes');
        $property->setAccessible(true);
        $attributes = $property->getValue();
        
        $this->assertArrayHasKey('email', $attributes);
        $this->assertArrayHasKey('required', $attributes['email']);
        $this->assertArrayHasKey('type', $attributes['email']);
        $this->assertArrayHasKey('maxlength', $attributes['email']);
        $this->assertArrayHasKey(FormConstants::ARIA_REQUIRED, $attributes['email']);
        
        $this->assertEquals('required', $attributes['email']['required']);
        $this->assertEquals('email', $attributes['email']['type']);
        $this->assertEquals('255', $attributes['email']['maxlength']);
        $this->assertEquals('true', $attributes['email'][FormConstants::ARIA_REQUIRED]);
    }
    
    /**
     * Test between rule generates min and max attributes
     */
    public function test_between_rule_generates_min_max(): void
    {
        $this->form->setValidations([
            'age' => ['between:18,65']
        ]);
        
        $reflection = new \ReflectionClass($this->form);
        $property = $reflection->getProperty('validation_attributes');
        $property->setAccessible(true);
        $attributes = $property->getValue();
        
        $this->assertArrayHasKey('age', $attributes);
        $this->assertArrayHasKey('min', $attributes['age']);
        $this->assertArrayHasKey('max', $attributes['age']);
        $this->assertEquals('18', $attributes['age']['min']);
        $this->assertEquals('65', $attributes['age']['max']);
    }
    
    /**
     * Test url rule generates type="url" attribute
     */
    public function test_url_rule_generates_url_type(): void
    {
        $this->form->setValidations([
            'website' => ['url']
        ]);
        
        $reflection = new \ReflectionClass($this->form);
        $property = $reflection->getProperty('validation_attributes');
        $property->setAccessible(true);
        $attributes = $property->getValue();
        
        $this->assertArrayHasKey('website', $attributes);
        $this->assertArrayHasKey('type', $attributes['website']);
        $this->assertEquals('url', $attributes['website']['type']);
    }
    
    /**
     * Test alpha rule generates pattern attribute
     */
    public function test_alpha_rule_generates_pattern(): void
    {
        $this->form->setValidations([
            'name' => ['alpha']
        ]);
        
        $reflection = new \ReflectionClass($this->form);
        $property = $reflection->getProperty('validation_attributes');
        $property->setAccessible(true);
        $attributes = $property->getValue();
        
        $this->assertArrayHasKey('name', $attributes);
        $this->assertArrayHasKey('pattern', $attributes['name']);
        $this->assertEquals('[A-Za-z]+', $attributes['name']['pattern']);
    }
    
    /**
     * Test alpha_dash rule generates pattern attribute
     */
    public function test_alpha_dash_rule_generates_pattern(): void
    {
        $this->form->setValidations([
            'slug' => ['alpha_dash']
        ]);
        
        $reflection = new \ReflectionClass($this->form);
        $property = $reflection->getProperty('validation_attributes');
        $property->setAccessible(true);
        $attributes = $property->getValue();
        
        $this->assertArrayHasKey('slug', $attributes);
        $this->assertArrayHasKey('pattern', $attributes['slug']);
        $this->assertEquals('[A-Za-z0-9_-]+', $attributes['slug']['pattern']);
    }
    
    /**
     * Test nested field validation (checkbox arrays)
     */
    public function test_nested_field_validation(): void
    {
        $this->form->setValidations([
            'roles' => ['required']
        ]);
        
        // Test that checkValidationAttributes handles nested field names
        $reflection = new \ReflectionClass($this->form);
        $method = $reflection->getMethod('checkValidationAttributes');
        $method->setAccessible(true);
        
        // Set validation attributes
        $property = $reflection->getProperty('validation_attributes');
        $property->setAccessible(true);
        $property->setValue($this->form, [
            'roles' => ['required' => 'required', FormConstants::ARIA_REQUIRED => 'true']
        ]);
        
        // Test with array notation
        $attributes = $method->invoke($this->form, 'roles[]', []);
        
        $this->assertArrayHasKey('required', $attributes);
        $this->assertEquals('required', $attributes['required']);
        $this->assertArrayHasKey(FormConstants::ARIA_REQUIRED, $attributes);
        $this->assertEquals('true', $attributes[FormConstants::ARIA_REQUIRED]);
    }
    
    /**
     * Test string format validation rules (pipe-separated)
     */
    public function test_string_format_validation_rules(): void
    {
        $this->form->setValidations([
            'email' => 'required|email|max:255'
        ]);
        
        $reflection = new \ReflectionClass($this->form);
        $property = $reflection->getProperty('validation_attributes');
        $property->setAccessible(true);
        $attributes = $property->getValue();
        
        $this->assertArrayHasKey('email', $attributes);
        $this->assertArrayHasKey('required', $attributes['email']);
        $this->assertArrayHasKey('type', $attributes['email']);
        $this->assertArrayHasKey('maxlength', $attributes['email']);
    }
    
    /**
     * Test that validation attributes merge with existing attributes
     */
    public function test_validation_attributes_merge_with_existing(): void
    {
        $this->form->setValidations([
            'email' => ['required', 'email']
        ]);
        
        $reflection = new \ReflectionClass($this->form);
        $method = $reflection->getMethod('checkValidationAttributes');
        $method->setAccessible(true);
        
        // Set validation attributes
        $property = $reflection->getProperty('validation_attributes');
        $property->setAccessible(true);
        $property->setValue($this->form, [
            'email' => [
                'required' => 'required',
                'type' => 'email',
                FormConstants::ARIA_REQUIRED => 'true'
            ]
        ]);
        
        // Merge with existing attributes
        $existing = [
            'class' => 'form-control',
            'placeholder' => 'Enter email',
            'id' => 'email-input'
        ];
        
        $merged = $method->invoke($this->form, 'email', $existing);
        
        // Check that both validation and existing attributes are present
        $this->assertArrayHasKey('class', $merged);
        $this->assertArrayHasKey('placeholder', $merged);
        $this->assertArrayHasKey('id', $merged);
        $this->assertArrayHasKey('required', $merged);
        $this->assertArrayHasKey('type', $merged);
        $this->assertArrayHasKey(FormConstants::ARIA_REQUIRED, $merged);
        
        // Check values
        $this->assertEquals('form-control', $merged['class']);
        $this->assertEquals('Enter email', $merged['placeholder']);
        $this->assertEquals('email-input', $merged['id']);
        $this->assertEquals('required', $merged['required']);
        $this->assertEquals('email', $merged['type']);
        $this->assertEquals('true', $merged[FormConstants::ARIA_REQUIRED]);
    }
    
    /**
     * Test backward compatibility with old validation_attributes format (string instead of array)
     */
    public function test_backward_compatibility_with_string_format(): void
    {
        // Simulate old format where validation_attributes contains strings instead of arrays
        $reflection = new \ReflectionClass($this->form);
        $property = $reflection->getProperty('validation_attributes');
        $property->setAccessible(true);
        
        // Old format: field name as string value
        $property->setValue($this->form, [
            'email' => 'email'  // Old format: string instead of array
        ]);
        
        $method = $reflection->getMethod('checkValidationAttributes');
        $method->setAccessible(true);
        
        // Should not throw error, should just skip the string value
        $attributes = $method->invoke($this->form, 'email', ['class' => 'form-control']);
        
        // Should return original attributes without error
        $this->assertArrayHasKey('class', $attributes);
        $this->assertEquals('form-control', $attributes['class']);
        
        // Should not have added any validation attributes from the string
        $this->assertArrayNotHasKey('required', $attributes);
    }
    
    /**
     * Test that mixed format (some strings, some arrays) works correctly
     */
    public function test_mixed_format_validation_attributes(): void
    {
        $reflection = new \ReflectionClass($this->form);
        $property = $reflection->getProperty('validation_attributes');
        $property->setAccessible(true);
        
        // Mixed format: some fields have arrays, some have strings
        $property->setValue($this->form, [
            'email' => ['required' => 'required', 'type' => 'email'],  // New format: array
            'name' => 'name',  // Old format: string
            'age' => ['type' => 'number']  // New format: array
        ]);
        
        $method = $reflection->getMethod('checkValidationAttributes');
        $method->setAccessible(true);
        
        // Test email field (array format)
        $emailAttrs = $method->invoke($this->form, 'email', []);
        $this->assertArrayHasKey('required', $emailAttrs);
        $this->assertArrayHasKey('type', $emailAttrs);
        
        // Test name field (string format - should not error)
        $nameAttrs = $method->invoke($this->form, 'name', ['class' => 'form-control']);
        $this->assertArrayHasKey('class', $nameAttrs);
        $this->assertArrayNotHasKey('required', $nameAttrs);
        
        // Test age field (array format)
        $ageAttrs = $method->invoke($this->form, 'age', []);
        $this->assertArrayHasKey('type', $ageAttrs);
        $this->assertEquals('number', $ageAttrs['type']);
    }

}
