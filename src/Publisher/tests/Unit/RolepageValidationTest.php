<?php

namespace Tests\Unit;

use Tests\TestCase;
use Canvastack\Canvastack\Exceptions\Controller\ControllerValidationException;

/**
 * Simple validation test for rolepage() SQL injection prevention
 * 
 * This test verifies that the rolepage() method properly validates
 * the $usein parameter to prevent SQL injection attacks.
 */
class RolepageValidationTest extends TestCase
{
    /**
     * Test that invalid usein parameter throws exception
     * 
     * @test
     */
    public function test_invalid_usein_throws_exception()
    {
        $this->expectException(ControllerValidationException::class);
        $this->expectExceptionMessage('Invalid context parameter');

        // Create a mock controller that uses the MappingPage trait
        $controller = new class {
            use \Canvastack\Canvastack\Controllers\Admin\System\Includes\MappingPage;
            
            private $nodeID = '__node__';
            
            private function map() {
                return new \Canvastack\Canvastack\Models\Admin\System\MappingPage();
            }
        };

        // Try to call rolepage with invalid usein
        $controller->rolepage(['test' => 'data'], 'invalid_context');
    }

    /**
     * Test that SQL injection attempt is rejected
     * 
     * @test
     */
    public function test_sql_injection_attempt_is_rejected()
    {
        $this->expectException(ControllerValidationException::class);

        $controller = new class {
            use \Canvastack\Canvastack\Controllers\Admin\System\Includes\MappingPage;
            
            private $nodeID = '__node__';
            
            private function map() {
                return new \Canvastack\Canvastack\Models\Admin\System\MappingPage();
            }
        };

        // Try SQL injection
        $controller->rolepage(['test' => 'data'], "table_name'; DROP TABLE users--");
    }

    /**
     * Test that empty data throws exception
     * 
     * @test
     */
    public function test_empty_data_throws_exception()
    {
        $this->expectException(ControllerValidationException::class);
        $this->expectExceptionMessage('Data parameter cannot be empty');

        $controller = new class {
            use \Canvastack\Canvastack\Controllers\Admin\System\Includes\MappingPage;
            
            private $nodeID = '__node__';
            
            private function map() {
                return new \Canvastack\Canvastack\Models\Admin\System\MappingPage();
            }
        };

        // Try with empty data
        $controller->rolepage([], 'table_name');
    }

    /**
     * Test that valid contexts are accepted
     * 
     * @test
     * @dataProvider validContextsProvider
     */
    public function test_valid_contexts_are_accepted(string $validContext)
    {
        $controller = new class {
            use \Canvastack\Canvastack\Controllers\Admin\System\Includes\MappingPage;
            
            private $nodeID = '__node__';
            
            private function map() {
                // Return a mock that won't actually query the database
                return new class {
                    public static function getData($data, $usein, $nodeID) {
                        return ['mocked_result'];
                    }
                };
            }
        };

        // This should not throw an exception
        $result = $controller->rolepage(['test' => 'data'], $validContext);
        
        $this->assertIsArray($result);
    }

    /**
     * Data provider for valid contexts
     */
    public static function validContextsProvider(): array
    {
        return [
            'table_name' => ['table_name'],
            'field_name' => ['field_name'],
            'field_value' => ['field_value'],
        ];
    }
}
