<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;

/**
 * Test conditions and formatting methods (Phase 5).
 *
 * Requirements: 17.1-17.11, 18.1-18.10, 19.1-19.7, 36.1, 36.2
 */
class ConditionsFormattingTest extends TestCase
{
    private TableBuilder $tableBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        // Drop table if exists, then create test table
        \Illuminate\Support\Facades\Schema::dropIfExists('users');
        \Illuminate\Support\Facades\Schema::create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('status');
            $table->decimal('price', 10, 2);
            $table->integer('quantity');
            $table->decimal('discount', 5, 2);
            $table->timestamp('created_at')->nullable();
            $table->decimal('total', 10, 2);
        });

        // Create TableBuilder instance with all dependencies
        $this->tableBuilder = $this->createTableBuilder();

        // Create and set model
        $model = new class () extends Model {
            protected $table = 'users';

            public $timestamps = false;
        };

        $this->tableBuilder->setModel($model);
        $this->tableBuilder->setName('users');
    }

    protected function tearDown(): void
    {
        // Drop test table
        \Illuminate\Support\Facades\Schema::dropIfExists('users');

        parent::tearDown();
    }

    /**
     * Helper method to set private properties.
     */
    protected function setPrivateProperty(object $object, string $property, $value): void
    {
        $reflection = new \ReflectionClass($object);
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }

    /**
     * Test columnCondition() validates field exists.
     *
     * Requirement 17.1: Validate fieldName exists in table schema
     */
    public function test_column_condition_validates_field_exists(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Column 'invalid_field' does not exist in table 'users'");

        $this->tableBuilder->columnCondition(
            'invalid_field',
            'cell',
            '==',
            'active',
            'css style',
            'color: green;'
        );
    }

    /**
     * Test columnCondition() validates target.
     *
     * Requirement 17.2: Validate target is 'cell' or 'row'
     */
    public function test_column_condition_validates_target(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid target: invalid');

        $this->tableBuilder->columnCondition(
            'status',
            'invalid',
            '==',
            'active',
            'css style',
            'color: green;'
        );
    }

    /**
     * Test columnCondition() validates operator.
     *
     * Requirement 17.3: Validate operator is in allowed list
     */
    public function test_column_condition_validates_operator(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid operator: <>');

        $this->tableBuilder->columnCondition(
            'status',
            'cell',
            '<>',
            'active',
            'css style',
            'color: green;'
        );
    }

    /**
     * Test columnCondition() validates rule.
     *
     * Requirement 17.4: Validate rule is in allowed list
     */
    public function test_column_condition_validates_rule(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid rule: invalid');

        $this->tableBuilder->columnCondition(
            'status',
            'cell',
            '==',
            'active',
            'invalid',
            'color: green;'
        );
    }

    /**
     * Test columnCondition() escapes HTML in action text.
     *
     * Requirement 17.8: Escape HTML in action text to prevent XSS
     */
    public function test_column_condition_escapes_html_in_action(): void
    {
        $result = $this->tableBuilder->columnCondition(
            'status',
            'cell',
            '==',
            'active',
            'prefix',
            '<script>alert("XSS")</script>'
        );

        $this->assertInstanceOf(TableBuilder::class, $result);

        // Access protected property using reflection
        $reflection = new \ReflectionClass($this->tableBuilder);
        $property = $reflection->getProperty('columnConditions');
        $property->setAccessible(true);
        $conditions = $property->getValue($this->tableBuilder);

        $this->assertCount(1, $conditions);
        $this->assertStringContainsString('&lt;script&gt;', $conditions[0]['action']);
        $this->assertStringNotContainsString('<script>', $conditions[0]['action']);
    }

    /**
     * Test columnCondition() with css style rule.
     *
     * Requirement 17.5: Support 'css style' rule
     */
    public function test_column_condition_with_css_style_rule(): void
    {
        $result = $this->tableBuilder->columnCondition(
            'status',
            'cell',
            '==',
            'active',
            'css style',
            'color: green; font-weight: bold;'
        );

        $this->assertInstanceOf(TableBuilder::class, $result);
    }

    /**
     * Test columnCondition() with prefix rule.
     *
     * Requirement 17.6: Support 'prefix' rule
     */
    public function test_column_condition_with_prefix_rule(): void
    {
        $result = $this->tableBuilder->columnCondition(
            'status',
            'cell',
            '==',
            'active',
            'prefix',
            '✓ '
        );

        $this->assertInstanceOf(TableBuilder::class, $result);
    }

    /**
     * Test columnCondition() with suffix rule.
     *
     * Requirement 17.7: Support 'suffix' rule
     */
    public function test_column_condition_with_suffix_rule(): void
    {
        $result = $this->tableBuilder->columnCondition(
            'status',
            'cell',
            '==',
            'active',
            'suffix',
            ' (Active)'
        );

        $this->assertInstanceOf(TableBuilder::class, $result);
    }

    /**
     * Test columnCondition() with replace rule.
     *
     * Requirement 17.9: Support 'replace' rule
     */
    public function test_column_condition_with_replace_rule(): void
    {
        $result = $this->tableBuilder->columnCondition(
            'status',
            'cell',
            '==',
            '1',
            'replace',
            'Active'
        );

        $this->assertInstanceOf(TableBuilder::class, $result);
    }

    /**
     * Test formula() validates fields exist.
     *
     * Requirement 18.1: Validate all fields in fieldLists exist
     */
    public function test_formula_validates_fields_exist(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Column 'invalid_field' does not exist in table 'users'");

        $this->tableBuilder->formula(
            'total',
            'Total',
            ['invalid_field', 'quantity'],
            'price * quantity'
        );
    }

    /**
     * Test formula() validates operators.
     *
     * Requirement 18.2: Validate logic contains only allowed operators
     */
    public function test_formula_validates_operators(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid operators in logic');

        $this->tableBuilder->formula(
            'total',
            'Total',
            ['price', 'quantity'],
            'price ^ quantity' // ^ is not allowed
        );
    }

    /**
     * Test formula() stores configuration correctly.
     *
     * Requirement 18.3-18.10: Store formula config
     */
    public function test_formula_stores_configuration(): void
    {
        $result = $this->tableBuilder->formula(
            'total',
            'Total Amount',
            ['price', 'quantity'],
            'price * quantity',
            'quantity',
            true
        );

        $this->assertInstanceOf(TableBuilder::class, $result);

        // Access protected property using reflection
        $reflection = new \ReflectionClass($this->tableBuilder);
        $property = $reflection->getProperty('formulas');
        $property->setAccessible(true);
        $formulas = $property->getValue($this->tableBuilder);

        $this->assertCount(1, $formulas);
        $this->assertEquals('total', $formulas[0]['name']);
        $this->assertEquals('Total Amount', $formulas[0]['label']);
        $this->assertEquals(['price', 'quantity'], $formulas[0]['fields']);
        $this->assertEquals('price * quantity', $formulas[0]['logic']);
        $this->assertEquals('quantity', $formulas[0]['location']);
        $this->assertTrue($formulas[0]['after']);
    }

    /**
     * Test formula() with complex logic.
     *
     * Requirement 18.2: Support multiple operators
     */
    public function test_formula_with_complex_logic(): void
    {
        $result = $this->tableBuilder->formula(
            'discount_total',
            'Discounted Total',
            ['price', 'quantity', 'discount'],
            '(price * quantity) - ((price * quantity) * discount / 100)'
        );

        $this->assertInstanceOf(TableBuilder::class, $result);
    }

    /**
     * Test format() validates fields exist.
     *
     * Requirement 19.1: Validate all fields exist
     */
    public function test_format_validates_fields_exist(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Column 'invalid_field' does not exist in table 'users'");

        $this->tableBuilder->format(['invalid_field'], 2, ',', 'number');
    }

    /**
     * Test format() validates format type.
     *
     * Requirement 19.2-19.5: Validate format type
     */
    public function test_format_validates_format_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid format type: invalid');

        $this->tableBuilder->format(['price'], 2, ',', 'invalid');
    }

    /**
     * Test format() with number format.
     *
     * Requirement 19.2: Support 'number' format
     */
    public function test_format_with_number_format(): void
    {
        $result = $this->tableBuilder->format(['price'], 2, ',', 'number');

        $this->assertInstanceOf(TableBuilder::class, $result);

        // Access protected property using reflection
        $reflection = new \ReflectionClass($this->tableBuilder);
        $property = $reflection->getProperty('formats');
        $property->setAccessible(true);
        $formats = $property->getValue($this->tableBuilder);

        $this->assertCount(1, $formats);
        $this->assertEquals(['price'], $formats[0]['fields']);
        $this->assertEquals(2, $formats[0]['decimals']);
        $this->assertEquals(',', $formats[0]['separator']);
        $this->assertEquals('number', $formats[0]['type']);
    }

    /**
     * Test format() with currency format.
     *
     * Requirement 19.3: Support 'currency' format
     */
    public function test_format_with_currency_format(): void
    {
        $result = $this->tableBuilder->format(['price', 'total'], 2, ',', 'currency');

        $this->assertInstanceOf(TableBuilder::class, $result);
    }

    /**
     * Test format() with percentage format.
     *
     * Requirement 19.4: Support 'percentage' format
     */
    public function test_format_with_percentage_format(): void
    {
        $result = $this->tableBuilder->format(['discount'], 1, '.', 'percentage');

        $this->assertInstanceOf(TableBuilder::class, $result);
    }

    /**
     * Test format() with date format.
     *
     * Requirement 19.5: Support 'date' format
     */
    public function test_format_with_date_format(): void
    {
        $result = $this->tableBuilder->format(['created_at'], 0, '.', 'date');

        $this->assertInstanceOf(TableBuilder::class, $result);
    }

    /**
     * Test format() backward compatibility (no parameters).
     *
     * Requirement 19.9: Maintain backward compatibility
     */
    public function test_format_backward_compatibility(): void
    {
        $result = $this->tableBuilder->format();

        $this->assertInstanceOf(TableBuilder::class, $result);

        // Verify no formats were added
        $reflection = new \ReflectionClass($this->tableBuilder);
        $property = $reflection->getProperty('formats');
        $property->setAccessible(true);
        $formats = $property->getValue($this->tableBuilder);

        $this->assertCount(0, $formats);
    }
}
