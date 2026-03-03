<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;

/**
 * Test display limit integration with DataTables.
 * 
 * Task 3.1.3: Integrate with DataTables
 * - Update DataTables configuration
 * - Implement pagination update
 * - Implement performance optimization
 */
class DisplayLimitDataTablesIntegrationTest extends TestCase
{
    protected TableBuilder $table;

    protected function setUp(): void
    {
        parent::setUp();
        $this->table = $this->createTableBuilder();
    }

    /**
     * Test that display limit is passed to DataTables configuration.
     */
    public function test_display_limit_passed_to_datatables_config(): void
    {
        // Set display limit
        $this->table->displayRowsLimitOnLoad(25);
        $this->table->setData([
            ['id' => 1, 'name' => 'Test 1'],
            ['id' => 2, 'name' => 'Test 2'],
        ]);
        $this->table->setFields(['id', 'name']);
        $this->table->format();

        // Render table
        $html = $this->table->render();

        // Check that pageLength is set to 25 in DataTables config
        $this->assertStringContainsString('pageLength: 25', $html);
    }

    /**
     * Test that 'all' display limit is converted to -1 for DataTables.
     */
    public function test_all_display_limit_converted_to_negative_one(): void
    {
        // Set display limit to 'all'
        $this->table->displayRowsLimitOnLoad('all');
        $this->table->setData([
            ['id' => 1, 'name' => 'Test 1'],
            ['id' => 2, 'name' => 'Test 2'],
        ]);
        $this->table->setFields(['id', 'name']);
        $this->table->format();

        // Render table
        $html = $this->table->render();

        // Check that pageLength is set to -1 in DataTables config
        $this->assertStringContainsString('pageLength: -1', $html);
    }

    /**
     * Test that '*' display limit is converted to -1 for DataTables.
     */
    public function test_asterisk_display_limit_converted_to_negative_one(): void
    {
        // Set display limit to '*'
        $this->table->displayRowsLimitOnLoad('*');
        $this->table->setData([
            ['id' => 1, 'name' => 'Test 1'],
            ['id' => 2, 'name' => 'Test 2'],
        ]);
        $this->table->setFields(['id', 'name']);
        $this->table->format();

        // Render table
        $html = $this->table->render();

        // Check that pageLength is set to -1 in DataTables config
        $this->assertStringContainsString('pageLength: -1', $html);
    }

    /**
     * Test that DataTables script includes display-limit-changed event listener.
     */
    public function test_datatables_script_includes_display_limit_event_listener(): void
    {
        $this->table->setData([
            ['id' => 1, 'name' => 'Test 1'],
        ]);
        $this->table->setFields(['id', 'name']);
        $this->table->format();

        $html = $this->table->render();

        // Check for event listener
        $this->assertStringContainsString("addEventListener('display-limit-changed'", $html);
        $this->assertStringContainsString('table.page.len(pageLength).draw()', $html);
    }

    /**
     * Test that DataTables script includes updateDisplayLimit method.
     */
    public function test_datatables_script_includes_update_display_limit_method(): void
    {
        $this->table->setData([
            ['id' => 1, 'name' => 'Test 1'],
        ]);
        $this->table->setFields(['id', 'name']);
        $this->table->format();

        $html = $this->table->render();

        // Check for updateDisplayLimit method
        $this->assertStringContainsString('table.updateDisplayLimit = function(limit)', $html);
    }

    /**
     * Test that default display limit (10) is used when not set.
     */
    public function test_default_display_limit_used_when_not_set(): void
    {
        $this->table->setData([
            ['id' => 1, 'name' => 'Test 1'],
        ]);
        $this->table->setFields(['id', 'name']);
        $this->table->format();

        $html = $this->table->render();

        // Check that default pageLength is 10
        $this->assertStringContainsString('pageLength: 10', $html);
    }

    /**
     * Test that session-persisted display limit is used.
     */
    public function test_session_persisted_display_limit_used(): void
    {
        // Enable session persistence
        $this->table->sessionFilters();
        
        // Set display limit
        $this->table->displayRowsLimitOnLoad(50);
        
        $this->table->setData([
            ['id' => 1, 'name' => 'Test 1'],
        ]);
        $this->table->setFields(['id', 'name']);
        $this->table->format();

        $html = $this->table->render();

        // Check that pageLength is set to 50
        $this->assertStringContainsString('pageLength: 50', $html);
    }

    /**
     * Test that lengthMenu includes 'All' option.
     */
    public function test_length_menu_includes_all_option(): void
    {
        $this->table->setData([
            ['id' => 1, 'name' => 'Test 1'],
        ]);
        $this->table->setFields(['id', 'name']);
        $this->table->format();

        $html = $this->table->render();

        // Check that lengthMenu includes -1 and 'All'
        $this->assertStringContainsString('lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, \'All\']]', $html);
    }

    /**
     * Test that both AdminRenderer and PublicRenderer support display limit integration.
     */
    public function test_both_renderers_support_display_limit_integration(): void
    {
        // Test AdminRenderer
        $this->table->setContext('admin');
        $this->table->displayRowsLimitOnLoad(25);
        $this->table->setData([['id' => 1, 'name' => 'Test']]);
        $this->table->setFields(['id', 'name']);
        $this->table->format();

        $adminHtml = $this->table->render();
        $this->assertStringContainsString('pageLength: 25', $adminHtml);
        $this->assertStringContainsString('updateDisplayLimit', $adminHtml);

        // Reset and test PublicRenderer
        $this->table = $this->createTableBuilder();
        $this->table->setContext('public');
        $this->table->displayRowsLimitOnLoad(50);
        $this->table->setData([['id' => 1, 'name' => 'Test']]);
        $this->table->setFields(['id', 'name']);
        $this->table->format();

        $publicHtml = $this->table->render();
        $this->assertStringContainsString('pageLength: 50', $publicHtml);
        $this->assertStringContainsString('updateDisplayLimit', $publicHtml);
    }

    /**
     * Test that invalid display limits fall back to default.
     */
    public function test_invalid_display_limits_fall_back_to_default(): void
    {
        // This should throw an exception, but let's test the fallback behavior
        try {
            $this->table->displayRowsLimitOnLoad(-5);
            $this->fail('Expected InvalidArgumentException was not thrown');
        } catch (\InvalidArgumentException $e) {
            // Expected behavior
            $this->assertStringContainsString('Invalid display limit', $e->getMessage());
        }

        // Test with valid data
        $this->table->setData([['id' => 1, 'name' => 'Test']]);
        $this->table->setFields(['id', 'name']);
        $this->table->format();

        $html = $this->table->render();

        // Should use default limit (10)
        $this->assertStringContainsString('pageLength: 10', $html);
    }
}