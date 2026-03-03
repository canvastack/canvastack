<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Test tab navigation UI generation for TableBuilder.
 *
 * Tests the Alpine.js-powered tab navigation component including:
 * - Tab navigation HTML generation
 * - Active tab highlighting
 * - URL parameter sync
 * - Smooth transitions
 * - Accessibility attributes
 * - Keyboard navigation support
 */
class TabNavigationUITest extends TestCase
{
    protected TableBuilder $table;

    protected function setUp(): void
    {
        parent::setUp();
        $this->table = $this->app->make(TableBuilder::class);
        $this->table->setContext('admin');
    }

    /**
     * Test that tab navigation component is rendered when tabs exist.
     */
    public function test_renders_tab_navigation_when_tabs_exist(): void
    {
        // Create tabs
        $this->table->openTab('Summary');
        $this->table->setData([['name' => 'Test 1']]);
        $this->table->setFields(['name:Name']);
        $this->table->closeTab();

        $this->table->openTab('Detail');
        $this->table->setData([['name' => 'Test 2']]);
        $this->table->setFields(['name:Name']);
        $this->table->closeTab();

        // Render with tabs
        $html = $this->table->renderWithTabs();

        // Assert tab navigation is present
        $this->assertStringContainsString('x-data="tableTabs_', $html);
        $this->assertStringContainsString('role="tablist"', $html);
        $this->assertStringContainsString('role="tab"', $html);
        $this->assertStringContainsString('role="tabpanel"', $html);
    }

    /**
     * Test that regular table is rendered when no tabs exist.
     */
    public function test_renders_regular_table_when_no_tabs_exist(): void
    {
        // Setup table without tabs
        $this->table->setData([['name' => 'Test']]);
        $this->table->setFields(['name:Name']);
        $this->table->format();

        // Render with tabs (should fall back to regular render)
        $html = $this->table->renderWithTabs();

        // Assert no tab navigation
        $this->assertStringNotContainsString('x-data="tableTabs_', $html);
        $this->assertStringNotContainsString('role="tablist"', $html);

        // Assert regular table is present
        $this->assertStringContainsString('table', $html);
    }

    /**
     * Test that tab navigation includes all tab names.
     */
    public function test_tab_navigation_includes_all_tab_names(): void
    {
        // Create multiple tabs
        $this->table->openTab('Summary');
        $this->table->setData([['name' => 'Test 1']]);
        $this->table->setFields(['name:Name']);
        $this->table->closeTab();

        $this->table->openTab('Detail');
        $this->table->setData([['name' => 'Test 2']]);
        $this->table->setFields(['name:Name']);
        $this->table->closeTab();

        $this->table->openTab('Monthly');
        $this->table->setData([['name' => 'Test 3']]);
        $this->table->setFields(['name:Name']);
        $this->table->closeTab();

        // Render
        $html = $this->table->renderWithTabs();

        // Get tabs array
        $tabs = $this->table->getTabManager()->getTabsArray();

        // Assert all tab names are in the HTML
        foreach ($tabs as $tab) {
            $this->assertStringContainsString($tab['name'], $html);
        }
    }

    /**
     * Test that active tab is highlighted.
     */
    public function test_active_tab_is_highlighted(): void
    {
        // Create tabs
        $this->table->openTab('Summary');
        $this->table->setData([['name' => 'Test 1']]);
        $this->table->setFields(['name:Name']);
        $this->table->closeTab();

        $this->table->openTab('Detail');
        $this->table->setData([['name' => 'Test 2']]);
        $this->table->setFields(['name:Name']);
        $this->table->closeTab();

        // Get active tab
        $activeTab = $this->table->getTabManager()->getActiveTab();

        // Render
        $html = $this->table->renderWithTabs();

        // Assert active tab is in the data
        $this->assertStringContainsString("activeTab: \"{$activeTab}\"", $html);
    }

    /**
     * Test that tab navigation has proper ARIA attributes.
     */
    public function test_tab_navigation_has_aria_attributes(): void
    {
        // Create tabs
        $this->table->openTab('Summary');
        $this->table->setData([['name' => 'Test']]);
        $this->table->setFields(['name:Name']);
        $this->table->closeTab();

        // Render
        $html = $this->table->renderWithTabs();

        // Assert ARIA attributes
        $this->assertStringContainsString('aria-label="Tabs"', $html);
        $this->assertStringContainsString('aria-selected', $html);
        $this->assertStringContainsString('aria-controls', $html);
        $this->assertStringContainsString('aria-labelledby', $html);
    }

