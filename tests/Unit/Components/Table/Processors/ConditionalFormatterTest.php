<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table\Processors;

use Canvastack\Canvastack\Components\Table\Processors\ConditionalFormatter;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ConditionalFormatter.
 */
class ConditionalFormatterTest extends TestCase
{
    private ConditionalFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formatter = new ConditionalFormatter();
    }

    /**
     * Test evaluateCondition with == operator.
     */
    public function test_evaluate_condition_with_equals_operator(): void
    {
        $this->assertTrue($this->formatter->evaluateCondition(5, '==', 5));
        $this->assertTrue($this->formatter->evaluateCondition('5', '==', 5));
        $this->assertFalse($this->formatter->evaluateCondition(5, '==', 10));
    }

    /**
     * Test evaluateCondition with != operator.
     */
    public function test_evaluate_condition_with_not_equals_operator(): void
    {
        $this->assertTrue($this->formatter->evaluateCondition(5, '!=', 10));
        $this->assertFalse($this->formatter->evaluateCondition(5, '!=', 5));
    }

    /**
     * Test evaluateCondition with === operator.
     */
    public function test_evaluate_condition_with_strict_equals_operator(): void
    {
        $this->assertTrue($this->formatter->evaluateCondition(5, '===', 5));
        $this->assertFalse($this->formatter->evaluateCondition('5', '===', 5));
        $this->assertFalse($this->formatter->evaluateCondition(5, '===', 10));
    }

    /**
     * Test evaluateCondition with !== operator.
     */
    public function test_evaluate_condition_with_strict_not_equals_operator(): void
    {
        $this->assertTrue($this->formatter->evaluateCondition('5', '!==', 5));
        $this->assertTrue($this->formatter->evaluateCondition(5, '!==', 10));
        $this->assertFalse($this->formatter->evaluateCondition(5, '!==', 5));
    }

    /**
     * Test evaluateCondition with > operator.
     */
    public function test_evaluate_condition_with_greater_than_operator(): void
    {
        $this->assertTrue($this->formatter->evaluateCondition(10, '>', 5));
        $this->assertFalse($this->formatter->evaluateCondition(5, '>', 10));
        $this->assertFalse($this->formatter->evaluateCondition(5, '>', 5));
    }

    /**
     * Test evaluateCondition with < operator.
     */
    public function test_evaluate_condition_with_less_than_operator(): void
    {
        $this->assertTrue($this->formatter->evaluateCondition(5, '<', 10));
        $this->assertFalse($this->formatter->evaluateCondition(10, '<', 5));
        $this->assertFalse($this->formatter->evaluateCondition(5, '<', 5));
    }

    /**
     * Test evaluateCondition with >= operator.
     */
    public function test_evaluate_condition_with_greater_than_or_equal_operator(): void
    {
        $this->assertTrue($this->formatter->evaluateCondition(10, '>=', 5));
        $this->assertTrue($this->formatter->evaluateCondition(5, '>=', 5));
        $this->assertFalse($this->formatter->evaluateCondition(5, '>=', 10));
    }

    /**
     * Test evaluateCondition with <= operator.
     */
    public function test_evaluate_condition_with_less_than_or_equal_operator(): void
    {
        $this->assertTrue($this->formatter->evaluateCondition(5, '<=', 10));
        $this->assertTrue($this->formatter->evaluateCondition(5, '<=', 5));
        $this->assertFalse($this->formatter->evaluateCondition(10, '<=', 5));
    }

    /**
     * Test evaluateCondition throws exception for invalid operator.
     */
    public function test_evaluate_condition_throws_exception_for_invalid_operator(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported operator');

        $this->formatter->evaluateCondition(5, '<>', 10);
    }

    /**
     * Test applyRule with css style rule.
     */
    public function test_apply_rule_with_css_style(): void
    {
        $result = $this->formatter->applyRule('css style', 100, 'bg-red-500 text-white', 'cell');

        $this->assertEquals('bg-red-500 text-white', $result);
    }

    /**
     * Test applyRule with prefix rule.
     */
    public function test_apply_rule_with_prefix(): void
    {
        $result = $this->formatter->applyRule('prefix', 100, '$', 'cell');

        $this->assertEquals('$100', $result);
    }

    /**
     * Test applyRule with suffix rule.
     */
    public function test_apply_rule_with_suffix(): void
    {
        $result = $this->formatter->applyRule('suffix', 100, '%', 'cell');

        $this->assertEquals('100%', $result);
    }

    /**
     * Test applyRule with prefix&suffix rule using array.
     */
    public function test_apply_rule_with_prefix_and_suffix_array(): void
    {
        $result = $this->formatter->applyRule('prefix&suffix', 100, ['$', ' USD'], 'cell');

        $this->assertEquals('$100 USD', $result);
    }

    /**
     * Test applyRule with prefix&suffix rule using string.
     */
    public function test_apply_rule_with_prefix_and_suffix_string(): void
    {
        $result = $this->formatter->applyRule('prefix&suffix', 100, '*', 'cell');

        $this->assertEquals('*100*', $result);
    }

    /**
     * Test applyRule with replace rule.
     */
    public function test_apply_rule_with_replace(): void
    {
        $result = $this->formatter->applyRule('replace', 100, 'N/A', 'cell');

        $this->assertEquals('N/A', $result);
    }

    /**
     * Test applyRule sanitizes HTML in action text.
     */
    public function test_apply_rule_sanitizes_html_in_prefix(): void
    {
        $result = $this->formatter->applyRule('prefix', 100, '<script>alert("xss")</script>', 'cell');

        $this->assertStringContainsString('&lt;script&gt;', $result);
        $this->assertStringNotContainsString('<script>', $result);
    }

    /**
     * Test applyRule sanitizes HTML in suffix.
     */
    public function test_apply_rule_sanitizes_html_in_suffix(): void
    {
        $result = $this->formatter->applyRule('suffix', 100, '<img src=x onerror=alert(1)>', 'cell');

        $this->assertStringContainsString('&lt;img', $result);
        $this->assertStringNotContainsString('<img', $result);
    }

    /**
     * Test applyRule sanitizes HTML in replace.
     */
    public function test_apply_rule_sanitizes_html_in_replace(): void
    {
        $result = $this->formatter->applyRule('replace', 100, '<b>Bold</b>', 'cell');

        $this->assertEquals('&lt;b&gt;Bold&lt;/b&gt;', $result);
    }

    /**
     * Test applyRule sanitizes quotes.
     */
    public function test_apply_rule_sanitizes_quotes(): void
    {
        $result = $this->formatter->applyRule('prefix', 100, '" onclick="alert(1)"', 'cell');

        // Verify quotes are escaped
        $this->assertStringContainsString('&quot;', $result);
        // Verify the onclick is escaped (not executable)
        $this->assertStringContainsString('&quot; onclick=&quot;', $result);
        // Verify it doesn't contain unescaped onclick= which would be dangerous
        $this->assertStringNotContainsString('onclick="alert', $result);
    }

    /**
     * Test applyRule throws exception for invalid rule.
     */
    public function test_apply_rule_throws_exception_for_invalid_rule(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported rule');

        $this->formatter->applyRule('invalid_rule', 100, 'action', 'cell');
    }

    /**
     * Test applyRule throws exception for invalid target.
     */
    public function test_apply_rule_throws_exception_for_invalid_target(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid target');

        $this->formatter->applyRule('prefix', 100, '$', 'invalid_target');
    }

    /**
     * Test applyRule works with row target.
     */
    public function test_apply_rule_works_with_row_target(): void
    {
        $result = $this->formatter->applyRule('css style', 100, 'bg-red-500', 'row');

        $this->assertEquals('bg-red-500', $result);
    }

    /**
     * Test applyRule handles empty prefix&suffix array.
     */
    public function test_apply_rule_handles_empty_prefix_suffix_array(): void
    {
        $result = $this->formatter->applyRule('prefix&suffix', 100, [], 'cell');

        $this->assertEquals('100', $result);
    }

    /**
     * Test applyRule handles numeric values.
     */
    public function test_apply_rule_handles_numeric_values(): void
    {
        $result = $this->formatter->applyRule('prefix', 42.5, '$', 'cell');

        $this->assertEquals('$42.5', $result);
    }

    /**
     * Test evaluateCondition with string comparisons.
     */
    public function test_evaluate_condition_with_string_comparisons(): void
    {
        $this->assertTrue($this->formatter->evaluateCondition('active', '==', 'active'));
        $this->assertFalse($this->formatter->evaluateCondition('active', '==', 'inactive'));
        $this->assertTrue($this->formatter->evaluateCondition('active', '!=', 'inactive'));
    }

    /**
     * Test evaluateCondition with null values.
     */
    public function test_evaluate_condition_with_null_values(): void
    {
        $this->assertTrue($this->formatter->evaluateCondition(null, '==', null));
        // In PHP, null == 0 is true (loose comparison)
        $this->assertTrue($this->formatter->evaluateCondition(null, '==', 0));
        // But null === 0 is false (strict comparison)
        $this->assertFalse($this->formatter->evaluateCondition(null, '===', 0));
        $this->assertTrue($this->formatter->evaluateCondition(null, '===', null));
    }
}
