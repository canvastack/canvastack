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
 * Test for TanStack Table independent initialization.
 * 
 * Validates Requirement 8.3: Each instance initializes independently
 */
class TanStackIndependentInitializationTest extends TestCase
{
    protected TanStackRenderer $renderer;
    protected HashGenerator $hashGenerator;
    protected $themeLocaleIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->hashGenerator = new HashGenerator();
        
        // Mock theme locale integration
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
        
        $this->renderer = new TanStackRenderer($this->themeLocaleIntegration);
        
        // Reset the static flag for global functions output
        // This ensures each test starts with a clean state
        TanStackRenderer::resetGlobalFunctionsFlag();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Create a mock TableBuilder with a unique ID.
     */
    protected function createMockTable(string $uniqueId): TableBuilder
    {
        $table = Mockery::mock(TableBuilder::class);
        $table->shouldReceive('getUniqueId')->andReturn($uniqueId);
        $table->shouldReceive('getModel')->andReturn(null);
        $table->shouldReceive('getConfiguration')->andReturn((object)['searchableColumns' => []]);
        
        return $table;
    }

    /**
     * Test that each table instance gets a unique component name.
     * 
     * Validates: Requirement 8.1, 8.2, 8.3
     */
    public function test_each_instance_has_unique_component_name(): void
    {
        // Generate two unique IDs
        $id1 = $this->hashGenerator->generate('table1', 'mysql', ['id', 'name']);
        $id2 = $this->hashGenerator->generate('table2', 'mysql', ['id', 'email']);
        
        // Assert IDs are different
        $this->assertNotEquals($id1, $id2, 'Each table instance should have a unique ID');
        
        // Assert IDs follow the correct format
        $this->assertMatchesRegularExpression('/^canvastable_[a-f0-9]{16}$/', $id1);
        $this->assertMatchesRegularExpression('/^canvastable_[a-f0-9]{16}$/', $id2);
    }

    /**
     * Test that rendered scripts contain unique component names.
     * 
     * Validates: Requirement 8.3
     * 
     * Note: This test verifies that each table instance has its own unique
     * component name and tableId constant within its IIFE wrapper. The IIFE
     * ensures proper isolation of table-specific logic.
     */
    public function test_rendered_scripts_contain_unique_component_names(): void
    {
        // Create two mock tables with unique IDs
        $id1 = $this->hashGenerator->generate('table1', 'mysql', ['id', 'name']);
        $id2 = $this->hashGenerator->generate('table2', 'mysql', ['id', 'email']);
        
        $table1 = $this->createMockTable($id1);
        $table2 = $this->createMockTable($id2);
        
        // Render scripts for both tables
        $config = ['columns' => [], 'pagination' => []];
        $columns = [];
        $alpineData = ['data' => [], 'pageSize' => 10, 'totalRows' => 0];
        
        $scripts1 = $this->renderer->renderScripts($table1, $config, $columns, $alpineData);
        $scripts2 = $this->renderer->renderScripts($table2, $config, $columns, $alpineData);
        
        // Assert each script contains its unique component name
        $this->assertStringContainsString('tanstackTable_' . $id1, $scripts1);
        $this->assertStringContainsString('tanstackTable_' . $id2, $scripts2);
        
        // Assert each script contains its own tableId constant declaration
        $this->assertStringContainsString("const tableId = '{$id1}';", $scripts1);
        $this->assertStringContainsString("const tableId = '{$id2}';", $scripts2);
        
        // Assert scripts are wrapped in IIFE for isolation
        $this->assertStringContainsString('(function() {', $scripts1);
        $this->assertStringContainsString('})();', $scripts1);
        $this->assertStringContainsString('(function() {', $scripts2);
        $this->assertStringContainsString('})();', $scripts2);
    }

    /**
     * Test that scripts contain duplicate registration prevention.
     * 
     * Validates: Requirement 8.3 - Prevent ID collisions
     */
    public function test_scripts_contain_duplicate_registration_prevention(): void
    {
        $id = $this->hashGenerator->generate('table', 'mysql', ['id', 'name']);
        $table = $this->createMockTable($id);
        
        $config = ['columns' => [], 'pagination' => []];
        $columns = [];
        $alpineData = ['data' => [], 'pageSize' => 10, 'totalRows' => 0];
        
        $scripts = $this->renderer->renderScripts($table, $config, $columns, $alpineData);
        
        // Assert script contains duplicate registration check
        $this->assertStringContainsString("'already registered, skipping'", $scripts);
        $this->assertStringContainsString('// Prevent duplicate registration (ID collision prevention)', $scripts);
    }

