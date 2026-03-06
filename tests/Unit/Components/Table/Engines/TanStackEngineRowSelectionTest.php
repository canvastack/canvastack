<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table\Engines;

use Canvastack\Canvastack\Components\Table\Engines\TanStackEngine;
use Canvastack\Canvastack\Components\Table\Renderers\TanStackRenderer;
use Canvastack\Canvastack\Components\Table\ServerSide\TanStackServerAdapter;
use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Test TanStack Engine Row Selection
 *
 * Tests row selection functionality in TanStack Table engine.
 *
 * @package Canvastack\Canvastack\Tests\Unit\Components\Table\Engines
 */
class TanStackEngineRowSelectionTest extends TestCase
{
    /**
     * TanStack engine instance.
     *
     * @var TanStackEngine
     */
    protected TanStackEngine $engine;

    /**
     * Table builder instance.
     *
     * @var TableBuilder
     */
    protected TableBuilder $table;

    /**
     * Setup test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create TanStack engine
        $renderer = new TanStackRenderer();
        $serverAdapter = new TanStackServerAdapter();
        $this->engine = new TanStackEngine($renderer, $serverAdapter);

        // Create table builder
        $this->table = new TableBuilder();
        $this->table->setContext('admin');
    }

    /**
     * Test that TanStack engine supports row selection.
     *
     * @return void
     */
    public function test_tanstack_engine_supports_row_selection(): void
    {
        $this->assertTrue(
            $this->engine->supports('row-selection'),
            'TanStack engine should support row-selection feature'
        );
    }

    /**
     * Test that row selection is enabled in configuration.
     *
     * @return void
     */
    public function test_row_selection_enabled_in_config(): void
    {
        // Enable row selection
        $this->table->setSelectable(true);
        $this->table->setSelectionMode('multiple');

        // Configure engine
        $this->engine->configure($this->table);

        // Get configuration
        $config = $this->engine->getConfig();

        $this->assertArrayHasKey('rowSelection', $config);
        $this->assertTrue($config['rowSelection']['enabled']);
        $this->assertEquals('multiple', $config['rowSelection']['mode']);
    }

    /**
     * Test that row selection is disabled by default.
     *
     * @return void
     */
    public function test_row_selection_disabled_by_default(): void
    {
        // Configure engine without enabling selection
        $this->engine->configure($this->table);

        // Get configuration
        $config = $this->engine->getConfig();

        $this->assertArrayHasKey('rowSelection', $config);
        $this->assertFalse($config['rowSelection']['enabled']);
    }

    /**
     * Test single selection mode.
     *
     * @return void
     */
    public function test_single_selection_mode(): void
    {
        // Enable single selection
        $this->table->setSelectable(true);
        $this->table->setSelectionMode('single');

        // Configure engine
        $this->engine->configure($this->table);

        // Get configuration
        $config = $this->engine->getConfig();

        $this->assertEquals('single', $config['rowSelection']['mode']);
    }

    /**
     * Test multiple selection mode.
     *
     * @return void
     */
    public function test_multiple_selection_mode(): void
    {
        // Enable multiple selection
        $this->table->setSelectable(true);
        $this->table->setSelectionMode('multiple');

        // Configure engine
        $this->engine->configure($this->table);

        // Get configuration
        $config = $this->engine->getConfig();

        $this->assertEquals('multiple', $config['rowSelection']['mode']);
    }

    /**
     * Test that rendered HTML includes selection checkboxes.
     *
     * @return void
     */
    public function test_rendered_html_includes_selection_checkboxes(): void
    {
        // Enable row selection
        $this->table->setSelectable(true);
        $this->table->setSelectionMode('multiple');
        $this->table->setFields(['name' => 'Name', 'email' => 'Email']);

        // Render table
        $html = $this->engine->render($this->table);

        // Check for selection checkbox elements
        $this->assertStringContainsString('tanstack-table-select-column', $html);
        $this->assertStringContainsString('tanstack-table-checkbox', $html);
        $this->assertStringContainsString('x-model="selectAll"', $html);
        $this->assertStringContainsString('@change="onSelectAllChange"', $html);
    }

    /**
     * Test that rendered HTML includes selected row count display.
     *
     * @return void
     */
    public function test_rendered_html_includes_selected_count(): void
    {
        // Enable row selection
        $this->table->setSelectable(true);
        $this->table->setSelectionMode('multiple');
        $this->table->setFields(['name' => 'Name', 'email' => 'Email']);

        // Render table
        $html = $this->engine->render($this->table);

        // Check for selected count display
        $this->assertStringContainsString('selectedCount', $html);
        $this->assertStringContainsString('row_selected', $html);
        $this->assertStringContainsString('rows_selected', $html);
        $this->assertStringContainsString('clear_selection', $html);
    }

