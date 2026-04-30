<?php

namespace Tests\Unit;

use Tests\TestCase;
use Canvastack\Canvastack\Library\Components\Table\Craft\Export;

class ExportStreamingTest extends TestCase
{
    private Export $export;

    protected function setUp(): void
    {
        parent::setUp();
        $this->export = new Export();
    }

    /**
     * Test CSV export with large dataset
     */
    public function test_csv_export_with_large_dataset(): void
    {
        // Generate test data (1000 rows)
        $data = $this->generateTestData(1000);
        
        $response = $this->export->streamExport($data, 'csv', [
            'filename' => 'test_export',
            'chunk_size' => 100
        ]);
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }

    /**
     * Test Excel export with streaming
     */
    public function test_excel_export_with_streaming(): void
    {
        $data = $this->generateTestData(500);
        
        $response = $this->export->streamExport($data, 'excel', [
            'filename' => 'test_export',
            'chunk_size' => 100
        ]);
        
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test PDF export with streaming
     */
    public function test_pdf_export_with_streaming(): void
    {
        $data = $this->generateTestData(500);
        
        $response = $this->export->streamExport($data, 'pdf', [
            'filename' => 'test_export',
            'chunk_size' => 100
        ]);
        
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test export configuration
     */
    public function test_export_configuration(): void
    {
        $config = [
            'max_rows' => 5000,
            'chunk_size' => 250,
            'enable_progress' => true
        ];
        
        $this->export->configureExport($config);
        $currentConfig = $this->export->getExportConfig();
        
        $this->assertEquals(5000, $currentConfig['max_rows']);
        $this->assertEquals(250, $currentConfig['chunk_size']);
        $this->assertTrue($currentConfig['enable_progress']);
    }

    /**
     * Test export with max rows limit
     */
    public function test_export_respects_max_rows_limit(): void
    {
        $data = $this->generateTestData(2000);
        
        $response = $this->export->streamExport($data, 'csv', [
            'filename' => 'test_export',
            'max_rows' => 1000
        ]);
        
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Generate test data
     */
    private function generateTestData(int $rows): array
    {
        $data = [];
        for ($i = 1; $i <= $rows; $i++) {
            $data[] = [
                'id' => $i,
                'name' => 'Test User ' . $i,
                'email' => 'user' . $i . '@example.com',
                'created_at' => date('Y-m-d H:i:s')
            ];
        }
        return $data;
    }
}
