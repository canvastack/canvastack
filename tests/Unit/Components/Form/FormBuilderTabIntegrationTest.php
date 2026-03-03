<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Form;

use Canvastack\Canvastack\Components\Form\FormBuilder;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Integration Test: FormBuilder with Tabs.
 *
 * Tests complete tab workflow integration with FormBuilder.
 * Validates Requirements 1.1, 1.2, 1.3, 1.10, 1.11.
 */
class FormBuilderTabIntegrationTest extends TestCase
{
    private FormBuilder $formBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formBuilder = app(FormBuilder::class);
    }

    /**
     * Test complete tab workflow.
     *
     * @test
     */
    public function test_complete_tab_workflow(): void
    {
        // Arrange & Act: Create form with tabs
        $this->formBuilder->openTab('Personal Info', 'active');
        $this->formBuilder->text('name', 'Name');
        $this->formBuilder->email('email', 'Email');
        $this->formBuilder->closeTab();

        $this->formBuilder->openTab('Address');
        $this->formBuilder->text('street', 'Street');
        $this->formBuilder->text('city', 'City');
        $this->formBuilder->closeTab();

        // Assert: Tabs should be created
        $tabManager = $this->formBuilder->getTabManager();
        $this->assertTrue($tabManager->hasTabs(), 'Form should have tabs');
        $this->assertCount(2, $tabManager->getTabs(), 'Form should have 2 tabs');

        // Assert: Fields should be associated with tabs
        $tabs = $tabManager->getTabs();
        $this->assertCount(2, $tabs[0]->getFields(), 'First tab should have 2 fields');
        $this->assertCount(2, $tabs[1]->getFields(), 'Second tab should have 2 fields');

        // Assert: First tab should be active
        $this->assertTrue($tabs[0]->isActive(), 'First tab should be active');
        $this->assertFalse($tabs[1]->isActive(), 'Second tab should not be active');
    }

    /**
     * Test tab rendering with fields.
     *
     * @test
     */
    public function test_tab_rendering_with_fields(): void
    {
        // Arrange: Create form with tabs
        $this->formBuilder->openTab('Tab 1', 'active');
        $this->formBuilder->text('field1', 'Field 1');
        $this->formBuilder->closeTab();

        $this->formBuilder->openTab('Tab 2');
        $this->formBuilder->text('field2', 'Field 2');
        $this->formBuilder->closeTab();

        // Act: Render form
        $html = $this->formBuilder->render();

        // Assert: HTML should contain tab structure
        $this->assertStringContainsString('tabs-container', $html, 'HTML should contain tabs container');
        $this->assertStringContainsString('role="tablist"', $html, 'HTML should contain tab list');
        $this->assertStringContainsString('role="tabpanel"', $html, 'HTML should contain tab panels');
        $this->assertStringContainsString('Tab 1', $html, 'HTML should contain first tab label');
        $this->assertStringContainsString('Tab 2', $html, 'HTML should contain second tab label');
        $this->assertStringContainsString('Field 1', $html, 'HTML should contain first field label');
        $this->assertStringContainsString('Field 2', $html, 'HTML should contain second field label');
    }

    /**
     * Test validation error highlighting.
     *
     * @test
     */
    public function test_validation_error_highlighting(): void
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

        // Assert: Second tab should be active
        $tabs = $this->formBuilder->getTabManager()->getTabs();
        $this->assertFalse($tabs[0]->isActive(), 'First tab should not be active');
        $this->assertTrue($tabs[1]->isActive(), 'Second tab with errors should be active');
    }

    /**
     * Test form without tabs renders normally.
     *
     * @test
     */
    public function test_form_without_tabs_renders_normally(): void
    {
        // Arrange: Create form without tabs
        $this->formBuilder->text('name', 'Name');
        $this->formBuilder->email('email', 'Email');

        // Act: Render form
        $html = $this->formBuilder->render();

        // Assert: HTML should not contain tab structure
        $this->assertStringNotContainsString('tabs-container', $html, 'HTML should not contain tabs container');
        $this->assertStringNotContainsString('role="tablist"', $html, 'HTML should not contain tab list');

        // Assert: HTML should contain fields
        $this->assertStringContainsString('Name', $html, 'HTML should contain name field');
        $this->assertStringContainsString('Email', $html, 'HTML should contain email field');
    }

    /**
     * Test mixed tabs and non-tab fields.
     *
     * @test
     */
    public function test_mixed_tabs_and_non_tab_fields(): void
    {
        // Arrange: Create form with tabs and non-tab fields
        $this->formBuilder->text('field_before', 'Field Before Tabs');

        $this->formBuilder->openTab('Tab 1', 'active');
        $this->formBuilder->text('field1', 'Field 1');
        $this->formBuilder->closeTab();

        $this->formBuilder->openTab('Tab 2');
        $this->formBuilder->text('field2', 'Field 2');
        $this->formBuilder->closeTab();

        $this->formBuilder->text('field_after', 'Field After Tabs');

        // Act: Render form
        $html = $this->formBuilder->render();

        // Assert: HTML should contain both tabs and non-tab fields
        $this->assertStringContainsString('tabs-container', $html, 'HTML should contain tabs');
        $this->assertStringContainsString('Field Before Tabs', $html, 'HTML should contain field before tabs');
        $this->assertStringContainsString('Field After Tabs', $html, 'HTML should contain field after tabs');
    }

    /**
     * Test tab with custom content.
     *
     * @test
     */
    public function test_tab_with_custom_content(): void
    {
        // Arrange: Create tab with custom content
        $this->formBuilder->openTab('Tab 1', 'active');
        $this->formBuilder->text('field1', 'Field 1');
        $this->formBuilder->addTabContent('<div class="custom-content">Custom HTML</div>');
        $this->formBuilder->closeTab();

        // Act: Render form
        $html = $this->formBuilder->render();

        // Assert: HTML should contain custom content
        $this->assertStringContainsString('custom-content', $html, 'HTML should contain custom content');
        $this->assertStringContainsString('Custom HTML', $html, 'HTML should contain custom HTML text');
    }

    /**
     * Test multiple tabs with different field types.
     *
     * @test
     */
    public function test_multiple_tabs_with_different_field_types(): void
    {
        // Arrange: Create form with various field types
        $this->formBuilder->openTab('Text Fields', 'active');
        $this->formBuilder->text('name', 'Name');
        $this->formBuilder->email('email', 'Email');
        $this->formBuilder->closeTab();

        $this->formBuilder->openTab('Selection Fields');
        $this->formBuilder->select('country', 'Country', ['US' => 'United States', 'UK' => 'United Kingdom']);
        $this->formBuilder->checkbox('terms', 'Terms', ['agree' => 'I agree']);
        $this->formBuilder->closeTab();

        $this->formBuilder->openTab('Date Fields');
        $this->formBuilder->date('birthdate', 'Birth Date');
        $this->formBuilder->time('appointment', 'Appointment Time');
        $this->formBuilder->closeTab();

        // Act: Get tabs
        $tabs = $this->formBuilder->getTabManager()->getTabs();

        // Assert: All tabs should have correct field counts
        $this->assertCount(3, $tabs, 'Form should have 3 tabs');
        $this->assertCount(2, $tabs[0]->getFields(), 'First tab should have 2 fields');
        $this->assertCount(2, $tabs[1]->getFields(), 'Second tab should have 2 fields');
        $this->assertCount(2, $tabs[2]->getFields(), 'Third tab should have 2 fields');
    }

    /**
     * Test tab state preservation across renders.
     *
     * @test
     */
    public function test_tab_state_preservation_across_renders(): void
    {
        // Arrange: Create form with tabs
        $this->formBuilder->openTab('Tab 1');
        $this->formBuilder->text('field1', 'Field 1');
        $this->formBuilder->closeTab();

        $this->formBuilder->openTab('Tab 2', 'active');
        $this->formBuilder->text('field2', 'Field 2');
        $this->formBuilder->closeTab();

        // Act: Render form multiple times
        $html1 = $this->formBuilder->render();
        $html2 = $this->formBuilder->render();

        // Assert: Both renders should be identical
        $this->assertEquals($html1, $html2, 'Multiple renders should produce identical output');

        // Assert: Second tab should remain active
        $tabs = $this->formBuilder->getTabManager()->getTabs();
        $this->assertTrue($tabs[1]->isActive(), 'Second tab should remain active');
    }

    /**
     * Test Alpine.js integration in tab rendering.
     *
     * @test
     */
    public function test_alpine_js_integration_in_tab_rendering(): void
    {
        // Arrange: Create form with tabs
        $this->formBuilder->openTab('Tab 1', 'active');
        $this->formBuilder->text('field1', 'Field 1');
        $this->formBuilder->closeTab();

        // Act: Render form
        $html = $this->formBuilder->render();

        // Assert: HTML should contain Alpine.js directives
        $this->assertStringContainsString('x-data', $html, 'HTML should contain x-data directive');
        $this->assertStringContainsString('x-show', $html, 'HTML should contain x-show directive');
        $this->assertStringContainsString('@click.prevent', $html, 'HTML should contain click handler');
        $this->assertStringContainsString('activeTab', $html, 'HTML should contain activeTab variable');
    }

    /**
     * Test accessibility attributes in tab rendering.
     *
     * @test
     */
    public function test_accessibility_attributes_in_tab_rendering(): void
    {
        // Arrange: Create form with tabs
        $this->formBuilder->openTab('Tab 1', 'active');
        $this->formBuilder->text('field1', 'Field 1');
        $this->formBuilder->closeTab();

        $this->formBuilder->openTab('Tab 2');
        $this->formBuilder->text('field2', 'Field 2');
        $this->formBuilder->closeTab();

        // Act: Render form
        $html = $this->formBuilder->render();

        // Assert: HTML should contain ARIA attributes
        $this->assertStringContainsString('role="tab"', $html, 'HTML should contain tab role');
        $this->assertStringContainsString('role="tabpanel"', $html, 'HTML should contain tabpanel role');
        $this->assertStringContainsString('aria-controls', $html, 'HTML should contain aria-controls');
        $this->assertStringContainsString('aria-selected', $html, 'HTML should contain aria-selected');
        $this->assertStringContainsString('aria-hidden', $html, 'HTML should contain aria-hidden');
    }

    /**
     * Test tab rendering in different contexts (Admin/Public).
     *
     * @test
     */
    public function test_tab_rendering_in_different_contexts(): void
    {
        // Test Admin Context
        $adminBuilder = app(FormBuilder::class);
        $adminBuilder->setContext('admin');
        $adminBuilder->openTab('Tab 1', 'active');
        $adminBuilder->text('field1', 'Field 1');
        $adminBuilder->closeTab();

        // Act: Render in admin context
        $adminHtml = $adminBuilder->render();

        // Assert: Admin HTML should contain tabs
        $this->assertStringContainsString('tabs-container', $adminHtml, 'Admin HTML should contain tabs');

        // Test Public Context
        $publicBuilder = app(FormBuilder::class);
        $publicBuilder->setContext('public');
        $publicBuilder->openTab('Tab 1', 'active');
        $publicBuilder->text('field1', 'Field 1');
        $publicBuilder->closeTab();

        // Act: Render in public context
        $publicHtml = $publicBuilder->render();

        // Assert: Public HTML should also contain tabs
        $this->assertStringContainsString('tabs-container', $publicHtml, 'Public HTML should contain tabs');
    }

    /**
     * Test getActiveTabId method.
     *
     * @test
     */
    public function test_get_active_tab_id_method(): void
    {
        // Arrange: Create form with tabs
        $this->formBuilder->openTab('Tab 1');
        $this->formBuilder->text('field1', 'Field 1');
        $this->formBuilder->closeTab();

        $this->formBuilder->openTab('Tab 2', 'active');
        $this->formBuilder->text('field2', 'Field 2');
        $this->formBuilder->closeTab();

        // Act: Get active tab ID
        $activeTabId = $this->formBuilder->getActiveTabId();

        // Assert: Should return second tab's ID
        $tabs = $this->formBuilder->getTabManager()->getTabs();
        $this->assertEquals($tabs[1]->getId(), $activeTabId, 'Active tab ID should match second tab');
    }

    /**
     * Test setActiveTab method.
     *
     * @test
     */
    public function test_set_active_tab_method(): void
    {
        // Arrange: Create form with tabs
        $this->formBuilder->openTab('Tab 1', 'active');
        $this->formBuilder->text('field1', 'Field 1');
        $this->formBuilder->closeTab();

        $this->formBuilder->openTab('Tab 2');
        $this->formBuilder->text('field2', 'Field 2');
        $this->formBuilder->closeTab();

        $tabs = $this->formBuilder->getTabManager()->getTabs();
        $secondTabId = $tabs[1]->getId();

        // Act: Set second tab as active
        $this->formBuilder->setActiveTab($secondTabId);

        // Assert: Second tab should be active
        $this->assertFalse($tabs[0]->isActive(), 'First tab should not be active');
        $this->assertTrue($tabs[1]->isActive(), 'Second tab should be active');
    }
}
