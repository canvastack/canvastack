<?php

namespace Tests\Unit\Theme;

use Tests\TestCase;
use Canvastack\Canvastack\Library\Theme\Adapters\TailwindAdapter;

/**
 * Unit tests for TailwindAdapter utility methods.
 *
 * Validates that each utility method returns the correct Tailwind value,
 * distinct from Bootstrap 4 and Bootstrap 5 equivalents.
 *
 * Requirements: 6.5, 6.6
 */
class TailwindAdapterUtilityTest extends TestCase
{
    private TailwindAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adapter = new TailwindAdapter();
    }

    // ── getDataToggleAttribute ────────────────────────────────────────────

    /**
     * @test
     * Requirement 6.5: TailwindAdapter::getDataToggleAttribute() returns 'data-toggle'
     * (Tailwind uses custom JS, not Bootstrap framework-specific attributes)
     */
    public function test_get_data_toggle_attribute_returns_data_toggle(): void
    {
        $this->assertSame('data-toggle', $this->adapter->getDataToggleAttribute());
    }

    /**
     * @test
     * Requirement 6.5: getDataToggleAttribute() must NOT return the Bootstrap 5 value
     */
    public function test_get_data_toggle_attribute_is_not_bootstrap5_value(): void
    {
        $this->assertNotSame('data-bs-toggle', $this->adapter->getDataToggleAttribute());
    }

    // ── getDismissAttribute ───────────────────────────────────────────────

    /**
     * @test
     * Requirement 6.5: TailwindAdapter::getDismissAttribute() returns 'data-dismiss'
     * (Tailwind uses custom JS, not Bootstrap framework-specific attributes)
     */
    public function test_get_dismiss_attribute_returns_data_dismiss(): void
    {
        $this->assertSame('data-dismiss', $this->adapter->getDismissAttribute());
    }

    /**
     * @test
     * Requirement 6.5: getDismissAttribute() must NOT return the Bootstrap 5 value
     */
    public function test_get_dismiss_attribute_is_not_bootstrap5_value(): void
    {
        $this->assertNotSame('data-bs-dismiss', $this->adapter->getDismissAttribute());
    }

    // ── getHideClass ──────────────────────────────────────────────────────

    /**
     * @test
     * Requirement 6.5: TailwindAdapter::getHideClass() returns 'hidden'
     */
    public function test_get_hide_class_returns_hidden(): void
    {
        $this->assertSame('hidden', $this->adapter->getHideClass());
    }

    /**
     * @test
     * Requirement 6.5: getHideClass() must NOT return Bootstrap 4 or Bootstrap 5 values
     */
    public function test_get_hide_class_is_not_bootstrap_value(): void
    {
        $this->assertNotSame('hide', $this->adapter->getHideClass());
        $this->assertNotSame('d-none', $this->adapter->getHideClass());
    }

    // ── getFloatRightClass ────────────────────────────────────────────────

    /**
     * @test
     * Requirement 6.5: TailwindAdapter::getFloatRightClass() returns 'ml-auto'
     */
    public function test_get_float_right_class_returns_ml_auto(): void
    {
        $this->assertSame('ml-auto', $this->adapter->getFloatRightClass());
    }

    /**
     * @test
     * Requirement 6.5: getFloatRightClass() must NOT return Bootstrap 4 or Bootstrap 5 values
     */
    public function test_get_float_right_class_is_not_bootstrap_value(): void
    {
        $this->assertNotSame('pull-right', $this->adapter->getFloatRightClass());
        $this->assertNotSame('float-end', $this->adapter->getFloatRightClass());
    }

    // ── getSelectBoxClass ─────────────────────────────────────────────────

    /**
     * @test
     * Requirement 6.6: TailwindAdapter::getSelectBoxClass() returns 'form-input'
     */
    public function test_get_select_box_class_returns_form_input(): void
    {
        $this->assertSame('form-input', $this->adapter->getSelectBoxClass());
    }

    /**
     * @test
     * Requirement 6.6: getSelectBoxClass() must NOT return Bootstrap 4 Chosen.js classes
     */
    public function test_get_select_box_class_does_not_return_chosen_js_classes(): void
    {
        $this->assertStringNotContainsString('chosen-select', $this->adapter->getSelectBoxClass());
        $this->assertStringNotContainsString('chosen-selectbox', $this->adapter->getSelectBoxClass());
    }

    /**
     * @test
     * Requirement 6.6: getSelectBoxClass() must NOT return Bootstrap 5 form-select class
     */
    public function test_get_select_box_class_is_not_bootstrap5_value(): void
    {
        $this->assertNotSame('form-select', $this->adapter->getSelectBoxClass());
    }

    // ── getTableClass ─────────────────────────────────────────────────────

    /**
     * @test
     * Requirement 9.5: TailwindAdapter::getTableClass() contains Tailwind utility classes
     */
    public function test_get_table_class_contains_tailwind_utility_classes(): void
    {
        $tableClass = $this->adapter->getTableClass();

        $this->assertStringContainsString('w-full', $tableClass);
        $this->assertStringContainsString('text-sm', $tableClass);
        $this->assertStringContainsString('text-left', $tableClass);
    }

    /**
     * @test
     * Requirement 9.5: TailwindAdapter::getTableClass() does NOT contain Bootstrap animation classes
     */
    public function test_get_table_class_omits_bootstrap_animation_classes(): void
    {
        $tableClass = $this->adapter->getTableClass();

        $this->assertStringNotContainsString('animated', $tableClass);
        $this->assertStringNotContainsString('fadeIn', $tableClass);
    }

    /**
     * @test
     * Requirement 9.5: getTableClass() still contains the CanvaStack-table identifier
     */
    public function test_get_table_class_contains_canvastack_table(): void
    {
        $this->assertStringContainsString('CanvaStack-table', $this->adapter->getTableClass());
    }

    /**
     * @test
     * Requirement 9.5: getTableClass() does NOT contain Bootstrap-specific table modifier classes
     */
    public function test_get_table_class_does_not_contain_bootstrap_table_classes(): void
    {
        $tableClass = $this->adapter->getTableClass();

        $this->assertStringNotContainsString('table-striped', $tableClass);
        $this->assertStringNotContainsString('table-bordered', $tableClass);
        $this->assertStringNotContainsString('table-hover', $tableClass);
        $this->assertStringNotContainsString('table-default', $tableClass);
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
     * TailwindAdapter toggle and dismiss attributes do NOT use the 'data-bs-' prefix.
     */
    public function test_toggle_and_dismiss_attributes_do_not_use_bs_prefix(): void
    {
        $this->assertStringNotContainsString('data-bs-', $this->adapter->getDataToggleAttribute());
        $this->assertStringNotContainsString('data-bs-', $this->adapter->getDismissAttribute());
    }
}
