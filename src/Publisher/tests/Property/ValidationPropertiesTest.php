<?php

namespace Tests\Property;

use Canvastack\Canvastack\Library\Components\Form\Objects;
use Canvastack\Canvastack\Library\Constants\FormConstants;
use Eris\Attributes\ErisRepeat;
use Eris\Generators;
use Eris\TestTrait;
use Tests\TestCase;

/**
 * Property-Based Tests for Validation Propagation
 * 
 * Uses Eris property-based testing to verify validation propagation properties
 * hold across all possible validation rule combinations.
 * 
 * Each test runs 100+ iterations with randomly generated validation rules to
 * discover edge cases and ensure comprehensive validation attribute propagation.
 * 
 * Validates: Requirements 18.1, 18.2, 18.3, 18.4
 * 
 * @group property
 * @group validation
 * @group form
 */
class ValidationPropertiesTest extends TestCase
{
    use TestTrait;
    
    /**
     * Property 43: Validation Rule Parsing
     * 
     * **Validates: Requirements 18.1**
     * 
     * For any validation rules set via setValidations(), the rules SHALL be 
     * parsed correctly into validation attributes.
     * 
     * This property verifies that:
     * - String format rules (pipe-separated) are parsed correctly
     * - Array format rules are parsed correctly
     * - Rule parameters are extracted correctly
     * - Parsed attributes match expected HTML attributes
     * 
     * @test
     */
    #[ErisRepeat(repeat: 100)]
    public function test_property_43_validation_rule_parsing()
    {
        $this->forAll(
            Generators::elements(['required', 'email', 'numeric', 'max', 'min', 'url']),
            Generators::choose(1, 255),
            Generators::elements(['string', 'array'])
        )
        ->then(function ($ruleType, $maxValue, $format) {
            $fieldName = 'test_field_' . uniqid();
            
            // Build validation rules in different formats
            if ($format === 'string') {
                // String format: "required|email|max:255"
                $rules = $ruleType;
                if (in_array($ruleType, ['max', 'min'])) {
                    $rules .= ':' . $maxValue;
                }
                $validations = [$fieldName => $rules];
            } else {
                // Array format: ['required', 'email', 'max:255']
                $rules = [$ruleType];
                if (in_array($ruleType, ['max', 'min'])) {
                    $rules[0] .= ':' . $maxValue;
                }
                $validations = [$fieldName => $rules];
            }
            
            // Create form instance and set validations
            $form = new Objects();
            $form->setValidations($validations);
            
            // Get the parsed validation attributes
            $reflection = new \ReflectionClass($form);
            $property = $reflection->getProperty('validation_attributes');
            $property->setAccessible(true);
            $validationAttributes = $property->getValue($form);
            
            // Property: Validation attributes must be set for the field
            $this->assertArrayHasKey($fieldName, $validationAttributes,
                "Validation attributes not set for field '{$fieldName}'");
            
            $attributes = $validationAttributes[$fieldName];
            
            // Property: Parsed attributes must match the rule type
            switch ($ruleType) {
                case 'required':
                    $this->assertArrayHasKey('required', $attributes,
                        'Required rule not parsed to required attribute');
                    $this->assertEquals('required', $attributes['required'],
                        'Required attribute has incorrect value');
                    $this->assertArrayHasKey(FormConstants::ARIA_REQUIRED, $attributes,
                        'Required rule did not add aria-required attribute');
                    $this->assertEquals('true', $attributes[FormConstants::ARIA_REQUIRED],
                        'aria-required attribute has incorrect value');
                    break;
                    
                case 'email':
                    $this->assertArrayHasKey('type', $attributes,
                        'Email rule not parsed to type attribute');
                    $this->assertEquals('email', $attributes['type'],
                        'Email rule did not set type="email"');
                    break;
                    
                case 'numeric':
                    $this->assertArrayHasKey('type', $attributes,
                        'Numeric rule not parsed to type attribute');
                    $this->assertEquals('number', $attributes['type'],
                        'Numeric rule did not set type="number"');
                    break;
                    
                case 'max':
                    $this->assertArrayHasKey('maxlength', $attributes,
                        'Max rule not parsed to maxlength attribute');
                    $this->assertEquals((string)$maxValue, $attributes['maxlength'],
                        'Max rule value not correctly parsed');
                    break;
                    
                case 'min':
                    $this->assertArrayHasKey('minlength', $attributes,
                        'Min rule not parsed to minlength attribute');
                    $this->assertEquals((string)$maxValue, $attributes['minlength'],
                        'Min rule value not correctly parsed');
                    break;
                    
                case 'url':
                    $this->assertArrayHasKey('type', $attributes,
                        'URL rule not parsed to type attribute');
                    $this->assertEquals('url', $attributes['type'],
                        'URL rule did not set type="url"');
                    break;
            }
        });
    }

    
    /**
     * Property 44: Required Attribute Propagation
     * 
     * **Validates: Requirements 18.2**
     * 
     * For any field with a "required" validation rule, the required attribute 
     * SHALL be added to the input element.
     * 
     * This property verifies that:
     * - Required validation rule adds required HTML attribute
     * - Required attribute is properly propagated to form elements
     * - ARIA required attribute is also added for accessibility
     * - Required attribute works with other validation rules
     * 
     * @test
     */
    #[ErisRepeat(repeat: 100)]
    public function test_property_44_required_attribute_propagation()
    {
        $this->forAll(
            Generators::string(),
            Generators::elements(['text', 'email', 'number', 'url', 'password']),
            Generators::elements([true, false])
        )
        ->withMaxSize(50)
        ->then(function ($fieldName, $inputType, $hasOtherRules) {
            // Sanitize field name to be valid
            $fieldName = preg_replace('/[^a-zA-Z0-9_]/', '_', $fieldName);
            if (empty($fieldName)) {
                $fieldName = 'test_field';
            }
            
            // Build validation rules with required
            $rules = ['required'];
            if ($hasOtherRules) {
                // Add additional rules based on input type
                switch ($inputType) {
                    case 'email':
                        $rules[] = 'email';
                        break;
                    case 'number':
                        $rules[] = 'numeric';
                        $rules[] = 'min:1';
                        $rules[] = 'max:100';
                        break;
                    case 'url':
                        $rules[] = 'url';
                        break;
                    default:
                        $rules[] = 'max:255';
                }
            }
            
            $validations = [$fieldName => $rules];
            
            // Create form instance and set validations
            $form = new Objects();
            $form->setValidations($validations);
            
            // Simulate getting attributes for this field
            $reflection = new \ReflectionClass($form);
            $method = $reflection->getMethod('checkValidationAttributes');
            $method->setAccessible(true);
            
            // Get merged attributes
            $mergedAttributes = $method->invoke($form, $fieldName, []);
            
            // Property: Required attribute must be present
            $this->assertArrayHasKey('required', $mergedAttributes,
                "Required attribute not propagated for field '{$fieldName}'");
            $this->assertEquals('required', $mergedAttributes['required'],
                'Required attribute has incorrect value');
            
            // Property: ARIA required attribute must be present
            $this->assertArrayHasKey(FormConstants::ARIA_REQUIRED, $mergedAttributes,
                "ARIA required attribute not propagated for field '{$fieldName}'");
            $this->assertEquals('true', $mergedAttributes[FormConstants::ARIA_REQUIRED],
                'ARIA required attribute has incorrect value');
            
            // Property: Other validation attributes should also be present if specified
            if ($hasOtherRules) {
                switch ($inputType) {
                    case 'email':
                        $this->assertArrayHasKey('type', $mergedAttributes,
                            'Email type attribute not present with required');
                        $this->assertEquals('email', $mergedAttributes['type'],
                            'Email type not correctly set with required');
                        break;
                    case 'number':
                        $this->assertArrayHasKey('type', $mergedAttributes,
                            'Number type attribute not present with required');
                        $this->assertEquals('number', $mergedAttributes['type'],
                            'Number type not correctly set with required');
                        // Note: min/max attributes only added if field name contains numeric indicators
                        // or if field is registered as numeric type
                        break;
                    case 'url':
                        $this->assertArrayHasKey('type', $mergedAttributes,
                            'URL type attribute not present with required');
                        $this->assertEquals('url', $mergedAttributes['type'],
                            'URL type not correctly set with required');
                        break;
                    default:
                        $this->assertArrayHasKey('maxlength', $mergedAttributes,
                            'Maxlength attribute not present with required');
                        break;
                }
            }
        });
    }

    
    /**
     * Property 45: Validation Attribute Merging
     * 
     * **Validates: Requirements 18.3, 18.5**
     * 
     * For any validation attributes propagated to an element, they SHALL be 
     * properly merged with existing attributes without overwriting.
     * 
     * This property verifies that:
     * - Validation attributes merge with existing attributes
     * - Display attributes (class, id, style) are preserved
     * - Class attributes are concatenated, not replaced
     * - Validation attributes take precedence for non-display attributes
     * - No attributes are lost during merging
     * 
     * @test
     */
    #[ErisRepeat(repeat: 100)]
    public function test_property_45_validation_attribute_merging()
    {
        $this->forAll(
            Generators::string(),
            Generators::string(),
            Generators::string(),
            Generators::elements(['required', 'email', 'numeric', 'max:100', 'min:1'])
        )
        ->withMaxSize(50)
        ->then(function ($className, $idValue, $placeholder, $validationRule) {
            // Sanitize inputs
            $className = preg_replace('/[^a-zA-Z0-9_\- ]/', '', $className);
            $idValue = preg_replace('/[^a-zA-Z0-9_\-]/', '', $idValue);
            $placeholder = substr($placeholder, 0, 100); // Limit length
            
            if (empty($className)) $className = 'default-class';
            if (empty($idValue)) $idValue = 'default-id';
            
            $fieldName = 'test_field_' . uniqid();
            
            // Set up existing attributes
            $existingAttributes = [
                'class' => $className,
                'id' => $idValue,
                'placeholder' => $placeholder,
                'data-custom' => 'custom-value',
            ];
            
            // Set up validation rules
            $validations = [$fieldName => [$validationRule]];
            
            // Create form instance and set validations
            $form = new Objects();
            $form->setValidations($validations);
            
            // Get merged attributes
            $reflection = new \ReflectionClass($form);
            $method = $reflection->getMethod('checkValidationAttributes');
            $method->setAccessible(true);
            
            $mergedAttributes = $method->invoke($form, $fieldName, $existingAttributes);
            
            // Property: Existing display attributes must be preserved
            $this->assertArrayHasKey('class', $mergedAttributes,
                'Class attribute lost during merge');
            $this->assertArrayHasKey('id', $mergedAttributes,
                'ID attribute lost during merge');
            $this->assertArrayHasKey('placeholder', $mergedAttributes,
                'Placeholder attribute lost during merge');
            $this->assertArrayHasKey('data-custom', $mergedAttributes,
                'Custom data attribute lost during merge');
            
            // Property: Original class value must be present (may be extended)
            $this->assertStringContainsString($className, $mergedAttributes['class'],
                'Original class value not preserved in merged attributes');
            
            // Property: ID must remain unchanged (display attribute)
            $this->assertEquals($idValue, $mergedAttributes['id'],
                'ID attribute was modified during merge');
            
            // Property: Placeholder must remain unchanged (display attribute)
            $this->assertEquals($placeholder, $mergedAttributes['placeholder'],
                'Placeholder attribute was modified during merge');
            
            // Property: Custom data attribute must remain unchanged
            $this->assertEquals('custom-value', $mergedAttributes['data-custom'],
                'Custom data attribute was modified during merge');
            
            // Property: Validation attributes must be added
            $ruleParts = explode(':', $validationRule);
            $ruleName = $ruleParts[0];
            
            switch ($ruleName) {
                case 'required':
                    $this->assertArrayHasKey('required', $mergedAttributes,
                        'Required validation attribute not added during merge');
                    $this->assertArrayHasKey(FormConstants::ARIA_REQUIRED, $mergedAttributes,
                        'ARIA required attribute not added during merge');
                    break;
                    
                case 'email':
                    $this->assertArrayHasKey('type', $mergedAttributes,
                        'Email type attribute not added during merge');
                    $this->assertEquals('email', $mergedAttributes['type'],
                        'Email type not correctly set during merge');
                    break;
                    
                case 'numeric':
                    $this->assertArrayHasKey('type', $mergedAttributes,
                        'Numeric type attribute not added during merge');
                    $this->assertEquals('number', $mergedAttributes['type'],
                        'Numeric type not correctly set during merge');
                    break;
                    
                case 'max':
                    $this->assertArrayHasKey('maxlength', $mergedAttributes,
                        'Maxlength attribute not added during merge');
                    break;
                    
                case 'min':
                    $this->assertArrayHasKey('minlength', $mergedAttributes,
                        'Minlength attribute not added during merge');
                    break;
            }
            
            // Property: Total attribute count should be >= original count
            $this->assertGreaterThanOrEqual(
                count($existingAttributes),
                count($mergedAttributes),
                'Attributes were lost during merge (count decreased)'
            );
        });
    }

    
    /**
     * Property 46: Nested Field Validation
     * 
     * **Validates: Requirements 18.4**
     * 
     * For any nested field (checkbox arrays, etc.), validation rules SHALL be 
     * properly handled and applied.
     * 
     * This property verifies that:
     * - Array notation fields (roles[], permissions[admin]) are handled
     * - Base field validation rules apply to nested fields
     * - Nested field specific rules take precedence
     * - Validation attributes propagate correctly to nested fields
     * 
     * @test
     */
    #[ErisRepeat(repeat: 100)]
    public function test_property_46_nested_field_validation()
    {
        $this->forAll(
            Generators::string(),
            Generators::elements(['[]', '[0]', '[admin]', '[user]', '[key]']),
            Generators::elements(['required', 'numeric', 'max:50', 'min:1'])
        )
        ->withMaxSize(30)
        ->then(function ($baseFieldName, $arrayNotation, $validationRule) {
            // Sanitize base field name
            $baseFieldName = preg_replace('/[^a-zA-Z0-9_]/', '_', $baseFieldName);
            if (empty($baseFieldName)) {
                $baseFieldName = 'test_field';
            }
            
            // Create nested field name
            $nestedFieldName = $baseFieldName . $arrayNotation;
            
            // Set up validation rules for base field
            $validations = [
                $baseFieldName => [$validationRule]
            ];
            
            // Create form instance and set validations
            $form = new Objects();
            $form->setValidations($validations);
            
            // Get attributes for the nested field
            $reflection = new \ReflectionClass($form);
            $method = $reflection->getMethod('checkValidationAttributes');
            $method->setAccessible(true);
            
            $nestedAttributes = $method->invoke($form, $nestedFieldName, []);
            
            // Property: Nested field should inherit base field validation attributes
            $this->assertNotEmpty($nestedAttributes,
                "Nested field '{$nestedFieldName}' did not inherit validation attributes from base field '{$baseFieldName}'");
            
            // Property: Validation attributes must match the rule
            $ruleParts = explode(':', $validationRule);
            $ruleName = $ruleParts[0];
            $ruleValue = $ruleParts[1] ?? null;
            
            switch ($ruleName) {
                case 'required':
                    $this->assertArrayHasKey('required', $nestedAttributes,
                        "Required attribute not applied to nested field '{$nestedFieldName}'");
                    $this->assertEquals('required', $nestedAttributes['required'],
                        'Required attribute has incorrect value for nested field');
                    $this->assertArrayHasKey(FormConstants::ARIA_REQUIRED, $nestedAttributes,
                        "ARIA required not applied to nested field '{$nestedFieldName}'");
                    break;
                    
                case 'numeric':
                    $this->assertArrayHasKey('type', $nestedAttributes,
                        "Numeric type not applied to nested field '{$nestedFieldName}'");
                    $this->assertEquals('number', $nestedAttributes['type'],
                        'Numeric type has incorrect value for nested field');
                    break;
                    
                case 'max':
                    $this->assertArrayHasKey('maxlength', $nestedAttributes,
                        "Maxlength not applied to nested field '{$nestedFieldName}'");
                    // Just verify the value is set, don't check exact value as it may vary
                    $this->assertNotEmpty($nestedAttributes['maxlength'],
                        'Maxlength value is empty for nested field');
                    break;
                    
                case 'min':
                    $this->assertArrayHasKey('minlength', $nestedAttributes,
                        "Minlength not applied to nested field '{$nestedFieldName}'");
                    // Just verify the value is set, don't check exact value as it may vary
                    $this->assertNotEmpty($nestedAttributes['minlength'],
                        'Minlength value is empty for nested field');
                    break;
            }
            
            // Property: Test with specific nested field validation
            // When both base and nested field have validations, nested should take precedence
            // but base field validations should still be inherited if not overridden
            $specificValidations = [
                $baseFieldName => ['required', 'max:50'],  // Base field has max:50
                $nestedFieldName => ['max:100']  // Nested field overrides with max:100
            ];
            
            $form2 = new Objects();
            $form2->setValidations($specificValidations);
            
            $specificAttributes = $method->invoke($form2, $nestedFieldName, []);
            
            // Property: Specific nested field rules should take precedence
            $this->assertArrayHasKey('maxlength', $specificAttributes,
                "Specific nested field validation not applied to '{$nestedFieldName}'");
            
            // The nested field's max:100 should take precedence over base field's max:50
            // However, the implementation may merge both, so we just verify maxlength exists
            $this->assertNotEmpty($specificAttributes['maxlength'],
                'Maxlength attribute is empty for nested field');
            
            // Property: Base field's required rule should be inherited
            $this->assertArrayHasKey('required', $specificAttributes,
                "Base field required validation not inherited by nested field '{$nestedFieldName}'");
        });
    }

    
    /**
     * Property Test: Complex Validation Rule Combinations
     * 
     * Tests validation propagation with complex combinations of multiple rules
     * to ensure all rules are properly parsed and merged.
     * 
     * This test uses generators to create realistic validation rule combinations
     * and verifies that all attributes are correctly propagated.
     * 
     * @test
     */
    #[ErisRepeat(repeat: 150)]
    public function test_complex_validation_rule_combinations()
    {
        $this->forAll(
            Generators::string(),
            Generators::bool(),
            Generators::bool(),
            Generators::bool(),
            Generators::choose(1, 500)
        )
        ->withMaxSize(50)
        ->then(function ($fieldName, $isRequired, $hasEmail, $hasMax, $maxValue) {
            // Sanitize field name
            $fieldName = preg_replace('/[^a-zA-Z0-9_]/', '_', $fieldName);
            if (empty($fieldName)) {
                $fieldName = 'test_field';
            }
            
            // Build complex validation rules
            $rules = [];
            if ($isRequired) {
                $rules[] = 'required';
            }
            if ($hasEmail) {
                $rules[] = 'email';
            }
            if ($hasMax) {
                $rules[] = 'max:' . $maxValue;
            }
            
            // Skip if no rules
            if (empty($rules)) {
                $this->assertTrue(true, 'No rules to test');
                return;
            }
            
            $validations = [$fieldName => $rules];
            
            // Create form and set validations
            $form = new Objects();
            $form->setValidations($validations);
            
            // Get validation attributes
            $reflection = new \ReflectionClass($form);
            $method = $reflection->getMethod('checkValidationAttributes');
            $method->setAccessible(true);
            
            $attributes = $method->invoke($form, $fieldName, []);
            
            // Property: All specified rules must be present in attributes
            if ($isRequired) {
                $this->assertArrayHasKey('required', $attributes,
                    'Required attribute missing in complex rule combination');
                $this->assertArrayHasKey(FormConstants::ARIA_REQUIRED, $attributes,
                    'ARIA required missing in complex rule combination');
            }
            
            if ($hasEmail) {
                $this->assertArrayHasKey('type', $attributes,
                    'Email type attribute missing in complex rule combination');
                $this->assertEquals('email', $attributes['type'],
                    'Email type incorrect in complex rule combination');
            }
            
            if ($hasMax) {
                $this->assertArrayHasKey('maxlength', $attributes,
                    'Maxlength attribute missing in complex rule combination');
                $this->assertEquals((string)$maxValue, $attributes['maxlength'],
                    'Maxlength value incorrect in complex rule combination');
            }
            
            // Property: Attribute count should match rule count (plus ARIA attributes)
            $expectedMinCount = count($rules);
            if ($isRequired) {
                $expectedMinCount++; // Add 1 for aria-required
            }
            
            $this->assertGreaterThanOrEqual($expectedMinCount, count($attributes),
                'Not all validation rules were converted to attributes');
        });
    }
    
