<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Test for TableBuilder number formatting.
 *
 * Validates Requirements 40.14, 52.10.
 */
class TableBuilderNumberFormattingTest extends TestCase
{
    /**
     * TableBuilder instance.
     *
     * @var TableBuilder
     */
    protected TableBuilder $table;

    /**
     * Setup test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mocked dependencies
        $queryOptimizer = $this->createMock(\Canvastack\Canvastack\Components\Table\Query\QueryOptimizer::class);
        $filterBuilder = $this->createMock(\Canvastack\Canvastack\Components\Table\Query\FilterBuilder::class);
        $schemaInspector = $this->createMock(\Canvastack\Canvastack\Components\Table\Validation\SchemaInspector::class);
        $columnValidator = $this->createMock(\Canvastack\Canvastack\Components\Table\Validation\ColumnValidator::class);
        
        $this->table = new TableBuilder(
            $queryOptimizer,
            $filterBuilder,
            $schemaInspector,
            $columnValidator
        );
    }

    /**
     * Test that number columns can be configured.
     *
     * @return void
     */
    public function test_set_number_columns(): void
    {
        $this->table->setFields(['price:Price', 'total:Total']);
        $this->table->setNumberColumns(['price', 'total'], 'decimal', 2);

        $this->assertTrue($this->table->isNumberColumn('price'));
        $this->assertTrue($this->table->isNumberColumn('total'));
        $this->assertFalse($this->table->isNumberColumn('name'));
    }

    /**
     * Test that number column configuration is stored correctly.
     *
     * @return void
     */
    public function test_get_number_column_config(): void
    {
        $this->table->setFields(['price:Price']);
        $this->table->setNumberColumns(['price'], 'currency', 2, 'USD');

        $config = $this->table->getNumberColumnConfig('price');

        $this->assertIsArray($config);
        $this->assertEquals('currency', $config['type']);
        $this->assertEquals(2, $config['decimals']);
        $this->assertEquals('USD', $config['currency']);
    }

    /**
     * Test that decimal numbers are formatted correctly.
     *
     * @return void
     */
    public function test_format_decimal_numbers(): void
    {
        $this->table->setFields(['price:Price']);
        $this->table->setNumberColumns(['price'], 'decimal', 2);

        $formatted = $this->table->formatNumber('price', 1234.56);

        $this->assertIsString($formatted);
        $this->assertStringContainsString('1,234', $formatted);
    }

    /**
     * Test that currency values are formatted correctly.
     *
     * @return void
     */
    public function test_format_currency_values(): void
    {
        $this->table->setFields(['amount:Amount']);
        $this->table->setNumberColumns(['amount'], 'currency', 2, 'USD');

        $formatted = $this->table->formatNumber('amount', 1234.56);

        $this->assertIsString($formatted);
        $this->assertStringContainsString('1,234', $formatted);
        $this->assertMatchesRegularExpression('/\$|USD/', $formatted);
    }

    /**
     * Test that percentage values are formatted correctly.
     *
     * @return void
     */
    public function test_format_percentage_values(): void
    {
        $this->table->setFields(['discount:Discount']);
        $this->table->setNumberColumns(['discount'], 'percent', 1);

        $formatted = $this->table->formatNumber('discount', 15.5);

        $this->assertIsString($formatted);
        $this->assertStringContainsString('15', $formatted);
        $this->assertStringContainsString('%', $formatted);
    }

    /**
     * Test that specific locale can be used.
     *
     * @return void
     */
    public function test_format_with_specific_locale(): void
    {
        $this->table->setFields(['price:Price']);
        $this->table->setNumberColumns(['price'], 'decimal', 2, null, 'id_ID');

        $formatted = $this->table->formatNumber('price', 1234.56);

        $this->assertIsString($formatted);
        // Indonesian uses period as thousands separator
        $this->assertStringContainsString('1.234', $formatted);
    }

    /**
     * Test that number column configuration can be cleared.
     *
     * @return void
     */
    public function test_clear_number_columns(): void
    {
        $this->table->setFields(['price:Price', 'total:Total']);
        $this->table->setNumberColumns(['price', 'total'], 'decimal', 2);

        $this->assertTrue($this->table->isNumberColumn('price'));
        $this->assertTrue($this->table->isNumberColumn('total'));

        // Clear specific column
        $this->table->clearNumberColumns('price');
        $this->assertFalse($this->table->isNumberColumn('price'));
        $this->assertTrue($this->table->isNumberColumn('total'));

        // Clear all columns
        $this->table->clearNumberColumns();
        $this->assertFalse($this->table->isNumberColumn('total'));
    }

    /**
     * Test that invalid type throws exception.
     *
     * @return void
     */
    public function test_invalid_type_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid number format type');

        $this->table->setFields(['price:Price']);
        $this->table->setNumberColumns(['price'], 'invalid_type', 2);
    }

    /**
     * Test that currency type requires currency code.
     *
     * @return void
     */
    public function test_currency_type_requires_currency_code(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Currency code is required');

        $this->table->setFields(['amount:Amount']);
        $this->table->setNumberColumns(['amount'], 'currency', 2);
    }

    /**
     * Test that non-configured columns return original value.
     *
     * @return void
     */
    public function test_non_configured_columns_return_original_value(): void
    {
        $this->table->setFields(['name:Name']);

        $formatted = $this->table->formatNumber('name', 123.45);

        $this->assertEquals('123.45', $formatted);
    }

    /**
     * Test that multiple columns can have different formats.
     *
     * @return void
     */
    public function test_multiple_columns_with_different_formats(): void
    {
        $this->table->setFields(['price:Price', 'discount:Discount', 'total:Total']);
        $this->table->setNumberColumns(['price'], 'decimal', 2);
        $this->table->setNumberColumns(['discount'], 'percent', 1);
        $this->table->setNumberColumns(['total'], 'currency', 2, 'USD');

        $this->assertEquals('decimal', $this->table->getNumberColumnConfig('price')['type']);
        $this->assertEquals('percent', $this->table->getNumberColumnConfig('discount')['type']);
        $this->assertEquals('currency', $this->table->getNumberColumnConfig('total')['type']);
    }

    /**
     * Test that zero values are formatted correctly.
     *
     * @return void
     */
    public function test_format_zero_values(): void
    {
        $this->table->setFields(['price:Price']);
        $this->table->setNumberColumns(['price'], 'decimal', 2);

        $formatted = $this->table->formatNumber('price', 0);

        $this->assertIsString($formatted);
        $this->assertStringContainsString('0', $formatted);
    }

    /**
     * Test that negative values are formatted correctly.
     *
     * @return void
     */
    public function test_format_negative_values(): void
    {
        $this->table->setFields(['balance:Balance']);
        $this->table->setNumberColumns(['balance'], 'currency', 2, 'USD');

        $formatted = $this->table->formatNumber('balance', -1234.56);

        $this->assertIsString($formatted);
        $this->assertStringContainsString('1,234', $formatted);
        $this->assertMatchesRegularExpression('/-|\(/', $formatted);
    }

    /**
     * Test that large numbers are formatted correctly.
     *
     * @return void
     */
    public function test_format_large_numbers(): void
    {
        $this->table->setFields(['revenue:Revenue']);
        $this->table->setNumberColumns(['revenue'], 'currency', 2, 'USD');

        $formatted = $this->table->formatNumber('revenue', 1234567890.12);

        $this->assertIsString($formatted);
        $this->assertStringContainsString('1,234,567,890', $formatted);
    }
}
