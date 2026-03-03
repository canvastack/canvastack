<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table\Tab;

use Canvastack\Canvastack\Components\Table\Tab\Tab;
use Canvastack\Canvastack\Components\Table\Tab\TableInstance;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Test for Tab class.
 * 
 * Tests table instance management, content management, configuration management,
 * and rendering functionality.
 */
class TabTest extends TestCase
{
    /**
     * Test that tab can be created with name and ID.
     */
    public function test_tab_can_be_created_with_name_and_id(): void
    {
        $tab = new Tab('Summary', 'summary-tab');
        
        $this->assertEquals('Summary', $tab->getName());
        $this->assertEquals('summary-tab', $tab->getId());
    }

    /**
     * Test that table instance can be added to tab.
     */
    public function test_table_instance_can_be_added(): void
    {
        $tab = new Tab('Summary', 'summary-tab');
        
        $tableInstance = new TableInstance(
            'users',
            ['name:Name', 'email:Email'],
            ['sortable' => true]
        );
        
        $tab->addTable($tableInstance);
        
        $tables = $tab->getTables();
        $this->assertCount(1, $tables);
        $this->assertInstanceOf(TableInstance::class, $tables[0]);
    }

    /**
     * Test that multiple table instances can be added.
     */
    public function test_multiple_table_instances_can_be_added(): void
    {
        $tab = new Tab('Summary', 'summary-tab');
        
        $table1 = new TableInstance('users', ['name:Name'], []);
        $table2 = new TableInstance('posts', ['title:Title'], []);
        $table3 = new TableInstance('comments', ['body:Body'], []);
        
        $tab->addTable($table1);
        $tab->addTable($table2);
        $tab->addTable($table3);
        
        $tables = $tab->getTables();
        $this->assertCount(3, $tables);
    }

    /**
     * Test that HTML content can be added to tab.
     */
    public function test_html_content_can_be_added(): void
    {
        $tab = new Tab('Summary', 'summary-tab');
        
        $html = '<p>Last updated: 2026-03-02</p>';
        $tab->addContent($html);
        
        $content = $tab->getContent();
        $this->assertCount(1, $content);
        $this->assertEquals($html, $content[0]);
    }

    /**
     * Test that multiple content blocks can be added.
     */
    public function test_multiple_content_blocks_can_be_added(): void
    {
        $tab = new Tab('Summary', 'summary-tab');
        
        $content1 = '<p>Last updated: 2026-03-02</p>';
        $content2 = '<div class="alert">Important notice</div>';
        $content3 = '<p>Data source: Database</p>';
        
        $tab->addContent($content1);
        $tab->addContent($content2);
        $tab->addContent($content3);
        
        $content = $tab->getContent();
        $this->assertCount(3, $content);
        $this->assertEquals($content1, $content[0]);
        $this->assertEquals($content2, $content[1]);
        $this->assertEquals($content3, $content[2]);
    }

    /**
     * Test that tab configuration can be set.
     */
    public function test_tab_configuration_can_be_set(): void
    {
        $tab = new Tab('Summary', 'summary-tab');
        
        $config = [
            'sortable' => true,
            'searchable' => true,
            'filters' => ['status', 'category'],
        ];
        
        $tab->setConfig($config);
        
        $this->assertEquals($config, $tab->getConfig());
    }

    /**
     * Test that tab configuration can be updated.
     */
    public function test_tab_configuration_can_be_updated(): void
    {
        $tab = new Tab('Summary', 'summary-tab');
        
        $config1 = ['sortable' => true];
        $tab->setConfig($config1);
        $this->assertEquals($config1, $tab->getConfig());
        
        $config2 = ['sortable' => false, 'searchable' => true];
        $tab->setConfig($config2);
        $this->assertEquals($config2, $tab->getConfig());
    }

