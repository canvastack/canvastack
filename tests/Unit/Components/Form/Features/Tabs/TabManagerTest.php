<?php

declare(strict_types=1);

namespace Tests\Unit\Components\Form\Features\Tabs;

use Canvastack\Canvastack\Components\Form\Features\Tabs\Tab;
use Canvastack\Canvastack\Components\Form\Features\Tabs\TabManager;
use Canvastack\Canvastack\Components\Form\Fields\TextField;
use Canvastack\Canvastack\Components\Form\Renderers\AdminRenderer;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Unit Tests for TabManager.
 *
 * Tests Requirements: 1.1, 1.2, 1.3
 */
class TabManagerTest extends TestCase
{
    protected TabManager $tabManager;

    protected AdminRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->renderer = new AdminRenderer();
        $this->tabManager = new TabManager($this->renderer);
    }

    /**
     * Test tab creation with label and class
     * Requirement 1.1.
     *
     * @test
     */
    public function test_can_create_tab_with_label(): void
    {
        $tab = $this->tabManager->openTab('Personal Info');

        $this->assertInstanceOf(Tab::class, $tab);
        $this->assertEquals('Personal Info', $tab->getLabel());
    }

    /**
     * Test tab creation with active class
     * Requirement 1.1.
     *
     * @test
     */
    public function test_can_create_tab_with_active_class(): void
    {
        $tab = $this->tabManager->openTab('Personal Info', 'active');

        $this->assertTrue($tab->isActive());
        $this->assertTrue($tab->hasClass('active'));
    }

    /**
     * Test tab creation with custom class
     * Requirement 1.1.
     *
     * @test
     */
    public function test_can_create_tab_with_custom_class(): void
    {
        $tab = $this->tabManager->openTab('Personal Info', 'custom-class');

        $this->assertTrue($tab->hasClass('custom-class'));
    }

    /**
     * Test tab creation with boolean true for active
     * Requirement 1.1.
     *
     * @test
     */
    public function test_can_create_tab_with_boolean_active(): void
    {
        $tab = $this->tabManager->openTab('Personal Info', true);

        $this->assertTrue($tab->isActive());
        $this->assertTrue($tab->hasClass('active'));
    }

    /**
     * Test field association with active tab
     * Requirement 1.2.
     *
     * @test
     */
    public function test_can_add_field_to_active_tab(): void
    {
        $tab = $this->tabManager->openTab('Personal Info');
        $field = new TextField('name', 'Name');

        $this->tabManager->addFieldToActiveTab($field);

        $this->assertCount(1, $tab->getFields());
        $this->assertSame($field, $tab->getFields()[0]);
    }

    /**
     * Test multiple fields association
     * Requirement 1.2.
     *
     * @test
     */
    public function test_can_add_multiple_fields_to_active_tab(): void
    {
        $tab = $this->tabManager->openTab('Personal Info');

        $field1 = new TextField('name', 'Name');
        $field2 = new TextField('email', 'Email');
        $field3 = new TextField('phone', 'Phone');

        $this->tabManager->addFieldToActiveTab($field1);
        $this->tabManager->addFieldToActiveTab($field2);
        $this->tabManager->addFieldToActiveTab($field3);

        $this->assertCount(3, $tab->getFields());
    }

    /**
     * Test field not added when no active tab
     * Requirement 1.2.
     *
     * @test
     */
    public function test_field_not_added_when_no_active_tab(): void
    {
        $field = new TextField('name', 'Name');

        $this->tabManager->addFieldToActiveTab($field);

        $this->assertCount(0, $this->tabManager->getTabs());
    }

    /**
     * Test tab state management - close tab
     * Requirement 1.3.
     *
     * @test
     */
    public function test_can_close_active_tab(): void
    {
        $this->tabManager->openTab('Personal Info');
        $this->assertNotNull($this->tabManager->getActiveTab());

        $this->tabManager->closeTab();
        $this->assertNull($this->tabManager->getActiveTab());
    }

    /**
     * Test tab state management - multiple tabs
     * Requirement 1.3.
     *
     * @test
     */
    public function test_opening_new_tab_changes_active_tab(): void
    {
        $tab1 = $this->tabManager->openTab('Tab 1');
        $this->assertSame($tab1, $this->tabManager->getActiveTab());

        $tab2 = $this->tabManager->openTab('Tab 2');
        $this->assertSame($tab2, $this->tabManager->getActiveTab());
        $this->assertNotSame($tab1, $this->tabManager->getActiveTab());
    }

    /**
     * Test adding custom content to active tab
     * Requirement 1.4.
     *
     * @test
     */
    public function test_can_add_content_to_active_tab(): void
    {
        $tab = $this->tabManager->openTab('Personal Info');
        $html = '<p>Custom content</p>';

        $this->tabManager->addTabContent($html);

        $this->assertCount(1, $tab->getContent());
        $this->assertEquals($html, $tab->getContent()[0]);
    }

    /**
     * Test adding multiple content blocks
     * Requirement 1.4.
     *
     * @test
     */
    public function test_can_add_multiple_content_blocks(): void
    {
        $tab = $this->tabManager->openTab('Personal Info');

        $this->tabManager->addTabContent('<p>Content 1</p>');
        $this->tabManager->addTabContent('<div>Content 2</div>');
        $this->tabManager->addTabContent('<span>Content 3</span>');

        $this->assertCount(3, $tab->getContent());
    }

    /**
     * Test content not added when no active tab
     * Requirement 1.4.
     *
     * @test
     */
    public function test_content_not_added_when_no_active_tab(): void
    {
        $this->tabManager->addTabContent('<p>Content</p>');

        $this->assertCount(0, $this->tabManager->getTabs());
    }

    /**
     * Test hasTabs returns false when no tabs.
     *
     * @test
     */
    public function test_has_tabs_returns_false_when_no_tabs(): void
    {
        $this->assertFalse($this->tabManager->hasTabs());
    }

    /**
     * Test hasTabs returns true when tabs exist.
     *
     * @test
     */
    public function test_has_tabs_returns_true_when_tabs_exist(): void
    {
        $this->tabManager->openTab('Tab 1');

        $this->assertTrue($this->tabManager->hasTabs());
    }

    /**
     * Test getTabs returns all tabs.
     *
     * @test
     */
    public function test_get_tabs_returns_all_tabs(): void
    {
        $tab1 = $this->tabManager->openTab('Tab 1');
        $tab2 = $this->tabManager->openTab('Tab 2');
        $tab3 = $this->tabManager->openTab('Tab 3');

        $tabs = $this->tabManager->getTabs();

        $this->assertCount(3, $tabs);
        $this->assertSame($tab1, $tabs[0]);
        $this->assertSame($tab2, $tabs[1]);
        $this->assertSame($tab3, $tabs[2]);
    }

    /**
     * Test getActiveTab returns current active tab.
     *
     * @test
     */
    public function test_get_active_tab_returns_current_active_tab(): void
    {
        $tab = $this->tabManager->openTab('Active Tab');

        $this->assertSame($tab, $this->tabManager->getActiveTab());
    }

    /**
     * Test getActiveTab returns null when no active tab.
     *
     * @test
     */
    public function test_get_active_tab_returns_null_when_no_active_tab(): void
    {
        $this->assertNull($this->tabManager->getActiveTab());
    }

    /**
     * Test render returns empty string when no tabs.
     *
     * @test
     */
    public function test_render_returns_empty_string_when_no_tabs(): void
    {
        $html = $this->tabManager->render();

        $this->assertEquals('', $html);
    }

    /**
     * Test getTabWithErrors finds tab with errors
     * Requirement 1.11.
     *
     * @test
     */
    public function test_get_tab_with_errors_finds_tab_with_errors(): void
    {
        $tab1 = $this->tabManager->openTab('Tab 1');
        $field1 = new TextField('name', 'Name');
        $this->tabManager->addFieldToActiveTab($field1);
        $this->tabManager->closeTab();

        $tab2 = $this->tabManager->openTab('Tab 2');
        $field2 = new TextField('email', 'Email');
        $this->tabManager->addFieldToActiveTab($field2);
        $this->tabManager->closeTab();

        $errors = ['email' => 'Email is required'];

        $tabWithErrors = $this->tabManager->getTabWithErrors($errors);

        $this->assertSame($tab2, $tabWithErrors);
    }

    /**
     * Test getTabWithErrors returns null when no errors
     * Requirement 1.11.
     *
     * @test
     */
    public function test_get_tab_with_errors_returns_null_when_no_errors(): void
    {
        $tab = $this->tabManager->openTab('Tab 1');
        $field = new TextField('name', 'Name');
        $this->tabManager->addFieldToActiveTab($field);

        $errors = ['other_field' => 'Error'];

        $tabWithErrors = $this->tabManager->getTabWithErrors($errors);

        $this->assertNull($tabWithErrors);
    }

    /**
     * Test reset clears all tabs and active tab.
     *
     * @test
     */
    public function test_reset_clears_all_tabs_and_active_tab(): void
    {
        $this->tabManager->openTab('Tab 1');
        $this->tabManager->openTab('Tab 2');
        $this->tabManager->openTab('Tab 3');

        $this->assertCount(3, $this->tabManager->getTabs());
        $this->assertNotNull($this->tabManager->getActiveTab());

        $this->tabManager->reset();

        $this->assertCount(0, $this->tabManager->getTabs());
        $this->assertNull($this->tabManager->getActiveTab());
    }

    /**
     * Test complete workflow: open, add fields, close, repeat.
     *
     * @test
     */
    public function test_complete_workflow(): void
    {
        // Tab 1
        $tab1 = $this->tabManager->openTab('Personal Info', 'active');
        $this->tabManager->addFieldToActiveTab(new TextField('name', 'Name'));
        $this->tabManager->addFieldToActiveTab(new TextField('email', 'Email'));
        $this->tabManager->addTabContent('<p>Personal information section</p>');
        $this->tabManager->closeTab();

        // Tab 2
        $tab2 = $this->tabManager->openTab('Address');
        $this->tabManager->addFieldToActiveTab(new TextField('street', 'Street'));
        $this->tabManager->addFieldToActiveTab(new TextField('city', 'City'));
        $this->tabManager->closeTab();

        // Tab 3
        $tab3 = $this->tabManager->openTab('Documents');
        $this->tabManager->addFieldToActiveTab(new TextField('document', 'Document'));
        $this->tabManager->closeTab();

        // Assertions
        $this->assertCount(3, $this->tabManager->getTabs());
        $this->assertNull($this->tabManager->getActiveTab());

        $this->assertTrue($tab1->isActive());
        $this->assertCount(2, $tab1->getFields());
        $this->assertCount(1, $tab1->getContent());

        $this->assertFalse($tab2->isActive());
        $this->assertCount(2, $tab2->getFields());

        $this->assertFalse($tab3->isActive());
        $this->assertCount(1, $tab3->getFields());
    }
}
