<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Table\Processors;

/**
 * FormulaCalculator - Parses and calculates formulas for computed table columns.
 *
 * FEATURES:
 * - Arithmetic operations: +, -, *, /, % (modulo)
 * - Logical operations: || (OR), && (AND)
 * - Field placeholder replacement: {0}, {1}, {2}, etc.
 * - Safe expression evaluation with validation
 * - Division by zero handling
 *
 * SECURITY:
 * - Validates operators to prevent code injection
 * - Only allows whitelisted operators
 * - Sanitizes expressions before evaluation
 *
 * USE CASES:
 * - Calculate totals: "{0} + {1}" (price + tax)
 * - Calculate percentages: "{0} / {1} * 100" (sold / total * 100)
 * - Conditional logic: "{0} || {1}" (use value1 OR value2)
 * - Complex formulas: "({0} + {1}) * {2} / 100" (compound calculations)
 */
class FormulaCalculator
{
    /**
     * Supported arithmetic operators.
     */
    private const LOGICAL_OPERATORS = ['||', '&&'];

    /**
     * All supported operators.
     */
    private const ALLOWED_OPERATORS = ['+', '-', '*', '/', '%', '||', '&&'];

    /**
     * Parse a formula logic string and replace field placeholders with actual field names.
     *
     * Replaces numeric placeholders ({0}, {1}, {2}, etc.) with actual field names
     * from the provided array. Validates that only allowed operators are used.
     *
     * PLACEHOLDER FORMAT:
     * - {0} = first field in fieldLists array
     * - {1} = second field in fieldLists array
     * - {n} = (n+1)th field in fieldLists array
     *
     * SECURITY: Validates operators before parsing to prevent code injection.
     *
     * @param string $logic The formula logic string (e.g., "{0} + {1} * 100")
     * @param array $fieldLists Array of field names to replace placeholders (indexed array)
     * @return string The parsed formula with field names replacing placeholders
     *
     * @throws \InvalidArgumentException If the logic contains invalid operators
     *
     * @example
     * parseFormula("{0} + {1}", ['price', 'tax']) // Returns: "price + tax"
     * parseFormula("{0} * 100 / {1}", ['sold', 'total']) // Returns: "sold * 100 / total"
     */
    public function parseFormula(string $logic, array $fieldLists): string
    {
        // Validate operators in the logic
        $this->validateOperators($logic);

        // Replace field placeholders {0}, {1}, etc. with actual field names
        $parsedLogic = $logic;
        foreach ($fieldLists as $index => $fieldName) {
            $placeholder = '{' . $index . '}';
            if (is_string($parsedLogic)) {
                $parsedLogic = str_replace($placeholder, $fieldName, $parsedLogic);
            }
        }

        return $parsedLogic;
    }

    /**
     * Calculate the formula value for a given row.
     *
     * Evaluates a formula by replacing field placeholders with actual values
     * from the row data, then calculating the result.
     *
     * PROCESS:
     * 1. Validates operators in the formula
     * 2. Replaces field placeholders with actual values from row
     * 3. Determines if formula is arithmetic or logical
     * 4. Evaluates the expression safely
     * 5. Returns the calculated result
     *
     * SAFETY:
     * - Non-numeric values are treated as 0
     * - Division by zero returns 0
     * - Invalid expressions return 0
     * - All errors are caught and handled gracefully
     *
     * @param array $row The data row containing field values (associative array)
     * @param string $logic The formula logic string (e.g., "{0} + {1} * 100")
     * @param array $fieldLists Array of field names used in the formula (indexed array)
     * @return mixed The calculated result (float, int, or bool for logical expressions)
     *
     * @throws \InvalidArgumentException If the logic contains invalid operators
     *
     * @example
     * calculateValue(['price' => 100, 'tax' => 10], "{0} + {1}", ['price', 'tax']) // Returns: 110
     * calculateValue(['sold' => 50, 'total' => 100], "{0} / {1} * 100", ['sold', 'total']) // Returns: 50.0
     */
    public function calculateValue(array $row, string $logic, array $fieldLists): mixed
    {
        // Validate operators
        $this->validateOperators($logic);

        // Replace field placeholders with actual values
        $expression = $logic;
        foreach ($fieldLists as $index => $fieldName) {
            $placeholder = '{' . $index . '}';
            $value = $row[$fieldName] ?? 0;

            // Convert to numeric value for calculations
            $numericValue = is_numeric($value) ? $value : 0;

            if (is_string($expression)) {
                $expression = str_replace($placeholder, (string) $numericValue, $expression);
            }
        }

        // Check if this is a logical expression
        if ($this->containsLogicalOperators($expression)) {
            return $this->evaluateLogicalExpression($expression);
        }

        // Evaluate arithmetic expression
        return $this->evaluateArithmeticExpression($expression);
    }

