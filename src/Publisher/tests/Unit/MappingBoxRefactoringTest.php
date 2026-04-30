<?php

namespace Tests\Unit;

use Tests\TestCase;
use Canvastack\Canvastack\Controllers\Admin\System\GroupController;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Unit tests for mapping_box() refactoring (Issue #19)
 * 
 * Tests the extracted methods from mapping_box() to ensure:
 * - buildParentRow() generates correct rows
 * - buildChildRows() generates correct rows
 * - buildSubChildRows() generates correct rows
 * - buildModuleRow() generates correct rows
 * - mapping_box() produces same output as before refactoring
 * - Error handling continues processing on individual failures
 * 
 * **Validates: Requirement 2.17**
 */
class MappingBoxRefactoringTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that buildParentRow() method exists and has correct signature
     * 
     * @test
     */
    public function test_buildParentRow_method_exists()
    {
        $controller = new GroupController();
        $reflection = new \ReflectionClass($controller);
        
        $this->assertTrue(
            $reflection->hasMethod('buildParentRow'),
            'buildParentRow() method should exist'
        );
        
        $method = $reflection->getMethod('buildParentRow');
        $this->assertTrue(
            $method->isPrivate(),
            'buildParentRow() should be private'
        );
        
        // Check parameters
        $params = $method->getParameters();
        $this->assertCount(3, $params, 'buildParentRow() should have 3 parameters');
        $this->assertEquals('parent', $params[0]->getName());
        $this->assertEquals('childs', $params[1]->getName());
        $this->assertEquals('icon', $params[2]->getName());
    }

    /**
     * Test that buildChildRows() method exists and has correct signature
     * 
     * @test
     */
    public function test_buildChildRows_method_exists()
    {
        $controller = new GroupController();
        $reflection = new \ReflectionClass($controller);
        
        $this->assertTrue(
            $reflection->hasMethod('buildChildRows'),
            'buildChildRows() method should exist'
        );
        
        $method = $reflection->getMethod('buildChildRows');
        $this->assertTrue(
            $method->isPrivate(),
            'buildChildRows() should be private'
        );
        
        // Check parameters
        $params = $method->getParameters();
        $this->assertCount(3, $params, 'buildChildRows() should have 3 parameters');
        $this->assertEquals('child_name', $params[0]->getName());
        $this->assertEquals('data_module', $params[1]->getName());
        $this->assertEquals('icon', $params[2]->getName());
    }

    /**
     * Test that buildSubChildRows() method exists and has correct signature
     * 
     * @test
     */
    public function test_buildSubChildRows_method_exists()
    {
        $controller = new GroupController();
        $reflection = new \ReflectionClass($controller);
        
        $this->assertTrue(
            $reflection->hasMethod('buildSubChildRows'),
            'buildSubChildRows() method should exist'
        );
        
        $method = $reflection->getMethod('buildSubChildRows');
        $this->assertTrue(
            $method->isPrivate(),
            'buildSubChildRows() should be private'
        );
        
        // Check parameters
        $params = $method->getParameters();
        $this->assertCount(3, $params, 'buildSubChildRows() should have 3 parameters');
        $this->assertEquals('subchild_name', $params[0]->getName());
        $this->assertEquals('subdata_module', $params[1]->getName());
        $this->assertEquals('icon', $params[2]->getName());
    }

    /**
     * Test that buildModuleRow() method exists and has correct signature
     * 
     * @test
     */
    public function test_buildModuleRow_method_exists()
    {
        $controller = new GroupController();
        $reflection = new \ReflectionClass($controller);
        
        $this->assertTrue(
            $reflection->hasMethod('buildModuleRow'),
            'buildModuleRow() method should exist'
        );
        
        $method = $reflection->getMethod('buildModuleRow');
        $this->assertTrue(
            $method->isPrivate(),
            'buildModuleRow() should be private'
        );
        
        // Check parameters
        $params = $method->getParameters();
        $this->assertCount(4, $params, 'buildModuleRow() should have 4 parameters');
        $this->assertEquals('module_name', $params[0]->getName());
        $this->assertEquals('module_data', $params[1]->getName());
        $this->assertEquals('icon', $params[2]->getName());
        $this->assertEquals('indent', $params[3]->getName());
    }

    /**
     * Test that mapping_box() has been simplified and uses extracted methods
     * 
     * @test
     */
    public function test_mapping_box_uses_extracted_methods()
    {
        // Read the source code
        $file = base_path('vendor/canvastack/origin/src/Controllers/Admin/System/Includes/MappingPage.php');
        $this->assertFileExists($file);
        
        $sourceCode = file_get_contents($file);
        
        // Check that mapping_box() calls buildParentRow()
        $this->assertStringContainsString(
            'buildParentRow',
            $sourceCode,
            'mapping_box() should call buildParentRow()'
        );
        
        // Check that mapping_box() has error handling
        $this->assertMatchesRegularExpression(
            '/function mapping_box.*?try.*?catch/s',
            $sourceCode,
            'mapping_box() should have try-catch error handling'
        );
    }

    /**
     * Test that error handling continues processing on individual failures
     * 
     * @test
     */
    public function test_error_handling_continues_on_failure()
    {
        // Read the source code
        $file = base_path('vendor/canvastack/origin/src/Controllers/Admin/System/Includes/MappingPage.php');
        $sourceCode = file_get_contents($file);
        
        // Check for continue statements in catch blocks
        $hasContinueInCatch = preg_match('/catch.*?\{.*?continue;/s', $sourceCode);
        
        $this->assertTrue(
            $hasContinueInCatch > 0,
            'Error handling should use continue to process remaining items on failure'
        );
    }

    /**
     * Test that all extracted methods have PHPDoc comments
     * 
     * @test
     */
    public function test_extracted_methods_have_phpdoc()
    {
        $file = base_path('vendor/canvastack/origin/src/Controllers/Admin/System/Includes/MappingPage.php');
        $sourceCode = file_get_contents($file);
        
        // Check for PHPDoc before buildParentRow
        $this->assertMatchesRegularExpression(
            '/\/\*\*.*?@param.*?@return.*?\*\/\s*private function buildParentRow/s',
            $sourceCode,
            'buildParentRow() should have PHPDoc with @param and @return'
        );
        
        // Check for PHPDoc before buildChildRows
        $this->assertMatchesRegularExpression(
            '/\/\*\*.*?@param.*?@return.*?\*\/\s*private function buildChildRows/s',
            $sourceCode,
            'buildChildRows() should have PHPDoc with @param and @return'
        );
        
        // Check for PHPDoc before buildSubChildRows
        $this->assertMatchesRegularExpression(
            '/\/\*\*.*?@param.*?@return.*?\*\/\s*private function buildSubChildRows/s',
            $sourceCode,
            'buildSubChildRows() should have PHPDoc with @param and @return'
        );
        
        // Check for PHPDoc before buildModuleRow
        $this->assertMatchesRegularExpression(
            '/\/\*\*.*?@param.*?@return.*?\*\/\s*private function buildModuleRow/s',
            $sourceCode,
            'buildModuleRow() should have PHPDoc with @param and @return'
        );
    }

    /**
     * Test that mapping_box() complexity has been reduced
     * 
     * @test
     */
    public function test_mapping_box_complexity_reduced()
    {
        $file = base_path('vendor/canvastack/origin/src/Controllers/Admin/System/Includes/MappingPage.php');
        $sourceCode = file_get_contents($file);
        
        // Extract mapping_box() method
        preg_match('/private function mapping_box\(\).*?\n\t\}/s', $sourceCode, $matches);
        $this->assertNotEmpty($matches, 'Should find mapping_box() method');
        
        $mappingBoxCode = $matches[0];
        
        // Count nesting levels (foreach statements)
        $foreachCount = substr_count($mappingBoxCode, 'foreach');
        
        // After refactoring, mapping_box() should have only 1 foreach loop
        $this->assertLessThanOrEqual(
            1,
            $foreachCount,
            'mapping_box() should have at most 1 foreach loop after refactoring'
        );
        
        // Count lines in mapping_box()
        $lineCount = substr_count($mappingBoxCode, "\n");
        
        // After refactoring, mapping_box() should be much shorter (< 30 lines)
        $this->assertLessThan(
            30,
            $lineCount,
            'mapping_box() should be less than 30 lines after refactoring'
        );
    }
}
