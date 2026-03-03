<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\View\Components\Table;

use Canvastack\Canvastack\Tests\TestCase;
use Canvastack\Canvastack\View\Components\Table\DisplayLimit;
use Illuminate\View\View;

/**
 * Test DisplayLimit Blade component.
 */
class DisplayLimitTest extends TestCase
{
    /**
     * Test component can be instantiated with default values.
     */
    public function test_component_instantiation_with_defaults(): void
    {
        $component = new DisplayLimit();

        $this->assertEquals('default', $component->tableName);
        $this->assertEquals(10, $component->currentLimit);
        $this->assertTrue($component->showLabel);
        $this->assertEquals('sm', $component->size);
        $this->assertEquals(DisplayLimit::DEFAULT_OPTIONS, $component->options);
    }

    /**
     * Test component can be instantiated with custom values.
     */
    public function test_component_instantiation_with_custom_values(): void
    {
        $customOptions = [
            ['value' => '5', 'label' => '5'],
            ['value' => '15', 'label' => '15'],
            ['value' => 'all', 'label' => 'All'],
        ];

        $component = new DisplayLimit(
            tableName: 'users_table',
            currentLimit: 25,
            options: $customOptions,
            showLabel: false,
            size: 'lg'
        );

        $this->assertEquals('users_table', $component->tableName);
        $this->assertEquals(25, $component->currentLimit);
        $this->assertFalse($component->showLabel);
        $this->assertEquals('lg', $component->size);
        $this->assertEquals($customOptions, $component->options);
    }

    /**
     * Test limit validation with valid integer.
     */
    public function test_limit_validation_with_valid_integer(): void
    {
        $component = new DisplayLimit(currentLimit: 50);
        $this->assertEquals(50, $component->currentLimit);
    }

    /**
     * Test limit validation with 'all' string.
     */
    public function test_limit_validation_with_all_string(): void
    {
        $component = new DisplayLimit(currentLimit: 'all');
        $this->assertEquals('all', $component->currentLimit);
    }

    /**
     * Test limit validation with '*' string converts to 'all'.
     */
    public function test_limit_validation_with_asterisk_converts_to_all(): void
    {
        $component = new DisplayLimit(currentLimit: '*');
        $this->assertEquals('all', $component->currentLimit);
    }

    /**
     * Test limit validation with invalid value defaults to 10.
     */
    public function test_limit_validation_with_invalid_value_defaults_to_10(): void
    {
        $component = new DisplayLimit(currentLimit: -5);
        $this->assertEquals(10, $component->currentLimit);

        $component = new DisplayLimit(currentLimit: 'invalid');
        $this->assertEquals(10, $component->currentLimit);
    }

    /**
     * Test options formatting with valid array.
     */
    public function test_options_formatting_with_valid_array(): void
    {
        $options = [
            ['value' => '10', 'label' => '10'],
            ['value' => '25', 'label' => '25'],
        ];

        $component = new DisplayLimit(options: $options);
        $this->assertEquals($options, $component->options);
    }

    /**
     * Test options formatting with string values.
     */
    public function test_options_formatting_with_string_values(): void
    {
        $options = ['10', '25', 'all'];
        $expected = [
            ['value' => '10', 'label' => '10'],
            ['value' => '25', 'label' => '25'],
            ['value' => 'all', 'label' => 'All'],
        ];

        $component = new DisplayLimit(options: $options);
        $this->assertEquals($expected, $component->options);
    }

    /**
     * Test getCurrentLimit method without session.
     */
    public function test_get_current_limit_without_session(): void
    {
        $component = new DisplayLimit(currentLimit: 25);
        $this->assertEquals(25, $component->getCurrentLimit());
    }

    /**
     * Test getCurrentLimit method with session data.
     */
    public function test_get_current_limit_with_session(): void
    {
        // Set session data
        session(['table_display_limit_test_table' => 50]);

        $component = new DisplayLimit(
            tableName: 'test_table',
            currentLimit: 25
        );

        $this->assertEquals(50, $component->getCurrentLimit());
    }

