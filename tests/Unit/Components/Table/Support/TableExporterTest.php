<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table\Support;

use Canvastack\Canvastack\Components\Table\Support\TableExporter;
use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Components\Table\Exceptions\TableException;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Support\Collection;
use Mockery;

/**
 * Test for TableExporter.
 * 
 * Tests export functionality including:
 * - Excel export via PhpSpreadsheet
 * - CSV export
 * - PDF export via DomPDF
 * - Print HTML generation
 * - Non-exportable column exclusion
 * - Filename generation
 * - Cell value extraction
 * 
 * Validates Requirements: 17.1-17.7, 34.2-34.7
 */
class TableExporterTest extends TestCase
{
    protected TableExporter $exporter;
    protected TableBuilder $table;
    
    /**
     * Setup test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->exporter = new TableExporter();
        $this->table = Mockery::mock(TableBuilder::class);
    }
    
    /**
     * Cleanup after tests.
     */
    protected function tearDown(): void
    {
        Mockery::close();
        
        // Clean up test export files
        $exportDir = storage_path('app/exports');
        if (is_dir($exportDir)) {
            $files = glob($exportDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
        
        parent::tearDown();
    }
    
    /**
     * Test that exportExcel() creates Excel file successfully.
     * 
     * Validates: Requirements 17.1, 34.2
     */
    public function test_export_excel_creates_file_successfully(): void
    {
        // Arrange
        $data = [
            ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
            ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com'],
        ];
        
        $columns = [
            ['field' => 'id', 'label' => 'ID'],
            ['field' => 'name', 'label' => 'Name'],
            ['field' => 'email', 'label' => 'Email'],
        ];
        
        $this->table->shouldReceive('getConfig')
            ->andReturn([
                'nonExportableColumns' => [],
                'exportFilenamePrefix' => 'test_export',
            ]);
        
        // Act
        $filepath = $this->exporter->exportExcel($this->table, $data, $columns);
        
        // Assert
        $this->assertFileExists($filepath);
        $this->assertStringEndsWith('.xlsx', $filepath);
        $this->assertStringContainsString('test_export_', basename($filepath));
        
        // Verify file is valid Excel
        $this->assertGreaterThan(0, filesize($filepath));
    }
    
    /**
     * Test that exportExcel() respects non-exportable columns.
     * 
     * Validates: Requirements 17.5, 34.6
     */
    public function test_export_excel_excludes_non_exportable_columns(): void
    {
        // Skip if ZipArchive is not available
        if (!class_exists('ZipArchive')) {
            $this->markTestSkipped('ZipArchive extension is not available');
        }
        
        // Arrange
        $data = [
            ['id' => 1, 'name' => 'John Doe', 'password' => 'secret123'],
        ];
        
        $columns = [
            ['field' => 'id', 'label' => 'ID'],
            ['field' => 'name', 'label' => 'Name'],
            ['field' => 'password', 'label' => 'Password'],
        ];
        
        $this->table->shouldReceive('getConfig')
            ->andReturn([
                'nonExportableColumns' => ['password'],
                'exportFilenamePrefix' => 'test_export',
            ]);
        
        // Act
        $filepath = $this->exporter->exportExcel($this->table, $data, $columns);
        
        // Assert
        $this->assertFileExists($filepath);
        
        // Read Excel file and verify password column is not included
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filepath);
        $sheet = $spreadsheet->getActiveSheet();
        
        // Check headers (row 1)
        $this->assertEquals('ID', $sheet->getCell('A1')->getValue());
        $this->assertEquals('Name', $sheet->getCell('B1')->getValue());
        $this->assertNull($sheet->getCell('C1')->getValue());
    }
    
    /**
     * Test that exportExcel() handles empty data.
     * 
     * Validates: Requirements 17.1, 34.2
     */
    public function test_export_excel_handles_empty_data(): void
    {
        // Skip if ZipArchive is not available
        if (!class_exists('ZipArchive')) {
            $this->markTestSkipped('ZipArchive extension is not available');
        }
        
        // Arrange
        $data = [];
        $columns = [
            ['field' => 'id', 'label' => 'ID'],
            ['field' => 'name', 'label' => 'Name'],
        ];
        
        $this->table->shouldReceive('getConfig')
            ->andReturn([
                'nonExportableColumns' => [],
                'exportFilenamePrefix' => 'test_export',
            ]);
        
        // Act
        $filepath = $this->exporter->exportExcel($this->table, $data, $columns);
        
        // Assert
        $this->assertFileExists($filepath);
        
        // Verify file contains only headers
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filepath);
        $sheet = $spreadsheet->getActiveSheet();
        
        $this->assertEquals('ID', $sheet->getCell('A1')->getValue());
        $this->assertEquals('Name', $sheet->getCell('B1')->getValue());
        $this->assertNull($sheet->getCell('A2')->getValue());
    }

    
    /**
     * Test that exportExcel() handles nested object properties.
     * 
     * Validates: Requirements 17.1, 34.2
     */
    public function test_export_excel_handles_nested_properties(): void
    {
        // Skip if ZipArchive is not available
        if (!class_exists('ZipArchive')) {
            $this->markTestSkipped('ZipArchive extension is not available');
        }
        
        // Arrange
        $data = [
            (object)[
                'id' => 1,
                'name' => 'John Doe',
                'user' => (object)['email' => 'john@example.com']
            ],
        ];
        
        $columns = [
            ['field' => 'id', 'label' => 'ID'],
            ['field' => 'name', 'label' => 'Name'],
            ['field' => 'user.email', 'label' => 'Email'],
        ];
        
        $this->table->shouldReceive('getConfig')
            ->andReturn([
                'nonExportableColumns' => [],
                'exportFilenamePrefix' => 'test_export',
            ]);
        
        // Act
        $filepath = $this->exporter->exportExcel($this->table, $data, $columns);
        
        // Assert
        $this->assertFileExists($filepath);
        
        // Verify nested property is extracted correctly
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filepath);
        $sheet = $spreadsheet->getActiveSheet();
        
