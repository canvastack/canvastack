<?php

namespace Tests\Unit\Table;

use Tests\TestCase;
use Canvastack\Canvastack\Library\Components\Table\Craft\Formula;
use Illuminate\Support\Facades\DB;

/**
 * Test suite for Formula class enhancements.
 *
 * Tests:
 * - Formula syntax validation
 * - Mathematical operations
 * - String operations
 * - Conditional logic
 * - Error handling
 * - Complex formulas
 *
 * @group unit
 * @group table
 * @group formula
 */
class FormulaEnhancementsTest extends TestCase
{
    /**
     * Create a mock query object with attributes.
     *
     * @param array $attributes
     * @return object
     */
    private function createMockQuery(array $attributes): object
    {
        return new class($attributes) {
            private array $attributes;
            
            public function __construct(array $attributes)
            {
                $this->attributes = $attributes;
            }
            
            public function getAttributes(): array
            {
                return $this->attributes;
            }
        };
    }

    /**
     * Test formula syntax validation - valid formulas.
     *
     * @return void
     */
    public function test_validates_valid_formula_syntax(): void
    {
        $validFormulas = [
            'price * quantity',
            'if(status == 1, active_price, inactive_price)',
            'concat(first_name, " ", last_name)',
            'round(price * 1.1, 2)',
            'max(price1, price2, price3)',
        ];

        foreach ($validFormulas as $formulaLogic) {
            $mockQuery = $this->createMockQuery(['price' => 100, 'quantity' => 2]);
            
            $formula = new Formula([
                'logic' => $formulaLogic,
                'name' => 'test_column',
                'field_lists' => ['price', 'quantity'],
            ], $mockQuery);

            // Should not throw exception
            $result = $formula->calculate();
            $this->assertIsNumeric($result);
        }
    }

    /**
     * Test formula syntax validation - invalid formulas.
     *
     * @return void
     */
    public function test_rejects_invalid_formula_syntax(): void
    {
        $invalidFormulas = [
            'eval(malicious_code)',
            'exec("rm -rf /")',
            'system("cat /etc/passwd")',
            '__construct()',
            '$$variable',
            '`backtick command`',
        ];

        foreach ($invalidFormulas as $formulaLogic) {
            $mockQuery = $this->createMockQuery(['price' => 100]);
            
            $formula = new Formula([
                'logic' => $formulaLogic,
                'name' => 'test_column',
                'field_lists' => ['price'],
            ], $mockQuery);

            // Should return 0 for invalid formulas
            $result = $formula->calculate();
            $this->assertEquals(0, $result);
        }
    }

    /**
     * Test mathematical operations - basic arithmetic.
     *
     * @return void
     */
    public function test_mathematical_operations_basic_arithmetic(): void
    {
        $testCases = [
            ['formula' => 'price + tax', 'data' => ['price' => 100, 'tax' => 10], 'expected' => 110],
            ['formula' => 'price - discount', 'data' => ['price' => 100, 'discount' => 15], 'expected' => 85],
            ['formula' => 'price * quantity', 'data' => ['price' => 50, 'quantity' => 3], 'expected' => 150],
            ['formula' => 'total / count', 'data' => ['total' => 100, 'count' => 4], 'expected' => 25],
            ['formula' => 'value % 10', 'data' => ['value' => 37], 'expected' => 7],
        ];

        foreach ($testCases as $testCase) {
            $mockQuery = $this->createMockQuery($testCase['data']);
            
            $formula = new Formula([
                'logic' => $testCase['formula'],
                'name' => 'result',
                'field_lists' => array_keys($testCase['data']),
            ], $mockQuery);

            $result = $formula->calculate();
            $this->assertEquals($testCase['expected'], $result, "Failed for formula: {$testCase['formula']}");
        }
    }

    /**
     * Test mathematical operations - advanced functions.
     *
     * @return void
     */
    public function test_mathematical_operations_advanced_functions(): void
    {
        $testCases = [
            ['formula' => 'abs(value)', 'data' => ['value' => -25], 'expected' => 25],
            ['formula' => 'ceil(value)', 'data' => ['value' => 4.3], 'expected' => 5],
            ['formula' => 'floor(value)', 'data' => ['value' => 4.7], 'expected' => 4],
            ['formula' => 'round(value)', 'data' => ['value' => 4.6], 'expected' => 5],
            ['formula' => 'sqrt(value)', 'data' => ['value' => 16], 'expected' => 4],
            ['formula' => 'pow(base, exp)', 'data' => ['base' => 2, 'exp' => 3], 'expected' => 8],
            ['formula' => 'min(a, b, c)', 'data' => ['a' => 10, 'b' => 5, 'c' => 15], 'expected' => 5],
            ['formula' => 'max(a, b, c)', 'data' => ['a' => 10, 'b' => 5, 'c' => 15], 'expected' => 15],
        ];

        foreach ($testCases as $testCase) {
            $mockQuery = $this->createMockQuery($testCase['data']);
            
            $formula = new Formula([
                'logic' => $testCase['formula'],
                'name' => 'result',
                'field_lists' => array_keys($testCase['data']),
            ], $mockQuery);

            $result = $formula->calculate();
            $this->assertEquals($testCase['expected'], $result, "Failed for formula: {$testCase['formula']}");
        }
    }