    /**
     * Test that tab navigation includes Alpine.js data.
     */
    public function test_tab_navigation_includes_alpine_data(): void
    {
        // Create tabs
        $this->table->openTab('Summary');
        $this->table->setData([['name' => 'Test']]);
        $this->table->setFields(['name:Name']);
        $this->table->closeTab();

        // Render
        $html = $this->table->renderWithTabs();

        // Assert Alpine.js attributes
        $this->assertStringContainsString('x-data=', $html);
        $this->assertStringContainsString('x-init=', $html);
        $this->assertStringContainsString('x-for=', $html);
        $this->assertStringContainsString('x-show=', $html);
        $this->assertStringContainsString('@click=', $html);
    }

    /**
     * Test that tab navigation includes transition classes.
     */
    public function test_tab_navigation_includes_transitions(): void
    {
        // Create tabs
        $this->table->openTab('Summary');
        $this->table->setData([['name' => 'Test']]);
        $this->table->setFields(['name:Name']);
        $this->table->closeTab();

        // Render
        $html = $this->table->renderWithTabs();

        // Assert transition attributes
        $this->assertStringContainsString('x-transition:', $html);
        $this->assertStringContainsString('transition ease-out duration-200', $html);
    }

    /**
     * Test that tab navigation includes JavaScript function.
     */
    public function test_tab_navigation_includes_javascript_function(): void
    {
        // Create tabs
        $this->table->openTab('Summary');
        $this->table->setData([['name' => 'Test']]);
        $this->table->setFields(['name:Name']);
        $this->table->closeTab();

        // Render
        $html = $this->table->renderWithTabs();

        // Assert JavaScript function
        $this->assertStringContainsString('function tableTabs_', $html);
        $this->assertStringContainsString('switchTab', $html);
        $this->assertStringContainsString('restoreTabFromUrl', $html);
        $this->assertStringContainsString('restoreTabFromSession', $html);
        $this->assertStringContainsString('updateUrl', $html);
        $this->assertStringContainsString('saveToSession', $html);
    }

    /**
     * Test that tab navigation includes keyboard navigation support.
     */
    public function test_tab_navigation_includes_keyboard_navigation(): void
    {
        // Create tabs
        $this->table->openTab('Summary');
        $this->table->setData([['name' => 'Test']]);
        $this->table->setFields(['name:Name']);
        $this->table->closeTab();

        // Render
        $html = $this->table->renderWithTabs();

        // Assert keyboard navigation functions
        $this->assertStringContainsString('setupKeyboardNavigation', $html);
        $this->assertStringContainsString('navigateToPreviousTab', $html);
        $this->assertStringContainsString('navigateToNextTab', $html);
        $this->assertStringContainsString('ArrowLeft', $html);
        $this->assertStringContainsString('ArrowRight', $html);
        $this->assertStringContainsString('Home', $html);
        $this->assertStringContainsString('End', $html);
    }

    /**
     * Test that tab navigation includes URL parameter sync.
     */
    public function test_tab_navigation_includes_url_parameter_sync(): void
    {
        // Create tabs
        $this->table->openTab('Summary');
        $this->table->setData([['name' => 'Test']]);
        $this->table->setFields(['name:Name']);
        $this->table->closeTab();

        // Render
        $html = $this->table->renderWithTabs();

        // Assert URL sync functions
        $this->assertStringContainsString('URLSearchParams', $html);
        $this->assertStringContainsString('window.location.search', $html);
        $this->assertStringContainsString('window.history.pushState', $html);
    }

    /**
     * Test that tab navigation includes session storage.
     */
    public function test_tab_navigation_includes_session_storage(): void
    {
        // Create tabs
        $this->table->openTab('Summary');
        $this->table->setData([['name' => 'Test']]);
        $this->table->setFields(['name:Name']);
        $this->table->closeTab();

        // Render
        $html = $this->table->renderWithTabs();

        // Assert session storage functions
        $this->assertStringContainsString('sessionStorage', $html);
        $this->assertStringContainsString('table_tab_', $html);
    }

