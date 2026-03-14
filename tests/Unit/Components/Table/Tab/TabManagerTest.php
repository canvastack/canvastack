<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table\Tab;

use Canvastack\Canvastack\Components\Table\Tab\Tab;
use Canvastack\Canvastack\Components\Table\Tab\TableInstance;
use Canvastack\Canvastack\Components\Table\Tab\TabManager;
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

    /**
     * Test that lazy loading is enabled by default.
     */
    public function test_lazy_loading_is_enabled_by_default(): void
    {
        $this->assertTrue($this->tabManager->isLazyLoadingEnabled());
    }

    /**
     * Test that lazy loading reads from configuration.
     * Requirement 6.10: Configurable lazy loading.
     * Requirement 9.4: Configuration management for lazy loading.
     */
    public function test_lazy_loading_reads_from_configuration(): void
    {
        // Set config value to false
        config(['canvastack.table.tabs.lazy_load_enabled' => false]);
        
        // Create new instance to read from config
        $tabManager = new TabManager();
        
        $this->assertFalse($tabManager->isLazyLoadingEnabled());
        
        // Set config value to true
        config(['canvastack.table.tabs.lazy_load_enabled' => true]);
        
        // Create new instance to read from config
        $tabManager = new TabManager();
        
        $this->assertTrue($tabManager->isLazyLoadingEnabled());
    }

    /**
     * Test that lazy loading defaults to true when config is not set.
     * Requirement 6.10: Default lazy loading to true.
     */
    public function test_lazy_loading_defaults_to_true_when_config_not_set(): void
    {
        // Remove the config key entirely (simulating it not being set)
        $originalConfig = config('canvastack.table.tabs.lazy_load_enabled');
        
        // Temporarily unset the config
        config(['canvastack.table.tabs' => []]);
        
        // Create new instance
        $tabManager = new TabManager();
        
        // Should default to true
        $this->assertTrue($tabManager->isLazyLoadingEnabled());
        
        // Restore original config
        config(['canvastack.table.tabs.lazy_load_enabled' => $originalConfig]);
    }

    /**
     * Test that lazy loading can be disabled.
     */
    public function test_lazy_loading_can_be_disabled(): void
    {
        $this->tabManager->setLazyLoading(false);

        $this->assertFalse($this->tabManager->isLazyLoadingEnabled());
    }

    /**
     * Test that lazy loading can be enabled after being disabled.
     */
    public function test_lazy_loading_can_be_enabled_after_being_disabled(): void
    {
        $this->tabManager->setLazyLoading(false);
        $this->assertFalse($this->tabManager->isLazyLoadingEnabled());

        $this->tabManager->setLazyLoading(true);
        $this->assertTrue($this->tabManager->isLazyLoadingEnabled());
    }

    /**
     * Test that addTableConfig throws exception when no tab is open.
     * Requirement 4.4: addTableConfig must validate tab is open.
     */
    public function test_add_table_config_throws_exception_when_no_tab_open(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot add table config: No tab is currently open');

        $this->tabManager->addTableConfig(['field' => 'value']);
    }

    /**
     * Test that addTableConfig works when tab is open.
     * Requirement 4.4: addTableConfig must store table configuration.
     */
    public function test_add_table_config_works_when_tab_is_open(): void
    {
        $config = [
            'id' => 'table_1',
            'fields' => ['name', 'email'],
            'model' => 'User',
        ];

        $this->tabManager->openTab('Summary');
        $this->tabManager->addTableConfig($config);
        $this->tabManager->closeTab();

        $tabs = $this->tabManager->getTabs();
        $tab = $tabs['summary'];

        $this->assertInstanceOf(Tab::class, $tab);
        
        // Verify config was stored
        $tabConfig = $tab->getConfig();
        $this->assertArrayHasKey('tables', $tabConfig);
        $this->assertCount(1, $tabConfig['tables']);
        $this->assertEquals($config, $tabConfig['tables'][0]);
    }

    /**
     * Test that multiple table configs can be added to same tab.
     * Requirement 4.10: Support multiple tables per tab.
     */
    public function test_multiple_table_configs_can_be_added_to_same_tab(): void
    {
        $config1 = ['id' => 'table_1', 'model' => 'User'];
        $config2 = ['id' => 'table_2', 'model' => 'Post'];
        $config3 = ['id' => 'table_3', 'model' => 'Comment'];

        $this->tabManager->openTab('Summary');
        $this->tabManager->addTableConfig($config1);
        $this->tabManager->addTableConfig($config2);
        $this->tabManager->addTableConfig($config3);
        $this->tabManager->closeTab();

        $tabs = $this->tabManager->getTabs();
        $tab = $tabs['summary'];
        $tabConfig = $tab->getConfig();

        $this->assertArrayHasKey('tables', $tabConfig);
        $this->assertCount(3, $tabConfig['tables']);
        $this->assertEquals($config1, $tabConfig['tables'][0]);
        $this->assertEquals($config2, $tabConfig['tables'][1]);
        $this->assertEquals($config3, $tabConfig['tables'][2]);
    }

    /**
     * Test that table configs are isolated between tabs.
     * Requirement 4.7: Maintain internal array of tab configurations.
     */
    public function test_table_configs_are_isolated_between_tabs(): void
    {
        $config1 = ['id' => 'table_1', 'model' => 'User'];
        $config2 = ['id' => 'table_2', 'model' => 'Post'];

        // Add config to first tab
        $this->tabManager->openTab('Tab 1');
        $this->tabManager->addTableConfig($config1);
        $this->tabManager->closeTab();

        // Add config to second tab
        $this->tabManager->openTab('Tab 2');
        $this->tabManager->addTableConfig($config2);
        $this->tabManager->closeTab();

        // Verify isolation
        $tabs = $this->tabManager->getTabs();
        
        $tab1Config = $tabs['tab-1']->getConfig();
        $this->assertArrayHasKey('tables', $tab1Config);
        $this->assertCount(1, $tab1Config['tables']);
        $this->assertEquals($config1, $tab1Config['tables'][0]);

        $tab2Config = $tabs['tab-2']->getConfig();
        $this->assertArrayHasKey('tables', $tab2Config);
        $this->assertCount(1, $tab2Config['tables']);
        $this->assertEquals($config2, $tab2Config['tables'][0]);
    }

    /**
     * Test that addTableConfig can be mixed with addContent.
     * Requirement 4.6: Support custom HTML content per tab.
     */
    public function test_add_table_config_can_be_mixed_with_add_content(): void
    {
        $config = ['id' => 'table_1', 'model' => 'User'];
        $content = '<div class="custom-content">Custom HTML</div>';

        $this->tabManager->openTab('Summary');
        $this->tabManager->addContent($content);
        $this->tabManager->addTableConfig($config);
        $this->tabManager->closeTab();

        $tabs = $this->tabManager->getTabs();
        $tab = $tabs['summary'];

        // Verify both content and config were stored
        $this->assertInstanceOf(Tab::class, $tab);
        
        $tabConfig = $tab->getConfig();
        $this->assertArrayHasKey('tables', $tabConfig);
        $this->assertCount(1, $tabConfig['tables']);
        $this->assertEquals($config, $tabConfig['tables'][0]);
    }

    /**
     * Test that reopening a tab allows adding more configs.
     * Requirement 4.1: openTab should allow reopening existing tabs.
     */
    public function test_reopening_tab_allows_adding_more_configs(): void
    {
        $config1 = ['id' => 'table_1', 'model' => 'User'];
        $config2 = ['id' => 'table_2', 'model' => 'Post'];

        // First time opening tab
        $this->tabManager->openTab('Summary');
        $this->tabManager->addTableConfig($config1);
        $this->tabManager->closeTab();

        // Reopen same tab
        $this->tabManager->openTab('Summary');
        $this->tabManager->addTableConfig($config2);
        $this->tabManager->closeTab();

        $tabs = $this->tabManager->getTabs();
        $tab = $tabs['summary'];
        $tabConfig = $tab->getConfig();

        // Both configs should be present
        $this->assertArrayHasKey('tables', $tabConfig);
        $this->assertCount(2, $tabConfig['tables']);
        $this->assertEquals($config1, $tabConfig['tables'][0]);
        $this->assertEquals($config2, $tabConfig['tables'][1]);
    }

    /**
     * Test that lazy load URL can be set for a tab.
     * Requirement 6.4: Generate unique AJAX endpoint per tab and store in tab configuration.
     */
    public function test_enable_lazy_load_for_tab_stores_url(): void
    {
        $this->tabManager->openTab('Summary');
        $this->tabManager->closeTab();

        $url = '/canvastack/table/tab/canvastable_abc123/1';
        $this->tabManager->enableLazyLoadForTab('summary', $url);

        $tab = $this->tabManager->getTab('summary');
        $this->assertTrue($tab->isLazyLoaded());
        $this->assertEquals($url, $tab->getLazyLoadUrl());
    }

    /**
     * Test that lazy load URL is included in tab array.
     * Requirement 6.4: Store URL in tab configuration.
     */
    public function test_tab_array_includes_lazy_load_url(): void
    {
        $this->tabManager->openTab('Summary');
        $this->tabManager->closeTab();

        $url = '/canvastack/table/tab/canvastable_abc123/1';
        $this->tabManager->enableLazyLoadForTab('summary', $url);

        $tabsArray = $this->tabManager->getTabsArray();
        $this->assertCount(1, $tabsArray);
        $this->assertArrayHasKey('url', $tabsArray[0]);
        $this->assertEquals($url, $tabsArray[0]['url']);
        $this->assertTrue($tabsArray[0]['lazy_load']);
    }

    /**
     * Test that enable lazy load throws exception for non-existent tab.
     * Requirement 6.4: Validate tab exists before setting URL.
     */
    public function test_enable_lazy_load_throws_exception_for_invalid_tab(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Tab with ID 'non-existent' does not exist");

        $this->tabManager->enableLazyLoadForTab('non-existent', '/some/url');
    }

    /**
     * Test that multiple tabs can have different lazy load URLs.
     * Requirement 6.4: Generate unique AJAX endpoint per tab.
     */
    public function test_multiple_tabs_can_have_different_urls(): void
    {
        $this->tabManager->openTab('Summary');
        $this->tabManager->closeTab();

        $this->tabManager->openTab('Details');
        $this->tabManager->closeTab();

        $url1 = '/canvastack/table/tab/canvastable_abc123/0';
        $url2 = '/canvastack/table/tab/canvastable_abc123/1';

        $this->tabManager->enableLazyLoadForTab('summary', $url1);
        $this->tabManager->enableLazyLoadForTab('details', $url2);

        $tab1 = $this->tabManager->getTab('summary');
        $tab2 = $this->tabManager->getTab('details');

        $this->assertEquals($url1, $tab1->getLazyLoadUrl());
        $this->assertEquals($url2, $tab2->getLazyLoadUrl());
        $this->assertNotEquals($url1, $url2);
    }

    /**
     * Test that hasLazyLoadedTabs returns false when no tabs have lazy loading.
     * Requirement 32.7: Check if any tabs have lazy loading enabled.
     */
    public function test_has_lazy_loaded_tabs_returns_false_when_no_lazy_tabs(): void
    {
        $this->tabManager->openTab('Summary');
        $this->tabManager->closeTab();

        $this->assertFalse($this->tabManager->hasLazyLoadedTabs());
    }

    /**
     * Test that hasLazyLoadedTabs returns true when at least one tab has lazy loading.
     * Requirement 32.7: Check if any tabs have lazy loading enabled.
     */
    public function test_has_lazy_loaded_tabs_returns_true_when_lazy_tabs_exist(): void
    {
        $this->tabManager->openTab('Summary');
        $this->tabManager->closeTab();

        $this->tabManager->openTab('Details');
        $this->tabManager->closeTab();

        $url = '/canvastack/table/tab/canvastable_abc123/1';
        $this->tabManager->enableLazyLoadForTab('details', $url);

        $this->assertTrue($this->tabManager->hasLazyLoadedTabs());
    }

    /**
     * Test that setActiveTabFromUrl returns false for null tab ID.
     * Requirement 32.4: Set active tab from URL parameter.
     */
    public function test_set_active_tab_from_url_returns_false_for_null(): void
    {
        $this->tabManager->openTab('Summary');
        $this->tabManager->closeTab();

        $result = $this->tabManager->setActiveTabFromUrl(null);

        $this->assertFalse($result);
        $this->assertEquals('summary', $this->tabManager->getActiveTab());
    }

    /**
     * Test that setActiveTabFromUrl returns false for non-existent tab.
     * Requirement 32.4: Set active tab from URL parameter.
     */
    public function test_set_active_tab_from_url_returns_false_for_non_existent_tab(): void
    {
        $this->tabManager->openTab('Summary');
        $this->tabManager->closeTab();

        $result = $this->tabManager->setActiveTabFromUrl('non-existent');

        $this->assertFalse($result);
        $this->assertEquals('summary', $this->tabManager->getActiveTab());
    }

    /**
     * Test that setActiveTabFromUrl returns true and sets active tab.
     * Requirement 32.4: Set active tab from URL parameter.
     */
    public function test_set_active_tab_from_url_returns_true_and_sets_active(): void
    {
        $this->tabManager->openTab('Summary');
        $this->tabManager->closeTab();

        $this->tabManager->openTab('Details');
        $this->tabManager->closeTab();

        $result = $this->tabManager->setActiveTabFromUrl('details');

        $this->assertTrue($result);
        $this->assertEquals('details', $this->tabManager->getActiveTab());
    }

    /**
     * Test that getActiveTabForUrl returns active tab ID.
     * Requirement 32.4: Get active tab for URL persistence.
     */
    public function test_get_active_tab_for_url_returns_active_tab_id(): void
    {
        $this->tabManager->openTab('Summary');
        $this->tabManager->closeTab();

        $this->tabManager->openTab('Details');
        $this->tabManager->closeTab();

        $this->tabManager->setActiveTab('details');

        $this->assertEquals('details', $this->tabManager->getActiveTabForUrl());
    }

    /**
     * Test that getActiveTabForUrl returns null when no tabs exist.
     * Requirement 32.4: Get active tab for URL persistence.
     */
    public function test_get_active_tab_for_url_returns_null_when_no_tabs(): void
    {
        $this->assertNull($this->tabManager->getActiveTabForUrl());
    }

    /**
     * Test that validateTabs throws exception when no tabs exist.
     * Requirement 15.6: Validate method call order.
     */
    public function test_validate_tabs_throws_exception_when_no_tabs(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot render tabs: No tabs have been defined');

        $this->tabManager->validateTabs();
    }

    /**
     * Test that validateTabs throws exception when tab is empty.
     * Requirement 15.6: Validate method call order.
     */
    public function test_validate_tabs_throws_exception_when_tab_is_empty(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/Cannot render tabs: Tab ".*" is empty/');

        $this->tabManager->openTab('Empty Tab');
        $this->tabManager->closeTab();

        $this->tabManager->validateTabs();
    }

    /**
     * Test that validateTabs passes when all tabs have content.
     * Requirement 15.6: Validate method call order.
     */
    public function test_validate_tabs_passes_when_all_tabs_have_content(): void
    {
        $this->tabManager->openTab('Summary');
        $this->tabManager->addContent('<p>Content</p>');
        $this->tabManager->closeTab();

        $this->tabManager->openTab('Details');
        $table = new TableInstance('users', ['name', 'email'], []);
        $this->tabManager->addTableToCurrentTab($table);
        $this->tabManager->closeTab();

        // Should not throw exception
        $this->tabManager->validateTabs();

        $this->assertTrue(true); // If we get here, validation passed
    }

    /**
     * Test that closeTab throws exception when no tab is open.
     * Requirement 15.6: Validate method call order.
     */
    public function test_close_tab_throws_exception_when_no_tab_open(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot close tab: No tab is currently open');

        $this->tabManager->closeTab();
    }

    /**
     * Test that addChart throws exception when no tab is open.
     * Requirement 4.6: Support custom content per tab.
     */
    public function test_add_chart_throws_exception_when_no_tab_open(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot add chart: No tab is currently open');

        $chart = $this->createMock(\Canvastack\Canvastack\Components\Chart\ChartBuilder::class);
        $this->tabManager->addChart($chart);
    }

    /**
     * Test that getKeyboardNavigationScript returns JavaScript code.
     * Requirement 32.5: Keyboard navigation support.
     */
    public function test_get_keyboard_navigation_script_returns_javascript(): void
    {
        $script = $this->tabManager->getKeyboardNavigationScript('container-id');

        $this->assertIsString($script);
        $this->assertStringContainsString('container-id', $script);
        $this->assertStringContainsString('ArrowLeft', $script);
        $this->assertStringContainsString('ArrowRight', $script);
        $this->assertStringContainsString('Home', $script);
        $this->assertStringContainsString('End', $script);
    }

    /**
     * Test that getAlpineKeyboardData returns array with handleKeydown.
     * Requirement 32.5: Keyboard navigation support.
     */
    public function test_get_alpine_keyboard_data_returns_array(): void
    {
        $data = $this->tabManager->getAlpineKeyboardData();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('handleKeydown', $data);
        $this->assertIsString($data['handleKeydown']);
    }

    /**
     * Test that getLazyLoadScript returns JavaScript code.
     * Requirement 32.7: Lazy loading support.
     */
    public function test_get_lazy_load_script_returns_javascript(): void
    {
        $script = $this->tabManager->getLazyLoadScript('container-id');

        $this->assertIsString($script);
        $this->assertStringContainsString('container-id', $script);
        $this->assertStringContainsString('data-lazy-load', $script);
        $this->assertStringContainsString('fetch', $script);
    }

    /**
     * Test that getAlpineLazyLoadData returns array with loadTab function.
     * Requirement 32.7: Lazy loading support.
     */
    public function test_get_alpine_lazy_load_data_returns_array(): void
    {
        $data = $this->tabManager->getAlpineLazyLoadData();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('loadingTabs', $data);
        $this->assertArrayHasKey('loadTab', $data);
        $this->assertIsString($data['loadTab']);
    }
}


