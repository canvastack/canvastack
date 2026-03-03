<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table\Tab;

use Canvastack\Canvastack\Components\Table\Tab\Tab;
use Canvastack\Canvastack\Components\Table\Tab\TabManager;
use Canvastack\Canvastack\Components\Table\Tab\TableInstance;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Test for TabManager class.
 */
class TabManagerTest extends TestCase
{
    protected TabManager $tabManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tabManager = new TabManager();
    }

    /**
     * Test that TabManager can be instantiated.
     */
    public function test_tab_manager_can_be_instantiated(): void
    {
        $this->assertInstanceOf(TabManager::class, $this->tabManager);
    }

    /**
     * Test that openTab creates a new tab.
     */
    public function test_open_tab_creates_new_tab(): void
    {
        $this->tabManager->openTab('Summary');

        $this->assertTrue($this->tabManager->hasTabs());
        $this->assertEquals(1, $this->tabManager->count());
    }

    /**
     * Test that openTab sets the first tab as active.
     */
    public function test_open_tab_sets_first_tab_as_active(): void
    {
        $this->tabManager->openTab('Summary');

        $this->assertNotNull($this->tabManager->getActiveTab());
        $this->assertEquals('summary', $this->tabManager->getActiveTab());
    }

    /**
     * Test that multiple tabs can be created.
     */
    public function test_multiple_tabs_can_be_created(): void
    {
        $this->tabManager->openTab('Summary');
        $this->tabManager->closeTab();

        $this->tabManager->openTab('Detail');
        $this->tabManager->closeTab();

        $this->tabManager->openTab('Monthly');
        $this->tabManager->closeTab();

        $this->assertEquals(3, $this->tabManager->count());
    }

    /**
     * Test that closeTab clears the current tab reference.
     */
    public function test_close_tab_clears_current_tab(): void
    {
        $this->tabManager->openTab('Summary');
        $this->assertNotNull($this->tabManager->getCurrentTab());

        $this->tabManager->closeTab();
        $this->assertNull($this->tabManager->getCurrentTab());
    }

    /**
     * Test that addContent throws exception when no tab is open.
     */
    public function test_add_content_throws_exception_when_no_tab_open(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot add content: No tab is currently open');

        $this->tabManager->addContent('<p>Test content</p>');
    }

    /**
     * Test that addContent works when tab is open.
     */
    public function test_add_content_works_when_tab_is_open(): void
    {
        $this->tabManager->openTab('Summary');
        $this->tabManager->addContent('<p>Test content</p>');
        $this->tabManager->closeTab();

        $tabs = $this->tabManager->getTabs();
        $tab = $tabs['summary'];

        $this->assertInstanceOf(Tab::class, $tab);
    }

    /**
     * Test that addTableToCurrentTab throws exception when no tab is open.
     */
    public function test_add_table_throws_exception_when_no_tab_open(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot add table: No tab is currently open');

        $table = new TableInstance('users', ['name', 'email'], []);
        $this->tabManager->addTableToCurrentTab($table);
    }

    /**
     * Test that getTabs returns all tabs.
     */
    public function test_get_tabs_returns_all_tabs(): void
    {
        $this->tabManager->openTab('Summary');
        $this->tabManager->closeTab();

        $this->tabManager->openTab('Detail');
        $this->tabManager->closeTab();

        $tabs = $this->tabManager->getTabs();

        $this->assertCount(2, $tabs);
        $this->assertArrayHasKey('summary', $tabs);
        $this->assertArrayHasKey('detail', $tabs);
    }

    /**
     * Test that getTabsArray returns tabs as array.
     */
    public function test_get_tabs_array_returns_array(): void
    {
        $this->tabManager->openTab('Summary');
        $this->tabManager->closeTab();

        $tabsArray = $this->tabManager->getTabsArray();

        $this->assertIsArray($tabsArray);
        $this->assertCount(1, $tabsArray);
        $this->assertArrayHasKey('name', $tabsArray[0]);
        $this->assertArrayHasKey('id', $tabsArray[0]);
    }

    /**
     * Test that setActiveTab changes the active tab.
     */
    public function test_set_active_tab_changes_active_tab(): void
    {
        $this->tabManager->openTab('Summary');
        $this->tabManager->closeTab();

        $this->tabManager->openTab('Detail');
        $this->tabManager->closeTab();

        $this->assertEquals('summary', $this->tabManager->getActiveTab());

        $this->tabManager->setActiveTab('detail');
        $this->assertEquals('detail', $this->tabManager->getActiveTab());
    }

    /**
     * Test that setActiveTab throws exception for invalid tab ID.
     */
    public function test_set_active_tab_throws_exception_for_invalid_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Tab with ID 'invalid' does not exist");

        $this->tabManager->setActiveTab('invalid');
    }

    /**
     * Test that clearConfig clears tab configuration.
     */
    public function test_clear_config_clears_tab_configuration(): void
    {
        $this->tabManager->openTab('Summary');
        $this->tabManager->setConfig(['key' => 'value']);
        $this->tabManager->clearConfig();
        $this->tabManager->closeTab();

        // Config should be empty after clear
        $tabs = $this->tabManager->getTabs();
        $tab = $tabs['summary'];
        $config = $tab->getConfig();

        $this->assertEmpty($config);
    }

    /**
     * Test that setConfig sets tab configuration.
     */
    public function test_set_config_sets_tab_configuration(): void
    {
        $this->tabManager->openTab('Summary');
        $this->tabManager->setConfig(['key' => 'value']);
        $this->tabManager->closeTab();

        $tabs = $this->tabManager->getTabs();
        $tab = $tabs['summary'];
        $config = $tab->getConfig();

        $this->assertArrayHasKey('key', $config);
        $this->assertEquals('value', $config['key']);
    }

    /**
     * Test that hasTabs returns correct boolean.
     */
    public function test_has_tabs_returns_correct_boolean(): void
    {
        $this->assertFalse($this->tabManager->hasTabs());

        $this->tabManager->openTab('Summary');
        $this->assertTrue($this->tabManager->hasTabs());
    }

    /**
     * Test that count returns correct number of tabs.
     */
    public function test_count_returns_correct_number(): void
    {
        $this->assertEquals(0, $this->tabManager->count());

        $this->tabManager->openTab('Summary');
        $this->tabManager->closeTab();
        $this->assertEquals(1, $this->tabManager->count());

        $this->tabManager->openTab('Detail');
        $this->tabManager->closeTab();
        $this->assertEquals(2, $this->tabManager->count());
    }

    /**
     * Test that clearAll removes all tabs.
     */
    public function test_clear_all_removes_all_tabs(): void
    {
        $this->tabManager->openTab('Summary');
        $this->tabManager->closeTab();

        $this->tabManager->openTab('Detail');
        $this->tabManager->closeTab();

        $this->assertEquals(2, $this->tabManager->count());

        $this->tabManager->clearAll();

        $this->assertEquals(0, $this->tabManager->count());
        $this->assertNull($this->tabManager->getActiveTab());
        $this->assertNull($this->tabManager->getCurrentTab());
    }

    /**
     * Test that getTab returns specific tab.
     */
    public function test_get_tab_returns_specific_tab(): void
    {
        $this->tabManager->openTab('Summary');
        $this->tabManager->closeTab();

        $tab = $this->tabManager->getTab('summary');

        $this->assertInstanceOf(Tab::class, $tab);
        $this->assertEquals('Summary', $tab->getName());
    }

    /**
     * Test that getTab returns null for non-existent tab.
     */
    public function test_get_tab_returns_null_for_non_existent_tab(): void
    {
        $tab = $this->tabManager->getTab('non-existent');

        $this->assertNull($tab);
    }

    /**
     * Test that hasTab returns correct boolean.
     */
    public function test_has_tab_returns_correct_boolean(): void
    {
        $this->assertFalse($this->tabManager->hasTab('summary'));

        $this->tabManager->openTab('Summary');
        $this->assertTrue($this->tabManager->hasTab('summary'));
    }

    /**
     * Test that removeTab removes a tab.
     */
    public function test_remove_tab_removes_tab(): void
    {
        $this->tabManager->openTab('Summary');
        $this->tabManager->closeTab();

        $this->tabManager->openTab('Detail');
        $this->tabManager->closeTab();

        $this->assertEquals(2, $this->tabManager->count());

        $this->tabManager->removeTab('summary');

        $this->assertEquals(1, $this->tabManager->count());
        $this->assertFalse($this->tabManager->hasTab('summary'));
    }

    /**
     * Test that removeTab updates active tab when removing active tab.
     */
    public function test_remove_tab_updates_active_tab_when_removing_active(): void
    {
        $this->tabManager->openTab('Summary');
        $this->tabManager->closeTab();

        $this->tabManager->openTab('Detail');
        $this->tabManager->closeTab();

        $this->assertEquals('summary', $this->tabManager->getActiveTab());

        $this->tabManager->removeTab('summary');

        $this->assertEquals('detail', $this->tabManager->getActiveTab());
    }

    /**
     * Test that tab ID generation works correctly.
     */
    public function test_tab_id_generation_works_correctly(): void
    {
        $this->tabManager->openTab('Summary Report');
        $this->tabManager->closeTab();

        $this->assertTrue($this->tabManager->hasTab('summary-report'));

        $this->tabManager->openTab('Detail (Monthly)');
        $this->tabManager->closeTab();

        $this->assertTrue($this->tabManager->hasTab('detail-monthly'));
    }

    /**
     * Test that opening same tab twice reuses existing tab.
     */
    public function test_opening_same_tab_twice_reuses_existing_tab(): void
    {
        $this->tabManager->openTab('Summary');
        $this->tabManager->closeTab();

        $this->assertEquals(1, $this->tabManager->count());

        $this->tabManager->openTab('Summary');
        $this->tabManager->closeTab();

        $this->assertEquals(1, $this->tabManager->count());
    }

    /**
     * Test that configuration isolation works between tabs.
     */
    public function test_configuration_isolation_between_tabs(): void
    {
        $this->tabManager->openTab('Summary');
        $this->tabManager->setConfig(['key1' => 'value1']);
        $this->tabManager->closeTab();

        $this->tabManager->openTab('Detail');
        $this->tabManager->setConfig(['key2' => 'value2']);
        $this->tabManager->closeTab();

        $tabs = $this->tabManager->getTabs();
        $summaryConfig = $tabs['summary']->getConfig();
        $detailConfig = $tabs['detail']->getConfig();

        $this->assertArrayHasKey('key1', $summaryConfig);
        $this->assertArrayNotHasKey('key2', $summaryConfig);

        $this->assertArrayHasKey('key2', $detailConfig);
        $this->assertArrayNotHasKey('key1', $detailConfig);
    }
}
