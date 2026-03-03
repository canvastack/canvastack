<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Table\Processors;

/**
 * ConditionalFormatter - Applies conditional formatting to table cells and rows.
 *
 * FEATURES:
 * - Condition evaluation with multiple operators (==, !=, >, <, >=, <=, ===, !==)
 * - Multiple formatting rules (CSS styling, prefix, suffix, value replacement)
 * - Cell-level and row-level formatting
 * - XSS protection through HTML escaping
 *
 * USE CASES:
 * - Highlight rows based on status (e.g., red for 'inactive', green for 'active')
 * - Add currency symbols as prefixes (e.g., '$' before amounts)
 * - Replace numeric codes with readable text (e.g., 1 -> 'Active', 0 -> 'Inactive')
 * - Apply custom CSS classes based on value ranges
 *
 * SECURITY: All output is HTML-escaped to prevent XSS attacks.
 */
class ConditionalFormatter
{
    /**
     * Supported comparison operators.
     */
    private const ALLOWED_OPERATORS = ['==', '!=', '===', '!==', '>', '<', '>=', '<='];

    /**
     * Supported formatting rules.
     */
    private const ALLOWED_RULES = ['css style', 'prefix', 'suffix', 'prefix&suffix', 'replace'];

    /**
     * Evaluate a condition by comparing a value against a comparison value using an operator.
     *
     * Performs type-safe comparisons using PHP's comparison operators.
     * Supports both loose (==, !=) and strict (===, !==) equality checks.
     *
     * OPERATORS:
     * - == : Loose equality (type coercion allowed)
     * - != : Loose inequality
     * - === : Strict equality (type must match)
     * - !== : Strict inequality
     * - > : Greater than
     * - < : Less than
     * - >= : Greater than or equal
     * - <= : Less than or equal
     *
     * @param mixed $value The value to evaluate (from table cell)
     * @param string $operator The comparison operator
     * @param mixed $compareValue The value to compare against (from condition config)
     * @return bool True if the condition is met, false otherwise
     *
     * @throws \InvalidArgumentException If the operator is not supported
     *
     * @example
     * evaluateCondition(100, '>', 50) // Returns: true
     * evaluateCondition('active', '==', 'active') // Returns: true
     * evaluateCondition('1', '===', 1) // Returns: false (strict comparison)
     */
    public function evaluateCondition(mixed $value, string $operator, mixed $compareValue): bool
    {
        if (!in_array($operator, self::ALLOWED_OPERATORS, true)) {
            throw new \InvalidArgumentException(
                "Unsupported operator: {$operator}. Allowed operators: " . implode(', ', self::ALLOWED_OPERATORS)
            );
        }

        return match ($operator) {
            '==' => $value == $compareValue,
            '!=' => $value != $compareValue,
            '===' => $value === $compareValue,
            '!==' => $value !== $compareValue,
            '>' => $value > $compareValue,
            '<' => $value < $compareValue,
            '>=' => $value >= $compareValue,
            '<=' => $value <= $compareValue,
        };
    }

    /**
     * Apply a formatting rule to a value.
     *
     * Applies one of several formatting rules to transform or style a cell value.
     * All output is HTML-escaped to prevent XSS attacks.
     *
     * RULES:
     * - 'css style': Returns CSS class string for styling
     * - 'prefix': Adds text before the value
     * - 'suffix': Adds text after the value
     * - 'prefix&suffix': Adds text before and after the value
     * - 'replace': Replaces the entire value
     *
     * TARGETS:
     * - 'cell': Apply formatting to individual cell
     * - 'row': Apply formatting to entire row
     *
     * SECURITY: All text output is HTML-escaped using htmlspecialchars().
     *
     * @param string $rule The formatting rule to apply
     * @param mixed $value The original cell value
     * @param mixed $action The action to apply (CSS class, text, or array for prefix&suffix)
     * @param string $target The target of the formatting ('cell' or 'row')
     * @return string The formatted value or CSS class string
     *
     * @throws \InvalidArgumentException If the rule or target is not supported
     *
     * @example
     * applyRule('prefix', 100, '$') // Returns: "$100"
     * applyRule('css style', 'active', 'bg-green-500') // Returns: "bg-green-500"
     * applyRule('replace', 1, 'Active') // Returns: "Active"
     */
    public function applyRule(string $rule, mixed $value, mixed $action, string $target = 'cell'): string
    {
        if (!in_array($rule, self::ALLOWED_RULES, true)) {
            throw new \InvalidArgumentException(
                "Unsupported rule: {$rule}. Allowed rules: " . implode(', ', self::ALLOWED_RULES)
            );
        }

        if (!in_array($target, ['cell', 'row'], true)) {
            throw new \InvalidArgumentException(
                "Invalid target: {$target}. Allowed targets: cell, row"
            );
        }

        return match ($rule) {
            'css style' => $this->applyCssStyle($action),
            'prefix' => $this->applyPrefix($value, $action),
            'suffix' => $this->applySuffix($value, $action),
            'prefix&suffix' => $this->applyPrefixAndSuffix($value, $action),
            'replace' => $this->applyReplace($action),
        };
    }

    /**
     * Apply CSS style rule.
     *
     * @param mixed $action The CSS class string
     * @return string The sanitized CSS class string
     */
    private function applyCssStyle(mixed $action): string
    {
        // Return the CSS class string as-is (will be used as a class attribute)
        return htmlspecialchars((string) $action, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Apply prefix rule.
     *
     * @param mixed $value The original value
     * @param mixed $action The prefix text
     * @return string The value with prefix
     */
    private function applyPrefix(mixed $value, mixed $action): string
    {
        $prefix = htmlspecialchars((string) $action, ENT_QUOTES, 'UTF-8');
        $valueStr = htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

        return $prefix . $valueStr;
    }

    /**
     * Apply suffix rule.
     *
     * @param mixed $value The original value
     * @param mixed $action The suffix text
     * @return string The value with suffix
     */
    private function applySuffix(mixed $value, mixed $action): string
    {
        $suffix = htmlspecialchars((string) $action, ENT_QUOTES, 'UTF-8');
        $valueStr = htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

        return $valueStr . $suffix;
    }

    /**
     * Apply prefix and suffix rule.
     *
     * @param mixed $value The original value
     * @param mixed $action Array with [prefix, suffix] or string for both
     * @return string The value with prefix and suffix
     */
    private function applyPrefixAndSuffix(mixed $value, mixed $action): string
    {
        $valueStr = htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

        if (is_array($action)) {
            $prefix = htmlspecialchars((string) ($action[0] ?? ''), ENT_QUOTES, 'UTF-8');
            $suffix = htmlspecialchars((string) ($action[1] ?? ''), ENT_QUOTES, 'UTF-8');
        } else {
            $prefix = htmlspecialchars((string) $action, ENT_QUOTES, 'UTF-8');
            $suffix = $prefix;
        }

        return $prefix . $valueStr . $suffix;
    }

    /**
     * Apply replace rule.
     *
     * @param mixed $action The replacement value
     * @return string The replacement value
     */
    private function applyReplace(mixed $action): string
    {
        return htmlspecialchars((string) $action, ENT_QUOTES, 'UTF-8');
    }
}