    /**
     * Test that scripts contain instance-specific event listeners.
     * 
     * Validates: Requirement 8.3 - Independent initialization
     */
    public function test_scripts_contain_instance_specific_event_listeners(): void
    {
        $id = $this->hashGenerator->generate('table', 'mysql', ['id', 'name']);
        $table = $this->createMockTable($id);
        
        $config = ['columns' => [], 'pagination' => []];
        $columns = [];
        $alpineData = ['data' => [], 'pageSize' => 10, 'totalRows' => 0];
        
        $scripts = $this->renderer->renderScripts($table, $config, $columns, $alpineData);
        
        // Assert script contains instance-specific filter event
        $this->assertStringContainsString("'filters-applied-' + tableId", $scripts);
        
        // Assert script contains instance registry
        $this->assertStringContainsString('window._tanstackInstances', $scripts);
        $this->assertStringContainsString('window._tanstackInstances[tableId] = this', $scripts);
    }

    /**
     * Test that scripts contain cleanup/destroy method.
     * 
     * Validates: Requirement 8.3 - Independent initialization and cleanup
     */
    public function test_scripts_contain_cleanup_method(): void
    {
        $id = $this->hashGenerator->generate('table', 'mysql', ['id', 'name']);
        $table = $this->createMockTable($id);
        
        $config = ['columns' => [], 'pagination' => []];
        $columns = [];
        $alpineData = ['data' => [], 'pageSize' => 10, 'totalRows' => 0];
        
        $scripts = $this->renderer->renderScripts($table, $config, $columns, $alpineData);
        
        // Assert script contains destroy method
        $this->assertStringContainsString('destroy()', $scripts);
        $this->assertStringContainsString('Destroying instance', $scripts);
        $this->assertStringContainsString('delete window._tanstackInstances[tableId]', $scripts);
    }

    /**
     * Test that scripts use requestAnimationFrame for initialization.
     * 
     * Validates: Requirement 8.3 - Prevent race conditions
     */
    public function test_scripts_use_request_animation_frame(): void
    {
        $id = $this->hashGenerator->generate('table', 'mysql', ['id', 'name']);
        $table = $this->createMockTable($id);
        
        $config = ['columns' => [], 'pagination' => []];
        $columns = [];
        $alpineData = ['data' => [], 'pageSize' => 10, 'totalRows' => 0];
        
        $scripts = $this->renderer->renderScripts($table, $config, $columns, $alpineData);
        
        // Assert script uses requestAnimationFrame instead of setTimeout
        $this->assertStringContainsString('requestAnimationFrame', $scripts);
        $this->assertStringContainsString('// Use requestAnimationFrame for better timing and to avoid race conditions', $scripts);
    }

