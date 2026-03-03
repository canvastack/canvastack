<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table\Processors;

use Canvastack\Canvastack\Components\Table\Processors\FormulaCalculator;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for FormulaCalculator.
 */
class FormulaCalculatorTest extends TestCase
{
    private FormulaCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new FormulaCalculator();
    }

    /**
     * Test parseFormula replaces placeholders with field names.
     */
    public function test_parse_formula_replaces_placeholders(): void
    {
        $logic = '{0} + {1}';
        $fields = ['price', 'tax'];

        $result = $this->calculator->parseFormula($logic, $fields);

        $this->assertEquals('price + tax', $result);
    }

    /**
     * Test parseFormula with multiple placeholders.
     */
    public function test_parse_formula_with_multiple_placeholders(): void
    {
        $logic = '{0} * {1} + {2}';
        $fields = ['quantity', 'price', 'discount'];

        $result = $this->calculator->parseFormula($logic, $fields);

        $this->assertEquals('quantity * price + discount', $result);
    }

    /**
     * Test parseFormula validates operators.
     */
    public function test_parse_formula_validates_operators(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('invalid operators');

        $logic = '{0} ^ {1}'; // ^ is not allowed
        $fields = ['a', 'b'];

        $this->calculator->parseFormula($logic, $fields);
    }

    /**
     * Test calculateValue with addition.
     */
    public function test_calculate_value_with_addition(): void
    {
        $row = ['price' => 100, 'tax' => 10];
        $logic = '{0} + {1}';
        $fields = ['price', 'tax'];

        $result = $this->calculator->calculateValue($row, $logic, $fields);

        $this->assertEquals(110, $result);
    }

    /**
     * Test calculateValue with subtraction.
     */
    public function test_calculate_value_with_subtraction(): void
    {
        $row = ['price' => 100, 'discount' => 20];
        $logic = '{0} - {1}';
        $fields = ['price', 'discount'];

        $result = $this->calculator->calculateValue($row, $logic, $fields);

        $this->assertEquals(80, $result);
    }

    /**
     * Test calculateValue with multiplication.
     */
    public function test_calculate_value_with_multiplication(): void
    {
        $row = ['quantity' => 5, 'price' => 20];
        $logic = '{0} * {1}';
        $fields = ['quantity', 'price'];

        $result = $this->calculator->calculateValue($row, $logic, $fields);

        $this->assertEquals(100, $result);
    }

    /**
     * Test calculateValue with division.
     */
    public function test_calculate_value_with_division(): void
    {
        $row = ['total' => 100, 'count' => 4];
        $logic = '{0} / {1}';
        $fields = ['total', 'count'];

        $result = $this->calculator->calculateValue($row, $logic, $fields);

        $this->assertEquals(25, $result);
    }

    /**
     * Test calculateValue with modulo.
     */
    public function test_calculate_value_with_modulo(): void
    {
        $row = ['value' => 17, 'divisor' => 5];
        $logic = '{0} % {1}';
        $fields = ['value', 'divisor'];

        $result = $this->calculator->calculateValue($row, $logic, $fields);

        $this->assertEquals(2, $result);
    }

    /**
     * Test calculateValue handles division by zero.
     */
    public function test_calculate_value_handles_division_by_zero(): void
    {
        $row = ['total' => 100, 'count' => 0];
        $logic = '{0} / {1}';
        $fields = ['total', 'count'];

        $result = $this->calculator->calculateValue($row, $logic, $fields);

        $this->assertEquals(0, $result);
    }

    /**
     * Test calculateValue with complex arithmetic expression.
     */
    public function test_calculate_value_with_complex_expression(): void
    {
        $row = ['quantity' => 5, 'price' => 20, 'discount' => 10];
        $logic = '{0} * {1} - {2}';
        $fields = ['quantity', 'price', 'discount'];

        $result = $this->calculator->calculateValue($row, $logic, $fields);

        $this->assertEquals(90, $result);
    }

    /**
     * Test calculateValue with parentheses.
     */
    public function test_calculate_value_with_parentheses(): void
    {
        $row = ['a' => 5, 'b' => 3, 'c' => 2];
        $logic = '{0} * ({1} + {2})';
        $fields = ['a', 'b', 'c'];

        $result = $this->calculator->calculateValue($row, $logic, $fields);

        $this->assertEquals(25, $result);
    }

    /**
     * Test calculateValue with logical OR operator.
     */
    public function test_calculate_value_with_logical_or(): void
    {
        $row = ['a' => 0, 'b' => 1];
        $logic = '{0} || {1}';
        $fields = ['a', 'b'];

        $result = $this->calculator->calculateValue($row, $logic, $fields);

        $this->assertTrue($result);
    }

    /**
     * Test calculateValue with logical OR returns false when both are zero.
     */
    public function test_calculate_value_with_logical_or_both_zero(): void
    {
        $row = ['a' => 0, 'b' => 0];
        $logic = '{0} || {1}';
        $fields = ['a', 'b'];

        $result = $this->calculator->calculateValue($row, $logic, $fields);

        $this->assertFalse($result);
    }

    /**
     * Test calculateValue with logical AND operator.
     */
    public function test_calculate_value_with_logical_and(): void
    {
        $row = ['a' => 1, 'b' => 1];
        $logic = '{0} && {1}';
        $fields = ['a', 'b'];

        $result = $this->calculator->calculateValue($row, $logic, $fields);

        $this->assertTrue($result);
    }

    /**
     * Test calculateValue with logical AND returns false when one is zero.
     */
    public function test_calculate_value_with_logical_and_one_zero(): void
    {
        $row = ['a' => 1, 'b' => 0];
        $logic = '{0} && {1}';
        $fields = ['a', 'b'];

        $result = $this->calculator->calculateValue($row, $logic, $fields);

        $this->assertFalse($result);
    }

    /**
     * Test calculateValue handles missing field in row.
     */
    public function test_calculate_value_handles_missing_field(): void
    {
        $row = ['price' => 100];
        $logic = '{0} + {1}';
        $fields = ['price', 'tax'];

        $result = $this->calculator->calculateValue($row, $logic, $fields);

        $this->assertEquals(100, $result); // Missing field defaults to 0
    }

    /**
     * Test calculateValue handles non-numeric values.
     */
    public function test_calculate_value_handles_non_numeric_values(): void
    {
        $row = ['price' => 'invalid', 'tax' => 10];
        $logic = '{0} + {1}';
        $fields = ['price', 'tax'];

        $result = $this->calculator->calculateValue($row, $logic, $fields);

        $this->assertEquals(10, $result); // Non-numeric converts to 0
    }

    /**
     * Test calculateValue with decimal numbers.
     */
    public function test_calculate_value_with_decimal_numbers(): void
    {
        $row = ['price' => 19.99, 'tax' => 2.50];
        $logic = '{0} + {1}';
        $fields = ['price', 'tax'];

        $result = $this->calculator->calculateValue($row, $logic, $fields);

        $this->assertEquals(22.49, $result);
    }

    /**
     * Test calculateValue with negative numbers.
     */
    public function test_calculate_value_with_negative_numbers(): void
    {
        $row = ['value' => 100, 'adjustment' => -20];
        $logic = '{0} + {1}';
        $fields = ['value', 'adjustment'];

        $result = $this->calculator->calculateValue($row, $logic, $fields);

        $this->assertEquals(80, $result);
    }

    /**
     * Test parseFormula with no placeholders.
     */
    public function test_parse_formula_with_no_placeholders(): void
    {
        $logic = '100 + 200';
        $fields = [];

        $result = $this->calculator->parseFormula($logic, $fields);

        $this->assertEquals('100 + 200', $result);
    }

    /**
     * Test calculateValue validates operators.
     */
    public function test_calculate_value_validates_operators(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $row = ['a' => 5, 'b' => 3];
        $logic = '{0} ^ {1}'; // Invalid operator
        $fields = ['a', 'b'];

        $this->calculator->calculateValue($row, $logic, $fields);
    }

    /**
     * Test calculateValue with percentage calculation.
     */
    public function test_calculate_value_with_percentage_calculation(): void
    {
        $row = ['price' => 100, 'percentage' => 15];
        $logic = '{0} * {1} / 100';
        $fields = ['price', 'percentage'];

        $result = $this->calculator->calculateValue($row, $logic, $fields);

        $this->assertEquals(15, $result);
    }

    /**
     * Test calculateValue with order of operations.
     */
    public function test_calculate_value_respects_order_of_operations(): void
    {
        $row = ['a' => 2, 'b' => 3, 'c' => 4];
        $logic = '{0} + {1} * {2}';
        $fields = ['a', 'b', 'c'];

        $result = $this->calculator->calculateValue($row, $logic, $fields);

        $this->assertEquals(14, $result); // 2 + (3 * 4) = 14, not (2 + 3) * 4 = 20
    }
}