    /**
     * Test that tab renders content blocks.
     */
    public function test_tab_renders_content_blocks(): void
    {
        $tab = new Tab('Summary', 'summary-tab');
        
        $content1 = '<p>Content 1</p>';
        $content2 = '<div>Content 2</div>';
        
        $tab->addContent($content1);
        $tab->addContent($content2);
        
        $rendered = $tab->render();
        
        $this->assertStringContainsString($content1, $rendered);
        $this->assertStringContainsString($content2, $rendered);
    }

    /**
     * Test that tab renders table instances.
     */
    public function test_tab_renders_table_instances(): void
    {
        $tab = new Tab('Summary', 'summary-tab');
        
        // Create mock table instance
        $tableInstance = $this->createMock(TableInstance::class);
        $tableInstance->method('render')
            ->willReturn('<table>Table HTML</table>');
        
        $tab->addTable($tableInstance);
        
        $rendered = $tab->render();
        
        $this->assertStringContainsString('<table>Table HTML</table>', $rendered);
    }

    /**
     * Test that tab renders content before tables.
     */
    public function test_tab_renders_content_before_tables(): void
    {
        $tab = new Tab('Summary', 'summary-tab');
        
        $content = '<p>Header content</p>';
        $tab->addContent($content);
        
        $tableInstance = $this->createMock(TableInstance::class);
        $tableInstance->method('render')
            ->willReturn('<table>Table</table>');
        
        $tab->addTable($tableInstance);
        
        $rendered = $tab->render();
        
        // Content should appear before table
        $contentPos = strpos($rendered, '<p>Header content</p>');
        $tablePos = strpos($rendered, '<table>Table</table>');
        
        $this->assertNotFalse($contentPos);
        $this->assertNotFalse($tablePos);
        $this->assertLessThan($tablePos, $contentPos);
    }

    /**
     * Test that tab renders multiple tables in order.
     */
    public function test_tab_renders_multiple_tables_in_order(): void
    {
        $tab = new Tab('Summary', 'summary-tab');
        
        $table1 = $this->createMock(TableInstance::class);
        $table1->method('render')->willReturn('<table id="table1">Table 1</table>');
        
        $table2 = $this->createMock(TableInstance::class);
        $table2->method('render')->willReturn('<table id="table2">Table 2</table>');
        
        $tab->addTable($table1);
        $tab->addTable($table2);
        
        $rendered = $tab->render();
        
        $table1Pos = strpos($rendered, 'id="table1"');
        $table2Pos = strpos($rendered, 'id="table2"');
        
        $this->assertNotFalse($table1Pos);
        $this->assertNotFalse($table2Pos);
        $this->assertLessThan($table2Pos, $table1Pos);
    }

    /**
     * Test that empty tab renders empty string.
     */
    public function test_empty_tab_renders_empty_string(): void
    {
        $tab = new Tab('Empty', 'empty-tab');
        
        $rendered = $tab->render();
        
        $this->assertEquals('', $rendered);
    }

    /**
     * Test that tab can be converted to array.
     */
    public function test_tab_can_be_converted_to_array(): void
    {
        $tab = new Tab('Summary', 'summary-tab');
        
        $content = '<p>Content</p>';
        $tab->addContent($content);
        
        $config = ['sortable' => true];
        $tab->setConfig($config);
        
        $tableInstance = $this->createMock(TableInstance::class);
        $tableInstance->method('toArray')
            ->willReturn(['table' => 'users', 'fields' => ['name']]);
        
        $tab->addTable($tableInstance);
        
        $array = $tab->toArray();
        
        $this->assertIsArray($array);
        $this->assertEquals('Summary', $array['name']);
        $this->assertEquals('summary-tab', $array['id']);
        $this->assertCount(1, $array['tables']);
        $this->assertCount(1, $array['content']);
        $this->assertEquals($config, $array['config']);
    }

