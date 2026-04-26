<?php

namespace Tests\Integration;

use Tests\TestCase;
use Canvastack\Canvastack\Library\Components\Form\Objects;
use Illuminate\Support\Facades\View;
use Illuminate\Http\Request;

/**
 * Backward Compatibility Test Suite
 * 
 * This test suite ensures 100% backward compatibility with existing code.
 * All public method signatures, parameter orders, default values, and return
 * value formats must remain unchanged.
 * 
 * **Validates: Requirements 12.1-12.7**
 * 
 * Test Coverage:
 * - 5.11.1: All public method signatures unchanged
 * - 5.11.2: All parameter orders unchanged
 * - 5.11.3: All default values unchanged
 * - 5.11.4: All return value formats unchanged
 * - 5.11.5: Test with existing application code patterns
 * - 5.11.6: Comprehensive compatibility test suite
 * 
 * @group backward-compatibility
 * @group integration
 */
class BackwardCompatibilityTest extends TestCase
{
    protected Objects $form;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->form = new Objects();
    }
    
    /**
     * Test 5.11.1: All public method signatures unchanged
     * 
     * Verifies that all public methods exist with correct signatures.
     * Uses reflection to check method existence and parameter counts.
     * 
     * **Validates: Requirement 12.1**
     */
    public function test_all_public_method_signatures_unchanged(): void
    {
        $reflection = new \ReflectionClass(Objects::class);
        
        // Define expected public methods with their parameter counts
        $expectedMethods = [
            '__construct' => 0,
            '__get' => 1,
            'setValidations' => 1,  // array $data = []
            'draw' => 1,  // array|string $data = []
            'render' => 1,  // array|string $object
            'open' => 4,  // string|false $path = false, string|false $method = false, string|false $type = false, bool $file = false
            'method' => 1,  // string $method
            'model' => 5,  // object|string|null $model = null, int|false $row_selected = false, string|false $path = false, bool $file = false, string|false $type = false
            'modelWithFile' => 4,  // object|string|null $model = null, int|false $row_selected = false, string|false $path = false, string|false $type = false
            'close' => 4,  // string|false $action_buttons = false, array|false $option_buttons = false, string|false $prefix = false, string|false $suffix = false
            'token' => 0,
            'label' => 3,  // string $name, string $value, array $attributes = []
            'sync' => 6,  // string $source_field, string $target_field, string $values, ?string $labels = null, string $query, mixed $selected = null
            'addAttributes' => 1,  // array $attributes = []
            'fileUpload' => 3,  // string $upload_path, object $request, array $fileInfo
            'getUploadedFilePaths' => 1,  // ?string $inputname = null
            'verifyAssetPathAccessible' => 1,  // string $assetPath
            'verifyThumbnailPath' => 1,  // string $thumbnailPath
            'getThumbnailPath' => 2,  // string $filePath, ?string $inputname = null
        ];
        
        foreach ($expectedMethods as $methodName => $expectedParamCount) {
            $this->assertTrue(
                $reflection->hasMethod($methodName),
                "Public method '{$methodName}' must exist"
            );
            
            $method = $reflection->getMethod($methodName);
            $this->assertTrue(
                $method->isPublic(),
                "Method '{$methodName}' must be public"
            );
            
            // Check parameter count (including optional parameters)
            $actualParamCount = $method->getNumberOfParameters();
            $this->assertEquals(
                $expectedParamCount,
                $actualParamCount,
                "Method '{$methodName}' must have {$expectedParamCount} parameters, found {$actualParamCount}"
            );
        }
    }
    
    /**
     * Test 5.11.2: All parameter orders unchanged
     * 
     * Verifies that parameter orders remain consistent with original implementation.
     * Tests key methods with multiple parameters.
     * 
     * **Validates: Requirement 12.2**
     */
    public function test_all_parameter_orders_unchanged(): void
    {
        $reflection = new \ReflectionClass(Objects::class);
        
        // Test open() method parameter order
        $openMethod = $reflection->getMethod('open');
        $openParams = $openMethod->getParameters();
        $this->assertEquals('path', $openParams[0]->getName());
        $this->assertEquals('method', $openParams[1]->getName());
        $this->assertEquals('type', $openParams[2]->getName());
        $this->assertEquals('file', $openParams[3]->getName());
        
        // Test model() method parameter order
        $modelMethod = $reflection->getMethod('model');
        $modelParams = $modelMethod->getParameters();
        $this->assertEquals('model', $modelParams[0]->getName());
        $this->assertEquals('row_selected', $modelParams[1]->getName());
        $this->assertEquals('path', $modelParams[2]->getName());
        $this->assertEquals('file', $modelParams[3]->getName());
        $this->assertEquals('type', $modelParams[4]->getName());
        
        // Test close() method parameter order
        $closeMethod = $reflection->getMethod('close');
        $closeParams = $closeMethod->getParameters();
        $this->assertEquals('action_buttons', $closeParams[0]->getName());
        $this->assertEquals('option_buttons', $closeParams[1]->getName());
        $this->assertEquals('prefix', $closeParams[2]->getName());
        $this->assertEquals('suffix', $closeParams[3]->getName());
        
        // Test label() method parameter order
        $labelMethod = $reflection->getMethod('label');
        $labelParams = $labelMethod->getParameters();
        $this->assertEquals('name', $labelParams[0]->getName());
        $this->assertEquals('value', $labelParams[1]->getName());
        $this->assertEquals('attributes', $labelParams[2]->getName());
        
        // Test sync() method parameter order
        $syncMethod = $reflection->getMethod('sync');
        $syncParams = $syncMethod->getParameters();
        $this->assertEquals('source_field', $syncParams[0]->getName());
        $this->assertEquals('target_field', $syncParams[1]->getName());
        $this->assertEquals('values', $syncParams[2]->getName());
        $this->assertEquals('labels', $syncParams[3]->getName());
        $this->assertEquals('query', $syncParams[4]->getName());
        $this->assertEquals('selected', $syncParams[5]->getName());
    }
    
    /**
     * Test 5.11.3: All default values unchanged
     * 
     * Verifies that default parameter values remain consistent.
     * Tests methods with optional parameters.
     * 
     * **Validates: Requirement 12.3**
     */
    public function test_all_default_values_unchanged(): void
    {
        $reflection = new \ReflectionClass(Objects::class);
        
        // Test open() method defaults
        $openMethod = $reflection->getMethod('open');
        $openParams = $openMethod->getParameters();
        $this->assertTrue($openParams[0]->isDefaultValueAvailable());
        $this->assertFalse($openParams[0]->getDefaultValue());  // path = false
        $this->assertTrue($openParams[1]->isDefaultValueAvailable());
        $this->assertFalse($openParams[1]->getDefaultValue());  // method = false
        $this->assertTrue($openParams[2]->isDefaultValueAvailable());
        $this->assertFalse($openParams[2]->getDefaultValue());  // type = false
        $this->assertTrue($openParams[3]->isDefaultValueAvailable());
        $this->assertFalse($openParams[3]->getDefaultValue());  // file = false
        
        // Test model() method defaults
        $modelMethod = $reflection->getMethod('model');
        $modelParams = $modelMethod->getParameters();
        $this->assertTrue($modelParams[0]->isDefaultValueAvailable());
        $this->assertNull($modelParams[0]->getDefaultValue());  // model = null
        $this->assertTrue($modelParams[1]->isDefaultValueAvailable());
        $this->assertFalse($modelParams[1]->getDefaultValue());  // row_selected = false
        $this->assertTrue($modelParams[2]->isDefaultValueAvailable());
        $this->assertFalse($modelParams[2]->getDefaultValue());  // path = false
        $this->assertTrue($modelParams[3]->isDefaultValueAvailable());
        $this->assertFalse($modelParams[3]->getDefaultValue());  // file = false
        $this->assertTrue($modelParams[4]->isDefaultValueAvailable());
        $this->assertFalse($modelParams[4]->getDefaultValue());  // type = false
        
        // Test close() method defaults
        $closeMethod = $reflection->getMethod('close');
        $closeParams = $closeMethod->getParameters();
        $this->assertTrue($closeParams[0]->isDefaultValueAvailable());
        $this->assertFalse($closeParams[0]->getDefaultValue());  // action_buttons = false
        $this->assertTrue($closeParams[1]->isDefaultValueAvailable());
        $this->assertFalse($closeParams[1]->getDefaultValue());  // option_buttons = false
        $this->assertTrue($closeParams[2]->isDefaultValueAvailable());
        $this->assertFalse($closeParams[2]->getDefaultValue());  // prefix = false
        $this->assertTrue($closeParams[3]->isDefaultValueAvailable());
        $this->assertFalse($closeParams[3]->getDefaultValue());  // suffix = false
        
        // Test label() method defaults
        $labelMethod = $reflection->getMethod('label');
        $labelParams = $labelMethod->getParameters();
        $this->assertTrue($labelParams[2]->isDefaultValueAvailable());
        $this->assertEquals([], $labelParams[2]->getDefaultValue());  // attributes = []
        
        // Test setValidations() method defaults
        $setValidationsMethod = $reflection->getMethod('setValidations');
        $setValidationsParams = $setValidationsMethod->getParameters();
        $this->assertTrue($setValidationsParams[0]->isDefaultValueAvailable());
        $this->assertEquals([], $setValidationsParams[0]->getDefaultValue());  // data = []
        
        // Test draw() method defaults
        $drawMethod = $reflection->getMethod('draw');
        $drawParams = $drawMethod->getParameters();
        $this->assertTrue($drawParams[0]->isDefaultValueAvailable());
        $this->assertEquals([], $drawParams[0]->getDefaultValue());  // data = []
        
        // Test addAttributes() method defaults
        $addAttributesMethod = $reflection->getMethod('addAttributes');
        $addAttributesParams = $addAttributesMethod->getParameters();
        $this->assertTrue($addAttributesParams[0]->isDefaultValueAvailable());
        $this->assertEquals([], $addAttributesParams[0]->getDefaultValue());  // attributes = []
    }

    
    /**
     * Test 5.11.4: All return value formats unchanged
     * 
     * Verifies that return value types remain consistent.
     * Tests methods that return values.
     * 
     * **Validates: Requirement 12.4**
     */
    public function test_all_return_value_formats_unchanged(): void
    {
        $reflection = new \ReflectionClass(Objects::class);
        
        // Test __get() returns mixed
        $getMethod = $reflection->getMethod('__get');
        $this->assertTrue($getMethod->hasReturnType());
        $this->assertEquals('mixed', $getMethod->getReturnType()->getName());
        
        // Test render() returns array|string
        $renderMethod = $reflection->getMethod('render');
        $this->assertTrue($renderMethod->hasReturnType());
        $returnType = $renderMethod->getReturnType();
        $this->assertInstanceOf(\ReflectionUnionType::class, $returnType);
        
        // Test label() returns string
        $labelMethod = $reflection->getMethod('label');
        $this->assertTrue($labelMethod->hasReturnType());
        $this->assertEquals('string', $labelMethod->getReturnType()->getName());
        
        // Test getUploadedFilePaths() returns array|string|null
        $getUploadedFilePathsMethod = $reflection->getMethod('getUploadedFilePaths');
        $this->assertTrue($getUploadedFilePathsMethod->hasReturnType());
        $returnType = $getUploadedFilePathsMethod->getReturnType();
        $this->assertInstanceOf(\ReflectionUnionType::class, $returnType);
        
        // Test verifyAssetPathAccessible() returns bool
        $verifyAssetPathAccessibleMethod = $reflection->getMethod('verifyAssetPathAccessible');
        $this->assertTrue($verifyAssetPathAccessibleMethod->hasReturnType());
        $this->assertEquals('bool', $verifyAssetPathAccessibleMethod->getReturnType()->getName());
        
        // Test verifyThumbnailPath() returns bool
        $verifyThumbnailPathMethod = $reflection->getMethod('verifyThumbnailPath');
        $this->assertTrue($verifyThumbnailPathMethod->hasReturnType());
        $this->assertEquals('bool', $verifyThumbnailPathMethod->getReturnType()->getName());
        
        // Test getThumbnailPath() returns string|null
        $getThumbnailPathMethod = $reflection->getMethod('getThumbnailPath');
        $this->assertTrue($getThumbnailPathMethod->hasReturnType());
        $returnType = $getThumbnailPathMethod->getReturnType();
        // Union type or nullable type both acceptable
        $this->assertTrue(
            $returnType instanceof \ReflectionUnionType || $returnType instanceof \ReflectionNamedType,
            'getThumbnailPath must return string|null'
        );
        
        // Test methods that return void
        $voidMethods = ['setValidations', 'draw', 'open', 'method', 'model', 'modelWithFile', 'close', 'token', 'sync', 'addAttributes', 'fileUpload'];
        foreach ($voidMethods as $methodName) {
            $method = $reflection->getMethod($methodName);
            $this->assertTrue($method->hasReturnType());
            $this->assertEquals('void', $method->getReturnType()->getName(), "Method '{$methodName}' must return void");
        }
    }
    
    /**
     * Test 5.11.5: Test with existing application code patterns
     * 
     * Tests common usage patterns from existing application code.
     * Ensures backward compatibility with real-world usage.
     * 
     * **Validates: Requirement 12.5**
     */
    public function test_existing_application_code_patterns(): void
    {
        // Pattern 1: Label generation
        $label = $this->form->label('username', 'Username');
        $this->assertStringContainsString('Username', $label);
        $this->assertStringContainsString('for="username"', $label);
        
        // Pattern 2: Label with attributes
        $label = $this->form->label('email', 'Email Address', ['class' => 'required']);
        $this->assertStringContainsString('Email Address', $label);
        $this->assertStringContainsString('class=', $label);
        
        // Pattern 3: Set validations
        $this->form->setValidations([
            'username' => 'required|min:3',
            'email' => 'required|email'
        ]);
        $this->assertInstanceOf(Objects::class, $this->form);
        
        // Pattern 4: Add attributes
        $this->form->addAttributes(['class' => 'form-control']);
        $this->assertInstanceOf(Objects::class, $this->form);
        
        // Pattern 5: Verify methods can be called without errors
        $this->form->checkbox('test', [1 => 'Test']);
        $this->assertInstanceOf(Objects::class, $this->form);
        
        $this->form->text('test_text');
        $this->assertInstanceOf(Objects::class, $this->form);
        
        $this->form->selectbox('test_select', ['a' => 'Option A']);
        $this->assertInstanceOf(Objects::class, $this->form);
    }
    
    /**
     * Test trait method signatures unchanged - Check trait
     * 
     * Verifies checkbox method signature remains unchanged.
     * 
     * **Validates: Requirement 12.1**
     */
    public function test_checkbox_method_signature_unchanged(): void
    {
        $reflection = new \ReflectionClass(Objects::class);
        
        $this->assertTrue($reflection->hasMethod('checkbox'));
        $method = $reflection->getMethod('checkbox');
        $this->assertTrue($method->isPublic());
        
        $params = $method->getParameters();
        $this->assertCount(5, $params);
        $this->assertEquals('name', $params[0]->getName());
        $this->assertEquals('values', $params[1]->getName());
        $this->assertEquals('selected', $params[2]->getName());
        $this->assertEquals('attributes', $params[3]->getName());
        $this->assertEquals('label', $params[4]->getName());
        
        // Check default values
        $this->assertEquals([], $params[1]->getDefaultValue());
        $this->assertEquals([], $params[2]->getDefaultValue());
        $this->assertEquals([], $params[3]->getDefaultValue());
        $this->assertTrue($params[4]->getDefaultValue());
    }
    
    /**
     * Test trait method signatures unchanged - Radio trait
     * 
     * Verifies radiobox method signature remains unchanged.
     * 
     * **Validates: Requirement 12.1**
     */
    public function test_radiobox_method_signature_unchanged(): void
    {
        $reflection = new \ReflectionClass(Objects::class);
        
        $this->assertTrue($reflection->hasMethod('radiobox'));
        $method = $reflection->getMethod('radiobox');
        $this->assertTrue($method->isPublic());
        
        $params = $method->getParameters();
        $this->assertCount(5, $params);
        $this->assertEquals('name', $params[0]->getName());
        $this->assertEquals('values', $params[1]->getName());
        $this->assertEquals('selected', $params[2]->getName());
        $this->assertEquals('attributes', $params[3]->getName());
        $this->assertEquals('label', $params[4]->getName());
        
        // Check default values
        $this->assertEquals([], $params[1]->getDefaultValue());
        $this->assertFalse($params[2]->getDefaultValue());
        $this->assertEquals([], $params[3]->getDefaultValue());
        $this->assertTrue($params[4]->getDefaultValue());
    }
    
    /**
     * Test trait method signatures unchanged - Select trait
     * 
     * Verifies selectbox and month method signatures remain unchanged.
     * 
     * **Validates: Requirement 12.1**
     */
    public function test_select_methods_signature_unchanged(): void
    {
        $reflection = new \ReflectionClass(Objects::class);
        
        // Test selectbox method
        $this->assertTrue($reflection->hasMethod('selectbox'));
        $selectboxMethod = $reflection->getMethod('selectbox');
        $this->assertTrue($selectboxMethod->isPublic());
        
        $params = $selectboxMethod->getParameters();
        $this->assertCount(6, $params);
        $this->assertEquals('name', $params[0]->getName());
        $this->assertEquals('values', $params[1]->getName());
        $this->assertEquals('selected', $params[2]->getName());
        $this->assertEquals('attributes', $params[3]->getName());
        $this->assertEquals('label', $params[4]->getName());
        $this->assertEquals('set_first_value', $params[5]->getName());
        
        // Test month method
        $this->assertTrue($reflection->hasMethod('month'));
        $monthMethod = $reflection->getMethod('month');
        $this->assertTrue($monthMethod->isPublic());
        
        $params = $monthMethod->getParameters();
        $this->assertCount(4, $params);
        $this->assertEquals('name', $params[0]->getName());
        $this->assertEquals('value', $params[1]->getName());
        $this->assertEquals('attributes', $params[2]->getName());
        $this->assertEquals('label', $params[3]->getName());
    }
    
    /**
     * Test trait method signatures unchanged - Text trait
     * 
     * Verifies text input method signatures remain unchanged.
     * 
     * **Validates: Requirement 12.1**
     */
    public function test_text_methods_signature_unchanged(): void
    {
        $reflection = new \ReflectionClass(Objects::class);
        
        $textMethods = ['text', 'textarea', 'email', 'number', 'password', 'tags'];
        
        foreach ($textMethods as $methodName) {
            $this->assertTrue($reflection->hasMethod($methodName), "Method '{$methodName}' must exist");
            $method = $reflection->getMethod($methodName);
            $this->assertTrue($method->isPublic(), "Method '{$methodName}' must be public");
            
            $params = $method->getParameters();
            
            // Password has different signature (no value parameter)
            if ($methodName === 'password') {
                $this->assertCount(3, $params);
                $this->assertEquals('name', $params[0]->getName());
                $this->assertEquals('attributes', $params[1]->getName());
                $this->assertEquals('label', $params[2]->getName());
            } else {
                $this->assertCount(4, $params);
                $this->assertEquals('name', $params[0]->getName());
                $this->assertEquals('value', $params[1]->getName());
                $this->assertEquals('attributes', $params[2]->getName());
                $this->assertEquals('label', $params[3]->getName());
            }
        }
    }
    
    /**
     * Test trait method signatures unchanged - File trait
     * 
     * Verifies file method signature remains unchanged.
     * 
     * **Validates: Requirement 12.1**
     */
    public function test_file_method_signature_unchanged(): void
    {
        $reflection = new \ReflectionClass(Objects::class);
        
        $this->assertTrue($reflection->hasMethod('file'));
        $method = $reflection->getMethod('file');
        $this->assertTrue($method->isPublic());
        
        $params = $method->getParameters();
        $this->assertCount(3, $params);
        $this->assertEquals('name', $params[0]->getName());
        $this->assertEquals('attributes', $params[1]->getName());
        $this->assertEquals('label', $params[2]->getName());
        
        // Check default values
        $this->assertEquals([], $params[1]->getDefaultValue());
        $this->assertTrue($params[2]->getDefaultValue());
    }
    
    /**
     * Test trait method signatures unchanged - DateTime trait
     * 
     * Verifies date/time method signatures remain unchanged.
     * 
     * **Validates: Requirement 12.1**
     */
    public function test_datetime_methods_signature_unchanged(): void
    {
        $reflection = new \ReflectionClass(Objects::class);
        
        $datetimeMethods = ['date', 'datetime', 'daterange', 'time'];
        
        foreach ($datetimeMethods as $methodName) {
            $this->assertTrue($reflection->hasMethod($methodName), "Method '{$methodName}' must exist");
            $method = $reflection->getMethod($methodName);
            $this->assertTrue($method->isPublic(), "Method '{$methodName}' must be public");
            
            $params = $method->getParameters();
            $this->assertCount(4, $params);
            $this->assertEquals('name', $params[0]->getName());
            $this->assertEquals('value', $params[1]->getName());
            $this->assertEquals('attributes', $params[2]->getName());
            $this->assertEquals('label', $params[3]->getName());
            
            // Check default values
            $this->assertNull($params[1]->getDefaultValue());
            $this->assertEquals([], $params[2]->getDefaultValue());
            $this->assertTrue($params[3]->getDefaultValue());
        }
    }
    
    /**
     * Test trait method signatures unchanged - Tab trait
     * 
     * Verifies tab method signatures remain unchanged.
     * 
     * **Validates: Requirement 12.1**
     */
    public function test_tab_methods_signature_unchanged(): void
    {
        $reflection = new \ReflectionClass(Objects::class);
        
        // Test openTab method
        $this->assertTrue($reflection->hasMethod('openTab'));
        $openTabMethod = $reflection->getMethod('openTab');
        $this->assertTrue($openTabMethod->isPublic());
        
        $params = $openTabMethod->getParameters();
        $this->assertCount(2, $params);
        $this->assertEquals('label', $params[0]->getName());
        $this->assertEquals('class', $params[1]->getName());
        $this->assertFalse($params[1]->getDefaultValue());
        
        // Test addTabContent method
        $this->assertTrue($reflection->hasMethod('addTabContent'));
        $addTabContentMethod = $reflection->getMethod('addTabContent');
        $this->assertTrue($addTabContentMethod->isPublic());
        
        $params = $addTabContentMethod->getParameters();
        $this->assertCount(1, $params);
        $this->assertEquals('content', $params[0]->getName());
        
        // Test closeTab method
        $this->assertTrue($reflection->hasMethod('closeTab'));
        $closeTabMethod = $reflection->getMethod('closeTab');
        $this->assertTrue($closeTabMethod->isPublic());
        
        $params = $closeTabMethod->getParameters();
        $this->assertCount(0, $params);
        
        // Test renderTab method
        $this->assertTrue($reflection->hasMethod('renderTab'));
        $renderTabMethod = $reflection->getMethod('renderTab');
        $this->assertTrue($renderTabMethod->isPublic());
        
        $params = $renderTabMethod->getParameters();
        $this->assertCount(1, $params);
        $this->assertEquals('object', $params[0]->getName());
    }
    
    /**
     * Test existing checkbox usage patterns
     * 
     * Tests common checkbox usage patterns from existing code.
     * 
     * **Validates: Requirement 12.5**
     */
    public function test_existing_checkbox_usage_patterns(): void
    {
        // Pattern 1: Simple checkbox - verify method can be called
        $this->form->checkbox('terms', [1 => 'I agree']);
        $this->assertInstanceOf(Objects::class, $this->form);
        
        // Pattern 2: Checkbox with selected value
        $this->form->checkbox('options', [1 => 'Option 1', 2 => 'Option 2'], [1]);
        $this->assertInstanceOf(Objects::class, $this->form);
        
        // Pattern 3: Checkbox with attributes
        $this->form->checkbox('features', [1 => 'Feature A'], [], ['class' => 'custom']);
        $this->assertInstanceOf(Objects::class, $this->form);
        
        // Pattern 4: Checkbox without label
        $this->form->checkbox('hidden', [1 => 'Hidden'], [], [], false);
        $this->assertInstanceOf(Objects::class, $this->form);
        
        // Verify output can be rendered
        $output = $this->form->render([]);
        $this->assertIsArray($output);
    }
    
    /**
     * Test existing text input usage patterns
     * 
     * Tests common text input usage patterns from existing code.
     * 
     * **Validates: Requirement 12.5**
     */
    public function test_existing_text_input_usage_patterns(): void
    {
        // Pattern 1: Simple text input
        $this->form->text('username');
        $this->assertInstanceOf(Objects::class, $this->form);
        
        // Pattern 2: Text input with value
        $this->form->text('email', 'test@example.com');
        $this->assertInstanceOf(Objects::class, $this->form);
        
        // Pattern 3: Text input with attributes
        $this->form->text('phone', null, ['placeholder' => 'Enter phone']);
        $this->assertInstanceOf(Objects::class, $this->form);
        
        // Pattern 4: Email input
        $this->form->email('user_email');
        $this->assertInstanceOf(Objects::class, $this->form);
        
        // Pattern 5: Number input
        $this->form->number('age');
        $this->assertInstanceOf(Objects::class, $this->form);
        
        // Pattern 6: Password input
        $this->form->password('password');
        $this->assertInstanceOf(Objects::class, $this->form);
        
        // Verify output can be rendered
        $output = $this->form->render([]);
        $this->assertIsArray($output);
    }
    
    /**
     * Test existing select usage patterns
     * 
     * Tests common select usage patterns from existing code.
     * 
     * **Validates: Requirement 12.5**
     */
    public function test_existing_select_usage_patterns(): void
    {
        // Pattern 1: Simple select
        $this->form->selectbox('country', ['us' => 'United States', 'uk' => 'United Kingdom']);
        $this->assertInstanceOf(Objects::class, $this->form);
        
        // Pattern 2: Select with selected value
        $this->form->selectbox('status', ['active' => 'Active', 'inactive' => 'Inactive'], 'active');
        $this->assertInstanceOf(Objects::class, $this->form);
        
        // Pattern 3: Select without default empty option
        $this->form->selectbox('role', ['admin' => 'Admin', 'user' => 'User'], false, [], true, false);
        $this->assertInstanceOf(Objects::class, $this->form);
        
        // Verify output can be rendered
        $output = $this->form->render([]);
        $this->assertIsArray($output);
    }
    
    /**
     * Test HTML output format unchanged
     * 
     * Verifies that HTML output structure remains consistent.
     * Tests that security fixes don't break HTML structure.
     * 
     * **Validates: Requirement 12.6**
     */
    public function test_html_output_format_unchanged(): void
    {
        // Test label output structure
        $label = $this->form->label('test', 'Test Label');
        $this->assertMatchesRegularExpression('/<label[^>]*for="test"[^>]*>/', $label);
        $this->assertStringContainsString('Test Label', $label);
        $this->assertStringContainsString('</label>', $label);
        
        // Test label with attributes
        $label = $this->form->label('email', 'Email', ['class' => 'required']);
        $this->assertStringContainsString('<label', $label);
        $this->assertStringContainsString('Email', $label);
        $this->assertStringContainsString('</label>', $label);
        
        // Verify methods can be called without errors
        $this->form->text('test_field');
        $this->assertInstanceOf(Objects::class, $this->form);
        
        // Verify render method returns array
        $output = $this->form->render([]);
        $this->assertIsArray($output);
    }
    
    /**
     * Test no breaking changes to public properties
     * 
     * Verifies that public properties remain accessible.
     * 
     * **Validates: Requirement 12.7**
     */
    public function test_no_breaking_changes_to_public_properties(): void
    {
        $reflection = new \ReflectionClass(Objects::class);
        
        // Check that public properties exist
        $expectedPublicProperties = ['inputFiles', 'getFileUploads', 'isFileType'];
        
        foreach ($expectedPublicProperties as $propertyName) {
            $this->assertTrue(
                $reflection->hasProperty($propertyName),
                "Public property '{$propertyName}' must exist"
            );
            
            $property = $reflection->getProperty($propertyName);
            $this->assertTrue(
                $property->isPublic(),
                "Property '{$propertyName}' must be public"
            );
        }
    }
}