    /**
     * Test string operations.
     *
     * @return void
     */
    public function test_string_operations(): void
    {
        $testCases = [
            [
                'formula' => 'concat(first_name, " ", last_name)',
                'data' => ['first_name' => 'John', 'last_name' => 'Doe'],
                'expected' => 'John Doe'
            ],
            [
                'formula' => 'uppercase(name)',
                'data' => ['name' => 'hello'],
                'expected' => 'HELLO'
            ],
            [
                'formula' => 'lowercase(name)',
                'data' => ['name' => 'WORLD'],
                'expected' => 'world'
            ],
            [
                'formula' => 'length(text)',
                'data' => ['text' => 'Hello'],
                'expected' => 5
            ],
            [
                'formula' => 'trim(text)',
                'data' => ['text' => '  spaces  '],
                'expected' => 'spaces'
            ],
        ];

        foreach ($testCases as $testCase) {
            $mockQuery = $this->createMockQuery($testCase['data']);
            
            $formula = new Formula([
                'logic' => $testCase['formula'],
                'name' => 'result',
                'field_lists' => array_keys($testCase['data']),
            ], $mockQuery);

            $result = $formula->calculate();
            $this->assertEquals($testCase['expected'], $result, "Failed for formula: {$testCase['formula']}");
        }
    }

    /**
     * Test conditional logic - IF statements.
     *
     * @return void
     */
    public function test_conditional_logic_if_statements(): void
    {
        $testCases = [
            [
                'formula' => 'if(status == 1, 100, 0)',
                'data' => ['status' => 1],
                'expected' => 100
            ],
            [
                'formula' => 'if(status == 1, 100, 0)',
                'data' => ['status' => 0],
                'expected' => 0
            ],
            [
                'formula' => 'if(price > 100, price * 0.9, price)',
                'data' => ['price' => 150],
                'expected' => 135
            ],
            [
                'formula' => 'if(price > 100, price * 0.9, price)',
                'data' => ['price' => 50],
                'expected' => 50
            ],
        ];

        foreach ($testCases as $testCase) {
            $mockQuery = $this->createMockQuery($testCase['data']);
            
            $formula = new Formula([
                'logic' => $testCase['formula'],
                'name' => 'result',
                'field_lists' => array_keys($testCase['data']),
            ], $mockQuery);

            $result = $formula->calculate();
            $this->assertEquals($testCase['expected'], $result, "Failed for formula: {$testCase['formula']}");
        }
    }

    /**
     * Test conditional logic - AND, OR, NOT.
     *
     * @return void
     */
    public function test_conditional_logic_boolean_operators(): void
    {
        $testCases = [
            [
                'formula' => 'and(a, b)',
                'data' => ['a' => 1, 'b' => 1],
                'expected' => 1
            ],
            [
                'formula' => 'and(a, b)',
                'data' => ['a' => 1, 'b' => 0],
                'expected' => 0
            ],
            [
                'formula' => 'or(a, b)',
                'data' => ['a' => 0, 'b' => 1],
                'expected' => 1
            ],
            [
                'formula' => 'or(a, b)',
                'data' => ['a' => 0, 'b' => 0],
                'expected' => 0
            ],
            [
                'formula' => 'not(a)',
                'data' => ['a' => 0],
                'expected' => 1
            ],
            [
                'formula' => 'not(a)',
                'data' => ['a' => 1],
                'expected' => 0
            ],
        ];

        foreach ($testCases as $testCase) {
            $mockQuery = $this->createMockQuery($testCase['data']);
            
            $formula = new Formula([
                'logic' => $testCase['formula'],
                'name' => 'result',
                'field_lists' => array_keys($testCase['data']),
            ], $mockQuery);

            $result = $formula->calculate();
            $this->assertEquals($testCase['expected'], $result, "Failed for formula: {$testCase['formula']}");
        }
    }

