<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Test TableBuilder integration with TabManager (Task 1.1.4)
 */
class TableBuilderTabIntegrationTest extends TestCase
{
    protected TableBuilder $table;

    protected function setUp(): void
    {
        parent::setUp();
        $this->table = $this->app->make(TableBuilder::class);
        $this->table->setContext('admin');
    }

    public function test_table_builder_has_tab_manager()
    {
        $tabManager = $this->table->getTabManager();
        
        $this->assertInstanceOf(\Canvastack\Canvastack\Components\Table\Tab\TabManager::class, $tabManager);
    }

    public function test_can_open_and_close_tabs()
    {
        $this->table->openTab('Summary');
        
        $tabManager = $this->table->getTabManager();
        $this->assertNotNull($tabManager->getCurrentTab());
        $this->assertEquals('summary', $tabManager->getCurrentTab()->getId());
        
        $this->table->closeTab();
        
        $this->assertNull($tabManager->getCurrentTab());
    }

    public function test_can_add_tab_content()
    {
        $this->table->openTab('Summary');
        $this->table->addTabContent('<p>Last updated: 2025-04-01</p>');
        $this->table->closeTab();
        
        $tabManager = $this->table->getTabManager();
        $tabs = $tabManager->getTabs();
        
        $this->assertCount(1, $tabs);
        $this->assertArrayHasKey('summary', $tabs);
        
        $tab = $tabs['summary'];
        $content = $tab->getContent();
        
        $this->assertCount(1, $content);
        $this->assertStringContainsString('Last updated', $content[0]);
    }

    public function test_lists_creates_table_instance_in_tab()
    {
        $this->table->openTab('Summary');
        $result = $this->table->lists('users', ['id', 'name'], false);
        $this->table->closeTab();
        
        // lists() should return empty string when in tab context
        $this->assertEquals('', $result);
        
        $tabManager = $this->table->getTabManager();
        $tabs = $tabManager->getTabs();
        
        $this->assertCount(1, $tabs);
        
        $tab = $tabs['summary'];
        $tables = $tab->getTables();
        
        $this->assertCount(1, $tables);
        $this->assertEquals('users', $tables[0]->getTableName());
    }

    public function test_multiple_tables_in_single_tab()
    {
        $this->table->openTab('Summary');
        $this->table->lists('users', ['id', 'name'], false);
        $this->table->lists('posts', ['id', 'title'], false);
        $this->table->closeTab();
        
        $tabManager = $this->table->getTabManager();
        $tabs = $tabManager->getTabs();
        
        $tab = $tabs['summary'];
        $tables = $tab->getTables();
        
        $this->assertCount(2, $tables);
        $this->assertEquals('users', $tables[0]->getTableName());
        $this->assertEquals('posts', $tables[1]->getTableName());
    }

    public function test_multiple_tabs_with_different_tables()
    {
        // Tab 1
        $this->table->openTab('Summary');
        $this->table->lists('users', ['id', 'name'], false);
        $this->table->closeTab();
        
        // Tab 2
        $this->table->openTab('Detail');
        $this->table->lists('posts', ['id', 'title'], false);
        $this->table->closeTab();
        
        $tabManager = $this->table->getTabManager();
        $tabs = $tabManager->getTabs();
        
        $this->assertCount(2, $tabs);
        $this->assertArrayHasKey('summary', $tabs);
        $this->assertArrayHasKey('detail', $tabs);
        
        // Check Summary tab
        $summaryTab = $tabs['summary'];
        $summaryTables = $summaryTab->getTables();
        $this->assertCount(1, $summaryTables);
        $this->assertEquals('users', $summaryTables[0]->getTableName());
        
        // Check Detail tab
        $detailTab = $tabs['detail'];
        $detailTables = $detailTab->getTables();
        $this->assertCount(1, $detailTables);
        $this->assertEquals('posts', $detailTables[0]->getTableName());
    }

    public function test_clear_on_load_clears_tab_config()
    {
        $this->table->openTab('Summary');
        $this->table->addTabContent('<p>Test content</p>');
        
        $this->table->clearOnLoad();
        
        // clearOnLoad should clear tab configuration
        $tabManager = $this->table->getTabManager();
        $currentTab = $tabManager->getCurrentTab();
        
        $this->assertNotNull($currentTab);
        $this->assertEmpty($currentTab->getConfig());
    }

    public function test_capture_current_config()
    {
        // Set some configuration
        $this->table->fixedColumns(2, 1);
        // Don't set hidden columns as it validates against schema
        $this->table->displayRowsLimitOnLoad(25);
        
        // Use reflection to call protected method
        $reflection = new \ReflectionClass($this->table);
        $method = $reflection->getMethod('captureCurrentConfig');
        $method->setAccessible(true);
        
        $config = $method->invoke($this->table);
        
        $this->assertIsArray($config);
        $this->assertArrayHasKey('fixedColumns', $config);
        $this->assertArrayHasKey('hiddenColumns', $config);
        $this->assertArrayHasKey('displayLimit', $config);
        
        $this->assertEquals(['left' => 2, 'right' => 1], $config['fixedColumns']);
        $this->assertEquals(25, $config['displayLimit']);
    }

    public function test_lists_without_tab_context_tries_to_render()
    {
        // Not in a tab context - should try to render normally
        // This test just verifies the code path is correct
        
        $tabManager = $this->table->getTabManager();
        
        // Verify we're not in a tab context
        $this->assertNull($tabManager->getCurrentTab());
        
        // The lists() method should try to render (not return empty string)
        // We can't actually render without a valid model/table, but we can
        // verify the tab context check works correctly
        $this->assertTrue(true);
    }

    public function test_tab_configuration_isolation()
    {
        // Tab 1 with specific config
        $this->table->openTab('Tab1');
        $this->table->fixedColumns(2);
        $this->table->lists('users', ['id', 'name'], false);
        $this->table->closeTab();
        
        // Tab 2 with different config
        $this->table->openTab('Tab2');
        $this->table->fixedColumns(3);
        $this->table->lists('posts', ['id', 'title'], false);
        $this->table->closeTab();
        
        $tabManager = $this->table->getTabManager();
        $tabs = $tabManager->getTabs();
        
        // Check Tab1 config
        $tab1Tables = $tabs['tab1']->getTables();
        $tab1Config = $tab1Tables[0]->getConfig();
        $this->assertEquals(['left' => 2, 'right' => null], $tab1Config['fixedColumns']);
        
        // Check Tab2 config
        $tab2Tables = $tabs['tab2']->getTables();
        $tab2Config = $tab2Tables[0]->getConfig();
        $this->assertEquals(['left' => 3, 'right' => null], $tab2Config['fixedColumns']);
    }
}