    /**
     * Validate that the logic contains only allowed operators.
     *
     * @param string $logic The formula logic string
     * @throws \InvalidArgumentException If invalid operators are found
     */
    private function validateOperators(string $logic): void
    {
        // Remove placeholders, numbers, spaces, and parentheses
        $cleaned = preg_replace('/\{[0-9]+\}|[0-9.]+|\s+|\(|\)/', '', $logic);

        // Check for remaining characters that aren't allowed operators
        $operators = self::ALLOWED_OPERATORS;
        foreach ($operators as $operator) {
            if (is_string($cleaned)) {
                $cleaned = str_replace($operator, '', $cleaned);
            }
        }

        if (!empty($cleaned)) {
            throw new \InvalidArgumentException(
                'Formula contains invalid operators or characters. ' .
                'Allowed operators: ' . implode(', ', self::ALLOWED_OPERATORS)
            );
        }
    }

    /**
     * Check if the expression contains logical operators.
     *
     * @param string $expression The expression to check
     * @return bool True if logical operators are present
     */
    private function containsLogicalOperators(string $expression): bool
    {
        foreach (self::LOGICAL_OPERATORS as $operator) {
            if (strpos($expression, $operator) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Evaluate a logical expression.
     *
     * @param string $expression The logical expression to evaluate
     * @return bool The result of the logical expression
     */
    private function evaluateLogicalExpression(string $expression): bool
    {
        // Handle || (OR) operator
        if (strpos($expression, '||') !== false) {
            $parts = explode('||', $expression);
            foreach ($parts as $part) {
                if ($this->evaluateArithmeticExpression(trim($part)) != 0) {
                    return true;
                }
            }

            return false;
        }

        // Handle && (AND) operator
        if (strpos($expression, '&&') !== false) {
            $parts = explode('&&', $expression);
            foreach ($parts as $part) {
                if ($this->evaluateArithmeticExpression(trim($part)) == 0) {
                    return false;
                }
            }

            return true;
        }

        // Single value - convert to boolean
        return $this->evaluateArithmeticExpression($expression) != 0;
    }

    /**
     * Evaluate an arithmetic expression.
     *
     * @param string $expression The arithmetic expression to evaluate
     * @return float|int The calculated result
     */
    private function evaluateArithmeticExpression(string $expression): float|int
    {
        // Remove whitespace
        $expression = str_replace(' ', '', $expression);

        // Handle division by zero by checking for /0 patterns
        if (preg_match('/\/0(?![0-9.])/', $expression)) {
            return 0; // Return 0 for division by zero
        }

        try {
            // Use a safe evaluation approach
            // First, validate that the expression only contains numbers and operators
            if (!preg_match('/^[0-9+\-*\/%.()\s]+$/', $expression)) {
                return 0;
            }

            // Evaluate the expression safely
            // Note: In production, consider using a proper expression parser library
            // For now, we'll use a simple eval with strict validation
            $result = @eval('return ' . $expression . ';');

            if ($result === false || $result === null) {
                return 0;
            }

            return is_numeric($result) ? (is_float($result) ? $result : (int) $result) : 0;
        } catch (\Throwable $e) {
            // Handle any errors gracefully
            return 0;
        }
    }
}
