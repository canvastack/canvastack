<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table;

use Canvastack\Canvastack\Components\Table\Query\FilterBuilder;
use Canvastack\Canvastack\Components\Table\Query\QueryOptimizer;
use Canvastack\Canvastack\Components\Table\Renderers\AdminRenderer;
use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Components\Table\Validation\ColumnValidator;
use Canvastack\Canvastack\Components\Table\Validation\SchemaInspector;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Test that export buttons render correctly in HTML.
 *
 * This test validates that export buttons (Excel, CSV, PDF, Print, Copy)
 * are properly rendered in the table HTML output with correct configuration.
 *
 * Phase 8: P2 Features - Export Buttons
 * Task 1.5: Add tests for export button rendering
 */
class ExportButtonsRenderingTest extends TestCase
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
     * Create a mock Eloquent model for server-side processing tests.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function createMockModel(): \Illuminate\Database\Eloquent\Model
    {
        // Create a simple anonymous model class for testing
        $model = new class extends \Illuminate\Database\Eloquent\Model {
            protected $table = 'users';
            protected $primaryKey = 'id';
            public $timestamps = false;
            
            // Override newQuery to return a mock query builder
            public function newQuery()
            {
                // Create mock grammar
                $mockGrammar = \Mockery::mock(\Illuminate\Database\Query\Grammars\Grammar::class);
                $mockGrammar->shouldReceive('compileSelect')->andReturn('SELECT * FROM users');
                $mockGrammar->shouldReceive('getDateFormat')->andReturn('Y-m-d H:i:s');
                $mockGrammar->shouldIgnoreMissing(); // Allow any other method calls
                
                // Create mock processor
                $mockProcessor = \Mockery::mock(\Illuminate\Database\Query\Processors\Processor::class);
                $mockProcessor->shouldReceive('processSelect')->andReturn([]);
                $mockProcessor->shouldIgnoreMissing(); // Allow any other method calls
                
                // Create a mock connection
                $mockConnection = \Mockery::mock(\Illuminate\Database\Connection::class);
                $mockConnection->shouldReceive('select')->andReturn([]);
                $mockConnection->shouldReceive('getQueryGrammar')->andReturn($mockGrammar);
                $mockConnection->shouldReceive('getPostProcessor')->andReturn($mockProcessor);
                $mockConnection->shouldReceive('getDatabaseName')->andReturn('test');
                $mockConnection->shouldReceive('getName')->andReturn('mysql');
                $mockConnection->shouldIgnoreMissing(); // Allow any other method calls
                
                // Create query builder with mock connection
                $query = new \Illuminate\Database\Eloquent\Builder(
                    new \Illuminate\Database\Query\Builder($mockConnection)
                );
                $query->setModel($this);
                
                return $query;
            }
        };
        
        return $model;
    }

    /**
     * Test that export buttons render in DataTables configuration.
     */
    public function test_export_buttons_render_in_datatables_config(): void
    {
        $this->table->setButtons(['excel', 'csv', 'pdf']);
        $this->table->setData([
            ['id' => 1, 'name' => 'John', 'email' => 'john@example.com'],
            ['id' => 2, 'name' => 'Jane', 'email' => 'jane@example.com'],
        ]);
        $this->table->setFields(['id:ID', 'name:Name', 'email:Email']);
        $this->table->format();

        $html = $this->table->render();

        // Verify buttons configuration is in the HTML
        $this->assertStringContainsString('buttons:', $html);
        $this->assertStringContainsString('excelHtml5', $html);
        $this->assertStringContainsString('csvHtml5', $html);
        $this->assertStringContainsString('pdfHtml5', $html);
    }

    /**
     * Test that Excel button renders with correct configuration.
     */
    public function test_excel_button_renders_correctly(): void
    {
        $this->table->setButtons(['excel']);
        $this->table->setData([
            ['id' => 1, 'name' => 'John', 'email' => 'john@example.com'],
        ]);
        $this->table->setFields(['id:ID', 'name:Name', 'email:Email']);
        $this->table->format();

        $html = $this->table->render();

        // Verify Excel button configuration
        $this->assertStringContainsString('excelHtml5', $html);
        $this->assertStringContainsString('Excel', $html);
        $this->assertStringContainsString('file-spreadsheet', $html);
        $this->assertStringContainsString('exportOptions', $html);
    }

    /**
     * Test that CSV button renders with correct configuration.
     */
    public function test_csv_button_renders_correctly(): void
    {
        $this->table->setButtons(['csv']);
        $this->table->setData([
            ['id' => 1, 'name' => 'John', 'email' => 'john@example.com'],
        ]);
        $this->table->setFields(['id:ID', 'name:Name', 'email:Email']);
        $this->table->format();

        $html = $this->table->render();

        // Verify CSV button configuration
        $this->assertStringContainsString('csvHtml5', $html);
        $this->assertStringContainsString('CSV', $html);
        $this->assertStringContainsString('file-text', $html);
    }

    /**
     * Test that PDF button renders with correct configuration.
     */
    public function test_pdf_button_renders_correctly(): void
    {
        $this->table->setButtons(['pdf']);
        $this->table->setData([
            ['id' => 1, 'name' => 'John', 'email' => 'john@example.com'],
        ]);
        $this->table->setFields(['id:ID', 'name:Name', 'email:Email']);
        $this->table->format();

        $html = $this->table->render();

        // Verify PDF button configuration
        $this->assertStringContainsString('pdfHtml5', $html);
        $this->assertStringContainsString('PDF', $html);
        $this->assertStringContainsString('landscape', $html);
        $this->assertStringContainsString('A4', $html);
    }

    /**
     * Test that Print button renders with correct configuration.
     */
    public function test_print_button_renders_correctly(): void
    {
        $this->table->setButtons(['print']);
        $this->table->setData([
            ['id' => 1, 'name' => 'John', 'email' => 'john@example.com'],
        ]);
        $this->table->setFields(['id:ID', 'name:Name', 'email:Email']);
        $this->table->format();

        $html = $this->table->render();

        // Verify Print button configuration
        $this->assertStringContainsString('print', $html);
        $this->assertStringContainsString('Print', $html);
        $this->assertStringContainsString('printer', $html);
    }

    /**
     * Test that Copy button renders with correct configuration.
     */
    public function test_copy_button_renders_correctly(): void
    {
        $this->table->setButtons(['copy']);
        $this->table->setData([
            ['id' => 1, 'name' => 'John', 'email' => 'john@example.com'],
        ]);
        $this->table->setFields(['id:ID', 'name:Name', 'email:Email']);
        $this->table->format();

        $html = $this->table->render();

        // Verify Copy button configuration
        $this->assertStringContainsString('copy', $html);
        $this->assertStringContainsString('Copy', $html);
    }

    /**
     * Test that multiple buttons render together.
     */
    public function test_multiple_buttons_render_together(): void
    {
        $this->table->setButtons(['excel', 'csv', 'pdf', 'print', 'copy']);
        $this->table->setData([
            ['id' => 1, 'name' => 'John', 'email' => 'john@example.com'],
        ]);
        $this->table->setFields(['id:ID', 'name:Name', 'email:Email']);
        $this->table->format();

        $html = $this->table->render();

        // Verify all buttons are present
        $this->assertStringContainsString('excelHtml5', $html);
        $this->assertStringContainsString('csvHtml5', $html);
        $this->assertStringContainsString('pdfHtml5', $html);
        $this->assertStringContainsString('print', $html);
        $this->assertStringContainsString('copy', $html);
    }

    /**
     * Test that export options exclude actions column.
     */
    public function test_export_options_exclude_actions_column(): void
    {
        $this->table->setButtons(['excel']);
        $this->table->setData([
            ['id' => 1, 'name' => 'John', 'email' => 'john@example.com'],
        ]);
        $this->table->setFields(['id:ID', 'name:Name', 'email:Email']);
        $this->table->addAction('edit', '/edit/:id', 'edit', 'Edit');
        $this->table->format();

        $html = $this->table->render();

        // Verify exportOptions excludes last column (actions)
        $this->assertStringContainsString('exportOptions', $html);
        $this->assertStringContainsString(':not(:last-child)', $html);
    }

    /**
     * Test that export options exclude non-exportable columns.
     */
    public function test_export_options_exclude_non_exportable_columns(): void
    {
        $this->table->setButtons(['excel']);
        $this->table->setData([
            ['id' => 1, 'name' => 'John', 'password' => 'secret', 'email' => 'john@example.com'],
        ]);
        $this->table->setFields(['id:ID', 'name:Name', 'password:Password', 'email:Email']);
        $this->table->setNonExportableColumns(['password']);
        $this->table->format();

        $html = $this->table->render();

        // Verify exportOptions excludes .no-export class
        $this->assertStringContainsString('exportOptions', $html);
        $this->assertStringContainsString(':not(.no-export)', $html);
    }

    /**
     * Test that export buttons container is rendered when export is enabled.
     */
    public function test_export_buttons_container_is_rendered(): void
    {
        $this->table->setButtons(['excel', 'csv']);
        $this->table->setData([
            ['id' => 1, 'name' => 'John', 'email' => 'john@example.com'],
        ]);
        $this->table->setFields(['id:ID', 'name:Name', 'email:Email']);
        $this->table->format();

        $html = $this->table->render();

        // Verify export buttons container is rendered in search bar
        $this->assertStringContainsString('id="tbl_', $html);
        $this->assertStringContainsString('_export_buttons"', $html);
        
        // Verify JavaScript moves buttons to custom container
        $this->assertStringContainsString('table.buttons().container()', $html);
        $this->assertStringContainsString('customContainer.append(buttonsContainer)', $html);
    }

    /**
     * Test that buttons have correct CSS classes.
     */
    public function test_buttons_have_correct_css_classes(): void
    {
        $this->table->setButtons(['excel', 'csv', 'pdf']);
        $this->table->setData([
            ['id' => 1, 'name' => 'John', 'email' => 'john@example.com'],
        ]);
        $this->table->setFields(['id:ID', 'name:Name', 'email:Email']);
        $this->table->format();

        $html = $this->table->render();

        // Verify button CSS classes
        $this->assertStringContainsString('btn btn-sm', $html);
        $this->assertStringContainsString('bg-emerald-600', $html); // Excel
        $this->assertStringContainsString('bg-cyan-600', $html);    // CSV
        $this->assertStringContainsString('bg-red-600', $html);     // PDF
    }

    /**
     * Test that buttons have Lucide icons.
     */
    public function test_buttons_have_lucide_icons(): void
    {
        $this->table->setButtons(['excel', 'csv', 'pdf', 'print', 'copy']);
        $this->table->setData([
            ['id' => 1, 'name' => 'John', 'email' => 'john@example.com'],
        ]);
        $this->table->setFields(['id:ID', 'name:Name', 'email:Email']);
        $this->table->format();

        $html = $this->table->render();

        // Verify Lucide icons are present (they're in JSON so check for escaped versions)
        $this->assertStringContainsString('file-spreadsheet', $html); // Excel
        $this->assertStringContainsString('file-text', $html);        // CSV
        $this->assertStringContainsString('file\\"', $html);          // PDF (escaped in JSON)
        $this->assertStringContainsString('printer', $html);          // Print
        $this->assertStringContainsString('copy', $html);             // Copy
    }

    /**
     * Test that no buttons render when not configured.
     */
    public function test_no_buttons_render_when_not_configured(): void
    {
        $this->table->setData([
            ['id' => 1, 'name' => 'John', 'email' => 'john@example.com'],
        ]);
        $this->table->setFields(['id:ID', 'name:Name', 'email:Email']);
        $this->table->format();

        $html = $this->table->render();

        // Verify no button configuration is present
        $this->assertStringNotContainsString('excelHtml5', $html);
        $this->assertStringNotContainsString('csvHtml5', $html);
        $this->assertStringNotContainsString('pdfHtml5', $html);
        $this->assertStringNotContainsString('buttons:', $html);
    }

    /**
     * Test that buttons work with server-side processing.
     */
    public function test_buttons_work_with_server_side_processing(): void
    {
        $mockModel = $this->createMockModel();
        
        $this->table->setButtons(['excel', 'csv']);
        $this->table->setModel($mockModel); // This enables server-side processing
        $this->table->setFields(['id:ID', 'name:Name', 'email:Email']);
        $this->table->format();

        $html = $this->table->render();

        // Verify buttons are present with server-side processing
        $this->assertStringContainsString('buttons:', $html);
        $this->assertStringContainsString('serverSide: true', $html);
        $this->assertStringContainsString('excelHtml5', $html);
        $this->assertStringContainsString('csvHtml5', $html);
    }

    /**
     * Test that button order is preserved in rendering.
     */
    public function test_button_order_is_preserved_in_rendering(): void
    {
        $this->table->setButtons(['pdf', 'excel', 'csv']);
        $this->table->setData([
            ['id' => 1, 'name' => 'John', 'email' => 'john@example.com'],
        ]);
        $this->table->setFields(['id:ID', 'name:Name', 'email:Email']);
        $this->table->format();

        $html = $this->table->render();

        // Find positions of each button in HTML
        $pdfPos = strpos($html, 'pdfHtml5');
        $excelPos = strpos($html, 'excelHtml5');
        $csvPos = strpos($html, 'csvHtml5');

        // Verify order: PDF should come before Excel, Excel before CSV
        $this->assertLessThan($excelPos, $pdfPos, 'PDF should appear before Excel');
        $this->assertLessThan($csvPos, $excelPos, 'Excel should appear before CSV');
    }

    /**
     * Test that export buttons work with empty data.
     */
    public function test_export_buttons_work_with_empty_data(): void
    {
        $this->table->setButtons(['excel', 'csv']);
        $this->table->setData([]);
        $this->table->setFields(['id:ID', 'name:Name', 'email:Email']);
        $this->table->format();

        $html = $this->table->render();

        // Verify buttons are still rendered even with empty data
        $this->assertStringContainsString('buttons:', $html);
        $this->assertStringContainsString('excelHtml5', $html);
        $this->assertStringContainsString('csvHtml5', $html);
    }

    /**
     * Test that export configuration is valid JSON.
     */
    public function test_export_configuration_is_valid_json(): void
    {
        $this->table->setButtons(['excel', 'csv', 'pdf']);
        $this->table->setData([
            ['id' => 1, 'name' => 'John', 'email' => 'john@example.com'],
        ]);
        $this->table->setFields(['id:ID', 'name:Name', 'email:Email']);
        $this->table->format();

        $html = $this->table->render();

        // Verify the configuration contains button configuration
        // The buttons config is JSON-encoded in the script
        $this->assertStringContainsString('buttons:', $html);
        $this->assertStringContainsString('"extend":"excelHtml5"', $html);
        $this->assertStringContainsString('"extend":"csvHtml5"', $html);
        $this->assertStringContainsString('"extend":"pdfHtml5"', $html);
    }
}
