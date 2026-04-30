<?php

namespace Tests\Unit\Theme;

use Tests\TestCase;
use Canvastack\Canvastack\Library\Theme\Adapters\DefaultAdapter;
use Canvastack\Canvastack\Library\Constants\FormConstants;

/**
 * Unit tests for DefaultAdapter utility methods.
 *
 * Validates that each utility method returns the correct Bootstrap 4 value.
 *
 * Requirements: 4.6, 4.7, 2.7, 2.9, 2.11
 */
class DefaultAdapterUtilityTest extends TestCase
{
    private DefaultAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adapter = new DefaultAdapter();
    }

    // ── getDataToggleAttribute ────────────────────────────────────────────

    /**
     * @test
     * Requirement 4.6, 2.7: DefaultAdapter::getDataToggleAttribute() returns 'data-toggle'
     */
    public function test_get_data_toggle_attribute_returns_bootstrap4_value(): void
    {
        $this->assertSame('data-toggle', $this->adapter->getDataToggleAttribute());
    }

    // ── getDismissAttribute ───────────────────────────────────────────────

    /**
     * @test
     * Requirement 2.7: DefaultAdapter::getDismissAttribute() returns 'data-dismiss'
     */
    public function test_get_dismiss_attribute_returns_bootstrap4_value(): void
    {
        $this->assertSame('data-dismiss', $this->adapter->getDismissAttribute());
    }

    // ── getHideClass ──────────────────────────────────────────────────────

    /**
     * @test
     * Requirement 2.9: DefaultAdapter::getHideClass() returns 'hide'
     */
    public function test_get_hide_class_returns_bootstrap4_value(): void
    {
        $this->assertSame('hide', $this->adapter->getHideClass());
    }

    // ── getFloatRightClass ────────────────────────────────────────────────

    /**
     * @test
     * Requirement 2.11: DefaultAdapter::getFloatRightClass() returns 'pull-right'
     */
    public function test_get_float_right_class_returns_bootstrap4_value(): void
    {
        $this->assertSame('pull-right', $this->adapter->getFloatRightClass());
    }

    // ── getSelectBoxClass ─────────────────────────────────────────────────

    /**
     * @test
     * Requirement 4.7: DefaultAdapter::getSelectBoxClass() returns Chosen.js class string
     */
    public function test_get_select_box_class_returns_chosen_js_classes(): void
    {
        $this->assertSame('chosen-select-deselect chosen-selectbox', $this->adapter->getSelectBoxClass());
    }

    /**
     * @test
     * Requirement 4.7: getSelectBoxClass() matches the FormConstants::DEFAULT_SELECTBOX_CLASS constant
     */
    public function test_get_select_box_class_matches_form_constants(): void
    {
        $this->assertSame(FormConstants::DEFAULT_SELECTBOX_CLASS, $this->adapter->getSelectBoxClass());
    }

    // ── getTableClass ─────────────────────────────────────────────────────

    /**
     * @test
     * Requirement 9.3: DefaultAdapter::getTableClass() returns the full Bootstrap 4 DataTable class string
     */
    public function test_get_table_class_returns_full_bootstrap4_datatable_classes(): void
    {
        $expected = 'CanvaStack-table table animated fadeIn table-striped table-default table-bordered table-hover dataTable repeater display responsive nowrap';

        $this->assertSame($expected, $this->adapter->getTableClass());
    }

    /**
     * @test
     * Requirement 9.3: getTableClass() matches the CANVASTACK_DEFAULT_TABLE_CLASS constant
     */
    public function test_get_table_class_matches_constant(): void
    {
        $this->assertSame(CANVASTACK_DEFAULT_TABLE_CLASS, $this->adapter->getTableClass());
    }

    /**
     * @test
     * Requirement 9.3: getTableClass() contains the CanvaStack-table identifier
     */
    public function test_get_table_class_contains_canvastack_table(): void
    {
        $this->assertStringContainsString('CanvaStack-table', $this->adapter->getTableClass());
    }

    /**
     * @test
     * Requirement 9.3: getTableClass() contains Bootstrap 4 animation classes
     */
    public function test_get_table_class_contains_animation_classes(): void
    {
        $tableClass = $this->adapter->getTableClass();

        $this->assertStringContainsString('animated', $tableClass);
        $this->assertStringContainsString('fadeIn', $tableClass);
    }

    // ── Return type guarantees ────────────────────────────────────────────

    /**
     * @test
     * All utility methods return non-empty strings (never null, never empty).
     */
    public function test_all_utility_methods_return_non_empty_strings(): void
    {
        $this->assertIsString($this->adapter->getDataToggleAttribute());
        $this->assertIsString($this->adapter->getDismissAttribute());
        $this->assertIsString($this->adapter->getHideClass());
        $this->assertIsString($this->adapter->getFloatRightClass());
        $this->assertIsString($this->adapter->getSelectBoxClass());
        $this->assertIsString($this->adapter->getTableClass());

        $this->assertNotEmpty($this->adapter->getDataToggleAttribute());
        $this->assertNotEmpty($this->adapter->getDismissAttribute());
        $this->assertNotEmpty($this->adapter->getHideClass());
        $this->assertNotEmpty($this->adapter->getFloatRightClass());
        $this->assertNotEmpty($this->adapter->getSelectBoxClass());
        $this->assertNotEmpty($this->adapter->getTableClass());
    }

    /**
     * @test
     * Bootstrap 4 utility values must NOT use Bootstrap 5 prefixes.
     */
    public function test_utility_methods_do_not_return_bootstrap5_values(): void
    {
        $this->assertStringNotContainsString('data-bs-', $this->adapter->getDataToggleAttribute());
        $this->assertStringNotContainsString('data-bs-', $this->adapter->getDismissAttribute());
        $this->assertStringNotContainsString('d-none', $this->adapter->getHideClass());
        $this->assertStringNotContainsString('float-end', $this->adapter->getFloatRightClass());
        $this->assertStringNotContainsString('form-select', $this->adapter->getSelectBoxClass());
    }
}
