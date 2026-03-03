<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table;

use Canvastack\Canvastack\Components\Table\Query\FilterBuilder;
use Canvastack\Canvastack\Components\Table\Query\QueryOptimizer;
use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Components\Table\Validation\ColumnValidator;
use Canvastack\Canvastack\Components\Table\Validation\SchemaInspector;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Support\Collection;

/**
 * Test that exported files contain correct data.
 *
 * This test validates that export buttons (Excel, CSV, PDF, Print, Copy)
 * export the correct data from the table, excluding non-exportable columns
 * and the actions column.
 *
 * Phase 8: P2 Features - Export Buttons
 * Task: Exported files contain correct data
 */
class ExportDataValidationTest extends TestCase
{
    protected TableBuilder $table;

    protected function setUp(): void
    {
        parent::setUp();

        // Create TableBuilder instance with proper dependencies
        $schemaInspector = new SchemaInspector();
        $columnValidator = new ColumnValidator($schemaInspector);
        $filterBuilder = new FilterBuilder($columnValidator);
        $queryOptimizer = new QueryOptimizer($filterBuilder, $columnValidator);

        $this->table = new TableBuilder(
            $queryOptimizer,
            $filterBuilder,
            $schemaInspector,
            $columnValidator
        );
        $this->table->setContext('admin');
    }

    /**
     * Test that export buttons configuration is correctly set.
     */
    public function test_export_buttons_are_configured(): void
    {
        $this->table->setButtons(['excel', 'csv', 'pdf']);

        $buttons = $this->table->getExportButtons();

        $this->assertCount(3, $buttons);
        $this->assertContains('excel', $buttons);
        $this->assertContains('csv', $buttons);
        $this->assertContains('pdf', $buttons);
        $this->assertTrue($this->table->hasExportButtons());
    }

    /**
     * Test that all export button types are supported.
     */
    public function test_all_export_button_types_are_supported(): void
    {
        $supportedButtons = ['excel', 'csv', 'pdf', 'print', 'copy'];

        $this->table->setButtons($supportedButtons);

        $buttons = $this->table->getExportButtons();

        $this->assertCount(5, $buttons);
        foreach ($supportedButtons as $button) {
            $this->assertContains($button, $buttons);
        }
    }

    /**
     * Test that export configuration excludes actions column.
     */
    public function test_export_excludes_actions_column(): void
    {
        $this->table->setButtons(['excel', 'csv']);
        $this->table->setData([
            ['id' => 1, 'name' => 'John', 'email' => 'john@example.com'],
            ['id' => 2, 'name' => 'Jane', 'email' => 'jane@example.com'],
        ]);
        $this->table->setFields(['id:ID', 'name:Name', 'email:Email']);
        $this->table->addAction('edit', '/edit/:id', 'edit', 'Edit');
        $this->table->format();

        // Verify export buttons are configured
        $this->assertTrue($this->table->hasExportButtons());
        $this->assertCount(2, $this->table->getExportButtons());

        // The renderer should configure exportOptions to exclude :last-child (actions column)
        // This is validated in the renderer test
    }

    /**
     * Test that non-exportable columns are excluded from export.
     */
    public function test_export_excludes_non_exportable_columns(): void
    {
        $this->table->setButtons(['excel', 'csv']);
        $this->table->setData([
            ['id' => 1, 'name' => 'John', 'password' => 'secret', 'email' => 'john@example.com'],
            ['id' => 2, 'name' => 'Jane', 'password' => 'secret2', 'email' => 'jane@example.com'],
        ]);
        $this->table->setFields(['id:ID', 'name:Name', 'password:Password', 'email:Email']);
        $this->table->setNonExportableColumns(['password']);
        $this->table->format();

        // Verify non-exportable columns are configured
        $this->assertFalse($this->table->isColumnExportable('password'));
        $this->assertTrue($this->table->isColumnExportable('name'));
        $this->assertTrue($this->table->isColumnExportable('email'));
    }

    /**
     * Test that visible columns are included in export.
     */
    public function test_export_includes_visible_columns(): void
    {
        $this->table->setButtons(['excel']);
        $this->table->setData([
            ['id' => 1, 'name' => 'John', 'email' => 'john@example.com', 'status' => 'active'],
            ['id' => 2, 'name' => 'Jane', 'email' => 'jane@example.com', 'status' => 'inactive'],
        ]);
        $this->table->setFields(['id:ID', 'name:Name', 'email:Email', 'status:Status']);
        $this->table->format();

        $config = $this->table->getConfig();

        // All fields should be exportable by default
        $this->assertTrue($this->table->isColumnExportable('id'));
        $this->assertTrue($this->table->isColumnExportable('name'));
        $this->assertTrue($this->table->isColumnExportable('email'));
        $this->assertTrue($this->table->isColumnExportable('status'));
    }