    /**
     * Test that multiple instances can be rendered on the same page.
     * 
     * Validates: Requirement 8.3 - Multiple instances coexist
     * 
     * Note: This test validates that each instance has proper IIFE isolation
     * while accepting that global helper functions are shared across all instances.
     * The IIFE wrapper ensures that each table's Alpine component logic is isolated,
     * while global functions like toggleActionDropdown() are intentionally shared.
     * 
     * The key validation is that each script has exactly ONE tableId constant
     * declaration within its IIFE, which proves proper isolation.
     */
    public function test_multiple_instances_can_coexist(): void
    {
        // Create three unique IDs
        $ids = [
            $this->hashGenerator->generate('table1', 'mysql', ['id', 'name']),
            $this->hashGenerator->generate('table2', 'mysql', ['id', 'email']),
            $this->hashGenerator->generate('table3', 'mysql', ['id', 'status']),
        ];
        
        // Assert all IDs are unique
        $this->assertCount(3, array_unique($ids), 'All table instances should have unique IDs');
        
        // Create mock tables
        $tables = array_map(fn($id) => $this->createMockTable($id), $ids);
        
        // Render all tables
        $config = ['columns' => [], 'pagination' => []];
        $columns = [];
        $alpineData = ['data' => [], 'pageSize' => 10, 'totalRows' => 0];
        
        $allScripts = [];
        foreach ($tables as $table) {
            $allScripts[] = $this->renderer->renderScripts($table, $config, $columns, $alpineData);
        }
        
        // PART 1: Verify each script has its own unique identifiers
        // The key validation is that each script has exactly ONE tableId constant declaration
        // This proves proper IIFE isolation
        foreach ($allScripts as $i => $script) {
            $tableId = $ids[$i];
            
            // Each script should contain its own table ID constant within the IIFE
            $this->assertStringContainsString("const tableId = '{$tableId}';", $script,
                "Script {$i} should contain its own tableId constant");
            
            // Each script should be wrapped in an IIFE for isolation
            $this->assertStringContainsString('(function() {', $script,
                "Script {$i} should be wrapped in an IIFE");
            $this->assertStringContainsString('})();', $script,
                "Script {$i} should close the IIFE");
            
            // Each script should contain its own component name generation
            $this->assertStringContainsString("const componentName = 'tanstackTable_' + tableId;", $script,
                "Script {$i} should generate component name from tableId");
        }
        
        // PART 2: Verify IIFE isolation - each script has exactly ONE tableId constant
        // This is the KEY validation that proves proper isolation
        foreach ($allScripts as $i => $script) {
            $tableId = $ids[$i];
            
            // Count how many times this specific tableId appears as a constant declaration
            $pattern = "/const tableId = '{$tableId}';/";
            $matches = preg_match_all($pattern, $script);
            
            $this->assertEquals(1, $matches, 
                "Script {$i} should contain exactly one tableId constant declaration for {$tableId}");
        }
        
        // PART 3: Verify component name generation is scoped
        foreach ($allScripts as $i => $script) {
            $this->assertStringContainsString("const componentName = 'tanstackTable_' + tableId;", $script,
                "Script {$i} should contain component name generation");
        }
        
        // PART 4: Verify component name generation is scoped
        foreach ($allScripts as $i => $script) {
            $this->assertStringContainsString("const componentName = 'tanstackTable_' + tableId;", $script,
                "Script {$i} should contain component name generation");
        }
        
        // PART 5: Verify Alpine component registration is within IIFE
        foreach ($allScripts as $i => $script) {
            $this->assertStringContainsString('Alpine.data(componentName', $script,
                "Script {$i} should register Alpine component within IIFE");
            
            $this->assertStringContainsString('window._tanstackInstances[tableId] = this', $script,
                "Script {$i} should register instance in global registry");
        }
        
        // PART 6: Verify instance-specific event listeners use tableId variable
        foreach ($allScripts as $i => $script) {
            $this->assertStringContainsString("'filters-applied-' + tableId", $script,
                "Script {$i} should use tableId variable for instance-specific events");
        }
        
        // PART 7: Verify dynamic filter variable access pattern exists
        // This validates that the implementation uses the dynamic pattern for filter variables
        // The pattern ensures each instance accesses its own filters
        // Accepts both direct usage: window['tableFilters_' + tableId]
        // And variable indirection: const filterVarName = 'tableFilters_' + tableId; window[filterVarName]
        foreach ($allScripts as $i => $script) {
            // Check for direct pattern usage
            $directPattern = "window['tableFilters_' + tableId]";
            $directCount = substr_count($script, $directPattern);
            
            // Check for variable indirection pattern
            $variablePattern = "'tableFilters_' + tableId";
            $variableCount = substr_count($script, $variablePattern);
            
            // At least one method should use the dynamic pattern
            $this->assertGreaterThanOrEqual(1, $directCount + $variableCount,
                "Script {$i} should use dynamic tableId for filter variable access (direct or via variable)");
            
            // Verify the pattern appears in both checkSavedFilters() and loadData() methods
            // checkSavedFilters uses: window['tableFilters_' + tableId]
            // loadData uses: const filterVarName = 'tableFilters_' + tableId; window[filterVarName]
            $this->assertGreaterThanOrEqual(2, $variableCount,
                "Script {$i} should construct the filter variable name dynamically at least twice (checkSavedFilters and loadData)");
        }
    }

    /**
     * Test that instance logging includes table ID.
     * 
     * Validates: Requirement 8.3 - Debugging support
     */
    public function test_instance_logging_includes_table_id(): void
    {
        $id = $this->hashGenerator->generate('table', 'mysql', ['id', 'name']);
        $table = $this->createMockTable($id);
        
        $config = ['columns' => [], 'pagination' => []];
        $columns = [];
        $alpineData = ['data' => [], 'pageSize' => 10, 'totalRows' => 0];
        
        $scripts = $this->renderer->renderScripts($table, $config, $columns, $alpineData);
        
        // Assert console.log statements reference the tableId variable and include proper logging
        $this->assertStringContainsString("tableId", $scripts);
        $this->assertStringContainsString("'TanStack Table: Initializing instance', tableId", $scripts);
        $this->assertStringContainsString("'TanStack Table: Instance', tableId, 'initialized successfully'", $scripts);
        $this->assertStringContainsString("'TanStack Table: Destroying instance', tableId", $scripts);
    }
}