    /**
     * Test that tab navigation includes custom content.
     */
    public function test_tab_navigation_includes_custom_content(): void
    {
        // Create tab with custom content
        $this->table->openTab('Summary');
        $this->table->addTabContent('<p>Last updated: 2026-03-02</p>');
        $this->table->setData([['name' => 'Test']]);
        $this->table->setFields(['name:Name']);
        $this->table->closeTab();

        // Render
        $html = $this->table->renderWithTabs();

        // Assert custom content rendering
        $this->assertStringContainsString('tab.content', $html);
        $this->assertStringContainsString('x-html="content"', $html);
    }

    /**
     * Test that tab navigation includes dark mode support.
     */
    public function test_tab_navigation_includes_dark_mode_support(): void
    {
        // Create tabs
        $this->table->openTab('Summary');
        $this->table->setData([['name' => 'Test']]);
        $this->table->setFields(['name:Name']);
        $this->table->closeTab();

        // Render
        $html = $this->table->renderWithTabs();

        // Assert dark mode classes
        $this->assertStringContainsString('dark:', $html);
        $this->assertStringContainsString('dark:border-gray-700', $html);
        $this->assertStringContainsString('dark:text-primary-400', $html);
    }

    /**
     * Test that tab navigation is responsive.
     */
    public function test_tab_navigation_is_responsive(): void
    {
        // Create tabs
        $this->table->openTab('Summary');
        $this->table->setData([['name' => 'Test']]);
        $this->table->setFields(['name:Name']);
        $this->table->closeTab();

        // Render
        $html = $this->table->renderWithTabs();

        // Assert responsive classes
        $this->assertStringContainsString('overflow-x-auto', $html);
        $this->assertStringContainsString('scrollbar-thin', $html);
    }

    /**
     * Test that each tab has unique ID.
     */
    public function test_each_tab_has_unique_id(): void
    {
        // Create tabs
        $this->table->openTab('Summary');
        $this->table->setData([['name' => 'Test 1']]);
        $this->table->setFields(['name:Name']);
        $this->table->closeTab();

        $this->table->openTab('Detail');
        $this->table->setData([['name' => 'Test 2']]);
        $this->table->setFields(['name:Name']);
        $this->table->closeTab();

        // Get tabs
        $tabs = $this->table->getTabManager()->getTabsArray();

        // Assert each tab has unique ID
        $ids = array_column($tabs, 'id');
        $this->assertCount(2, $ids);
        $this->assertCount(2, array_unique($ids));
    }

    /**
     * Test that table instance has unique ID.
     */
    public function test_table_instance_has_unique_id(): void
    {
        // Create tabs
        $this->table->openTab('Summary');
        $this->table->setData([['name' => 'Test']]);
        $this->table->setFields(['name:Name']);
        $this->table->closeTab();

        // Render
        $html = $this->table->renderWithTabs();

        // Assert table ID is present
        $this->assertStringContainsString('data-table-id=', $html);
        $this->assertStringContainsString('table_', $html);
    }

    /**
     * Test that tab navigation includes focus management.
     */
    public function test_tab_navigation_includes_focus_management(): void
    {
        // Create tabs
        $this->table->openTab('Summary');
        $this->table->setData([['name' => 'Test']]);
        $this->table->setFields(['name:Name']);
        $this->table->closeTab();

        // Render
        $html = $this->table->renderWithTabs();

        // Assert focus management
        $this->assertStringContainsString('updateFocus', $html);
        $this->assertStringContainsString('tabindex', $html);
        $this->assertStringContainsString('focus:outline-none', $html);
        $this->assertStringContainsString('focus:ring-2', $html);
    }

    /**
     * Test that tab navigation emits custom events.
     */
    public function test_tab_navigation_emits_custom_events(): void
    {
        // Create tabs
        $this->table->openTab('Summary');
        $this->table->setData([['name' => 'Test']]);
        $this->table->setFields(['name:Name']);
        $this->table->closeTab();

        // Render
        $html = $this->table->renderWithTabs();

        // Assert custom event dispatch
        $this->assertStringContainsString('$dispatch', $html);
        $this->assertStringContainsString('tab-changed', $html);
    }
}