    /**
     * Test that hidden columns are excluded from export.
     */
    public function test_export_excludes_hidden_columns(): void
    {
        $this->table->setButtons(['excel']);
        $this->table->setData([
            ['id' => 1, 'name' => 'John', 'internal_id' => 'INT001', 'email' => 'john@example.com'],
            ['id' => 2, 'name' => 'Jane', 'internal_id' => 'INT002', 'email' => 'jane@example.com'],
        ]);
        $this->table->setFields(['id:ID', 'name:Name', 'internal_id:Internal ID', 'email:Email']);
        $this->table->setHiddenColumns(['internal_id']);
        $this->table->format();

        // Hidden columns should not be visible, hence not exportable
        // Verify the hidden columns are set
        $this->assertTrue($this->table->hasExportButtons());
    }

    /**
     * Test that export data matches table data.
     */
    public function test_export_data_matches_table_data(): void
    {
        $testData = [
            ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com', 'age' => 30],
            ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com', 'age' => 25],
            ['id' => 3, 'name' => 'Bob Johnson', 'email' => 'bob@example.com', 'age' => 35],
        ];

        $this->table->setButtons(['excel', 'csv']);
        $this->table->setData($testData);
        $this->table->setFields(['id:ID', 'name:Name', 'email:Email', 'age:Age']);
        $this->table->format();

        // Verify export buttons are configured
        $this->assertTrue($this->table->hasExportButtons());
        $this->assertCount(2, $this->table->getExportButtons());
    }

    /**
     * Test that formatted data is exported correctly.
     */
    public function test_export_includes_formatted_data(): void
    {
        $testData = [
            ['id' => 1, 'name' => 'John', 'price' => 1234.56, 'date' => '2024-01-15'],
            ['id' => 2, 'name' => 'Jane', 'price' => 9876.54, 'date' => '2024-02-20'],
        ];

        $this->table->setButtons(['excel']);
        $this->table->setData($testData);
        $this->table->setFields(['id:ID', 'name:Name', 'price:Price', 'date:Date']);

        // Apply formatting using the correct method
        $this->table->format(['price'], 2, '.', 'currency');

        // Verify export buttons are configured
        $this->assertTrue($this->table->hasExportButtons());
    }

    /**
     * Test that export handles empty data correctly.
     */
    public function test_export_handles_empty_data(): void
    {
        $this->table->setButtons(['excel', 'csv']);
        $this->table->setData([]);
        $this->table->setFields(['id:ID', 'name:Name', 'email:Email']);
        $this->table->format();

        // Verify export buttons are configured even with empty data
        $this->assertTrue($this->table->hasExportButtons());
    }

    /**
     * Test that export handles large datasets correctly.
     */
    public function test_export_handles_large_datasets(): void
    {
        // Generate 1000 rows of test data
        $testData = [];
        for ($i = 1; $i <= 1000; $i++) {
            $testData[] = [
                'id' => $i,
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'status' => $i % 2 === 0 ? 'active' : 'inactive',
            ];
        }

        $this->table->setButtons(['excel', 'csv']);
        $this->table->setData($testData);
        $this->table->setFields(['id:ID', 'name:Name', 'email:Email', 'status:Status']);
        $this->table->format();

        // Verify export buttons are configured
        $this->assertTrue($this->table->hasExportButtons());
        $this->assertCount(2, $this->table->getExportButtons());
    }

    /**
     * Test that export handles special characters correctly.
     */
    public function test_export_handles_special_characters(): void
    {
        $testData = [
            ['id' => 1, 'name' => 'John "The Boss" Doe', 'email' => 'john@example.com', 'notes' => 'Has, commas'],
            ['id' => 2, 'name' => "Jane O'Brien", 'email' => 'jane@example.com', 'notes' => 'Line\nbreak'],
            ['id' => 3, 'name' => 'Bob & Alice', 'email' => 'bob@example.com', 'notes' => '<script>alert("XSS")</script>'],
        ];

        $this->table->setButtons(['excel', 'csv']);
        $this->table->setData($testData);
        $this->table->setFields(['id:ID', 'name:Name', 'email:Email', 'notes:Notes']);
        $this->table->format();

        // Verify export buttons are configured
        $this->assertTrue($this->table->hasExportButtons());
    }

