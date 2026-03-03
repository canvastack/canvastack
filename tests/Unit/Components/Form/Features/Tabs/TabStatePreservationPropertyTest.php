<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Form\Features\Tabs;

use Canvastack\Canvastack\Components\Form\Features\Tabs\Tab;
use Canvastack\Canvastack\Components\Form\Features\Tabs\TabManager;
use Canvastack\Canvastack\Components\Form\FormBuilder;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Property Test 3: Tab State Preservation.
 *
 * Validates Requirements 1.10, 1.11:
 * - Tab state is preserved during validation errors
 * - Tabs containing errors are highlighted
 * - First tab with errors becomes active
 *
 * Property: For any form with tabs and validation errors,
 * the first tab containing errors MUST be active and highlighted.
 */
class TabStatePreservationPropertyTest extends TestCase
{
    private FormBuilder $formBuilder;

    private TabManager $tabManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formBuilder = app(FormBuilder::class);
        $this->tabManager = $this->formBuilder->getTabManager();
    }

    /**
     * Property: Tab with errors becomes active.
     *
     * For any form with multiple tabs, when validation errors occur,
     * the first tab containing fields with errors MUST become active.
     *
     * @test
     */
    public function property_tab_with_errors_becomes_active(): void
    {
        // Arrange: Create form with 3 tabs
        $this->formBuilder->openTab('Personal Info', 'active');
        $this->formBuilder->text('name', 'Name');
        $this->formBuilder->text('email', 'Email');
        $this->formBuilder->closeTab();

        $this->formBuilder->openTab('Address');
        $this->formBuilder->text('street', 'Street');
        $this->formBuilder->text('city', 'City');
        $this->formBuilder->closeTab();

        $this->formBuilder->openTab('Documents');
        $this->formBuilder->text('passport', 'Passport');
        $this->formBuilder->closeTab();

        // Simulate validation errors in second tab (Address)
        $errors = [
            'street' => 'The street field is required.',
            'city' => 'The city field is required.',
        ];

        // Act: Set validation errors
        $this->formBuilder->setValidationErrors($errors);

        // Assert: Second tab (Address) should be active
        $tabs = $this->tabManager->getTabs();
        $this->assertFalse($tabs[0]->isActive(), 'First tab should not be active');
        $this->assertTrue($tabs[1]->isActive(), 'Second tab with errors should be active');
        $this->assertFalse($tabs[2]->isActive(), 'Third tab should not be active');
    }

    /**
     * Property: Tabs with errors are marked with error class.
     *
     * For any form with tabs, when validation errors occur,
     * all tabs containing fields with errors MUST have 'has-errors' class.
     *
     * @test
     */
    public function property_tabs_with_errors_are_marked(): void
    {
        // Arrange: Create form with 3 tabs
        $this->formBuilder->openTab('Tab 1', 'active');
        $this->formBuilder->text('field1', 'Field 1');
        $this->formBuilder->closeTab();

        $this->formBuilder->openTab('Tab 2');
        $this->formBuilder->text('field2', 'Field 2');
        $this->formBuilder->closeTab();

        $this->formBuilder->openTab('Tab 3');
        $this->formBuilder->text('field3', 'Field 3');
        $this->formBuilder->closeTab();

        // Simulate validation errors in first and third tabs
        $errors = [
            'field1' => 'Error in field 1',
            'field3' => 'Error in field 3',
        ];

        // Act: Set validation errors
        $this->formBuilder->setValidationErrors($errors);

        // Assert: First and third tabs should have error class
        $tabs = $this->tabManager->getTabs();
        $this->assertTrue($tabs[0]->hasClass('has-errors'), 'First tab should have error class');
        $this->assertFalse($tabs[1]->hasClass('has-errors'), 'Second tab should not have error class');
        $this->assertTrue($tabs[2]->hasClass('has-errors'), 'Third tab should have error class');
    }

    /**
     * Property: First tab with errors takes precedence.
     *
     * When multiple tabs have errors, the FIRST tab with errors
     * MUST become active (left-to-right priority).
     *
     * @test
     */
    public function property_first_tab_with_errors_takes_precedence(): void
    {
        // Arrange: Create form with 4 tabs
        $this->formBuilder->openTab('Tab 1', 'active');
        $this->formBuilder->text('field1', 'Field 1');
        $this->formBuilder->closeTab();

        $this->formBuilder->openTab('Tab 2');
        $this->formBuilder->text('field2', 'Field 2');
        $this->formBuilder->closeTab();

        $this->formBuilder->openTab('Tab 3');
        $this->formBuilder->text('field3', 'Field 3');
        $this->formBuilder->closeTab();

        $this->formBuilder->openTab('Tab 4');
        $this->formBuilder->text('field4', 'Field 4');
        $this->formBuilder->closeTab();

        // Simulate validation errors in tabs 2, 3, and 4
        $errors = [
            'field2' => 'Error in field 2',
            'field3' => 'Error in field 3',
            'field4' => 'Error in field 4',
        ];

        // Act: Set validation errors
        $this->formBuilder->setValidationErrors($errors);

        // Assert: Second tab (first with errors) should be active
        $tabs = $this->tabManager->getTabs();
        $this->assertFalse($tabs[0]->isActive(), 'First tab should not be active');
        $this->assertTrue($tabs[1]->isActive(), 'Second tab (first with errors) should be active');
        $this->assertFalse($tabs[2]->isActive(), 'Third tab should not be active');
        $this->assertFalse($tabs[3]->isActive(), 'Fourth tab should not be active');
    }

    /**
     * Property: Tab state preserved when no errors.
     *
     * When no validation errors exist, the originally active tab
     * MUST remain active.
     *
     * @test
     */
    public function property_tab_state_preserved_when_no_errors(): void
    {
        // Arrange: Create form with 3 tabs, second tab active
        $this->formBuilder->openTab('Tab 1');
        $this->formBuilder->text('field1', 'Field 1');
        $this->formBuilder->closeTab();

        $this->formBuilder->openTab('Tab 2', 'active');
        $this->formBuilder->text('field2', 'Field 2');
        $this->formBuilder->closeTab();

        $this->formBuilder->openTab('Tab 3');
        $this->formBuilder->text('field3', 'Field 3');
        $this->formBuilder->closeTab();

        // Act: Set empty validation errors
        $this->formBuilder->setValidationErrors([]);

        // Assert: Second tab should still be active
        $tabs = $this->tabManager->getTabs();
        $this->assertFalse($tabs[0]->isActive(), 'First tab should not be active');
        $this->assertTrue($tabs[1]->isActive(), 'Second tab should remain active');
        $this->assertFalse($tabs[2]->isActive(), 'Third tab should not be active');
    }

    /**
     * Property: Active tab can be manually set.
     *
     * The setActiveTab method MUST correctly set the active tab
     * regardless of validation errors.
     *
     * @test
     */
    public function property_active_tab_can_be_manually_set(): void
    {
        // Arrange: Create form with 3 tabs
        $this->formBuilder->openTab('Tab 1', 'active');
        $this->formBuilder->text('field1', 'Field 1');
        $this->formBuilder->closeTab();

        $this->formBuilder->openTab('Tab 2');
        $this->formBuilder->text('field2', 'Field 2');
        $this->formBuilder->closeTab();

        $this->formBuilder->openTab('Tab 3');
        $this->formBuilder->text('field3', 'Field 3');
        $this->formBuilder->closeTab();

        $tabs = $this->tabManager->getTabs();
        $thirdTabId = $tabs[2]->getId();

        // Act: Manually set third tab as active
        $this->formBuilder->setActiveTab($thirdTabId);

        // Assert: Third tab should be active
        $this->assertFalse($tabs[0]->isActive(), 'First tab should not be active');
        $this->assertFalse($tabs[1]->isActive(), 'Second tab should not be active');
        $this->assertTrue($tabs[2]->isActive(), 'Third tab should be active');
    }

    /**
     * Property: getActiveTabId returns correct ID.
     *
     * The getActiveTabId method MUST return the ID of the currently active tab.
     *
     * @test
     */
    public function property_get_active_tab_id_returns_correct_id(): void
    {
        // Arrange: Create form with 3 tabs
        $this->formBuilder->openTab('Tab 1');
        $this->formBuilder->text('field1', 'Field 1');
        $this->formBuilder->closeTab();

        $this->formBuilder->openTab('Tab 2', 'active');
        $this->formBuilder->text('field2', 'Field 2');
        $this->formBuilder->closeTab();

        $this->formBuilder->openTab('Tab 3');
        $this->formBuilder->text('field3', 'Field 3');
        $this->formBuilder->closeTab();

        $tabs = $this->tabManager->getTabs();
        $expectedId = $tabs[1]->getId();

        // Act: Get active tab ID
        $activeTabId = $this->formBuilder->getActiveTabId();

        // Assert: Should return second tab's ID
        $this->assertEquals($expectedId, $activeTabId, 'Active tab ID should match second tab');
    }

    /**
     * Property: Validation errors are stored and retrievable.
     *
     * Validation errors set via setValidationErrors MUST be
     * retrievable via getValidationErrors.
     *
     * @test
     */
    public function property_validation_errors_are_stored_and_retrievable(): void
    {
        // Arrange: Create form with tabs
        $this->formBuilder->openTab('Tab 1', 'active');
        $this->formBuilder->text('field1', 'Field 1');
        $this->formBuilder->closeTab();

        $errors = [
            'field1' => 'The field1 field is required.',
            'field2' => 'The field2 field must be a valid email.',
        ];

        // Act: Set validation errors
        $this->formBuilder->setValidationErrors($errors);

        // Assert: Errors should be retrievable
        $retrievedErrors = $this->formBuilder->getValidationErrors();
        $this->assertEquals($errors, $retrievedErrors, 'Retrieved errors should match set errors');
    }

    /**
     * Property: Tab error detection works with array field names.
     *
     * Tabs MUST correctly detect errors in array fields (e.g., 'items[]', 'items[0]').
     *
     * @test
     */
    public function property_tab_error_detection_works_with_array_fields(): void
    {
        // Arrange: Create form with array fields
        $this->formBuilder->openTab('Items', 'active');
        $this->formBuilder->text('items[]', 'Items');
        $this->formBuilder->closeTab();

        // Simulate validation errors with array notation
        $errors = [
            'items.0' => 'The first item is required.',
            'items.1' => 'The second item is required.',
        ];

        // Act: Set validation errors
        $this->formBuilder->setValidationErrors($errors);

        // Assert: Tab should have errors
        $tabs = $this->tabManager->getTabs();
        $this->assertTrue($tabs[0]->hasClass('has-errors'), 'Tab should detect array field errors');
    }

    /**
     * Property: Rendering includes error highlighting.
     *
     * When form is rendered with validation errors, the rendered HTML
     * MUST include error indicators on tabs.
     *
     * @test
     */
    public function property_rendering_includes_error_highlighting(): void
    {
        // Arrange: Create form with tabs
        $this->formBuilder->openTab('Tab 1', 'active');
        $this->formBuilder->text('field1', 'Field 1');
        $this->formBuilder->closeTab();

        $this->formBuilder->openTab('Tab 2');
        $this->formBuilder->text('field2', 'Field 2');
        $this->formBuilder->closeTab();

        // Simulate validation errors in second tab
        $errors = [
            'field2' => 'The field2 field is required.',
        ];

        $this->formBuilder->setValidationErrors($errors);

        // Act: Render form
        $html = $this->formBuilder->render();

        // Assert: HTML should contain error indicators
        $this->assertStringContainsString('text-red-600', $html, 'HTML should contain error styling');
        $this->assertStringContainsString('!', $html, 'HTML should contain error badge');
    }
}
