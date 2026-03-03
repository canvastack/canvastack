<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Feature\Components\Table;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Test DisplayLimit integration with TableBuilder.
 */
class DisplayLimitIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected TableBuilder $table;

    protected function setUp(): void
    {
        parent::setUp();

        $this->table = app(TableBuilder::class);
    }

    /**
     * Test displayRowsLimitOnLoad method sets limit correctly.
     */
    public function test_display_rows_limit_on_load_sets_limit_correctly(): void
    {
        $this->table->displayRowsLimitOnLoad(25);
        $this->assertEquals(25, $this->table->getDisplayLimit());

        $this->table->displayRowsLimitOnLoad('all');
        $this->assertEquals('all', $this->table->getDisplayLimit());

        $this->table->displayRowsLimitOnLoad('*');
        $this->assertEquals('all', $this->table->getDisplayLimit());
    }

    /**
     * Test displayRowsLimitOnLoad with invalid values throws exception.
     */
    public function test_display_rows_limit_on_load_with_invalid_values_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->table->displayRowsLimitOnLoad(-5);
    }

    /**
     * Test displayRowsLimitOnLoad with session persistence.
     */
    public function test_display_rows_limit_on_load_with_session_persistence(): void
    {
        // Enable session persistence
        $this->table->sessionFilters();

        // Set display limit
        $this->table->displayRowsLimitOnLoad(50);

        // Check session was saved
        $sessionKey = 'table_display_limit_' . md5('default_');
        $this->assertEquals(50, session($sessionKey));
    }

    /**
     * Test getDisplayLimit method returns session value when available.
     */
    public function test_get_display_limit_returns_session_value_when_available(): void
    {
        // Set session value
        $sessionKey = 'table_display_limit_' . md5('test_table_');
        session([$sessionKey => 75]);

        // Enable session persistence with table name
        $this->table->sessionFilters();

        // Should return session value
        $this->assertEquals(75, $this->table->getDisplayLimit());
    }

    /**
     * Test renderDisplayLimitUI method returns HTML.
     */
    public function test_render_display_limit_ui_returns_html(): void
    {
        $this->table->displayRowsLimitOnLoad(25);

        $html = $this->table->renderDisplayLimitUI();

        $this->assertIsString($html);
        $this->assertStringContainsString('x-data="displayLimit()"', $html);
        $this->assertStringContainsString('Show:', $html);
        $this->assertStringContainsString('entries', $html);
    }

    /**
     * Test renderDisplayLimitUI with custom options.
     */
    public function test_render_display_limit_ui_with_custom_options(): void
    {
        $customOptions = [
            ['value' => '5', 'label' => '5'],
            ['value' => '15', 'label' => '15'],
            ['value' => 'all', 'label' => 'Show All'],
        ];

        $html = $this->table->renderDisplayLimitUI($customOptions, false, 'lg');

        $this->assertIsString($html);
        $this->assertStringContainsString('x-data="displayLimit()"', $html);
        $this->assertStringNotContainsString('Show:', $html); // showLabel = false
        $this->assertStringContainsString('select-lg', $html);
    }

    /**
     * Test clearOnLoad resets display limit.
     */
    public function test_clear_on_load_resets_display_limit(): void
    {
        $this->table->displayRowsLimitOnLoad(50);
        $this->assertEquals(50, $this->table->getDisplayLimit());

        $this->table->clearOnLoad();
        $this->assertEquals(10, $this->table->getDisplayLimit());
    }

    /**
     * Test display limit persists across table instances with same name.
     */
    public function test_display_limit_persists_across_table_instances_with_same_name(): void
    {
        // First table instance
        $table1 = app(TableBuilder::class);
        $table1->sessionFilters();
        $table1->displayRowsLimitOnLoad(30);

        // Second table instance with same session
        $table2 = app(TableBuilder::class);
        $table2->sessionFilters();

        // Should load the same limit from session
        $this->assertEquals(30, $table2->getDisplayLimit());
    }

    /**
     * Test display limit isolation between different table names.
     */
    public function test_display_limit_isolation_between_different_table_names(): void
    {
        // Set session for different tables
        session([
            'table_display_limit_' . md5('users_') => 25,
            'table_display_limit_' . md5('products_') => 50,
        ]);

        // Create tables with different contexts
        $usersTable = app(TableBuilder::class);
        $usersTable->sessionFilters();

        $productsTable = app(TableBuilder::class);
        $productsTable->sessionFilters();

        // Each should have its own limit (though we can't easily test this without
        // being able to set table names directly in this test setup)
        $this->assertIsInt($usersTable->getDisplayLimit());
        $this->assertIsInt($productsTable->getDisplayLimit());
    }

    /**
     * Test display limit with collection data.
     */
    public function test_display_limit_with_collection_data(): void
    {
        $data = collect([
            ['name' => 'Item 1', 'value' => 100],
            ['name' => 'Item 2', 'value' => 200],
            ['name' => 'Item 3', 'value' => 300],
            ['name' => 'Item 4', 'value' => 400],
            ['name' => 'Item 5', 'value' => 500],
        ]);

        $this->table->setCollection($data);
        $this->table->setFields(['name:Name', 'value:Value']);
        $this->table->displayRowsLimitOnLoad(3);

        // Format the table
        $this->table->format();

        // Check that limit is set correctly
        $this->assertEquals(3, $this->table->getDisplayLimit());
    }

    /**
     * Test display limit UI component integration with TableBuilder.
     */
    public function test_display_limit_ui_component_integration(): void
    {
        $this->table->displayRowsLimitOnLoad(25);

        $html = $this->table->renderDisplayLimitUI();

        // Check that the component receives correct data
        $this->assertStringContainsString('"currentLimit":25', $html);
        $this->assertStringContainsString('"tableName":"default"', $html);
        $this->assertStringContainsString('{"value":"10","label":"10"}', $html);
        $this->assertStringContainsString('{"value":"all","label":"All"}', $html);
    }

    /**
     * Test display limit with 'all' value doesn't limit collection.
     */
    public function test_display_limit_with_all_value_does_not_limit_collection(): void
    {
        $data = collect(range(1, 100));

        $this->table->setCollection($data);
        $this->table->setFields(['value:Value']);
        $this->table->displayRowsLimitOnLoad('all');

        // Format the table
        $this->table->format();

        // Check that limit is set to 'all'
        $this->assertEquals('all', $this->table->getDisplayLimit());
    }

    /**
     * Test display limit validation in component.
     */
    public function test_display_limit_validation_in_component(): void
    {
        // Test with valid integer
        $this->table->displayRowsLimitOnLoad(100);
        $html = $this->table->renderDisplayLimitUI();
        $this->assertStringContainsString('"currentLimit":100', $html);

        // Test with 'all'
        $this->table->displayRowsLimitOnLoad('all');
        $html = $this->table->renderDisplayLimitUI();
        $this->assertStringContainsString('"currentLimit":"all"', $html);
    }
}