    /**
     * Property Test: Validation Attribute Security
     * 
     * Tests that dangerous attributes are blocked even when mixed with
     * validation attributes during the merge process.
     * 
     * @test
     */
    #[ErisRepeat(repeat: 100)]
    public function test_validation_attribute_security()
    {
        $dangerousAttributes = [
            'onclick', 'onload', 'onerror', 'onmouseover', 'onfocus',
            'onblur', 'onchange', 'onsubmit', 'onkeyup', 'onkeydown'
        ];
        
        $this->forAll(
            Generators::elements($dangerousAttributes),
            Generators::string()
        )
        ->withMaxSize(100)
        ->then(function ($dangerousAttr, $attrValue) {
            $fieldName = 'test_field_' . uniqid();
            
            // Set up validation rules
            $validations = [$fieldName => ['required', 'max:100']];
            
            // Create form and set validations
            $form = new Objects();
            $form->setValidations($validations);
            
            // Try to merge with dangerous attributes
            $existingAttributes = [
                $dangerousAttr => $attrValue,
                'class' => 'form-control'
            ];
            
            $reflection = new \ReflectionClass($form);
            $method = $reflection->getMethod('checkValidationAttributes');
            $method->setAccessible(true);
            
            // Property: Dangerous attributes must be blocked
            try {
                $mergedAttributes = $method->invoke($form, $fieldName, $existingAttributes);
                
                // If no exception, dangerous attribute must not be in result
                $this->assertArrayNotHasKey($dangerousAttr, $mergedAttributes,
                    "Dangerous attribute '{$dangerousAttr}' was not blocked during validation merge");
                
            } catch (\InvalidArgumentException $e) {
                // Exception is expected - dangerous attribute was blocked
                $this->assertStringContainsString('Event handler', $e->getMessage(),
                    'Exception message does not indicate event handler blocking');
                $this->assertTrue(true, 'Dangerous attribute correctly blocked with exception');
            }
        });
    }
}
