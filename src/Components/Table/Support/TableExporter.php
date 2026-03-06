<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Table\Support;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Components\Table\Exceptions\TableException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv as CsvWriter;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Collection;

/**
 * TableExporter - Export table data to various formats
 * 
 * Provides export functionality for TanStack tables including:
 * - Excel export via PhpSpreadsheet
 * - CSV export
 * - PDF export via DomPDF
 * - Print functionality
 * - Respects non-exportable columns
 * 
 * @package Canvastack\Canvastack\Components\Table\Support
 */
class TableExporter
{
    /**
     * Export data to Excel format
     *
     * @param TableBuilder $table
     * @param array $data
     * @param array $columns
     * @return string Path to generated file
     * @throws TableException
     */
    public function exportExcel(TableBuilder $table, array $data, array $columns): string
    {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Filter out non-exportable columns
            $exportableColumns = $this->getExportableColumns($table, $columns);
            
            // Set headers
            $col = 1;
            foreach ($exportableColumns as $column) {
                $sheet->setCellValue($this->getColumnLetter($col) . '1', $column['label']);
                $col++;
            }
            
            // Style header row
            $headerStyle = [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E0E0E0']
                ]
            ];
            $sheet->getStyle('A1:' . $this->getColumnLetter(count($exportableColumns)) . '1')
                  ->applyFromArray($headerStyle);
            
            // Set data
            $row = 2;
            foreach ($data as $item) {
                $col = 1;
                foreach ($exportableColumns as $column) {
                    $value = $this->getCellValue($item, $column['field']);
                    $sheet->setCellValue($this->getColumnLetter($col) . $row, $value);
                    $col++;
                }
                $row++;
            }
            
            // Auto-size columns
            foreach (range(1, count($exportableColumns)) as $col) {
                $sheet->getColumnDimension($this->getColumnLetter($col))->setAutoSize(true);
            }
            
            // Generate filename
            $filename = $this->generateFilename($table, 'xlsx');
            $filepath = storage_path('app/exports/' . $filename);
            
            // Ensure directory exists
            if (!is_dir(dirname($filepath))) {
                mkdir(dirname($filepath), 0755, true);
            }
            
            // Write file
            $writer = new Xlsx($spreadsheet);
            $writer->save($filepath);
            
