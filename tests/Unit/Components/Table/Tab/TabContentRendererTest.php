<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table\Tab;

use Canvastack\Canvastack\Components\Table\Tab\Tab;
use Canvastack\Canvastack\Components\Table\Tab\TabContentRenderer;
use Canvastack\Canvastack\Components\Table\Tab\TabManager;
use Canvastack\Canvastack\Components\Table\Tab\TableInstance;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Test for TabContentRenderer
 */
class TabContentRendererTest extends TestCase
{
    protected TabContentRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->renderer = new TabContentRenderer('test-table', 'admin');
    }

    /**
     * Test that renderer can be instantiated
     */
    public function test_renderer_can_be_instantiated(): void
    {
        $this->assertInstanceOf(TabContentRenderer::class, $this->renderer);
    }

    /**
     * Test context getter and setter
     */
    public function test_context_getter_and_setter(): void
    {
        $this->assertEquals('admin', $this->renderer->getContext());

        $this->renderer->setContext('public');
        $this->assertEquals('public', $this->renderer->getContext());
    }

    /**
     * Test responsive getter and setter
     */
    public function test_responsive_getter_and_setter(): void
    {
        $this->assertTrue($this->renderer->isResponsive());

        $this->renderer->setResponsive(false);
        $this->assertFalse($this->renderer->isResponsive());
    }

    /**
     * Test table ID getter and setter
     */
    public function test_table_id_getter_and_setter(): void
    {
        $this->assertEquals('test-table', $this->renderer->getTableId());

        $this->renderer->setTableId('new-table');
        $this->assertEquals('new-table', $this->renderer->getTableId());
    }

    /**
     * Test rendering a single tab content
     */
    public function test_render_tab_content(): void
    {
        $tab = new Tab('Test Tab', 'test-tab');
        $tab->addContent('<p>Test content</p>');

        $html = $this->renderer->renderTabContent($tab, true);

        $this->assertStringContainsString('tabpanel-test-tab', $html);
        $this->assertStringContainsString('Test content', $html);
    }

    /**
     * Test rendering tab container with navigation
     */
    public function test_render_tab_container(): void
    {
        $this->markTestSkipped('Requires full Laravel application context for Blade component rendering');
    }

    /**
     * Test rendering a table instance
     */
    public function test_render_table_instance(): void
    {
        $table = new TableInstance('users', ['name', 'email'], []);

        $html = $this->renderer->renderTableInstance($table);

        $this->assertStringContainsString('table-instance', $html);
        $this->assertStringContainsString('data-table="users"', $html);
    }

    /**
     * Test rendering content block
     */
    public function test_render_content_block(): void
    {
        $content = '<p>Test content</p>';

        $html = $this->renderer->renderContentBlock($content);

        $this->assertStringContainsString('content-block', $html);
        $this->assertStringContainsString('Test content', $html);
    }

    /**
     * Test rendering content block with sanitization
     */
    public function test_render_content_block_with_sanitization(): void
    {
        $content = '<script>alert("XSS")</script>';

        $html = $this->renderer->renderContentBlock($content, ['sanitize' => true]);

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    /**
     * Test rendering empty state
     */
    public function test_render_empty_state(): void
    {
        $html = $this->renderer->renderEmptyState('No data available');

        $this->assertStringContainsString('empty-state', $html);
        $this->assertStringContainsString('No data available', $html);
    }

    /**
     * Test rendering loading placeholder
     */
    public function test_render_loading_placeholder(): void
    {
        $html = $this->renderer->renderLoadingPlaceholder('Loading data...');

        $this->assertStringContainsString('table-loading', $html);
        $this->assertStringContainsString('Loading data...', $html);
        $this->assertStringContainsString('animate-spin', $html);
    }

    /**
     * Test rendering tab navigation only
     */
    public function test_render_tab_navigation(): void
    {
        $tabManager = new TabManager();
        $tabManager->openTab('Tab 1');
        $tabManager->closeTab();

        $html = $this->renderer->renderTabNavigation($tabManager);

        // Check for key elements in the navigation
        $this->assertStringContainsString('x-data="tableTabs_', $html);
        $this->assertStringContainsString('Tab 1', $html);
        $this->assertStringContainsString('role="tablist"', $html);
    }

    /**
     * Test rendering multiple table instances
     */
    public function test_render_table_instances(): void
    {
        $tables = [
            new TableInstance('users', ['name', 'email'], []),
            new TableInstance('posts', ['title', 'content'], []),
        ];

        $html = $this->renderer->renderTableInstances($tables);

        $this->assertStringContainsString('tab-tables-container', $html);
        $this->assertStringContainsString('data-table="users"', $html);
        $this->assertStringContainsString('data-table="posts"', $html);
    }

    /**
     * Test rendering content blocks
     */
    public function test_render_content_blocks(): void
    {
        $contentBlocks = [
            '<p>Block 1</p>',
            '<p>Block 2</p>',
            '<p>Block 3</p>',
        ];

        $html = $this->renderer->renderContentBlocks($contentBlocks);

        $this->assertStringContainsString('tab-custom-content', $html);
        $this->assertStringContainsString('Block 1', $html);
        $this->assertStringContainsString('Block 2', $html);
        $this->assertStringContainsString('Block 3', $html);
    }

    /**
     * Test rendering empty content blocks returns empty string
     */
    public function test_render_empty_content_blocks_returns_empty_string(): void
    {
        $html = $this->renderer->renderContentBlocks([]);

        $this->assertEquals('', $html);
    }

    /**
     * Test getting responsive CSS classes
     */
    public function test_get_responsive_classes(): void
    {
        $this->renderer->setResponsive(true);
        $classes = $this->renderer->getResponsiveClasses();

        $this->assertStringContainsString('responsive', $classes);
        $this->assertStringContainsString('w-full', $classes);
    }

    /**
     * Test getting responsive classes when disabled
     */
    public function test_get_responsive_classes_when_disabled(): void
    {
        $this->renderer->setResponsive(false);
        $classes = $this->renderer->getResponsiveClasses();

        $this->assertEquals('', $classes);
    }

    /**
     * Test getting context-specific CSS classes
     */
    public function test_get_context_classes(): void
    {
        $this->renderer->setContext('admin');
        $this->assertEquals('admin-context', $this->renderer->getContextClasses());

        $this->renderer->setContext('public');
        $this->assertEquals('public-context', $this->renderer->getContextClasses());
    }

    /**
     * Test wrapping content in responsive container
     */
    public function test_wrap_in_responsive_container(): void
    {
        $content = '<p>Test content</p>';

        $html = $this->renderer->wrapInResponsiveContainer($content);

        $this->assertStringContainsString('responsive-container', $html);
        $this->assertStringContainsString('Test content', $html);
    }

    /**
     * Test wrapping content when responsive is disabled
     */
    public function test_wrap_in_responsive_container_when_disabled(): void
    {
        $this->renderer->setResponsive(false);
        $content = '<p>Test content</p>';

        $html = $this->renderer->wrapInResponsiveContainer($content);

        $this->assertEquals($content, $html);
    }

    /**
     * Test generating data attributes
     */
    public function test_generate_data_attributes(): void
    {
        $tab = new Tab('Test Tab', 'test-tab');
        $tab->addContent('<p>Content</p>');
        $tab->addTable(new TableInstance('users', ['name'], []));

        $attributes = $this->renderer->generateDataAttributes($tab);

        $this->assertStringContainsString('data-tab-id="test-tab"', $attributes);
        $this->assertStringContainsString('data-tab-name="Test Tab"', $attributes);
        $this->assertStringContainsString('data-table-count="1"', $attributes);
        $this->assertStringContainsString('data-content-count="1"', $attributes);
    }

    /**
     * Test validating tab
     */
    public function test_validate_tab(): void
    {
        $tab = new Tab('Test Tab', 'test-tab');

        $this->assertTrue($this->renderer->validateTab($tab));
    }

    /**
     * Test validating tab with empty name throws exception
     */
    public function test_validate_tab_with_empty_name_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Tab name cannot be empty');

        $tab = new Tab('', 'test-tab');
        $this->renderer->validateTab($tab);
    }

    /**
     * Test rendering tab with table instances
     */
    public function test_render_tab_with_table_instances(): void
    {
        $tab = new Tab('Test Tab', 'test-tab');
        $tab->addTable(new TableInstance('users', ['name', 'email'], []));
        $tab->addTable(new TableInstance('posts', ['title'], []));

        $html = $this->renderer->renderTabContent($tab, true);

        $this->assertStringContainsString('tabpanel-test-tab', $html);
    }

    /**
     * Test rendering tab with mixed content and tables
     */
    public function test_render_tab_with_mixed_content_and_tables(): void
    {
        $tab = new Tab('Test Tab', 'test-tab');
        $tab->addContent('<p>Custom content</p>');
        $tab->addTable(new TableInstance('users', ['name'], []));

        $html = $this->renderer->renderTabContent($tab, true);

        $this->assertStringContainsString('tabpanel-test-tab', $html);
        $this->assertStringContainsString('Custom content', $html);
    }

    /**
     * Test rendering tab with empty content shows empty state
     */
    public function test_render_tab_with_empty_content_shows_empty_state(): void
    {
        $tab = new Tab('Empty Tab', 'empty-tab');

        $html = $this->renderer->renderTabContent($tab, true);

        $this->assertStringContainsString('empty-state', $html);
    }

    /**
     * Test renderer fluent interface
     */
    public function test_renderer_fluent_interface(): void
    {
        $result = $this->renderer
            ->setContext('public')
            ->setResponsive(false)
            ->setTableId('fluent-table');

        $this->assertInstanceOf(TabContentRenderer::class, $result);
        $this->assertEquals('public', $this->renderer->getContext());
        $this->assertFalse($this->renderer->isResponsive());
        $this->assertEquals('fluent-table', $this->renderer->getTableId());
    }
}