    /**
     * Test isCurrentLimit method.
     */
    public function test_is_current_limit(): void
    {
        $component = new DisplayLimit(currentLimit: 25);

        $this->assertTrue($component->isCurrentLimit(25));
        $this->assertFalse($component->isCurrentLimit(10));
        $this->assertFalse($component->isCurrentLimit('all'));
    }

    /**
     * Test isCurrentLimit method with 'all' value.
     */
    public function test_is_current_limit_with_all(): void
    {
        $component = new DisplayLimit(currentLimit: 'all');

        $this->assertTrue($component->isCurrentLimit('all'));
        $this->assertFalse($component->isCurrentLimit(25));
    }

    /**
     * Test getSelectClasses method with different sizes.
     */
    public function test_get_select_classes_with_different_sizes(): void
    {
        $component = new DisplayLimit(size: 'xs');
        $this->assertStringContainsString('select-xs w-16', $component->getSelectClasses());

        $component = new DisplayLimit(size: 'sm');
        $this->assertStringContainsString('select-sm w-20', $component->getSelectClasses());

        $component = new DisplayLimit(size: 'md');
        $this->assertStringContainsString('select-md w-24', $component->getSelectClasses());

        $component = new DisplayLimit(size: 'lg');
        $this->assertStringContainsString('select-lg w-28', $component->getSelectClasses());
    }

    /**
     * Test getSelectClasses method with invalid size defaults to sm.
     */
    public function test_get_select_classes_with_invalid_size_defaults_to_sm(): void
    {
        $component = new DisplayLimit(size: 'invalid');
        $this->assertStringContainsString('select-sm w-20', $component->getSelectClasses());
    }

    /**
     * Test getTableName method.
     */
    public function test_get_table_name(): void
    {
        $component = new DisplayLimit(tableName: 'users_table');
        $this->assertEquals('users_table', $component->getTableName());
    }

    /**
     * Test getOptionsForJs method.
     */
    public function test_get_options_for_js(): void
    {
        $options = [
            ['value' => '10', 'label' => '10'],
            ['value' => '25', 'label' => '25'],
        ];

        $component = new DisplayLimit(options: $options);
        $this->assertEquals($options, $component->getOptionsForJs());
    }

    /**
     * Test getCurrentLimitForJs method.
     */
    public function test_get_current_limit_for_js(): void
    {
        $component = new DisplayLimit(currentLimit: 25);
        $this->assertEquals(25, $component->getCurrentLimitForJs());
    }

    /**
     * Test component renders view.
     */
    public function test_component_renders_view(): void
    {
        $component = new DisplayLimit();
        $view = $component->render();

        $this->assertInstanceOf(View::class, $view);
        $this->assertEquals('canvastack::components.table.display-limit', $view->getName());
    }

    /**
     * Test component with session persistence.
     */
    public function test_component_with_session_persistence(): void
    {
        // Set session data
        session(['table_display_limit_products' => 'all']);

        $component = new DisplayLimit(
            tableName: 'products',
            currentLimit: 10
        );

        // Should return session value instead of default
        $this->assertEquals('all', $component->getCurrentLimit());
        $this->assertTrue($component->isCurrentLimit('all'));
        $this->assertFalse($component->isCurrentLimit(10));
    }

    /**
     * Test component with invalid session data falls back to default.
     */
    public function test_component_with_invalid_session_data_falls_back_to_default(): void
    {
        // Set invalid session data
        session(['table_display_limit_orders' => 'invalid']);

        $component = new DisplayLimit(
            tableName: 'orders',
            currentLimit: 25
        );

        // Should validate session data and fall back to default
        $this->assertEquals(10, $component->getCurrentLimit()); // Validation converts invalid to 10
    }

    /**
     * Test default options constant.
     */
    public function test_default_options_constant(): void
    {
        $expected = [
            ['value' => '10', 'label' => '10'],
            ['value' => '25', 'label' => '25'],
            ['value' => '50', 'label' => '50'],
            ['value' => '100', 'label' => '100'],
            ['value' => 'all', 'label' => 'All'],
        ];

        $this->assertEquals($expected, DisplayLimit::DEFAULT_OPTIONS);
    }
}