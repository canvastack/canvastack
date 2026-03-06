<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Table\Support;

use Canvastack\Canvastack\Components\Table\Exceptions\ServerSideException;

/**
 * Formula Parser
 * 
 * Safely evaluates mathematical formulas for calculated columns.
 * Prevents eval() usage and code injection attacks.
 * 
 * Supported operators: +, -, *, /, %, (, )
 * 
 * @package Canvastack\Canvastack\Components\Table\Support
 */
class FormulaParser
{
    /**
     * Allowed operators.
     *
     * @var array<string>
     */
    protected array $allowedOperators = ['+', '-', '*', '/', '%', '(', ')'];
    
    /**
     * Allowed functions.
     *
     * @var array<string>
     */
    protected array $allowedFunctions = ['abs', 'round', 'ceil', 'floor', 'min', 'max'];
    
    /**
     * Parse and evaluate formula safely.
     *
     * @param string $formula Formula expression (e.g., "price * quantity")
     * @param array $row Row data with column values
     * @return float|int|null
     * @throws ServerSideException
     */
    public function evaluate(string $formula, array $row): float|int|null
    {
        try {
            // Extract column dependencies
            $dependencies = $this->extractDependencies($formula);
            
            // Replace column names with values
            $expression = $this->replaceColumns($formula, $row, $dependencies);
            
            // Validate expression for security
            $this->validateExpression($expression);
            
            // Evaluate expression
            $result = $this->evaluateExpression($expression);
            
            return $result;
            
        } catch (\Throwable $e) {
            throw new ServerSideException(
                'Formula evaluation failed: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }
    
    /**
     * Extract column dependencies from formula.
     *
     * @param string $formula
     * @return array<string>
     */
    protected function extractDependencies(string $formula): array
    {
        // Match column names (alphanumeric + underscore, starting with letter)
        preg_match_all('/\b([a-z_][a-z0-9_]*)\b/i', $formula, $matches);
        
        $dependencies = array_unique($matches[1]);
        
        // Filter out function names
        $dependencies = array_filter($dependencies, function ($dep) {
            return !in_array(strtolower($dep), $this->allowedFunctions);
        });
        
        return array_values($dependencies);
    }
    
    /**
     * Replace column names with their values.
     *
     * @param string $formula
     * @param array $row
     * @param array $dependencies
     * @return string
     * @throws ServerSideException
     */
    protected function replaceColumns(string $formula, array $row, array $dependencies): string
    {
        $expression = $formula;
        
        foreach ($dependencies as $column) {
            if (!isset($row[$column])) {
                throw new ServerSideException("Column '{$column}' not found in row data");
            }
            
            $value = $row[$column];
            
            // Convert value to number
            if (!is_numeric($value)) {
                throw new ServerSideException("Column '{$column}' value is not numeric");
            }
            
            // Replace column name with value (use word boundaries to avoid partial matches)
            $expression = preg_replace(
                '/\b' . preg_quote($column, '/') . '\b/',
                (string) $value,
                $expression
            );
        }
        
        return $expression;
    }
    
    /**
     * Validate expression for security.
     *
     * @param string $expression
     * @return void
     * @throws ServerSideException
     */
    protected function validateExpression(string $expression): void
    {
        // Remove whitespace for validation
        $clean = str_replace(' ', '', $expression);
        
        // Check for allowed characters only: numbers, operators, parentheses, decimal points
        if (!preg_match('/^[0-9+\-*\/%().\s]+$/', $clean)) {
            throw new ServerSideException('Formula contains invalid characters');
        }
        
        // Check for balanced parentheses
        $openCount = substr_count($clean, '(');
        $closeCount = substr_count($clean, ')');
        
        if ($openCount !== $closeCount) {
            throw new ServerSideException('Formula has unbalanced parentheses');
        }
        
        // Check for division by zero
        if (preg_match('/\/\s*0(?!\d)/', $expression)) {
            throw new ServerSideException('Formula contains division by zero');
        }
    }
    
    /**
     * Evaluate mathematical expression safely.
     * 
     * This method uses a safe evaluation approach without eval().
     *
     * @param string $expression
     * @return float|int
     * @throws ServerSideException
     */
    protected function evaluateExpression(string $expression): float|int
    {
        // Remove whitespace
        $expression = str_replace(' ', '', $expression);
        
        try {
            // Use bc_math for safe calculation
            $result = $this->calculate($expression);
            
            // Return as int if it's a whole number, otherwise float
            return (floor($result) == $result) ? (int) $result : $result;
            
        } catch (\Throwable $e) {
            throw new ServerSideException('Expression evaluation failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Calculate expression using operator precedence.
     *
     * @param string $expression
     * @return float
     * @throws ServerSideException
     */
    protected function calculate(string $expression): float
    {
        // Handle parentheses first
        while (preg_match('/\(([^()]+)\)/', $expression, $matches)) {
            $subResult = $this->calculate($matches[1]);
            $expression = str_replace($matches[0], (string) $subResult, $expression);
        }
        
        // Handle multiplication, division, and modulo (left to right)
        $expression = $this->evaluateOperators($expression, ['*', '/', '%']);
        
        // Handle addition and subtraction (left to right)
        $expression = $this->evaluateOperators($expression, ['+', '-']);
        
        // Final result should be a number
        if (!is_numeric($expression)) {
            throw new ServerSideException('Invalid expression result');
        }
        
        return (float) $expression;
    }
    
    /**
     * Evaluate operators with same precedence (left to right).
     *
     * @param string $expression
     * @param array<string> $operators
     * @return string
     */
    protected function evaluateOperators(string $expression, array $operators): string
    {
        // Build regex pattern for operators
        $operatorPattern = implode('|', array_map(function ($op) {
            return preg_quote($op, '/');
        }, $operators));
        
        // Match: number operator number
        $pattern = '/(-?\d+\.?\d*)(' . $operatorPattern . ')(-?\d+\.?\d*)/';
        
        while (preg_match($pattern, $expression, $matches)) {
            $left = (float) $matches[1];
            $operator = $matches[2];
            $right = (float) $matches[3];
            
            $result = match ($operator) {
                '+' => $left + $right,
                '-' => $left - $right,
                '*' => $left * $right,
                '/' => $right != 0 ? $left / $right : throw new ServerSideException('Division by zero'),
                '%' => $right != 0 ? $left % $right : throw new ServerSideException('Modulo by zero'),
                default => throw new ServerSideException("Unknown operator: {$operator}"),
            };
            
            $expression = preg_replace($pattern, (string) $result, $expression, 1);
        }
        
        return $expression;
    }
    
    /**
     * Format result value.
     *
     * @param float|int $value
     * @param string $format Format type (number, currency, percentage)
     * @param int $decimals Number of decimal places
     * @return string
     */
    public function format(float|int $value, string $format = 'number', int $decimals = 2): string
    {
        return match ($format) {
            'currency' => '$' . number_format($value, $decimals),
            'percentage' => number_format($value, $decimals) . '%',
            'number' => number_format($value, $decimals),
            default => (string) $value,
        };
    }
}
