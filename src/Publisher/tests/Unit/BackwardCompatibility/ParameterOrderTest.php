<?php

namespace Tests\Unit\BackwardCompatibility;

use Tests\TestCase;
use ReflectionClass;
use ReflectionMethod;
use Canvastack\Canvastack\Controllers\Core\Controller;

/**
 * Test 6.4.2: Test all parameter orders unchanged
 * 
 * Validates Requirement 25: Backward Compatibility
 * 
 * This test ensures that parameter orders in all public methods
 * remain unchanged. Changing parameter order would break existing
 * code that calls these methods.
 */
class ParameterOrderTest extends TestCase
{
    /**
     * Expected parameter orders for Controller methods
     * Format: 'methodName' => ['param1Name', 'param2Name', ...]
     */
    private array $expectedParameterOrders = [
        'callAction' => ['method', 'parameters'],
    ];
    
    /**
     * Expected parameter orders for Action trait methods
     */
    private array $expectedActionParameterOrders = [
        'show' => ['id'],
        'edit' => ['id'],
        'store' => ['request'],
        'update' => ['request', 'id'],
        'destroy' => ['id'],
    ];
    
    /**
     * Expected parameter orders for View trait methods
     */
    private array $expectedViewParameterOrders = [
        'render' => ['data'],
        'setPage' => ['page'],
        'configView' => ['config'],
    ];
    
    /**
     * Expected parameter orders for Session trait methods
     */
    private array $expectedSessionParameterOrders = [
        'setSessionData' => ['key', 'value'],
        'getSessionData' => ['key'],
    ];
    
    /**
     * Expected parameter orders for Scripts trait methods
     */
    private array $expectedScriptsParameterOrders = [
        'addScript' => ['src', 'position'],
        'addStyle' => ['href'],
    ];
    
    /**
     * Expected parameter orders for FileUpload trait methods
     */
    private array $expectedFileUploadParameterOrders = [
        'uploadFiles' => ['request', 'rules'],
        'validateFile' => ['file', 'rules'],
        'generateThumbnail' => ['path', 'width', 'height'],
    ];
    
    /**
     * Expected parameter orders for Privileges trait methods
     */
    private array $expectedPrivilegesParameterOrders = [
        'checkPrivilege' => ['userId', 'module', 'action'],
        'getUserPrivileges' => ['userId'],
        'hasPrivilege' => ['module', 'action'],
    ];
    
    /**
     * Expected parameter orders for RouteInfo trait methods
     */
    private array $expectedRouteInfoParameterOrders = [
        'generateActionButtons' => ['config'],
    ];
    
    public function test_controller_parameter_orders_unchanged(): void
    {
        $reflection = new ReflectionClass(Controller::class);
        
        foreach ($this->expectedParameterOrders as $methodName => $expectedParams) {
            if (!$reflection->hasMethod($methodName)) {
                continue;
            }
            
            $method = $reflection->getMethod($methodName);
            
            // Skip if not declared in Controller class
            if ($method->getDeclaringClass()->getName() !== Controller::class) {
                continue;
            }
            
            $actualParams = $method->getParameters();
            
            // Verify parameter count
            $this->assertCount(
                count($expectedParams),
                $actualParams,
                "Method {$methodName} has different number of parameters"
            );
            
            // Verify parameter order
            foreach ($actualParams as $index => $param) {
                $this->assertEquals(
                    $expectedParams[$index],
                    $param->getName(),
                    "Parameter order changed in {$methodName}: expected {$expectedParams[$index]} at position {$index}, got {$param->getName()}"
                );
            }
        }
    }
    
    public function test_action_trait_parameter_orders_unchanged(): void
    {
        // Test parameter orders in Action trait methods
        $traitPath = base_path('vendor/canvastack/origin/src/Controllers/Core/Craft/Action.php');
        
        if (!file_exists($traitPath)) {
            $this->markTestSkipped('Action trait file not found');
        }
        
        $content = file_get_contents($traitPath);
        
        // Verify parameter orders in method signatures
        $this->assertStringContainsString('public function show(int $id)', $content);
        $this->assertStringContainsString('public function edit(int $id)', $content);
        $this->assertStringContainsString('public function update(Request $request, int $id)', $content);
        $this->assertStringContainsString('public function destroy(int $id)', $content);
    }
    
    public function test_view_trait_parameter_orders_unchanged(): void
    {
        $traitPath = base_path('vendor/canvastack/origin/src/Controllers/Core/Craft/View.php');
        
        if (!file_exists($traitPath)) {
            $this->markTestSkipped('View trait file not found');
        }
        
        $content = file_get_contents($traitPath);
        
        // Verify parameter orders
        $this->assertStringContainsString('public function render(array $data', $content);
        $this->assertStringContainsString('public function setPage(string $page)', $content);
        $this->assertStringContainsString('public function configView(array $config', $content);
    }
    
    public function test_session_trait_parameter_orders_unchanged(): void
    {
        $traitPath = base_path('vendor/canvastack/origin/src/Controllers/Core/Craft/Session.php');
        
        if (!file_exists($traitPath)) {
            $this->markTestSkipped('Session trait file not found');
        }
        
        $content = file_get_contents($traitPath);
        
        // Verify parameter orders for setter/getter methods
        $this->assertStringContainsString('function setSessionData(string $key, mixed $value)', $content);
        $this->assertStringContainsString('function getSessionData(string $key)', $content);
    }
    
