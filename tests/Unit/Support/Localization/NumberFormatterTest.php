<?php

namespace Canvastack\Canvastack\Tests\Unit\Support\Localization;

use Canvastack\Canvastack\Support\Localization\LocaleManager;
use Canvastack\Canvastack\Support\Localization\NumberFormatter;
use Canvastack\Canvastack\Tests\TestCase;

class NumberFormatterTest extends TestCase
{
    protected NumberFormatter $formatter;

    protected LocaleManager $localeManager;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock LocaleManager
        $this->localeManager = $this->createMock(LocaleManager::class);
        $this->localeManager->method('getLocale')->willReturn('en');

        $this->formatter = new NumberFormatter($this->localeManager);
    }

    public function test_format_number()
    {
        $result = $this->formatter->format(1234.56);

        $this->assertIsString($result);
        $this->assertStringContainsString('1', $result);
        $this->assertStringContainsString('234', $result);
    }

    public function test_format_number_with_null()
    {
        $result = $this->formatter->format(null);

        $this->assertEquals('', $result);
    }

    public function test_format_integer()
    {
        $result = $this->formatter->formatInteger(1234.56);

        $this->assertIsString($result);
        $this->assertStringNotContainsString('.', $result);
    }

    public function test_format_decimal()
    {
        $result = $this->formatter->formatDecimal(1234.5678, 3);

        $this->assertIsString($result);
        // Should have 3 decimal places
    }

    public function test_format_percentage()
    {
        $result = $this->formatter->formatPercentage(45.67, 2);

        $this->assertIsString($result);
        $this->assertStringContainsString('%', $result);
        $this->assertStringContainsString('45', $result);
    }

    public function test_format_file_size_bytes()
    {
        $result = $this->formatter->formatFileSize(512);

        $this->assertStringContainsString('512', $result);
        $this->assertStringContainsString('B', $result);
    }

    public function test_format_file_size_kilobytes()
    {
        $result = $this->formatter->formatFileSize(1024);

        $this->assertStringContainsString('KB', $result);
    }

    public function test_format_file_size_megabytes()
    {
        $result = $this->formatter->formatFileSize(1048576); // 1 MB

        $this->assertStringContainsString('MB', $result);
    }

    public function test_format_file_size_gigabytes()
    {
        $result = $this->formatter->formatFileSize(1073741824); // 1 GB

        $this->assertStringContainsString('GB', $result);
    }

    public function test_format_file_size_zero()
    {
        $result = $this->formatter->formatFileSize(0);

        $this->assertEquals('0 B', $result);
    }

    public function test_format_file_size_null()
    {
        $result = $this->formatter->formatFileSize(null);

        $this->assertEquals('0 B', $result);
    }

    public function test_format_compact_small_number()
    {
        $result = $this->formatter->formatCompact(999);

        $this->assertStringContainsString('999', $result);
        $this->assertStringNotContainsString('K', $result);
    }

    public function test_format_compact_thousands()
    {
        $result = $this->formatter->formatCompact(1500);

        $this->assertStringContainsString('K', $result);
    }

    public function test_format_compact_millions()
    {
        $result = $this->formatter->formatCompact(1500000);

        $this->assertStringContainsString('M', $result);
    }

    public function test_format_compact_billions()
    {
        $result = $this->formatter->formatCompact(1500000000);

        $this->assertStringContainsString('B', $result);
    }

    public function test_format_ordinal_english_first()
    {
        $result = $this->formatter->formatOrdinal(1, 'en');

        $this->assertEquals('1st', $result);
    }

    public function test_format_ordinal_english_second()
    {
        $result = $this->formatter->formatOrdinal(2, 'en');

        $this->assertEquals('2nd', $result);
    }

    public function test_format_ordinal_english_third()
    {
        $result = $this->formatter->formatOrdinal(3, 'en');

        $this->assertEquals('3rd', $result);
    }

    public function test_format_ordinal_english_fourth()
    {
        $result = $this->formatter->formatOrdinal(4, 'en');

        $this->assertEquals('4th', $result);
    }

    public function test_format_ordinal_english_eleventh()
    {
        $result = $this->formatter->formatOrdinal(11, 'en');

        $this->assertEquals('11th', $result);
    }

    public function test_format_ordinal_english_twenty_first()
    {
        $result = $this->formatter->formatOrdinal(21, 'en');

        $this->assertEquals('21st', $result);
    }

    public function test_format_ordinal_indonesian()
    {
        $result = $this->formatter->formatOrdinal(1, 'id');

        $this->assertEquals('ke-1', $result);
    }

    public function test_format_ordinal_null()
    {
        $result = $this->formatter->formatOrdinal(null);

        $this->assertEquals('', $result);
    }

    public function test_parse_number()
    {
        $result = $this->formatter->parse('1,234.56', 'en');

        $this->assertEquals(1234.56, $result);
    }

    public function test_format_custom()
    {
        $result = $this->formatter->formatCustom(1234.56, 2, ',', '.');

        $this->assertEquals('1.234,56', $result);
    }

    public function test_format_with_sign_positive()
    {
        $result = $this->formatter->formatWithSign(123.45);

        $this->assertStringStartsWith('+', $result);
    }

    public function test_format_with_sign_negative()
    {
        $result = $this->formatter->formatWithSign(-123.45);

        $this->assertStringStartsWith('-', $result);
    }

    public function test_format_with_sign_zero()
    {
        $result = $this->formatter->formatWithSign(0);

        $this->assertStringStartsWith('+', $result);
    }

    public function test_format_range_both_values()
    {
        $result = $this->formatter->formatRange(10, 20);

        $this->assertStringContainsString('10', $result);
        $this->assertStringContainsString('20', $result);
        $this->assertStringContainsString('-', $result);
    }

    public function test_format_range_min_only()
    {
        $result = $this->formatter->formatRange(10, null);

        $this->assertStringContainsString('≥', $result);
        $this->assertStringContainsString('10', $result);
    }

    public function test_format_range_max_only()
    {
        $result = $this->formatter->formatRange(null, 20);

        $this->assertStringContainsString('≤', $result);
        $this->assertStringContainsString('20', $result);
    }

    public function test_format_range_both_null()
    {
        $result = $this->formatter->formatRange(null, null);

        $this->assertEquals('', $result);
    }

    public function test_format_with_unit()
    {
        $result = $this->formatter->formatWithUnit(100, 'kg');

        $this->assertStringContainsString('100', $result);
        $this->assertStringContainsString('kg', $result);
    }

    public function test_format_scientific()
    {
        $result = $this->formatter->formatScientific(1234567, 2);

        $this->assertIsString($result);
        $this->assertStringContainsString('E', $result);
    }

    public function test_format_fraction_whole_number()
    {
        $result = $this->formatter->formatFraction(5.0);

        $this->assertEquals('5', $result);
    }

    public function test_format_fraction_with_decimal()
    {
        $result = $this->formatter->formatFraction(2.5);

        $this->assertStringContainsString('/', $result);
    }

    public function test_format_fraction_null()
    {
        $result = $this->formatter->formatFraction(null);

        $this->assertEquals('', $result);
    }

    public function test_gcd()
    {
        $reflection = new \ReflectionClass($this->formatter);
        $method = $reflection->getMethod('gcd');
        $method->setAccessible(true);

        $result = $method->invoke($this->formatter, 12, 8);
        $this->assertEquals(4, $result);

        $result = $method->invoke($this->formatter, 15, 10);
        $this->assertEquals(5, $result);

        $result = $method->invoke($this->formatter, 7, 3);
        $this->assertEquals(1, $result);
    }
}
