<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table;

use Canvastack\Canvastack\Components\Table\HashGenerator;
use Canvastack\Canvastack\Components\Table\Renderers\TanStackRenderer;
use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Support\Integration\ThemeLocaleIntegration;
use Canvastack\Canvastack\Tests\TestCase;
use Mockery;

/**
 * Test TanStack Table state persistence functionality.
 * 
 * Validates Requirement 8.8: State persistence per tab
 * 
 * @group tanstack
 * @group state-persistence
 */
class TanStackStatePersistenceTest extends TestCase
{
    private TanStackRenderer $renderer;
    private TableBuilder $table;
    private HashGenerator $hashGenerator;
    private $themeLocaleIntegration;
    private $connectionManager;
    private $warningSystem;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock ThemeLocaleIntegration
        $this->themeLocaleIntegration = Mockery::mock(ThemeLocaleIntegration::class);
        $this->themeLocaleIntegration->shouldReceive('getLocalizedThemeCss')
            ->andReturn('<style>/* theme css */</style>')
            ->byDefault();
        $this->themeLocaleIntegration->shouldReceive('getHtmlAttributes')
            ->andReturn(['lang' => 'en', 'dir' => 'ltr', 'class' => ''])
            ->byDefault();
        $this->themeLocaleIntegration->shouldReceive('getBodyClasses')
            ->andReturn('')
            ->byDefault();
        $this->themeLocaleIntegration->shouldReceive('isRtl')
            ->andReturn(false)
            ->byDefault();

        // Mock TableBuilder dependencies
        $queryOptimizer = Mockery::mock(\Canvastack\Canvastack\Components\Table\Query\QueryOptimizer::class);
        $filterBuilder = Mockery::mock(\Canvastack\Canvastack\Components\Table\Query\FilterBuilder::class);
        $schemaInspector = Mockery::mock(\Canvastack\Canvastack\Components\Table\Validation\SchemaInspector::class);
        $columnValidator = Mockery::mock(\Canvastack\Canvastack\Components\Table\Validation\ColumnValidator::class);
        
        $this->hashGenerator = new HashGenerator();
        
        $this->connectionManager = Mockery::mock(\Canvastack\Canvastack\Components\Table\ConnectionManager::class);
        $this->connectionManager->shouldReceive('detectConnection')->andReturn('mysql')->byDefault();
        $this->connectionManager->shouldReceive('getConnection')->andReturn('mysql')->byDefault();
        $this->connectionManager->shouldReceive('hasConnectionMismatch')->andReturn(false)->byDefault();

        $this->warningSystem = Mockery::mock(\Canvastack\Canvastack\Components\Table\WarningSystem::class);
        $this->warningSystem->shouldReceive('isEnabled')->andReturn(false)->byDefault();

        $this->renderer = new TanStackRenderer($this->themeLocaleIntegration);
        