    public function test_scripts_trait_parameter_orders_unchanged(): void
    {
        $traitPath = base_path('vendor/canvastack/origin/src/Controllers/Core/Craft/Scripts.php');
        
        if (!file_exists($traitPath)) {
            $this->markTestSkipped('Scripts trait file not found');
        }
        
        $content = file_get_contents($traitPath);
        
        // Verify parameter orders
        $this->assertStringContainsString('function addScript(string $src, string $position', $content);
        $this->assertStringContainsString('function addStyle(string $href', $content);
    }
    
    public function test_file_upload_trait_parameter_orders_unchanged(): void
    {
        $traitPath = base_path('vendor/canvastack/origin/src/Controllers/Core/Craft/Includes/FileUpload.php');
        
        if (!file_exists($traitPath)) {
            $this->markTestSkipped('FileUpload trait file not found');
        }
        
        $content = file_get_contents($traitPath);
        
        // Verify parameter orders
        $this->assertStringContainsString('function uploadFiles(Request $request, array $rules', $content);
        $this->assertStringContainsString('function validateFile(UploadedFile $file, array $rules', $content);
    }
    
    public function test_privileges_trait_parameter_orders_unchanged(): void
    {
        $traitPath = base_path('vendor/canvastack/origin/src/Controllers/Core/Craft/Includes/Privileges.php');
        
        if (!file_exists($traitPath)) {
            $this->markTestSkipped('Privileges trait file not found');
        }
        
        $content = file_get_contents($traitPath);
        
        // Verify parameter orders
        $this->assertStringContainsString('function checkPrivilege(int $userId, string $module, string $action', $content);
        $this->assertStringContainsString('function getUserPrivileges(int $userId', $content);
        $this->assertStringContainsString('function hasPrivilege(string $module, string $action', $content);
    }
    
    public function test_route_info_trait_parameter_orders_unchanged(): void
    {
        $traitPath = base_path('vendor/canvastack/origin/src/Controllers/Core/Craft/Includes/RouteInfo.php');
        
        if (!file_exists($traitPath)) {
            $this->markTestSkipped('RouteInfo trait file not found');
        }
        
        $content = file_get_contents($traitPath);
        
        // Verify parameter orders
        $this->assertStringContainsString('function generateActionButtons(array $config', $content);
    }
    
    public function test_helper_functions_parameter_orders_unchanged(): void
    {
        $helperPath = base_path('vendor/canvastack/origin/src/Library/Helpers/App.php');
        
        if (!file_exists($helperPath)) {
            $this->markTestSkipped('Helper functions file not found');
        }
        
        $content = file_get_contents($helperPath);
        
        // Verify parameter orders for key helper functions
        // canvastack_insert($model, $data, $getField = false)
        $this->assertMatchesRegularExpression(
            '/function\s+canvastack_insert\s*\(\s*(?:object\s+)?\$model\s*,\s*(?:array\s+)?\$data/',
            $content,
            'canvastack_insert parameter order changed'
        );
        
        // canvastack_update($model, $data)
        $this->assertMatchesRegularExpression(
            '/function\s+canvastack_update\s*\(\s*(?:object\s+)?\$model\s*,\s*(?:array\s+)?\$data/',
            $content,
            'canvastack_update parameter order changed'
        );
        
        // canvastack_delete($model, $id)
        $this->assertMatchesRegularExpression(
            '/function\s+canvastack_delete\s*\(\s*(?:object\s+)?\$model\s*,\s*(?:int\s+)?\$id/',
            $content,
            'canvastack_delete parameter order changed'
        );
    }
    
    public function test_no_new_required_parameters_added(): void
    {
        // Test that no new required parameters were added to existing methods
        // New optional parameters are OK, but required ones break compatibility
        
        $reflection = new ReflectionClass(Controller::class);
        
        foreach ($this->expectedParameterOrders as $methodName => $expectedParams) {
            if (!$reflection->hasMethod($methodName)) {
                continue;
            }
            
            $method = $reflection->getMethod($methodName);
            
            if ($method->getDeclaringClass()->getName() !== Controller::class) {
                continue;
            }
            
            $actualParams = $method->getParameters();
            
            // Count required parameters
            $requiredCount = 0;
            foreach ($actualParams as $param) {
                if (!$param->isOptional()) {
                    $requiredCount++;
                }
            }
            
            // Required parameters should not exceed expected count
            $this->assertLessThanOrEqual(
                count($expectedParams),
                $requiredCount,
                "Method {$methodName} has more required parameters than before"
            );
        }
    }
    
    public function test_parameter_names_unchanged(): void
    {
        // Test that parameter names haven't changed
        // This is important for named parameter calls in PHP 8+
        
        $reflection = new ReflectionClass(Controller::class);
        
        foreach ($this->expectedParameterOrders as $methodName => $expectedParams) {
            if (!$reflection->hasMethod($methodName)) {
                continue;
            }
            
            $method = $reflection->getMethod($methodName);
            
            if ($method->getDeclaringClass()->getName() !== Controller::class) {
                continue;
            }
            
            $actualParams = $method->getParameters();
            
            // Verify each parameter name matches
            for ($i = 0; $i < count($expectedParams); $i++) {
                if (isset($actualParams[$i])) {
                    $this->assertEquals(
                        $expectedParams[$i],
                        $actualParams[$i]->getName(),
                        "Parameter name changed in {$methodName} at position {$i}"
                    );
                }
            }
        }
    }
}