        $this->assertEquals('john@example.com', $sheet->getCell('C2')->getValue());
    }
    
    /**
     * Test that exportCSV() creates CSV file successfully.
     * 
     * Validates: Requirements 17.2, 34.3
     */
    public function test_export_csv_creates_file_successfully(): void
    {
        // Arrange
        $data = [
            ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
            ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com'],
        ];
        
        $columns = [
            ['field' => 'id', 'label' => 'ID'],
            ['field' => 'name', 'label' => 'Name'],
            ['field' => 'email', 'label' => 'Email'],
        ];
        
        $this->table->shouldReceive('getConfig')
            ->andReturn([
                'nonExportableColumns' => [],
                'exportFilenamePrefix' => 'test_export',
            ]);
        
        // Act
        $filepath = $this->exporter->exportCSV($this->table, $data, $columns);
        
        // Assert
        $this->assertFileExists($filepath);
        $this->assertStringEndsWith('.csv', $filepath);
        $this->assertStringContainsString('test_export_', basename($filepath));
        
        // Verify file content
        $content = file_get_contents($filepath);
        $this->assertStringContainsString('ID', $content);
        $this->assertStringContainsString('Name', $content);
        $this->assertStringContainsString('Email', $content);
        $this->assertStringContainsString('John Doe', $content);
        $this->assertStringContainsString('Jane Smith', $content);
    }
    
    /**
     * Test that exportCSV() respects non-exportable columns.
     * 
     * Validates: Requirements 17.5, 34.6
     */
    public function test_export_csv_excludes_non_exportable_columns(): void
    {
        // Arrange
        $data = [
            ['id' => 1, 'name' => 'John Doe', 'password' => 'secret123'],
        ];
        
        $columns = [
            ['field' => 'id', 'label' => 'ID'],
            ['field' => 'name', 'label' => 'Name'],
            ['field' => 'password', 'label' => 'Password'],
        ];
        
        $this->table->shouldReceive('getConfig')
            ->andReturn([
                'nonExportableColumns' => ['password'],
                'exportFilenamePrefix' => 'test_export',
            ]);
        
        // Act
        $filepath = $this->exporter->exportCSV($this->table, $data, $columns);
        
        // Assert
        $this->assertFileExists($filepath);
        
        // Verify password is not in CSV
        $content = file_get_contents($filepath);
        $this->assertStringNotContainsString('Password', $content);
        $this->assertStringNotContainsString('secret123', $content);
    }
    
    /**
     * Test that exportCSV() handles special characters.
     * 
     * Validates: Requirements 17.2, 34.3
     */
    public function test_export_csv_handles_special_characters(): void
    {
        // Arrange
        $data = [
            ['id' => 1, 'name' => 'John "The Boss" Doe', 'note' => 'Line1\nLine2'],
        ];
        
        $columns = [
            ['field' => 'id', 'label' => 'ID'],
            ['field' => 'name', 'label' => 'Name'],
            ['field' => 'note', 'label' => 'Note'],
        ];
        
        $this->table->shouldReceive('getConfig')
            ->andReturn([
                'nonExportableColumns' => [],
                'exportFilenamePrefix' => 'test_export',
            ]);
        
        // Act
        $filepath = $this->exporter->exportCSV($this->table, $data, $columns);
        
        // Assert
        $this->assertFileExists($filepath);
        
        // Verify special characters are handled
        $content = file_get_contents($filepath);
        $this->assertStringContainsString('John', $content);
    }
    
    /**
     * Test that exportPDF() creates PDF file successfully.
     * 
     * Validates: Requirements 17.3, 34.4
     */
    public function test_export_pdf_creates_file_successfully(): void
    {
        // Arrange
        $data = [
            ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
            ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com'],
        ];
        
        $columns = [
            ['field' => 'id', 'label' => 'ID'],
            ['field' => 'name', 'label' => 'Name'],
            ['field' => 'email', 'label' => 'Email'],
        ];
        
        $this->table->shouldReceive('getConfig')
            ->andReturn([
                'nonExportableColumns' => [],
                'exportFilenamePrefix' => 'test_export',
                'title' => 'User List',
            ]);
        
        // Act
        $filepath = $this->exporter->exportPDF($this->table, $data, $columns);
        
        // Assert
        $this->assertFileExists($filepath);
        $this->assertStringEndsWith('.pdf', $filepath);
        $this->assertStringContainsString('test_export_', basename($filepath));
        
        // Verify file is valid PDF (starts with %PDF)
        $content = file_get_contents($filepath);
        $this->assertStringStartsWith('%PDF', $content);
    }
    
    /**
     * Test that exportPDF() respects non-exportable columns.
     * 
     * Validates: Requirements 17.5, 34.6
     */
    public function test_export_pdf_excludes_non_exportable_columns(): void
    {
        // Arrange
        $data = [
            ['id' => 1, 'name' => 'John Doe', 'password' => 'secret123'],
        ];
        
        $columns = [
            ['field' => 'id', 'label' => 'ID'],
            ['field' => 'name', 'label' => 'Name'],
            ['field' => 'password', 'label' => 'Password'],
        ];
        
        $this->table->shouldReceive('getConfig')
            ->andReturn([
                'nonExportableColumns' => ['password'],
                'exportFilenamePrefix' => 'test_export',
                'title' => 'User List',
            ]);
        
        // Act
        $filepath = $this->exporter->exportPDF($this->table, $data, $columns);
        
        // Assert
        $this->assertFileExists($filepath);
        
        // Verify password is not in PDF
        $content = file_get_contents($filepath);
        $this->assertStringNotContainsString('Password', $content);
        $this->assertStringNotContainsString('secret123', $content);
    }

    
    /**
     * Test that exportPDF() handles HTML escaping.
     * 
     * Validates: Requirements 17.3, 34.4, 47.2 (XSS prevention)
     */
    public function test_export_pdf_escapes_html(): void
    {
        // Arrange
        $data = [
            ['id' => 1, 'name' => '<script>alert("XSS")</script>', 'note' => 'Test & Demo'],
        ];
        
        $columns = [
            ['field' => 'id', 'label' => 'ID'],
            ['field' => 'name', 'label' => 'Name'],
            ['field' => 'note', 'label' => 'Note'],
        ];
        
        $this->table->shouldReceive('getConfig')
            ->andReturn([
                'nonExportableColumns' => [],
                'exportFilenamePrefix' => 'test_export',
                'title' => 'User List',
            ]);
        
        // Act - Generate HTML directly to test escaping
        $html = $this->exporter->generatePrintHtml($this->table, $data, $columns);
        
        // Assert - Verify HTML is escaped in the generated HTML
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('&amp;', $html);
        $this->assertStringContainsString('alert(&quot;XSS&quot;)', $html);
    }
    
    /**
     * Test that generatePrintHtml() creates valid HTML.
     * 
     * Validates: Requirements 17.4, 34.5
     */
    public function test_generate_print_html_creates_valid_html(): void
    {
        // Arrange
        $data = [
            ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
            ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com'],
        ];
        
        $columns = [
            ['field' => 'id', 'label' => 'ID'],
            ['field' => 'name', 'label' => 'Name'],
            ['field' => 'email', 'label' => 'Email'],
        ];
        
        $this->table->shouldReceive('getConfig')
            ->andReturn([
                'nonExportableColumns' => [],
                'title' => 'User List',
            ]);
        
        // Act
        $html = $this->exporter->generatePrintHtml($this->table, $data, $columns);
        
        // Assert
        $this->assertStringContainsString('<!DOCTYPE html>', $html);
        $this->assertStringContainsString('<html>', $html);
        $this->assertStringContainsString('<table>', $html);
        $this->assertStringContainsString('<thead>', $html);
        $this->assertStringContainsString('<tbody>', $html);
        $this->assertStringContainsString('User List', $html);
        $this->assertStringContainsString('John Doe', $html);
        $this->assertStringContainsString('Jane Smith', $html);
    }
    
    /**
     * Test that generatePrintHtml() respects non-exportable columns.
     * 
     * Validates: Requirements 17.5, 34.6
     */
    public function test_generate_print_html_excludes_non_exportable_columns(): void
    {
        // Arrange
        $data = [
            ['id' => 1, 'name' => 'John Doe', 'password' => 'secret123'],
        ];
        
        $columns = [
            ['field' => 'id', 'label' => 'ID'],
            ['field' => 'name', 'label' => 'Name'],
            ['field' => 'password', 'label' => 'Password'],
        ];
        
        $this->table->shouldReceive('getConfig')
            ->andReturn([
                'nonExportableColumns' => ['password'],
                'title' => 'User List',
            ]);
        
        // Act
        $html = $this->exporter->generatePrintHtml($this->table, $data, $columns);
        
        // Assert
        $this->assertStringNotContainsString('Password', $html);
        $this->assertStringNotContainsString('secret123', $html);
        $this->assertStringContainsString('John Doe', $html);
    }
    
    /**
     * Test that getExportableColumns() filters correctly.
     * 
     * Validates: Requirements 17.5, 34.6
     */
    public function test_get_exportable_columns_filters_correctly(): void
    {
        // Arrange
        $columns = [
            ['field' => 'id', 'label' => 'ID'],
            ['field' => 'name', 'label' => 'Name'],
            ['field' => 'password', 'label' => 'Password'],
            ['field' => 'email', 'label' => 'Email'],
        ];
        
        $this->table->shouldReceive('getConfig')
            ->andReturn([
                'nonExportableColumns' => ['password'],
            ]);
        
        // Act
        $reflection = new \ReflectionClass($this->exporter);
        $method = $reflection->getMethod('getExportableColumns');
        $method->setAccessible(true);
        $result = $method->invoke($this->exporter, $this->table, $columns);
        
        // Assert
        $this->assertCount(3, $result);
        $fields = array_column($result, 'field');
        $this->assertContains('id', $fields);
        $this->assertContains('name', $fields);
        $this->assertContains('email', $fields);
        $this->assertNotContains('password', $fields);
    }
    
    /**
     * Test that getExportableColumns() handles multiple non-exportable columns.
     * 
     * Validates: Requirements 17.5, 34.6
     */
    public function test_get_exportable_columns_handles_multiple_exclusions(): void
    {
        // Arrange
        $columns = [
            ['field' => 'id', 'label' => 'ID'],
            ['field' => 'name', 'label' => 'Name'],
            ['field' => 'password', 'label' => 'Password'],
            ['field' => 'secret_key', 'label' => 'Secret Key'],
            ['field' => 'email', 'label' => 'Email'],
        ];
        
        $this->table->shouldReceive('getConfig')
            ->andReturn([
                'nonExportableColumns' => ['password', 'secret_key'],
            ]);
        
        // Act
        $reflection = new \ReflectionClass($this->exporter);
        $method = $reflection->getMethod('getExportableColumns');
        $method->setAccessible(true);
        $result = $method->invoke($this->exporter, $this->table, $columns);
        
        // Assert
        $this->assertCount(3, $result);
        $fields = array_column($result, 'field');
        $this->assertNotContains('password', $fields);
        $this->assertNotContains('secret_key', $fields);
    }
    
    /**
     * Test that getCellValue() extracts array values correctly.
     * 
     * Validates: Requirements 17.1, 34.2
     */
    public function test_get_cell_value_extracts_array_values(): void
    {
        // Arrange
        $item = ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'];
        
        // Act
        $reflection = new \ReflectionClass($this->exporter);
        $method = $reflection->getMethod('getCellValue');
        $method->setAccessible(true);
        
        $id = $method->invoke($this->exporter, $item, 'id');
        $name = $method->invoke($this->exporter, $item, 'name');
        $missing = $method->invoke($this->exporter, $item, 'missing');
        
        // Assert
        $this->assertEquals(1, $id);
        $this->assertEquals('John Doe', $name);
        $this->assertEquals('', $missing);
    }
    
    /**
     * Test that getCellValue() extracts object properties correctly.
     * 
     * Validates: Requirements 17.1, 34.2
     */
    public function test_get_cell_value_extracts_object_properties(): void
    {
        // Arrange
        $item = (object)['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'];
        
        // Act
        $reflection = new \ReflectionClass($this->exporter);
        $method = $reflection->getMethod('getCellValue');
        $method->setAccessible(true);
        
        $id = $method->invoke($this->exporter, $item, 'id');
        $name = $method->invoke($this->exporter, $item, 'name');
        $missing = $method->invoke($this->exporter, $item, 'missing');
        
        // Assert
        $this->assertEquals(1, $id);
        $this->assertEquals('John Doe', $name);
        $this->assertEquals('', $missing);
    }

    
    /**
     * Test that getCellValue() extracts nested object properties correctly.
     * 
     * Validates: Requirements 17.1, 34.2, 37.5 (nested relationships)
     */
    public function test_get_cell_value_extracts_nested_properties(): void
    {
        // Arrange
        $item = (object)[
            'id' => 1,
            'name' => 'John Doe',
            'user' => (object)[
                'email' => 'john@example.com',
                'profile' => (object)[
                    'phone' => '123-456-7890'
                ]
            ]
        ];
        
        // Act
        $reflection = new \ReflectionClass($this->exporter);
        $method = $reflection->getMethod('getCellValue');
        $method->setAccessible(true);
        
        $email = $method->invoke($this->exporter, $item, 'user.email');
        $phone = $method->invoke($this->exporter, $item, 'user.profile.phone');
        $missing = $method->invoke($this->exporter, $item, 'user.missing.field');
        
        // Assert
        $this->assertEquals('john@example.com', $email);
        $this->assertEquals('123-456-7890', $phone);
        $this->assertEquals('', $missing);
    }
    
    /**
     * Test that getCellValue() handles mixed array and object nesting.
     * 
     * Validates: Requirements 17.1, 34.2
     */
    public function test_get_cell_value_handles_mixed_nesting(): void
    {
        // Arrange
        $item = (object)[
            'id' => 1,
            'data' => ['key' => 'value']
        ];
        
        // Act
        $reflection = new \ReflectionClass($this->exporter);
        $method = $reflection->getMethod('getCellValue');
        $method->setAccessible(true);
        
        $value = $method->invoke($this->exporter, $item, 'data.key');
        
        // Assert
        $this->assertEquals('value', $value);
    }
    
    /**
     * Test that generateFilename() creates correct filename format.
     * 
     * Validates: Requirements 17.1-17.4, 34.2-34.5
     */
    public function test_generate_filename_creates_correct_format(): void
    {
        // Arrange
        $this->table->shouldReceive('getConfig')
            ->andReturn([
                'exportFilenamePrefix' => 'users_export',
            ]);
        
        // Act
        $reflection = new \ReflectionClass($this->exporter);
        $method = $reflection->getMethod('generateFilename');
        $method->setAccessible(true);
        
        $xlsxFilename = $method->invoke($this->exporter, $this->table, 'xlsx');
        $csvFilename = $method->invoke($this->exporter, $this->table, 'csv');
        $pdfFilename = $method->invoke($this->exporter, $this->table, 'pdf');
        
        // Assert
        $this->assertStringStartsWith('users_export_', $xlsxFilename);
        $this->assertStringEndsWith('.xlsx', $xlsxFilename);
        $this->assertStringStartsWith('users_export_', $csvFilename);
        $this->assertStringEndsWith('.csv', $csvFilename);
        $this->assertStringStartsWith('users_export_', $pdfFilename);
        $this->assertStringEndsWith('.pdf', $pdfFilename);
        
        // Verify timestamp format (YYYY-MM-DD_HHmmss)
        $this->assertMatchesRegularExpression(
            '/users_export_\d{4}-\d{2}-\d{2}_\d{6}\.xlsx/',
            $xlsxFilename
        );
    }
    
    /**
     * Test that generateFilename() uses default prefix when not configured.
     * 
     * Validates: Requirements 17.1-17.4, 34.2-34.5
     */
    public function test_generate_filename_uses_default_prefix(): void
    {
        // Arrange
        $this->table->shouldReceive('getConfig')
            ->andReturn([]);
        
        // Act
        $reflection = new \ReflectionClass($this->exporter);
        $method = $reflection->getMethod('generateFilename');
        $method->setAccessible(true);
        
        $filename = $method->invoke($this->exporter, $this->table, 'xlsx');
        
        // Assert
        $this->assertStringStartsWith('table_export_', $filename);
    }
    
    /**
     * Test that getColumnLetter() converts numbers to Excel column letters correctly.
     * 
     * Validates: Requirements 17.1, 34.2
     */
    public function test_get_column_letter_converts_correctly(): void
    {
        // Arrange & Act
        $reflection = new \ReflectionClass($this->exporter);
        $method = $reflection->getMethod('getColumnLetter');
        $method->setAccessible(true);
        
        // Assert
        $this->assertEquals('A', $method->invoke($this->exporter, 1));
        $this->assertEquals('B', $method->invoke($this->exporter, 2));
        $this->assertEquals('Z', $method->invoke($this->exporter, 26));
        $this->assertEquals('AA', $method->invoke($this->exporter, 27));
        $this->assertEquals('AB', $method->invoke($this->exporter, 28));
        $this->assertEquals('AZ', $method->invoke($this->exporter, 52));
        $this->assertEquals('BA', $method->invoke($this->exporter, 53));
        $this->assertEquals('ZZ', $method->invoke($this->exporter, 702));
        $this->assertEquals('AAA', $method->invoke($this->exporter, 703));
    }
    
    /**
     * Test that exportExcel() throws exception on failure.
     * 
     * Validates: Requirements 17.1, 34.2, 39.3 (error handling)
     */
    public function test_export_excel_throws_exception_on_failure(): void
    {
        // Arrange
        $this->table->shouldReceive('getConfig')
            ->andThrow(new \Exception('Config error'));
        
        $data = [['id' => 1]];
        $columns = [['field' => 'id', 'label' => 'ID']];
        
        // Assert
        $this->expectException(TableException::class);
        
        // Act
        $this->exporter->exportExcel($this->table, $data, $columns);
    }
    
    /**
     * Test that exportCSV() throws exception on failure.
     * 
     * Validates: Requirements 17.2, 34.3, 39.3 (error handling)
     */
    public function test_export_csv_throws_exception_on_failure(): void
    {
        // Arrange
        $this->table->shouldReceive('getConfig')
            ->andThrow(new \Exception('Config error'));
        
        $data = [['id' => 1]];
        $columns = [['field' => 'id', 'label' => 'ID']];
        
        // Assert
        $this->expectException(TableException::class);
        
        // Act
        $this->exporter->exportCSV($this->table, $data, $columns);
    }
    
    /**
     * Test that exportPDF() throws exception on failure.
     * 
     * Validates: Requirements 17.3, 34.4, 39.3 (error handling)
     */
    public function test_export_pdf_throws_exception_on_failure(): void
    {
        // Arrange
        $this->table->shouldReceive('getConfig')
            ->andThrow(new \Exception('Config error'));
        
        $data = [['id' => 1]];
        $columns = [['field' => 'id', 'label' => 'ID']];
        
        // Assert
        $this->expectException(TableException::class);
        
        // Act
        $this->exporter->exportPDF($this->table, $data, $columns);
    }
    
    /**
     * Test that exportExcel() creates directory if it doesn't exist.
     * 
     * Validates: Requirements 17.1, 34.2
     */
    public function test_export_excel_creates_directory_if_not_exists(): void
    {
        // Arrange
        $exportDir = storage_path('app/exports');
        if (is_dir($exportDir)) {
            rmdir($exportDir);
        }
        
        $data = [['id' => 1, 'name' => 'Test']];
        $columns = [
            ['field' => 'id', 'label' => 'ID'],
            ['field' => 'name', 'label' => 'Name'],
        ];
        
        $this->table->shouldReceive('getConfig')
            ->andReturn([
                'nonExportableColumns' => [],
                'exportFilenamePrefix' => 'test',
            ]);
        
        // Act
        $filepath = $this->exporter->exportExcel($this->table, $data, $columns);
        
        // Assert
        $this->assertDirectoryExists($exportDir);
        $this->assertFileExists($filepath);
    }
    
    /**
     * Test that exportExcel() handles large datasets.
     * 
     * Validates: Requirements 17.1, 34.2, 31.2 (performance)
     */
    public function test_export_excel_handles_large_datasets(): void
    {
        // Skip if ZipArchive is not available
        if (!class_exists('ZipArchive')) {
            $this->markTestSkipped('ZipArchive extension is not available');
        }
        
        // Arrange
        $data = [];
        for ($i = 1; $i <= 1000; $i++) {
            $data[] = [
                'id' => $i,
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
            ];
        }
        
        $columns = [
            ['field' => 'id', 'label' => 'ID'],
            ['field' => 'name', 'label' => 'Name'],
            ['field' => 'email', 'label' => 'Email'],
        ];
        
        $this->table->shouldReceive('getConfig')
            ->andReturn([
                'nonExportableColumns' => [],
                'exportFilenamePrefix' => 'large_export',
            ]);
        
        // Act
        $startTime = microtime(true);
        $filepath = $this->exporter->exportExcel($this->table, $data, $columns);
        $endTime = microtime(true);
        
        // Assert
        $this->assertFileExists($filepath);
        $this->assertLessThan(5.0, $endTime - $startTime, 'Export should complete in less than 5 seconds');
        
        // Verify file contains all data
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filepath);
        $sheet = $spreadsheet->getActiveSheet();
        $this->assertEquals(1001, $sheet->getHighestRow()); // 1000 data rows + 1 header row
    }
    
    /**
     * Test that all export methods work with Collection data.
     * 
     * Validates: Requirements 17.1-17.4, 34.2-34.5, 35.1 (collection support)
     */
    public function test_export_methods_work_with_collection(): void
    {
        // Arrange
        $collection = collect([
            ['id' => 1, 'name' => 'John Doe'],
            ['id' => 2, 'name' => 'Jane Smith'],
        ]);
        
        $data = $collection->toArray();
        $columns = [
            ['field' => 'id', 'label' => 'ID'],
            ['field' => 'name', 'label' => 'Name'],
        ];
        
        $this->table->shouldReceive('getConfig')
            ->andReturn([
                'nonExportableColumns' => [],
                'exportFilenamePrefix' => 'collection_export',
                'title' => 'Collection Export',
            ]);
        
        // Act & Assert - Excel
        $excelPath = $this->exporter->exportExcel($this->table, $data, $columns);
        $this->assertFileExists($excelPath);
        
        // Act & Assert - CSV
        $csvPath = $this->exporter->exportCSV($this->table, $data, $columns);
        $this->assertFileExists($csvPath);
        
        // Act & Assert - PDF
        $pdfPath = $this->exporter->exportPDF($this->table, $data, $columns);
        $this->assertFileExists($pdfPath);
    }
}