    /**
     * Test error handling - division by zero.
     *
     * @return void
     */
    public function test_error_handling_division_by_zero(): void
    {
        $mockQuery = $this->createMockQuery(['total' => 100, 'count' => 0]);
        
        $formula = new Formula([
            'logic' => 'total / count',
            'name' => 'result',
            'field_lists' => ['total', 'count'],
        ], $mockQuery);

        // Should return 0 instead of throwing exception
        $result = $formula->calculate();
        $this->assertEquals(0, $result);
    }

    /**
     * Test error handling - invalid operations.
     *
     * @return void
     */
    public function test_error_handling_invalid_operations(): void
    {
        $mockQuery = $this->createMockQuery(['value' => 'not_a_number']);
        
        $formula = new Formula([
            'logic' => 'value * 2',
            'name' => 'result',
            'field_lists' => ['value'],
        ], $mockQuery);

        // Should return 0 for invalid operations
        $result = $formula->calculate();
        $this->assertIsNumeric($result);
    }

    /**
     * Test complex formulas - nested operations.
     *
     * @return void
     */
    public function test_complex_formulas_nested_operations(): void
    {
        $testCases = [
            [
                'formula' => 'if(quantity > 10, price * quantity * 0.9, price * quantity)',
                'data' => ['price' => 50, 'quantity' => 15],
                'expected' => 675 // 50 * 15 * 0.9
            ],
            [
                'formula' => 'round((price + tax) * quantity, 2)',
                'data' => ['price' => 10.5, 'tax' => 1.5, 'quantity' => 3],
                'expected' => 36
            ],
            [
                'formula' => 'max(price1 * 1.1, price2 * 1.2, price3)',
                'data' => ['price1' => 100, 'price2' => 90, 'price3' => 120],
                'expected' => 120
            ],
        ];

        foreach ($testCases as $testCase) {
            $mockQuery = $this->createMockQuery($testCase['data']);
            
            $formula = new Formula([
                'logic' => $testCase['formula'],
                'name' => 'result',
                'field_lists' => array_keys($testCase['data']),
            ], $mockQuery);

            $result = $formula->calculate();
            $this->assertEquals($testCase['expected'], $result, "Failed for formula: {$testCase['formula']}");
        }
    }

    /**
     * Test XSS protection in string results.
     *
     * @return void
     */
    public function test_xss_protection_in_string_results(): void
    {
        $mockQuery = $this->createMockQuery([
            'name' => '<script>alert("XSS")</script>',
            'suffix' => '<img src=x onerror=alert(1)>'
        ]);
        
        $formula = new Formula([
            'logic' => 'concat(name, " ", suffix)',
            'name' => 'result',
            'field_lists' => ['name', 'suffix'],
        ], $mockQuery);

        $result = $formula->calculate();
        
        // Result should be escaped
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringNotContainsString('<img', $result);
        $this->assertStringContainsString('&lt;', $result);
    }

    /**
     * Test performance metrics tracking.
     *
     * @return void
     */
    public function test_performance_metrics_tracking(): void
    {
        Formula::resetPerformanceMetrics();
        
        $mockQuery = $this->createMockQuery(['price' => 100, 'quantity' => 2]);
        
        $formula = new Formula([
            'logic' => 'price * quantity',
            'name' => 'total',
            'field_lists' => ['price', 'quantity'],
        ], $mockQuery);

        // First call - cache miss
        $formula->calculate();
        
        // Second call - cache hit
        $formula->calculate();
        
        $metrics = Formula::getPerformanceMetrics();
        
        $this->assertEquals(2, $metrics['call_count']);
        $this->assertEquals(1, $metrics['cache_hits']);
        $this->assertEquals(1, $metrics['cache_misses']);
        $this->assertGreaterThan(0, $metrics['total_time_ms']);
    }

    /**
     * Test formula result caching.
     *
     * @return void
     */
    public function test_formula_result_caching(): void
    {
        Formula::resetPerformanceMetrics();
        
        $mockQuery = $this->createMockQuery(['a' => 10, 'b' => 20]);
        
        $formula = new Formula([
            'logic' => 'a + b',
            'name' => 'sum',
            'field_lists' => ['a', 'b'],
        ], $mockQuery);

        // Multiple calls with same data should use cache
        $result1 = $formula->calculate();
        $result2 = $formula->calculate();
        $result3 = $formula->calculate();
        
        $this->assertEquals($result1, $result2);
        $this->assertEquals($result2, $result3);
        
        $metrics = Formula::getPerformanceMetrics();
        $this->assertEquals(3, $metrics['call_count']);
        $this->assertEquals(2, $metrics['cache_hits']); // 2nd and 3rd calls are cache hits
    }
}
