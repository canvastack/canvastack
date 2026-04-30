<?php

namespace Tests\Unit\Theme;

use Tests\TestCase;
use Canvastack\Canvastack\Library\Theme\Adapters\Bootstrap5Adapter;

/**
 * Unit tests for Bootstrap5Adapter utility methods.
 *
 * Validates that each utility method returns the correct Bootstrap 5 value,
 * distinct from Bootstrap 4 equivalents in DefaultAdapter.
 *
 * Requirements: 5.5, 5.6, 2.8, 2.10, 2.12
 */
class Bootstrap5AdapterUtilityTest extends TestCase
{
    private Bootstrap5Adapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adapter = new Bootstrap5Adapter();
    }

    // ── getDataToggleAttribute ────────────────────────────────────────────

    /**
     * @test
     * Requirement 5.5, 2.8: Bootstrap5Adapter::getDataToggleAttribute() returns 'data-bs-toggle'
     */
    public function test_get_data_toggle_attribute_returns_bootstrap5_value(): void
    {
        $this->assertSame('data-bs-toggle', $this->adapter->getDataToggleAttribute());
    }

    /**
     * @test
     * Requirement 5.5: getDataToggleAttribute() must NOT return the Bootstrap 4 value
     */
    public function test_get_data_toggle_attribute_is_not_bootstrap4_value(): void
    {
        $this->assertNotSame('data-toggle', $this->adapter->getDataToggleAttribute());
    }

    // ── getDismissAttribute ───────────────────────────────────────────────

    /**
     * @test
     * Requirement 2.8: Bootstrap5Adapter::getDismissAttribute() returns 'data-bs-dismiss'
     */
    public function test_get_dismiss_attribute_returns_bootstrap5_value(): void
    {
        $this->assertSame('data-bs-dismiss', $this->adapter->getDismissAttribute());
    }

    /**
     * @test
     * Requirement 2.8: getDismissAttribute() must NOT return the Bootstrap 4 value
     */
    public function test_get_dismiss_attribute_is_not_bootstrap4_value(): void
    {
        $this->assertNotSame('data-dismiss', $this->adapter->getDismissAttribute());
    }

    // ── getHideClass ──────────────────────────────────────────────────────

    /**
     * @test
     * Requirement 2.10: Bootstrap5Adapter::getHideClass() returns 'd-none'
     */
    public function test_get_hide_class_returns_bootstrap5_value(): void
    {
        $this->assertSame('d-none', $this->adapter->getHideClass());
    }

    /**
     * @test
     * Requirement 2.10: getHideClass() must NOT return the Bootstrap 4 value
     */
    public function test_get_hide_class_is_not_bootstrap4_value(): void
    {
        $this->assertNotSame('hide', $this->adapter->getHideClass());
    }

    // ── getFloatRightClass ────────────────────────────────────────────────

    /**
     * @test
     * Requirement 2.12: Bootstrap5Adapter::getFloatRightClass() returns 'float-end'
     */
    public function test_get_float_right_class_returns_bootstrap5_value(): void
    {
        $this->assertSame('float-end', $this->adapter->getFloatRightClass());
    }

    /**
     * @test
     * Requirement 2.12: getFloatRightClass() must NOT return the Bootstrap 4 value
     */
    public function test_get_float_right_class_is_not_bootstrap4_value(): void
    {
        $this->assertNotSame('pull-right', $this->adapter->getFloatRightClass());
    }

    // ── getSelectBoxClass ─────────────────────────────────────────────────

    /**
     * @test
     * Requirement 5.6: Bootstrap5Adapter::getSelectBoxClass() returns 'form-select'
     */
    public function test_get_select_box_class_returns_bootstrap5_native_select(): void
    {
        $this->assertSame('form-select', $this->adapter->getSelectBoxClass());
    }

    /**
     * @test
     * Requirement 5.6: getSelectBoxClass() must NOT return Chosen.js classes (Bootstrap 4)
     */
    public function test_get_select_box_class_does_not_return_chosen_js_classes(): void
    {
        $this->assertStringNotContainsString('chosen-select', $this->adapter->getSelectBoxClass());
        $this->assertStringNotContainsString('chosen-selectbox', $this->adapter->getSelectBoxClass());
    }

    // ── getTableClass ─────────────────────────────────────────────────────

    /**
     * @test
     * Requirement 9.4: Bootstrap5Adapter::getTableClass() does NOT contain 'animated' or 'fadeIn'
     */
    public function test_get_table_class_omits_animation_classes(): void
    {
        $tableClass = $this->adapter->getTableClass();

        $this->assertStringNotContainsString('animated', $tableClass);
        $this->assertStringNotContainsString('fadeIn', $tableClass);
    }

    /**
     * @test
     * Requirement 9.4: getTableClass() still contains the CanvaStack-table identifier
     */
    public function test_get_table_class_contains_canvastack_table(): void
    {
        $this->assertStringContainsString('CanvaStack-table', $this->adapter->getTableClass());
    }

    /**
     * @test
     * Requirement 9.4: getTableClass() contains core Bootstrap table classes
     */
    public function test_get_table_class_contains_core_bootstrap_table_classes(): void
    {
        $tableClass = $this->adapter->getTableClass();

        $this->assertStringContainsString('table', $tableClass);
        $this->assertStringContainsString('table-striped', $tableClass);
        $this->assertStringContainsString('table-bordered', $tableClass);
        $this->assertStringContainsString('table-hover', $tableClass);
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
     * Bootstrap 5 utility values must use the 'data-bs-' prefix where applicable.
     */
    public function test_toggle_and_dismiss_attributes_use_bs_prefix(): void
    {
        $this->assertStringStartsWith('data-bs-', $this->adapter->getDataToggleAttribute());
        $this->assertStringStartsWith('data-bs-', $this->adapter->getDismissAttribute());
    }
}