        $this->table = new TableBuilder(
            $queryOptimizer,
            $filterBuilder,
            $schemaInspector,
            $columnValidator,
            $this->hashGenerator,
            $this->connectionManager,
            $this->warningSystem
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test that state storage key is generated correctly.
     * 
     * @return void
     */
    public function test_state_storage_key_generation(): void
    {
        $this->table->setContext('admin');
        $this->table->setData([
            ['id' => 1, 'name' => 'Test'],
        ]);
        $this->table->setFields(['name:Name']);
        $this->table->format();

        $scripts = $this->renderer->renderScripts(
            $this->table,
            [],
            [],
            ['data' => [], 'pageSize' => 10, 'totalRows' => 0]
        );

        // Verify getStateStorageKey function exists
        $this->assertStringContainsString('getStateStorageKey()', $scripts);
        $this->assertStringContainsString('tanstack_table_state_', $scripts);
    }

    /**
     * Test that saveState function is defined.
     * 
     * @return void
     */
    public function test_save_state_function_defined(): void
    {
        $this->table->setContext('admin');
        $this->table->setData([
            ['id' => 1, 'name' => 'Test'],
        ]);
        $this->table->setFields(['name:Name']);
        $this->table->format();

        $scripts = $this->renderer->renderScripts(
            $this->table,
            [],
            [],
            ['data' => [], 'pageSize' => 10, 'totalRows' => 0]
        );

        // Verify saveState function exists
        $this->assertStringContainsString('saveState()', $scripts);
        $this->assertStringContainsString('sessionStorage.setItem', $scripts);
    }

    /**
     * Test that restoreState function is defined.
     * 
     * @return void
     */
    public function test_restore_state_function_defined(): void
    {
        $this->table->setContext('admin');
        $this->table->setData([
            ['id' => 1, 'name' => 'Test'],
        ]);
        $this->table->setFields(['name:Name']);
        $this->table->format();

        $scripts = $this->renderer->renderScripts(
            $this->table,
            [],
            [],
            ['data' => [], 'pageSize' => 10, 'totalRows' => 0]
        );

        // Verify restoreState function exists
        $this->assertStringContainsString('restoreState()', $scripts);
        $this->assertStringContainsString('sessionStorage.getItem', $scripts);
    }

    /**
     * Test that clearState function is defined.
     * 
     * @return void
     */
    public function test_clear_state_function_defined(): void
    {
        $this->table->setContext('admin');
        $this->table->setData([
            ['id' => 1, 'name' => 'Test'],
        ]);
        $this->table->setFields(['name:Name']);
        $this->table->format();

        $scripts = $this->renderer->renderScripts(
            $this->table,
            [],
            [],
            ['data' => [], 'pageSize' => 10, 'totalRows' => 0]
        );

        // Verify clearState function exists
        $this->assertStringContainsString('clearState()', $scripts);
        $this->assertStringContainsString('sessionStorage.removeItem', $scripts);
    }

    /**
     * Test that state is restored on init.
     * 
     * @return void
     */
    public function test_state_restored_on_init(): void
    {
        $this->table->setContext('admin');
        $this->table->setData([
            ['id' => 1, 'name' => 'Test'],
        ]);
        $this->table->setFields(['name:Name']);
        $this->table->format();

        $scripts = $this->renderer->renderScripts(
            $this->table,
            [],
            [],
            ['data' => [], 'pageSize' => 10, 'totalRows' => 0]
        );

        // Verify restoreState is called in init
        $this->assertStringContainsString('init()', $scripts);
        $this->assertStringContainsString('this.restoreState()', $scripts);
    }

    /**
     * Test that state includes sorting information.
     * 
     * @return void
     */
    public function test_state_includes_sorting(): void
    {
        $this->table->setContext('admin');
        $this->table->setData([
            ['id' => 1, 'name' => 'Test'],
        ]);
        $this->table->setFields(['name:Name']);
        $this->table->format();

        $scripts = $this->renderer->renderScripts(
            $this->table,
            [],
            [],
            ['data' => [], 'pageSize' => 10, 'totalRows' => 0]
        );

        // Verify sorting is saved in state
        $this->assertStringContainsString('sorting: this.sorting', $scripts);
    }

    /**
     * Test that state includes pagination information.
     * 
     * @return void
     */
    public function test_state_includes_pagination(): void
    {
        $this->table->setContext('admin');
        $this->table->setData([
            ['id' => 1, 'name' => 'Test'],
        ]);
        $this->table->setFields(['name:Name']);
        $this->table->format();

        $scripts = $this->renderer->renderScripts(
            $this->table,
            [],
            [],
            ['data' => [], 'pageSize' => 10, 'totalRows' => 0]
        );

        // Verify pagination is saved in state
        $this->assertStringContainsString('pagination:', $scripts);
        $this->assertStringContainsString('page: this.pagination.page', $scripts);
        $this->assertStringContainsString('pageSize: this.pagination.pageSize', $scripts);
    }

    /**
     * Test that state includes filter information.
     * 
     * @return void
     */
    public function test_state_includes_filters(): void
    {
        $this->table->setContext('admin');
        $this->table->setData([
            ['id' => 1, 'name' => 'Test'],
        ]);
        $this->table->setFields(['name:Name']);
        $this->table->format();

        $scripts = $this->renderer->renderScripts(
            $this->table,
            [],
            [],
            ['data' => [], 'pageSize' => 10, 'totalRows' => 0]
        );

        // Verify filters are saved in state
        $this->assertStringContainsString('globalFilter: this.globalFilter', $scripts);
        $this->assertStringContainsString('columnFilters: this.columnFilters', $scripts);
    }

    /**
     * Test that state includes row selection.
     * 
     * @return void
     */
    public function test_state_includes_row_selection(): void
    {
        $this->table->setContext('admin');
        $this->table->setData([
            ['id' => 1, 'name' => 'Test'],
        ]);
        $this->table->setFields(['name:Name']);
        $this->table->format();

        $scripts = $this->renderer->renderScripts(
            $this->table,
            [],
            [],
            ['data' => [], 'pageSize' => 10, 'totalRows' => 0]
        );

        // Verify row selection is saved in state
        $this->assertStringContainsString('rowSelection: this.rowSelection', $scripts);
    }

    /**
     * Test that state includes timestamp.
     * 
     * @return void
     */
    public function test_state_includes_timestamp(): void
    {
        $this->table->setContext('admin');
        $this->table->setData([
            ['id' => 1, 'name' => 'Test'],
        ]);
        $this->table->setFields(['name:Name']);
        $this->table->format();

        $scripts = $this->renderer->renderScripts(
            $this->table,
            [],
            [],
            ['data' => [], 'pageSize' => 10, 'totalRows' => 0]
        );

        // Verify timestamp is saved in state
        $this->assertStringContainsString('timestamp: Date.now()', $scripts);
    }

    /**
     * Test that state is saved on page change.
     * 
     * @return void
     */
    public function test_state_saved_on_page_change(): void
    {
        $this->table->setContext('admin');
        $this->table->setData([
            ['id' => 1, 'name' => 'Test'],
        ]);
        $this->table->setFields(['name:Name']);
        $this->table->format();

        $scripts = $this->renderer->renderScripts(
            $this->table,
            [],
            [],
            ['data' => [], 'pageSize' => 10, 'totalRows' => 0]
        );

        // Verify saveState is called in pagination methods
        $this->assertStringContainsString('goToPage(page)', $scripts);
        $this->assertStringContainsString('this.saveState()', $scripts);
        $this->assertStringContainsString('nextPage()', $scripts);
        $this->assertStringContainsString('previousPage()', $scripts);
    }

    /**
     * Test that state is saved on sort change.
     * 
     * @return void
     */
    public function test_state_saved_on_sort_change(): void
    {
        $this->table->setContext('admin');
        $this->table->setData([
            ['id' => 1, 'name' => 'Test'],
        ]);
        $this->table->setFields(['name:Name']);
        $this->table->format();

        $scripts = $this->renderer->renderScripts(
            $this->table,
            [],
            [],
            ['data' => [], 'pageSize' => 10, 'totalRows' => 0]
        );

        // Verify saveState is called in onSort
        $this->assertStringContainsString('onSort(columnId)', $scripts);
        $this->assertStringContainsString('this.applySorting()', $scripts);
        
        // Find the onSort method and verify saveState is called after applySorting
        $onSortPos = strpos($scripts, 'onSort(columnId)');
        $this->assertNotFalse($onSortPos);
        
        $applySortingPos = strpos($scripts, 'this.applySorting()', $onSortPos);
        $this->assertNotFalse($applySortingPos);
        
        $saveStatePos = strpos($scripts, 'this.saveState()', $applySortingPos);
        $this->assertNotFalse($saveStatePos);
    }

    /**
     * Test that state is saved on selection change.
     * 
     * @return void
     */
    public function test_state_saved_on_selection_change(): void
    {
        $this->table->setContext('admin');
        $this->table->setData([
            ['id' => 1, 'name' => 'Test'],
        ]);
        $this->table->setFields(['name:Name']);
        $this->table->format();

        $scripts = $this->renderer->renderScripts(
            $this->table,
            [],
            [],
            ['data' => [], 'pageSize' => 10, 'totalRows' => 0]
        );

        // Verify saveState is called in selection methods
        $this->assertStringContainsString('onRowSelectChange(rowId)', $scripts);
        $this->assertStringContainsString('onSelectAllChange()', $scripts);
        
        // Find the onRowSelectChange method and verify saveState is called
        $onRowSelectPos = strpos($scripts, 'onRowSelectChange(rowId)');
        $this->assertNotFalse($onRowSelectPos);
        
        $updateSelectionPos = strpos($scripts, 'this.updateSelectionState()', $onRowSelectPos);
        $this->assertNotFalse($updateSelectionPos);
        
        $saveStatePos = strpos($scripts, 'this.saveState()', $updateSelectionPos);
        $this->assertNotFalse($saveStatePos);
    }

    /**
     * Test that state has expiration check.
     * 
     * @return void
     */
    public function test_state_has_expiration_check(): void
    {
        $this->table->setContext('admin');
        $this->table->setData([
            ['id' => 1, 'name' => 'Test'],
        ]);
        $this->table->setFields(['name:Name']);
        $this->table->format();

        $scripts = $this->renderer->renderScripts(
            $this->table,
            [],
            [],
            ['data' => [], 'pageSize' => 10, 'totalRows' => 0]
        );

        // Verify state expiration check exists
        $this->assertStringContainsString('maxAge', $scripts);
        $this->assertStringContainsString('60 * 60 * 1000', $scripts); // 1 hour
        $this->assertStringContainsString('Date.now() - state.timestamp', $scripts);
        $this->assertStringContainsString('this.clearState()', $scripts);
    }

    /**
     * Test that state is saved on destroy.
     * 
     * @return void
     */
    public function test_state_saved_on_destroy(): void
    {
        $this->table->setContext('admin');
        $this->table->setData([
            ['id' => 1, 'name' => 'Test'],
        ]);
        $this->table->setFields(['name:Name']);
        $this->table->format();

        $scripts = $this->renderer->renderScripts(
            $this->table,
            [],
            [],
            ['data' => [], 'pageSize' => 10, 'totalRows' => 0]
        );

        // Verify saveState is called in destroy method
        $this->assertStringContainsString('destroy()', $scripts);
        
        // Find the destroy method and verify saveState is called at the beginning
        $destroyPos = strpos($scripts, 'destroy()');
        $this->assertNotFalse($destroyPos);
        
        $saveStatePos = strpos($scripts, 'this.saveState()', $destroyPos);
        $this->assertNotFalse($saveStatePos);
        
        // Verify saveState comes before cleanup
        $deleteInstancePos = strpos($scripts, 'delete window._tanstackInstances[tableId]', $destroyPos);
        $this->assertNotFalse($deleteInstancePos);
        $this->assertLessThan($deleteInstancePos, $saveStatePos);
    }

    /**
     * Test that each table instance has unique state storage.
     * 
     * @return void
     */
    public function test_each_instance_has_unique_state_storage(): void
    {
        // Mock dependencies for tables
        $queryOptimizer = Mockery::mock(\Canvastack\Canvastack\Components\Table\Query\QueryOptimizer::class);
        $filterBuilder = Mockery::mock(\Canvastack\Canvastack\Components\Table\Query\FilterBuilder::class);
        $schemaInspector = Mockery::mock(\Canvastack\Canvastack\Components\Table\Validation\SchemaInspector::class);
        $columnValidator = Mockery::mock(\Canvastack\Canvastack\Components\Table\Validation\ColumnValidator::class);
        
        // Create first table
        $table1 = new TableBuilder(
            $queryOptimizer,
            $filterBuilder,
            $schemaInspector,
            $columnValidator,
            $this->hashGenerator,
            $this->connectionManager,
            $this->warningSystem
        );
        $table1->setContext('admin');
        $table1->setData([['id' => 1, 'name' => 'Table 1']]);
        $table1->setFields(['name:Name']);
        $table1->format();

        // Create second table
        $table2 = new TableBuilder(
            $queryOptimizer,
            $filterBuilder,
            $schemaInspector,
            $columnValidator,
            $this->hashGenerator,
            $this->connectionManager,
            $this->warningSystem
        );
        $table2->setContext('admin');
        $table2->setData([['id' => 1, 'name' => 'Table 2']]);
        $table2->setFields(['name:Name']);
        $table2->format();

        $scripts1 = $this->renderer->renderScripts(
            $table1,
            [],
            [],
            ['data' => [], 'pageSize' => 10, 'totalRows' => 0]
        );

        $scripts2 = $this->renderer->renderScripts(
            $table2,
            [],
            [],
            ['data' => [], 'pageSize' => 10, 'totalRows' => 0]
        );

        // Extract table IDs from scripts
        preg_match('/const tableId = \'([^\']+)\'/', $scripts1, $matches1);
        preg_match('/const tableId = \'([^\']+)\'/', $scripts2, $matches2);

        $this->assertNotEmpty($matches1);
        $this->assertNotEmpty($matches2);
        $this->assertNotEquals($matches1[1], $matches2[1], 'Each table should have unique ID');

        // Verify each uses its own storage key
        $this->assertStringContainsString('tanstack_table_state_', $scripts1);
        $this->assertStringContainsString('tanstack_table_state_', $scripts2);
    }

    /**
     * Test that state restoration handles missing state gracefully.
     * 
     * @return void
     */
    public function test_state_restoration_handles_missing_state(): void
    {
        $this->table->setContext('admin');
        $this->table->setData([
            ['id' => 1, 'name' => 'Test'],
        ]);
        $this->table->setFields(['name:Name']);
        $this->table->format();

        $scripts = $this->renderer->renderScripts(
            $this->table,
            [],
            [],
            ['data' => [], 'pageSize' => 10, 'totalRows' => 0]
        );

        // Verify graceful handling of missing state
        $this->assertStringContainsString('if (!savedState)', $scripts);
        $this->assertStringContainsString('No saved state found', $scripts);
        $this->assertStringContainsString('return', $scripts);
    }

    /**
     * Test that state restoration handles errors gracefully.
     * 
     * @return void
     */
    public function test_state_restoration_handles_errors(): void
    {
        $this->table->setContext('admin');
        $this->table->setData([
            ['id' => 1, 'name' => 'Test'],
        ]);
        $this->table->setFields(['name:Name']);
        $this->table->format();

        $scripts = $this->renderer->renderScripts(
            $this->table,
            [],
            [],
            ['data' => [], 'pageSize' => 10, 'totalRows' => 0]
        );

        // Verify error handling in restoreState
        $this->assertStringContainsString('try {', $scripts);
        $this->assertStringContainsString('} catch (error) {', $scripts);
        $this->assertStringContainsString('Error restoring state', $scripts);
        $this->assertStringContainsString('this.clearState()', $scripts);
    }
}
