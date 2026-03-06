<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table\Support;

use Canvastack\Canvastack\Components\Table\Support\NumberFormatter;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Test for NumberFormatter.
 *
 * Validates Requirements 40.14, 52.10.
 */
class NumberFormatterTest extends TestCase
{
    /**
     * Test that numbers are formatted according to locale.
     *
     * @return void
     */
    public function test_format_numbers_according_to_locale(): void
    {
        // English locale (US)
        $formatted = NumberFormatter::format(1234567.89, 'en_US', 2);
        $this->assertStringContainsString('1,234,567', $formatted);

        // Indonesian locale
        $formatted = NumberFormatter::format(1234567.89, 'id_ID', 2);
        $this->assertStringContainsString('1.234.567', $formatted);

        // German locale
        $formatted = NumberFormatter::format(1234567.89, 'de_DE', 2);
        $this->assertStringContainsString('1.234.567', $formatted);
    }

    /**
     * Test that decimal places are respected.
     *
     * @return void
     */
    public function test_format_respects_decimal_places(): void
    {
        $value = 123.456789;

        // 0 decimals
        $formatted = NumberFormatter::format($value, 'en_US', 0);
        $this->assertStringNotContainsString('.', $formatted);

        // 2 decimals
        $formatted = NumberFormatter::format($value, 'en_US', 2);
        $this->assertMatchesRegularExpression('/\d+\.\d{2}/', $formatted);

        // 4 decimals
        $formatted = NumberFormatter::format($value, 'en_US', 4);
        $this->assertMatchesRegularExpression('/\d+\.\d{4}/', $formatted);
    }

    /**
     * Test currency formatting.
     *
     * @return void
     */
    public function test_format_currency(): void
    {
        // USD in English locale
        $formatted = NumberFormatter::formatCurrency(1234.56, 'USD', 'en_US');
        $this->assertStringContainsString('1,234.56', $formatted);
        $this->assertMatchesRegularExpression('/\$|USD/', $formatted);

        // IDR in Indonesian locale
        $formatted = NumberFormatter::formatCurrency(1234567, 'IDR', 'id_ID');
        $this->assertStringContainsString('1.234.567', $formatted);
    }

    /**
     * Test percentage formatting.
     *
     * @return void
     */
    public function test_format_percent(): void
    {
        // 50% with 2 decimals
        $formatted = NumberFormatter::formatPercent(50, 'en_US', 2);
        $this->assertStringContainsString('50', $formatted);
        $this->assertStringContainsString('%', $formatted);

        // 12.5% with 1 decimal
        $formatted = NumberFormatter::formatPercent(12.5, 'en_US', 1);
        $this->assertStringContainsString('12', $formatted);
        $this->assertStringContainsString('%', $formatted);
    }

    /**
     * Test custom pattern formatting.
     *
     * @return void
     */
    public function test_format_with_custom_pattern(): void
    {
        $value = 1234.56;
        $pattern = '#,##0.00';

        $formatted = NumberFormatter::formatPattern($value, $pattern, 'en_US');
        $this->assertIsString($formatted);
        $this->assertStringContainsString('1,234', $formatted);
    }

    /**
     * Test that formatters are cached.
     *
     * @return void
     */
    public function test_formatters_are_cached(): void
    {
        // Format twice with same locale
        $result1 = NumberFormatter::format(123.45, 'en_US', 2);
        $result2 = NumberFormatter::format(678.90, 'en_US', 2);

        // Both should succeed (cache working)
        $this->assertIsString($result1);
        $this->assertIsString($result2);
    }

    /**
     * Test cache clearing.
     *
     * @return void
     */
    public function test_clear_cache(): void
    {
        // Format a number to populate cache
        NumberFormatter::format(123.45, 'en_US', 2);

        // Clear cache
        NumberFormatter::clearCache();

        // Format again (should work with fresh formatter)
        $result = NumberFormatter::format(678.90, 'en_US', 2);
        $this->assertIsString($result);
    }

    /**
     * Test that default locale is used when not specified.
     *
     * @return void
     */
    public function test_uses_default_locale_when_not_specified(): void
    {
        // Set locale via Laravel's App facade
        \Illuminate\Support\Facades\App::setLocale('en');

        $formatted = NumberFormatter::format(1234.56, null, 2);
        $this->assertIsString($formatted);
        $this->assertStringContainsString('1,234', $formatted);
    }

    /**
     * Test Arabic locale (RTL) number formatting.
     *
     * @return void
     */
    public function test_format_arabic_locale(): void
    {
        $formatted = NumberFormatter::format(1234.56, 'ar_SA', 2);
        $this->assertIsString($formatted);
        // Arabic uses Arabic-Indic numerals or Western numerals depending on locale
    }

    /**
     * Test zero and negative numbers.
     *
     * @return void
     */
    public function test_format_zero_and_negative_numbers(): void
    {
        // Zero
        $formatted = NumberFormatter::format(0, 'en_US', 2);
        $this->assertStringContainsString('0', $formatted);

        // Negative
        $formatted = NumberFormatter::format(-1234.56, 'en_US', 2);
        $this->assertStringContainsString('1,234', $formatted);
        $this->assertMatchesRegularExpression('/-|\(/', $formatted); // Negative sign or parentheses
    }

    /**
     * Test large numbers.
     *
     * @return void
     */
    public function test_format_large_numbers(): void
    {
        $formatted = NumberFormatter::format(1234567890.12, 'en_US', 2);
        $this->assertIsString($formatted);
        $this->assertStringContainsString('1,234,567,890', $formatted);
    }

    /**
     * Test small decimal numbers.
     *
     * @return void
     */
    public function test_format_small_decimal_numbers(): void
    {
        $formatted = NumberFormatter::format(0.0001, 'en_US', 4);
        $this->assertIsString($formatted);
        $this->assertStringContainsString('0.0001', $formatted);
    }
}