    /**
     * Test that tab array includes all tables.
     */
    public function test_tab_array_includes_all_tables(): void
    {
        $tab = new Tab('Summary', 'summary-tab');
        
        $table1 = $this->createMock(TableInstance::class);
        $table1->method('toArray')->willReturn(['table' => 'users']);
        
        $table2 = $this->createMock(TableInstance::class);
        $table2->method('toArray')->willReturn(['table' => 'posts']);
        
        $tab->addTable($table1);
        $tab->addTable($table2);
        
        $array = $tab->toArray();
        
        $this->assertCount(2, $array['tables']);
        $this->assertEquals('users', $array['tables'][0]['table']);
        $this->assertEquals('posts', $array['tables'][1]['table']);
    }

    /**
     * Test that tab array includes all content blocks.
     */
    public function test_tab_array_includes_all_content_blocks(): void
    {
        $tab = new Tab('Summary', 'summary-tab');
        
        $content1 = '<p>Content 1</p>';
        $content2 = '<div>Content 2</div>';
        
        $tab->addContent($content1);
        $tab->addContent($content2);
        
        $array = $tab->toArray();
        
        $this->assertCount(2, $array['content']);
        $this->assertEquals($content1, $array['content'][0]);
        $this->assertEquals($content2, $array['content'][1]);
    }

    /**
     * Test that tab handles complex configuration.
     */
    public function test_tab_handles_complex_configuration(): void
    {
        $tab = new Tab('Summary', 'summary-tab');
        
        $config = [
            'sortable' => true,
            'searchable' => true,
            'filters' => [
                'status' => ['active', 'inactive'],
                'category' => ['news', 'blog'],
            ],
            'formats' => [
                'created_at' => 'date',
                'price' => 'currency',
            ],
            'conditions' => [
                'status' => [
                    'active' => ['class' => 'badge-success'],
                    'inactive' => ['class' => 'badge-error'],
                ],
            ],
        ];
        
        $tab->setConfig($config);
        
        $this->assertEquals($config, $tab->getConfig());
        $this->assertEquals(['active', 'inactive'], $tab->getConfig()['filters']['status']);
    }

    /**
     * Test that tab configuration is isolated.
     */
    public function test_tab_configuration_is_isolated(): void
    {
        $tab1 = new Tab('Tab 1', 'tab-1');
        $tab2 = new Tab('Tab 2', 'tab-2');
        
        $config1 = ['sortable' => true];
        $config2 = ['searchable' => true];
        
        $tab1->setConfig($config1);
        $tab2->setConfig($config2);
        
        $this->assertEquals($config1, $tab1->getConfig());
        $this->assertEquals($config2, $tab2->getConfig());
        $this->assertNotEquals($tab1->getConfig(), $tab2->getConfig());
    }

    /**
     * Test that tab content is isolated.
     */
    public function test_tab_content_is_isolated(): void
    {
        $tab1 = new Tab('Tab 1', 'tab-1');
        $tab2 = new Tab('Tab 2', 'tab-2');
        
        $tab1->addContent('<p>Tab 1 content</p>');
        $tab2->addContent('<p>Tab 2 content</p>');
        
        $this->assertCount(1, $tab1->getContent());
        $this->assertCount(1, $tab2->getContent());
        $this->assertNotEquals($tab1->getContent(), $tab2->getContent());
    }

    /**
     * Test that tab tables are isolated.
     */
    public function test_tab_tables_are_isolated(): void
    {
        $tab1 = new Tab('Tab 1', 'tab-1');
        $tab2 = new Tab('Tab 2', 'tab-2');
        
        $table1 = new TableInstance('users', ['name:Name'], []);
        $table2 = new TableInstance('posts', ['title:Title'], []);
        
        $tab1->addTable($table1);
        $tab2->addTable($table2);
        
        $this->assertCount(1, $tab1->getTables());
        $this->assertCount(1, $tab2->getTables());
        $this->assertNotEquals($tab1->getTables(), $tab2->getTables());
    }
}
