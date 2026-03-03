<?php

namespace Canvastack\Canvastack\Tests\Unit\Support\Localization;

use Canvastack\Canvastack\Support\Localization\CurrencyFormatter;
use Canvastack\Canvastack\Support\Localization\LocaleManager;
use Canvastack\Canvastack\Support\Localization\NumberFormatter;
use Canvastack\Canvastack\Tests\TestCase;

class CurrencyFormatterTest extends TestCase
{
    protected CurrencyFormatter $formatter;

    protected LocaleManager $localeManager;

    protected NumberFormatter $numberFormatter;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock LocaleManager
        $this->localeManager = $this->createMock(LocaleManager::class);
        $this->localeManager->method('getLocale')->willReturn('en');

        // Mock NumberFormatter
        $this->numberFormatter = $this->createMock(NumberFormatter::class);
        $this->numberFormatter->method('parse')->willReturnCallback(function ($value) {
            // Remove currency symbols and parse
            $cleaned = preg_replace('/[^0-9.,\-]/', '', $value);
            $cleaned = str_replace(',', '', $cleaned);

            return (float) $cleaned;
        });

        $this->formatter = new CurrencyFormatter($this->localeManager, $this->numberFormatter);
    }

    public function test_format_currency()
    {
        $result = $this->formatter->format(1234.56, 'USD');

        $this->assertIsString($result);
        $this->assertStringContainsString('1', $result);
        $this->assertStringContainsString('234', $result);
    }

    public function test_format_currency_with_null()
    {
        $result = $this->formatter->format(null);

        $this->assertEquals('', $result);
    }

    public function test_format_with_code()
    {
        $result = $this->formatter->formatWithCode(1234.56, 'USD');

        $this->assertIsString($result);
        $this->assertStringContainsString('USD', $result);
    }

    public function test_format_without_symbol()
    {
        $result = $this->formatter->formatWithoutSymbol(1234.56, 'USD');

        $this->assertIsString($result);
        $this->assertStringNotContainsString('$', $result);
        $this->assertStringNotContainsString('USD', $result);
    }

    public function test_format_accounting_positive()
    {
        $result = $this->formatter->formatAccounting(1234.56, 'USD');

        $this->assertIsString($result);
        $this->assertStringNotContainsString('(', $result);
        $this->assertStringNotContainsString(')', $result);
    }

    public function test_format_accounting_negative()
    {
        $result = $this->formatter->formatAccounting(-1234.56, 'USD');

        $this->assertIsString($result);
        $this->assertStringContainsString('(', $result);
        $this->assertStringContainsString(')', $result);
    }

    public function test_parse_currency()
    {
        $result = $this->formatter->parse('$1,234.56', 'USD');

        $this->assertIsFloat($result);
        $this->assertEquals(1234.56, $result);
    }

    public function test_convert_currency()
    {
        $rates = [
            'USD' => 1,
            'EUR' => 0.85,
            'IDR' => 15000,
        ];

        $result = $this->formatter->convert(100, 'USD', 'EUR', $rates);

        $this->assertIsString($result);
    }

    public function test_format_range_both_values()
    {
        $result = $this->formatter->formatRange(100, 200, 'USD');

        $this->assertIsString($result);
        $this->assertStringContainsString('-', $result);
    }

    public function test_format_range_min_only()
    {
        $result = $this->formatter->formatRange(100, null, 'USD');

        $this->assertIsString($result);
        $this->assertStringContainsString('From', $result);
    }

    public function test_format_range_max_only()
    {
        $result = $this->formatter->formatRange(null, 200, 'USD');

        $this->assertIsString($result);
        $this->assertStringContainsString('Up to', $result);
    }

    public function test_format_range_both_null()
    {
        $result = $this->formatter->formatRange(null, null, 'USD');

        $this->assertEquals('', $result);
    }

    public function test_get_symbol()
    {
        $result = $this->formatter->getSymbol('USD');

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function test_get_name()
    {
        $result = $this->formatter->getName('USD');

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function test_get_available_currencies()
    {
        $result = $this->formatter->getAvailableCurrencies();

        $this->assertIsArray($result);
    }

    public function test_add_symbol_before()
    {
        $reflection = new \ReflectionClass($this->formatter);
        $method = $reflection->getMethod('addSymbol');
        $method->setAccessible(true);

        $config = [
            'symbol' => '$',
            'position' => 'before',
            'space' => false,
        ];

        $result = $method->invoke($this->formatter, '1,234.56', $config);

        $this->assertEquals('$1,234.56', $result);
    }

    public function test_add_symbol_after()
    {
        $reflection = new \ReflectionClass($this->formatter);
        $method = $reflection->getMethod('addSymbol');
        $method->setAccessible(true);

        $config = [
            'symbol' => '€',
            'position' => 'after',
            'space' => true,
        ];

        $result = $method->invoke($this->formatter, '1,234.56', $config);

        $this->assertEquals('1,234.56 €', $result);
    }

    public function test_add_symbol_with_space()
    {
        $reflection = new \ReflectionClass($this->formatter);
        $method = $reflection->getMethod('addSymbol');
        $method->setAccessible(true);

        $config = [
            'symbol' => 'Rp',
            'position' => 'before',
            'space' => true,
        ];

        $result = $method->invoke($this->formatter, '1.234', $config);

        $this->assertEquals('Rp 1.234', $result);
    }
}