    /**
     * Test that rendered HTML includes row selection state management.
     *
     * @return void
     */
    public function test_rendered_html_includes_selection_state_management(): void
    {
        // Enable row selection
        $this->table->setSelectable(true);
        $this->table->setSelectionMode('multiple');
        $this->table->setFields(['name' => 'Name', 'email' => 'Email']);

        // Render table
        $html = $this->engine->render($this->table);

        // Check for selection state management functions
        $this->assertStringContainsString('isRowSelected', $html);
        $this->assertStringContainsString('onRowSelectChange', $html);
        $this->assertStringContainsString('onSelectAllChange', $html);
        $this->assertStringContainsString('updateSelectionState', $html);
        $this->assertStringContainsString('clearSelection', $html);
        $this->assertStringContainsString('getSelectedRowIds', $html);
        $this->assertStringContainsString('getSelectedRows', $html);
    }

    /**
     * Test that rendered HTML includes indeterminate checkbox state.
     *
     * @return void
     */
    public function test_rendered_html_includes_indeterminate_state(): void
    {
        // Enable row selection
        $this->table->setSelectable(true);
        $this->table->setSelectionMode('multiple');
        $this->table->setFields(['name' => 'Name', 'email' => 'Email']);

        // Render table
        $html = $this->engine->render($this->table);

        // Check for indeterminate state
        $this->assertStringContainsString(':indeterminate.prop="isIndeterminate"', $html);
        $this->assertStringContainsString('isIndeterminate', $html);
    }

    /**
     * Test that rendered HTML includes selected row styling.
     *
     * @return void
     */
    public function test_rendered_html_includes_selected_row_styling(): void
    {
        // Enable row selection
        $this->table->setSelectable(true);
        $this->table->setSelectionMode('multiple');
        $this->table->setFields(['name' => 'Name', 'email' => 'Email']);

        // Render table
        $html = $this->engine->render($this->table);

        // Check for selected row styling
        $this->assertStringContainsString('tanstack-table-row-selected', $html);
        $this->assertStringContainsString(':class="{ \'tanstack-table-row-selected\': isRowSelected(row.id) }"', $html);
    }

    /**
     * Test that row selection CSS styles are included.
     *
     * @return void
     */
    public function test_row_selection_css_styles_included(): void
    {
        // Enable row selection
        $this->table->setSelectable(true);
        $this->table->setSelectionMode('multiple');
        $this->table->setFields(['name' => 'Name', 'email' => 'Email']);

        // Render table
        $html = $this->engine->render($this->table);

        // Check for row selection CSS classes
        $this->assertStringContainsString('.tanstack-table-select-column', $html);
        $this->assertStringContainsString('.tanstack-table-checkbox', $html);
        $this->assertStringContainsString('.tanstack-table-row-selected', $html);
    }

    /**
     * Test that row selection works with Alpine.js data.
     *
     * @return void
     */
    public function test_row_selection_alpine_data(): void
    {
        // Enable row selection
        $this->table->setSelectable(true);
        $this->table->setSelectionMode('multiple');
        $this->table->setFields(['name' => 'Name', 'email' => 'Email']);

        // Configure engine
        $this->engine->configure($this->table);

        // Get configuration
        $config = $this->engine->getConfig();

        // Check Alpine.js data includes selection state
        $this->assertArrayHasKey('alpineData', $config);
        $alpineData = $config['alpineData'];

        $this->assertArrayHasKey('rowSelection', $alpineData);
        $this->assertArrayHasKey('selectedCount', $alpineData);
        $this->assertEquals([], $alpineData['rowSelection']);
        $this->assertEquals(0, $alpineData['selectedCount']);
    }

    /**
     * Test that row selection doesn't appear when disabled.
     *
     * @return void
     */
    public function test_row_selection_not_rendered_when_disabled(): void
    {
        // Disable row selection (default)
        $this->table->setSelectable(false);
        $this->table->setFields(['name' => 'Name', 'email' => 'Email']);

        // Render table
        $html = $this->engine->render($this->table);

        // Check that selection elements are NOT present
        $this->assertStringNotContainsString('tanstack-table-select-column', $html);
        $this->assertStringNotContainsString('x-model="selectAll"', $html);
    }

    /**
     * Test that row selection works with server-side processing.
     *
     * @return void
     */
    public function test_row_selection_with_server_side_processing(): void
    {
        // Enable row selection and server-side processing
        $this->table->setSelectable(true);
        $this->table->setSelectionMode('multiple');
        $this->table->setServerSide(true);
        $this->table->setFields(['name' => 'Name', 'email' => 'Email']);

        // Configure engine
        $this->engine->configure($this->table);

        // Get configuration
        $config = $this->engine->getConfig();

        // Both features should be enabled
        $this->assertTrue($config['rowSelection']['enabled']);
        $this->assertTrue($config['serverSide']['enabled']);
    }

    /**
     * Test that row selection translations are used.
     *
     * @return void
     */
    public function test_row_selection_uses_translations(): void
    {
        // Enable row selection
        $this->table->setSelectable(true);
        $this->table->setSelectionMode('multiple');
        $this->table->setFields(['name' => 'Name', 'email' => 'Email']);

        // Render table
        $html = $this->engine->render($this->table);

        // Check for translation keys (they should be translated by __() helper)
        // We check for the translation function calls
        $this->assertStringContainsString('components.table.select_all', $html);
        $this->assertStringContainsString('components.table.row_selected', $html);
        $this->assertStringContainsString('components.table.rows_selected', $html);
        $this->assertStringContainsString('components.table.clear_selection', $html);
    }
}