            return $filepath;
            
        } catch (\Exception $e) {
            throw new TableException(
                __('components.table.export.excel_error', ['error' => $e->getMessage()])
            );
        }
    }
    
    /**
     * Export data to CSV format
     *
     * @param TableBuilder $table
     * @param array $data
     * @param array $columns
     * @return string Path to generated file
     * @throws TableException
     */
    public function exportCSV(TableBuilder $table, array $data, array $columns): string
    {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Filter out non-exportable columns
            $exportableColumns = $this->getExportableColumns($table, $columns);
            
            // Set headers
            $col = 1;
            foreach ($exportableColumns as $column) {
                $sheet->setCellValue($this->getColumnLetter($col) . '1', $column['label']);
                $col++;
            }
            
            // Set data
            $row = 2;
            foreach ($data as $item) {
                $col = 1;
                foreach ($exportableColumns as $column) {
                    $value = $this->getCellValue($item, $column['field']);
                    $sheet->setCellValue($this->getColumnLetter($col) . $row, $value);
                    $col++;
                }
                $row++;
            }
            
            // Generate filename
            $filename = $this->generateFilename($table, 'csv');
            $filepath = storage_path('app/exports/' . $filename);
            
            // Ensure directory exists
            if (!is_dir(dirname($filepath))) {
                mkdir(dirname($filepath), 0755, true);
            }
            
            // Write file
            $writer = new CsvWriter($spreadsheet);
            $writer->save($filepath);
            
            return $filepath;
            
        } catch (\Exception $e) {
            throw new TableException(
                __('components.table.export.csv_error', ['error' => $e->getMessage()])
            );
        }
    }

    
    /**
     * Export data to PDF format
     *
     * @param TableBuilder $table
     * @param array $data
     * @param array $columns
     * @return string Path to generated file
     * @throws TableException
     */
    public function exportPDF(TableBuilder $table, array $data, array $columns): string
    {
        try {
            // Filter out non-exportable columns
            $exportableColumns = $this->getExportableColumns($table, $columns);
            
            // Generate HTML table
            $html = $this->generatePDFHtml($table, $data, $exportableColumns);
            
            // Configure DomPDF
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'DejaVu Sans');
            
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();
            
            // Generate filename
            $filename = $this->generateFilename($table, 'pdf');
            $filepath = storage_path('app/exports/' . $filename);
            
            // Ensure directory exists
            if (!is_dir(dirname($filepath))) {
                mkdir(dirname($filepath), 0755, true);
            }
            
            // Save PDF
            file_put_contents($filepath, $dompdf->output());
            
            return $filepath;
            
        } catch (\Exception $e) {
            throw new TableException(
                __('components.table.export.pdf_error', ['error' => $e->getMessage()])
            );
        }
    }
    
    /**
     * Generate HTML for print functionality
     *
     * @param TableBuilder $table
     * @param array $data
     * @param array $columns
     * @return string HTML content
     */
    public function generatePrintHtml(TableBuilder $table, array $data, array $columns): string
    {
        // Filter out non-exportable columns
        $exportableColumns = $this->getExportableColumns($table, $columns);
        
        return $this->generatePDFHtml($table, $data, $exportableColumns);
    }
    
    /**
     * Get exportable columns (excluding non-exportable ones)
     *
     * @param TableBuilder $table
     * @param array $columns
     * @return array
     */
    protected function getExportableColumns(TableBuilder $table, array $columns): array
    {
        $config = $table->getConfig();
        $nonExportable = $config['nonExportableColumns'] ?? [];
        
        return array_filter($columns, function($column) use ($nonExportable) {
            return !in_array($column['field'], $nonExportable);
        });
    }
    
    /**
     * Get cell value from data item
     *
     * @param mixed $item
     * @param string $field
     * @return mixed
     */
    protected function getCellValue($item, string $field)
    {
        if (is_array($item)) {
            return $item[$field] ?? '';
        }
        
        if (is_object($item)) {
            // Handle nested properties (e.g., 'user.name')
            if (strpos($field, '.') !== false) {
                $parts = explode('.', $field);
                $value = $item;
                
                foreach ($parts as $part) {
                    if (is_object($value) && isset($value->$part)) {
                        $value = $value->$part;
                    } elseif (is_array($value) && isset($value[$part])) {
                        $value = $value[$part];
                    } else {
                        return '';
                    }
                }
                
                return $value;
            }
            
            return $item->$field ?? '';
        }
        
        return '';
    }
    
    /**
     * Generate HTML for PDF export
     *
     * @param TableBuilder $table
     * @param array $data
     * @param array $columns
     * @return string
     */
    protected function generatePDFHtml(TableBuilder $table, array $data, array $columns): string
    {
        $config = $table->getConfig();
        $title = $config['title'] ?? __('components.table.export.table_export');
        
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . htmlspecialchars($title) . '</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10pt;
            margin: 20px;
        }
        h1 {
            font-size: 16pt;
            margin-bottom: 20px;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th {
            background-color: #f0f0f0;
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            font-weight: bold;
        }
        td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .footer {
            margin-top: 20px;
            font-size: 8pt;
            color: #666;
            text-align: center;
        }
    </style>
</head>
<body>
    <h1>' . htmlspecialchars($title) . '</h1>
    <table>
        <thead>
            <tr>';
        
        foreach ($columns as $column) {
            $html .= '<th>' . htmlspecialchars($column['label']) . '</th>';
        }
        
        $html .= '</tr>
        </thead>
        <tbody>';
        
        foreach ($data as $item) {
            $html .= '<tr>';
            foreach ($columns as $column) {
                $value = $this->getCellValue($item, $column['field']);
                $html .= '<td>' . htmlspecialchars((string)$value) . '</td>';
            }
            $html .= '</tr>';
        }
        
        $html .= '</tbody>
    </table>
    <div class="footer">
        ' . __('components.table.export.generated_at', ['date' => date('Y-m-d H:i:s')]) . '
    </div>
</body>
</html>';
        
        return $html;
    }
    
    /**
     * Generate filename for export
     *
     * @param TableBuilder $table
     * @param string $extension
     * @return string
     */
    protected function generateFilename(TableBuilder $table, string $extension): string
    {
        $config = $table->getConfig();
        $prefix = $config['exportFilenamePrefix'] ?? 'table_export';
        $timestamp = date('Y-m-d_His');
        
        return "{$prefix}_{$timestamp}.{$extension}";
    }
    
    /**
     * Get Excel column letter from number
     *
     * @param int $num Column number (1-based)
     * @return string Column letter (A, B, C, ..., Z, AA, AB, ...)
     */
    protected function getColumnLetter(int $num): string
    {
        $letter = '';
        
        while ($num > 0) {
            $num--;
            $letter = chr(65 + ($num % 26)) . $letter;
            $num = intdiv($num, 26);
        }
        
        return $letter;
    }
}
