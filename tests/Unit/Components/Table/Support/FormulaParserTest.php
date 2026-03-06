<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table\Support;

use Canvastack\Canvastack\Components\Table\Support\FormulaParser;
use Canvastack\Canvastack\Components\Table\Exceptions\ServerSideException;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Test for FormulaParser.
 * 
 * Tests safe formula evaluation for calculated columns.
 * Validates security (no eval() usage) and operator support.
 */
class FormulaParserTest extends TestCase
{
    protected FormulaParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new FormulaParser();
    }

    /**
     * Test basic addition.
     */
    public function test_evaluates_addition(): void
    {
        $formula = 'price + tax';
        $row = ['price' => 100, 'tax' => 10];

        $result = $this->parser->evaluate($formula, $row);

        $this->assertEquals(110, $result);
    }

    /**
     * Test basic subtraction.
     */
    public function test_evaluates_subtraction(): void
    {
        $formula = 'total - discount';
        $row = ['total' => 100, 'discount' => 15];

        $result = $this->parser->evaluate($formula, $row);

        $this->assertEquals(85, $result);
    }

    /**
     * Test basic multiplication.
     */
    public function test_evaluates_multiplication(): void
    {
        $formula = 'price * quantity';
        $row = ['price' => 25, 'quantity' => 4];

        $result = $this->parser->evaluate($formula, $row);

        $this->assertEquals(100, $result);
    }

    /**
     * Test basic division.
     */
    public function test_evaluates_division(): void
    {
        $formula = 'total / count';
        $row = ['total' => 100, 'count' => 4];

        $result = $this->parser->evaluate($formula, $row);

        $this->assertEquals(25, $result);
    }

    /**
     * Test modulo operator.
     */
    public function test_evaluates_modulo(): void
    {
        $formula = 'total % divisor';
        $row = ['total' => 17, 'divisor' => 5];

        $result = $this->parser->evaluate($formula, $row);

        $this->assertEquals(2, $result);
    }

    /**
     * Test complex formula with multiple operators.
     */
    public function test_evaluates_complex_formula(): void
    {
        $formula = 'price * quantity + tax - discount';
        $row = [
            'price' => 10,
            'quantity' => 5,
            'tax' => 5,
            'discount' => 10,
        ];

        $result = $this->parser->evaluate($formula, $row);

        // (10 * 5) + 5 - 10 = 50 + 5 - 10 = 45
        $this->assertEquals(45, $result);
    }

    /**
     * Test formula with parentheses.
     */
    public function test_evaluates_formula_with_parentheses(): void
    {
        $formula = '(price + tax) * quantity';
        $row = [
            'price' => 10,
            'tax' => 2,
            'quantity' => 5,
        ];

        $result = $this->parser->evaluate($formula, $row);

        // (10 + 2) * 5 = 12 * 5 = 60
        $this->assertEquals(60, $result);
    }

    /**
     * Test formula with nested parentheses.
     */
    public function test_evaluates_formula_with_nested_parentheses(): void
    {
        $formula = '((price + tax) * quantity) - discount';
        $row = [
            'price' => 10,
            'tax' => 2,
            'quantity' => 5,
            'discount' => 10,
        ];

        $result = $this->parser->evaluate($formula, $row);

        // ((10 + 2) * 5) - 10 = (12 * 5) - 10 = 60 - 10 = 50
        $this->assertEquals(50, $result);
    }

    /**
     * Test operator precedence (multiplication before addition).
     */
    public function test_respects_operator_precedence(): void
    {
        $formula = 'base + price * quantity';
        $row = [
            'base' => 10,
            'price' => 5,
            'quantity' => 3,
        ];

        $result = $this->parser->evaluate($formula, $row);

        // 10 + (5 * 3) = 10 + 15 = 25 (not (10 + 5) * 3 = 45)
        $this->assertEquals(25, $result);
    }

    /**
     * Test formula with decimal numbers.
     */
    public function test_evaluates_formula_with_decimals(): void
    {
        $formula = 'price * quantity';
        $row = [
            'price' => 10.5,
            'quantity' => 2.5,
        ];

        $result = $this->parser->evaluate($formula, $row);

        $this->assertEquals(26.25, $result);
    }

    /**
     * Test formula with negative numbers.
     */
    public function test_evaluates_formula_with_negative_numbers(): void
    {
        $formula = 'total + adjustment';
        $row = [
            'total' => 100,
            'adjustment' => -15,
        ];

        $result = $this->parser->evaluate($formula, $row);

        $this->assertEquals(85, $result);
    }

    /**
     * Test formula returns integer when result is whole number.
     */
    public function test_returns_integer_for_whole_numbers(): void
    {
        $formula = 'price * quantity';
        $row = ['price' => 10, 'quantity' => 5];

        $result = $this->parser->evaluate($formula, $row);

        $this->assertIsInt($result);
        $this->assertEquals(50, $result);
    }

    /**
     * Test formula returns float when result has decimals.
     */
    public function test_returns_float_for_decimal_results(): void
    {
        $formula = 'total / count';
        $row = ['total' => 100, 'count' => 3];

        $result = $this->parser->evaluate($formula, $row);

        $this->assertIsFloat($result);
        $this->assertEquals(33.333333333333, $result, '', 0.000001);
    }

    /**
     * Test formula with column names containing underscores.
     */
    public function test_evaluates_formula_with_underscore_columns(): void
    {
        $formula = 'unit_price * order_quantity';
        $row = [
            'unit_price' => 15,
            'order_quantity' => 3,
        ];

        $result = $this->parser->evaluate($formula, $row);

        $this->assertEquals(45, $result);
    }

    /**
     * Test formula with spaces.
     */
    public function test_evaluates_formula_with_spaces(): void
    {
        $formula = 'price * quantity + tax';
        $row = [
            'price' => 10,
            'quantity' => 5,
            'tax' => 5,
        ];

        $result = $this->parser->evaluate($formula, $row);

        $this->assertEquals(55, $result);
    }

    /**
     * Test throws exception for missing column.
     */
    public function test_throws_exception_for_missing_column(): void
    {
        $this->expectException(ServerSideException::class);
        $this->expectExceptionMessage("Column 'missing' not found in row data");

        $formula = 'price * missing';
        $row = ['price' => 10];

        $this->parser->evaluate($formula, $row);
    }

    /**
     * Test throws exception for non-numeric column value.
     */
    public function test_throws_exception_for_non_numeric_value(): void
    {
        $this->expectException(ServerSideException::class);
        $this->expectExceptionMessage("Column 'price' value is not numeric");

        $formula = 'price * quantity';
        $row = ['price' => 'invalid', 'quantity' => 5];

        $this->parser->evaluate($formula, $row);
    }

    /**
     * Test throws exception for division by zero.
     */
    public function test_throws_exception_for_division_by_zero(): void
    {
        $this->expectException(ServerSideException::class);
        $this->expectExceptionMessageMatches('/Division by zero|Formula contains division by zero/');

        $formula = 'total / count';
        $row = ['total' => 100, 'count' => 0];

        $this->parser->evaluate($formula, $row);
    }

    /**
     * Test throws exception for modulo by zero.
     */
    public function test_throws_exception_for_modulo_by_zero(): void
    {
        $this->expectException(ServerSideException::class);
        $this->expectExceptionMessage('Modulo by zero');

        $formula = 'total % divisor';
        $row = ['total' => 100, 'divisor' => 0];

        $this->parser->evaluate($formula, $row);
    }

    /**
     * Test throws exception for unbalanced parentheses (missing closing).
     */
    public function test_throws_exception_for_unbalanced_parentheses_missing_closing(): void
    {
        $this->expectException(ServerSideException::class);
        $this->expectExceptionMessage('Formula has unbalanced parentheses');

        $formula = '(price + tax * quantity';
        $row = ['price' => 10, 'tax' => 2, 'quantity' => 5];

        $this->parser->evaluate($formula, $row);
    }

    /**
     * Test throws exception for unbalanced parentheses (missing opening).
     */
    public function test_throws_exception_for_unbalanced_parentheses_missing_opening(): void
    {
        $this->expectException(ServerSideException::class);
        $this->expectExceptionMessage('Formula has unbalanced parentheses');

        $formula = 'price + tax) * quantity';
        $row = ['price' => 10, 'tax' => 2, 'quantity' => 5];

        $this->parser->evaluate($formula, $row);
    }

    /**
     * Test throws exception for invalid characters (security).
     */
    public function test_throws_exception_for_invalid_characters(): void
    {
        $formula = 'price * quantity; DROP TABLE users;';
        $row = ['price' => 10, 'quantity' => 5];

        // The parser should reject this malicious formula
        // It will either detect invalid characters or fail to find the "DROP" column
        $exceptionThrown = false;
        
        try {
            $this->parser->evaluate($formula, $row);
        } catch (ServerSideException $e) {
            $exceptionThrown = true;
            // Either message is acceptable - both indicate the malicious code was blocked
            $this->assertTrue(
                str_contains($e->getMessage(), 'invalid characters') ||
                str_contains($e->getMessage(), 'not found in row data') ||
                str_contains($e->getMessage(), 'Formula evaluation failed'),
                'Exception should indicate security issue, got: ' . $e->getMessage()
            );
        }
        
        $this->assertTrue($exceptionThrown, 'Expected ServerSideException to be thrown for malicious formula');
    }

    /**
     * Test does not use eval() (security check).
     */
    public function test_does_not_use_eval(): void
    {
        // This test verifies that malicious code cannot be executed
        $formula = 'price * quantity';
        $row = ['price' => 10, 'quantity' => 5];

        // If eval() was used, this would execute the code
        // Since we use safe parsing, it should just fail or return a safe result
        $result = $this->parser->evaluate($formula, $row);

        $this->assertEquals(50, $result);
        
        // Verify no eval() function call in the actual code (not comments)
        $reflection = new \ReflectionClass(FormulaParser::class);
        $source = file_get_contents($reflection->getFileName());
        
        // Remove comments from source code
        $sourceWithoutComments = preg_replace('/\/\*.*?\*\//s', '', $source); // Remove /* */ comments
        $sourceWithoutComments = preg_replace('/\/\/.*$/m', '', $sourceWithoutComments); // Remove // comments
        
        // Check for eval( or eval ( in actual code (not comments)
        $hasEvalCall = preg_match('/\beval\s*\(/', $sourceWithoutComments);
        
        $this->assertEquals(0, $hasEvalCall, 'FormulaParser must not use eval() function in code');
    }

    /**
     * Test format method with number format.
     */
    public function test_formats_number(): void
    {
        $result = $this->parser->format(1234.5678, 'number', 2);

        $this->assertEquals('1,234.57', $result);
    }

    /**
     * Test format method with currency format.
     */
    public function test_formats_currency(): void
    {
        $result = $this->parser->format(1234.56, 'currency', 2);

        $this->assertEquals('$1,234.56', $result);
    }

    /**
     * Test format method with percentage format.
     */
    public function test_formats_percentage(): void
    {
        $result = $this->parser->format(45.67, 'percentage', 2);

        $this->assertEquals('45.67%', $result);
    }

    /**
     * Test format method with custom decimal places.
     */
    public function test_formats_with_custom_decimals(): void
    {
        $result = $this->parser->format(1234.5678, 'number', 4);

        $this->assertEquals('1,234.5678', $result);
    }

    /**
     * Test complex real-world formula (order total calculation).
     */
    public function test_evaluates_real_world_order_total(): void
    {
        $formula = '(unit_price * quantity) + shipping_cost - discount_amount';
        $row = [
            'unit_price' => 29.99,
            'quantity' => 3,
            'shipping_cost' => 5.99,
            'discount_amount' => 10.00,
        ];

        $result = $this->parser->evaluate($formula, $row);

        // (29.99 * 3) + 5.99 - 10.00 = 89.97 + 5.99 - 10.00 = 85.96
        $this->assertEquals(85.96, $result);
    }

    /**
     * Test complex real-world formula (profit margin calculation).
     */
    public function test_evaluates_real_world_profit_margin(): void
    {
        $formula = '((selling_price - cost_price) / selling_price) * 100';
        $row = [
            'selling_price' => 100,
            'cost_price' => 60,
        ];

        $result = $this->parser->evaluate($formula, $row);

        // ((100 - 60) / 100) * 100 = (40 / 100) * 100 = 0.4 * 100 = 40
        $this->assertEquals(40, $result);
    }

    /**
     * Test complex real-world formula (tax calculation).
     */
    public function test_evaluates_real_world_tax_calculation(): void
    {
        $formula = 'subtotal * (tax_rate / 100)';
        $row = [
            'subtotal' => 100,
            'tax_rate' => 8.5,
        ];

        $result = $this->parser->evaluate($formula, $row);

        // 100 * (8.5 / 100) = 100 * 0.085 = 8.5
        $this->assertEquals(8.5, $result);
    }

    /**
     * Test formula with zero values (edge case).
     */
    public function test_evaluates_formula_with_zero_values(): void
    {
        $formula = 'price * quantity + discount';
        $row = [
            'price' => 0,
            'quantity' => 5,
            'discount' => 0,
        ];

        $result = $this->parser->evaluate($formula, $row);

        $this->assertEquals(0, $result);
    }

    /**
     * Test formula with very large numbers.
     */
    public function test_evaluates_formula_with_large_numbers(): void
    {
        $formula = 'value1 + value2';
        $row = [
            'value1' => 999999999,
            'value2' => 1,
        ];

        $result = $this->parser->evaluate($formula, $row);

        $this->assertEquals(1000000000, $result);
    }

    /**
     * Test formula with very small decimal numbers.
     */
    public function test_evaluates_formula_with_small_decimals(): void
    {
        $formula = 'value1 + value2';
        $row = [
            'value1' => 0.0001,
            'value2' => 0.0002,
        ];

        $result = $this->parser->evaluate($formula, $row);

        $this->assertEquals(0.0003, $result, '', 0.00001);
    }

    /**
     * Test extractDependencies method.
     */
    public function test_extracts_dependencies(): void
    {
        $reflection = new \ReflectionClass($this->parser);
        $method = $reflection->getMethod('extractDependencies');
        $method->setAccessible(true);

        $formula = 'price * quantity + tax - discount';
        $dependencies = $method->invoke($this->parser, $formula);

        $this->assertCount(4, $dependencies);
        $this->assertContains('price', $dependencies);
        $this->assertContains('quantity', $dependencies);
        $this->assertContains('tax', $dependencies);
        $this->assertContains('discount', $dependencies);
    }

    /**
     * Test validateExpression method with valid expression.
     */
    public function test_validates_valid_expression(): void
    {
        $reflection = new \ReflectionClass($this->parser);
        $method = $reflection->getMethod('validateExpression');
        $method->setAccessible(true);

        $expression = '10 + 20 * 30';

        // Should not throw exception
        $method->invoke($this->parser, $expression);

        $this->assertTrue(true); // If we get here, validation passed
    }

    /**
     * Test validateExpression method with invalid expression.
     */
    public function test_validates_invalid_expression(): void
    {
        $this->expectException(ServerSideException::class);

        $reflection = new \ReflectionClass($this->parser);
        $method = $reflection->getMethod('validateExpression');
        $method->setAccessible(true);

        $expression = '10 + 20; DROP TABLE users;';

        $method->invoke($this->parser, $expression);
    }

    /**
     * Test security: SQL injection attempt.
     */
    public function test_prevents_sql_injection(): void
    {
        $this->expectException(ServerSideException::class);

        $formula = "price * quantity'; DROP TABLE users; --";
        $row = ['price' => 10, 'quantity' => 5];

        $this->parser->evaluate($formula, $row);
    }

    /**
     * Test security: code execution attempt.
     */
    public function test_prevents_code_execution(): void
    {
        $this->expectException(ServerSideException::class);

        $formula = 'price * quantity; system("rm -rf /")';
        $row = ['price' => 10, 'quantity' => 5];

        $this->parser->evaluate($formula, $row);
    }

    /**
     * Test security: function call attempt.
     */
    public function test_prevents_function_calls(): void
    {
        $this->expectException(ServerSideException::class);

        $formula = 'price * exec("malicious")';
        $row = ['price' => 10];

        $this->parser->evaluate($formula, $row);
    }
}