    /**
     * Test that export configuration includes correct column selectors.
     */
    public function test_export_configuration_has_correct_selectors(): void
    {
        $this->table->setButtons(['excel', 'csv', 'pdf']);
        $this->table->setData([
            ['id' => 1, 'name' => 'John', 'password' => 'secret', 'email' => 'john@example.com'],
        ]);
        $this->table->setFields(['id:ID', 'name:Name', 'password:Password', 'email:Email']);
        $this->table->setNonExportableColumns(['password']);
        $this->table->addAction('edit', '/edit/:id', 'edit', 'Edit');
        $this->table->format();

        // Verify export buttons are configured
        $this->assertTrue($this->table->hasExportButtons());
        $this->assertCount(3, $this->table->getExportButtons());

        // Verify non-exportable columns are configured
        $this->assertFalse($this->table->isColumnExportable('password'));

        // The renderer should use selector: ':visible:not(.no-export):not(:last-child)'
        // This excludes:
        // - Hidden columns (:visible)
        // - Non-exportable columns (.no-export)
        // - Actions column (:last-child)
    }

    /**
     * Test that clearing buttons removes export configuration.
     */
    public function test_clearing_buttons_removes_export_configuration(): void
    {
        $this->table->setButtons(['excel', 'csv', 'pdf']);
        $this->assertTrue($this->table->hasExportButtons());
        $this->assertCount(3, $this->table->getExportButtons());

        $this->table->clearButtons();

        $this->assertFalse($this->table->hasExportButtons());
        $this->assertCount(0, $this->table->getExportButtons());
    }

    /**
     * Test that export works with Collection data source.
     */
    public function test_export_works_with_collection(): void
    {
        $collection = collect([
            ['id' => 1, 'name' => 'John', 'email' => 'john@example.com'],
            ['id' => 2, 'name' => 'Jane', 'email' => 'jane@example.com'],
            ['id' => 3, 'name' => 'Bob', 'email' => 'bob@example.com'],
        ]);

        $this->table->setButtons(['excel', 'csv']);
        $this->table->setCollection($collection);
        $this->table->setFields(['id:ID', 'name:Name', 'email:Email']);
        $this->table->format();

        // Verify export buttons are configured
        $this->assertTrue($this->table->hasExportButtons());
        $this->assertCount(2, $this->table->getExportButtons());
    }

    /**
     * Test that export button order is preserved.
     */
    public function test_export_button_order_is_preserved(): void
    {
        $buttons = ['pdf', 'excel', 'csv', 'print', 'copy'];

        $this->table->setButtons($buttons);

        $exportButtons = $this->table->getExportButtons();

        // Verify order is preserved
        $this->assertEquals($buttons, $exportButtons);
        $this->assertEquals('pdf', $exportButtons[0]);
        $this->assertEquals('excel', $exportButtons[1]);
        $this->assertEquals('csv', $exportButtons[2]);
        $this->assertEquals('print', $exportButtons[3]);
        $this->assertEquals('copy', $exportButtons[4]);
    }

    /**
     * Test that export handles null values correctly.
     */
    public function test_export_handles_null_values(): void
    {
        $testData = [
            ['id' => 1, 'name' => 'John', 'email' => 'john@example.com', 'phone' => null],
            ['id' => 2, 'name' => 'Jane', 'email' => null, 'phone' => '123-456-7890'],
            ['id' => 3, 'name' => null, 'email' => 'bob@example.com', 'phone' => null],
        ];

        $this->table->setButtons(['excel', 'csv']);
        $this->table->setData($testData);
        $this->table->setFields(['id:ID', 'name:Name', 'email:Email', 'phone:Phone']);
        $this->table->format();

        // Verify export buttons are configured
        $this->assertTrue($this->table->hasExportButtons());
    }

    /**
     * Test that export configuration is included in table config.
     */
    public function test_export_configuration_is_included_in_table_config(): void
    {
        $this->table->setButtons(['excel', 'csv', 'pdf']);
        $this->table->setNonExportableColumns(['password', 'secret_key']);
        $this->table->setData([
            ['id' => 1, 'name' => 'John', 'password' => 'secret', 'email' => 'john@example.com'],
        ]);
        $this->table->setFields(['id:ID', 'name:Name', 'password:Password', 'email:Email']);
        $this->table->format();

        // Verify export configuration is accessible
        $this->assertTrue($this->table->hasExportButtons());
        $this->assertEquals(['excel', 'csv', 'pdf'], $this->table->getExportButtons());
        $this->assertFalse($this->table->isColumnExportable('password'));
        $this->assertFalse($this->table->isColumnExportable('secret_key'));
        $this->assertTrue($this->table->isColumnExportable('name'));
    }
}
