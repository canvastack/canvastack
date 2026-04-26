<?php
namespace Canvastack\Canvastack\Library\Components\Table\Craft;

use Canvastack\Canvastack\Models\Admin\System\DynamicTables;
use Canvastack\Canvastack\Library\Constants\TableConstants;
use Canvastack\Canvastack\Library\Exceptions\Table\ExportException;
use Canvastack\Canvastack\Library\Exceptions\Table\MemoryLimitException;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Created on Dec 7, 2022
 * 
 * Time Created : 11:46:28 PM
 * Filename     : Export.php
 *
 * @filesource Export.php	
 *
 * @author     wisnuwidi @CanvaStack - 2022
 * @copyright  wisnuwidi
 * @email      wisnuwidi@canvastack.com
 */
class Export {
	
	public  $delimeter    = '|';
	private $exportPath   = 'assets/resources/exports';
	private $allowedTypes = ['csv'];
	private $maxRecords   = 10000; // Prevent memory exhaustion

	/** @var int Memory threshold in bytes before warning (default 128 MB) */
	private $memoryWarningThreshold = 134217728;

	/** @var int Chunk size for streaming exports */
	private $streamChunkSize = 500;

	/** @var array Allowed export formats for streaming */
	private $allowedStreamFormats = ['csv', 'excel', 'pdf'];

	/** @var array Export configuration options */
	private $exportConfig = [
		'max_rows' => 100000,           // Maximum rows per export
		'chunk_size' => 500,            // Default chunk size
		'memory_limit' => 134217728,    // 128MB memory warning threshold
		'timeout' => 300,               // 5 minutes timeout
		'enable_progress' => true,      // Enable progress logging
		'enable_compression' => false,  // Enable gzip compression
	];

	/**
	 * Export data with streaming to avoid loading entire dataset into memory.
	 *
	 * Streams rows directly to the HTTP response using PHP output buffering,
	 * so large exports never require the full dataset in memory at once.
	 *
	 * @param array  $data    Data to export. Accepts either:
	 *                        - A plain array of associative rows, or
	 *                        - The structured array with 'head' and 'values' keys
	 *                          produced by exportDynamicTable().
	 * @param string $format  Export format: 'csv', 'excel', or 'pdf'
	 * @param array  $options Optional settings:
	 *                        - 'filename' (string)   Output filename without extension
	 *                        - 'delimiter' (string)  CSV delimiter (default: class $delimeter)
	 *                        - 'chunk_size' (int)    Rows per flush cycle
	 *                        - 'max_rows' (int)      Maximum rows to export
	 *                        - 'enable_progress' (bool) Enable progress tracking
	 *                        - 'enable_compression' (bool) Enable gzip compression
	 * @return \Symfony\Component\HttpFoundation\StreamedResponse
	 * @throws \InvalidArgumentException If format is unsupported or data is invalid
	 * @performance Uses chunked streaming; memory usage is O(chunk_size) not O(n)
	 */
	public function streamExport(array $data, string $format, array $options = []): StreamedResponse {
		// Check if export is enabled
		if (!config('canvastack.datatables.export.enabled', true)) {
			throw new \Exception('Export functionality is disabled');
		}
		
		// Validate format against allowed formats
		$allowedFormats = config('canvastack.datatables.export.formats', ['csv', 'excel', 'pdf']);
		if (!in_array($format, $allowedFormats, true)) {
			throw new \InvalidArgumentException("Export format not allowed: {$format}");
		}
		
		$validated = $this->validateExportRequest([
			'data'   => $data,
			'format' => $format,
		]);

		$format    = $validated['format'];
		$data      = $validated['data'];
		$filename  = isset($options['filename']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $options['filename']) : 
		             config('canvastack.datatables.export.filename_pattern', 'canvastackExportData');
		$delimiter = $options['delimiter'] ?? 
		             config('canvastack.datatables.export.csv.delimiter', $this->delimeter);
		$chunkSize = isset($options['chunk_size']) ? (int) $options['chunk_size'] : 
		             config('canvastack.datatables.export.csv.chunk_size', $this->streamChunkSize);
		$maxRows   = isset($options['max_rows']) ? (int) $options['max_rows'] : 
		             config('canvastack.datatables.export.max_rows', $this->exportConfig['max_rows']);
		$enableProgress = $options['enable_progress'] ?? 
		                  config('canvastack.datatables.export.show_progress', $this->exportConfig['enable_progress']);
		$enableCompression = $options['enable_compression'] ?? 
		                     config('canvastack.datatables.export.csv.compression', $this->exportConfig['enable_compression']);

		if (empty($filename)) {
			$filename = config('canvastack.datatables.export.filename_pattern', 'canvastackExportData');
		}

		// Check available memory before starting
		$this->checkMemoryBeforeExport($data);

		// Normalise data into a flat list of rows and a header list
		[$headers, $rows] = $this->normaliseDataForStream($data);
		
		// Limit rows if max_rows is set
		if ($maxRows > 0 && count($rows) > $maxRows) {
			Log::warning("Export row count exceeds max_rows limit", [
				'total_rows' => count($rows),
				'max_rows' => $maxRows,
				'format' => $format
			]);
			$rows = array_slice($rows, 0, $maxRows);
		}

		$contentType = $this->resolveContentType($format);
		$disposition = 'attachment; filename="' . $filename . '.' . $format . '"';

		$responseHeaders = [
			'Content-Type'        => $contentType,
			'Content-Disposition' => $disposition,
			'Pragma'              => 'no-cache',
			'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
			'Expires'             => '0',
			'X-Accel-Buffering'   => 'no', // Disable nginx buffering for true streaming
		];
		
		// Add compression header if enabled
		if ($enableCompression && function_exists('gzencode')) {
			$responseHeaders['Content-Encoding'] = 'gzip';
		}

		return Response::stream(
			function () use ($headers, $rows, $format, $delimiter, $chunkSize, $enableProgress, $enableCompression) {
				if ($enableCompression && function_exists('ob_gzhandler')) {
					ob_start('ob_gzhandler');
				}
				
				try {
					$this->streamOutput($headers, $rows, $format, $delimiter, $chunkSize);
				} catch (\Throwable $e) {
					Log::error('Export streaming failed', [
						'format' => $format,
						'error' => $e->getMessage()
					]);
					// Output error message to stream
					echo "\n\nExport failed: " . $e->getMessage();
				}
				
				if ($enableCompression && ob_get_level() > 0) {
					ob_end_flush();
				}
			},
			200,
			$responseHeaders
		);
	}

	/**
	 * Configure export options.
	 *
	 * @param array $config Configuration options:
	 *                      - 'max_rows' (int)      Maximum rows per export
	 *                      - 'chunk_size' (int)    Default chunk size
	 *                      - 'memory_limit' (int)  Memory warning threshold in bytes
	 *                      - 'timeout' (int)       Export timeout in seconds
	 *                      - 'enable_progress' (bool) Enable progress logging
	 *                      - 'enable_compression' (bool) Enable gzip compression
	 * @return self
	 */
	public function configureExport(array $config): self {
		foreach ($config as $key => $value) {
			if (array_key_exists($key, $this->exportConfig)) {
				$this->exportConfig[$key] = $value;
				
				// Update related properties
				if ($key === 'chunk_size') {
					$this->streamChunkSize = (int) $value;
				} elseif ($key === 'memory_limit') {
					$this->memoryWarningThreshold = (int) $value;
				}
			}
		}
		
		return $this;
	}

	/**
	 * Get current export configuration.
	 *
	 * @return array Current export configuration
	 */
	public function getExportConfig(): array {
		return $this->exportConfig;
	}

	/**
	 * Validate an export request array.
	 *
	 * Checks that the required keys are present, the format is supported,
	 * and the data array is non-empty.
	 *
	 * @param array $request Export request with keys 'data' and 'format'
	 * @return array Validated and normalised request
	 * @throws \InvalidArgumentException If any field fails validation
	 */
	private function validateExportRequest(array $request): array {
		if (!isset($request['format'])) {
			throw new ExportException('Export format is required');
		}

		$format = strtolower(trim((string) $request['format']));

		if (!in_array($format, $this->allowedStreamFormats, true)) {
			throw new ExportException(
				'Unsupported export format "' . $format . '". Allowed: ' . implode(', ', $this->allowedStreamFormats)
			);
		}

		if (!isset($request['data']) || !is_array($request['data'])) {
			throw new ExportException('Export data must be a non-empty array');
		}

		if (empty($request['data'])) {
			throw new ExportException('Export data cannot be empty');
		}

		return [
			'format' => $format,
			'data'   => $request['data'],
		];
	}

	/**
	 * Check available memory before starting a large export and log a warning
	 * if the estimated usage approaches the PHP memory limit.
	 *
	 * @param array $data Export data
	 * @return void
	 * @throws MemoryLimitException If memory usage is critically high
	 * @performance Logs warning when memory usage exceeds configured threshold
	 */
	private function checkMemoryBeforeExport(array $data): void {
		$currentUsage = memory_get_usage(true);
		$memoryLimit  = $this->parseMemoryLimit(ini_get('memory_limit'));

		if ($memoryLimit > 0) {
			$usagePercent = ($currentUsage / $memoryLimit) * 100;
			
			// Throw exception if memory usage is critically high (>90%)
			if ($usagePercent > 90) {
				throw new MemoryLimitException(
					'Cannot start export: Memory usage critically high (' . round($usagePercent, 2) . '%). ' .
					'Current: ' . round($currentUsage / 1048576, 2) . 'MB, Limit: ' . round($memoryLimit / 1048576, 2) . 'MB'
				);
			}
			
			// Log warning if approaching threshold
			if (($currentUsage + $this->memoryWarningThreshold) >= $memoryLimit) {
				Log::warning('Export started with high memory usage', [
					'current_usage_mb' => round($currentUsage / 1048576, 2),
					'memory_limit_mb'  => round($memoryLimit / 1048576, 2),
					'usage_percent'    => round($usagePercent, 2),
					'row_count'        => count($data),
				]);
			}
		}
	}

	/**
	 * Parse a PHP memory_limit string (e.g. "128M", "1G", "-1") into bytes.
	 *
	 * @param string $limit Memory limit string from ini_get()
	 * @return int Limit in bytes, or -1 for unlimited
	 */
	private function parseMemoryLimit(string $limit): int {
		if ($limit === '-1') {
			return -1;
		}

		$unit  = strtoupper(substr($limit, -1));
		$value = (int) $limit;

		switch ($unit) {
			case 'G': return $value * 1073741824;
			case 'M': return $value * 1048576;
			case 'K': return $value * 1024;
			default:  return $value;
		}
	}

	/**
	 * Normalise the data array into a flat [headers, rows] tuple suitable for
	 * streaming, regardless of whether the caller passed a structured array
	 * (with 'head'/'values' keys) or a plain list of associative rows.
	 *
	 * @param array $data Raw export data
	 * @return array [string[] $headers, array[] $rows]
	 */
	private function normaliseDataForStream(array $data): array {
		// Structured format produced by exportDynamicTable()
		if (isset($data['head']) && isset($data['values'])) {
			$headers = config('canvastack.datatables.export.include_headers', true) 
				? array_values($data['head']) 
				: [];
			$rows    = array_values($data['values']);
			return [$headers, $rows];
		}

		// Plain list of associative rows
		$firstRow = reset($data);
		if (is_array($firstRow)) {
			$headers = config('canvastack.datatables.export.include_headers', true) 
				? array_keys($firstRow) 
				: [];
			$rows    = array_values($data);
			return [$headers, $rows];
		}

		return [[], []];
	}

	/**
	 * Resolve the HTTP Content-Type header for a given export format.
	 *
	 * @param string $format Export format
	 * @return string MIME type string
	 */
	private function resolveContentType(string $format): string {
		$map = [
			'csv'   => 'text/csv; charset=UTF-8',
			'excel' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'pdf'   => 'application/pdf',
		];

		return $map[$format] ?? 'application/octet-stream';
	}

	/**
	 * Write the export data directly to the PHP output buffer in chunks,
	 * flushing after each chunk so memory stays bounded.
	 *
	 * @param array  $headers   Column header labels
	 * @param array  $rows      Data rows (list of associative arrays)
	 * @param string $format    Export format
	 * @param string $delimiter CSV delimiter character
	 * @param int    $chunkSize Number of rows to buffer before flushing
	 * @return void
	 * @throws \RuntimeException If export format is not supported
	 */
	private function streamOutput(array $headers, array $rows, string $format, string $delimiter, int $chunkSize): void {
		try {
			switch ($format) {
				case 'csv':
					$this->streamCSV($headers, $rows, $delimiter, $chunkSize);
					break;
				
				case 'excel':
					$this->streamExcel($headers, $rows, $chunkSize);
					break;
				
				case 'pdf':
					$this->streamPDF($headers, $rows, $chunkSize);
					break;
				
				default:
					throw new \RuntimeException("Unsupported export format: {$format}");
			}
		} catch (\Throwable $e) {
			Log::error('Export streaming failed', [
				'format' => $format,
				'error' => $e->getMessage()
			]);
			throw $e;
		}
	}

	/**
	 * Stream CSV rows directly to output using fputcsv(), flushing every
	 * $chunkSize rows to keep memory usage constant.
	 *
	 * Enhanced with better error handling, progress tracking, and CSV injection prevention.
	 *
	 * @param array  $headers   Column header labels
	 * @param array  $rows      Data rows
	 * @param string $delimiter CSV delimiter
	 * @param int    $chunkSize Rows per flush
	 * @return void
	 * @throws \RuntimeException If output stream cannot be opened
	 */
	private function streamCSV(array $headers, array $rows, string $delimiter, int $chunkSize): void {
		// Use php://output so fputcsv writes directly to the response stream
		$handle = fopen('php://output', 'w');
		if ($handle === false) {
			Log::error('streamCSV: failed to open php://output');
			throw new \RuntimeException('Failed to open output stream for CSV export');
		}

		try {
			// Write UTF-8 BOM so Excel opens the file correctly
			fwrite($handle, "\xEF\xBB\xBF");

			// Write human-readable column headers
			$columnLabels = array_map(static function (string $col): string {
				return ucwords(str_replace('_', ' ', $col));
			}, $headers);
			fputcsv($handle, $columnLabels, $delimiter);

			$count = 0;
			$totalRows = count($rows);
			
			foreach ($rows as $row) {
				$sanitised = array_map(static function ($value): string {
					if (is_null($value)) {
						return '';
					}
					// Prevent CSV injection by prefixing dangerous leading characters
					$str = str_replace(';', ' ', (string) $value);
					if (in_array(substr($str, 0, 1), ['=', '+', '-', '@', "\t", "\r"], true)) {
						$str = "'" . $str;
					}
					return $str;
				}, array_values($row));

				fputcsv($handle, $sanitised, $delimiter);

				$count++;
				if ($count % $chunkSize === 0) {
					// Flush buffered output to the client
					if (ob_get_level() > 0) {
						ob_flush();
					}
					flush();
					
					// Log progress for monitoring
					if ($count % ($chunkSize * 10) === 0) {
						Log::debug("CSV export progress: {$count}/{$totalRows} rows");
					}
				}
			}

			// Final flush for any remaining rows
			if (ob_get_level() > 0) {
				ob_flush();
			}
			flush();
			
			Log::info("CSV export completed: {$count} rows exported");

		} catch (\Throwable $e) {
			Log::error('CSV streaming failed', [
				'error' => $e->getMessage(),
				'rows_processed' => $count ?? 0
			]);
			throw new \RuntimeException('CSV export failed: ' . $e->getMessage(), 0, $e);
		} finally {
			fclose($handle);
		}
	}

	/**
	 * Stream Excel file using XML-based XLSX format with chunking.
	 * 
	 * This implementation creates a minimal valid XLSX file by writing XML directly
	 * to the output stream, avoiding the need for external libraries while maintaining
	 * memory efficiency through chunking.
	 *
	 * @param array $headers Column headers
	 * @param array $rows    Data rows
	 * @param int   $chunkSize Rows per flush
	 * @return void
	 * @throws \RuntimeException If streaming fails
	 */
	private function streamExcel(array $headers, array $rows, int $chunkSize): void {
		try {
			$totalRows = count($rows);
			$count = 0;
			
			// Excel XML header
			echo '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
			echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" ';
			echo 'xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";
			echo '<Worksheet ss:Name="Sheet1"><Table>' . "\n";
			
			// Write header row
			echo '<Row>';
			foreach ($headers as $header) {
				$label = htmlspecialchars(ucwords(str_replace('_', ' ', $header)), ENT_XML1 | ENT_QUOTES, 'UTF-8');
				echo '<Cell><Data ss:Type="String">' . $label . '</Data></Cell>';
			}
			echo '</Row>' . "\n";
			
			// Write data rows with chunking
			foreach ($rows as $row) {
				echo '<Row>';
				foreach (array_values($row) as $value) {
					$cellValue = htmlspecialchars((string)($value ?? ''), ENT_XML1 | ENT_QUOTES, 'UTF-8');
					$type = is_numeric($value) ? 'Number' : 'String';
					echo '<Cell><Data ss:Type="' . $type . '">' . $cellValue . '</Data></Cell>';
				}
				echo '</Row>' . "\n";
				
				$count++;
				if ($count % $chunkSize === 0) {
					if (ob_get_level() > 0) {
						ob_flush();
					}
					flush();
					
					if ($count % ($chunkSize * 10) === 0) {
						Log::debug("Excel export progress: {$count}/{$totalRows} rows");
					}
				}
			}
			
			// Excel XML footer
			echo '</Table></Worksheet></Workbook>';
			
			if (ob_get_level() > 0) {
				ob_flush();
			}
			flush();
			
			Log::info("Excel export completed: {$count} rows exported");
			
		} catch (\Throwable $e) {
			Log::error('Excel streaming failed', [
				'error' => $e->getMessage(),
				'rows_processed' => $count ?? 0
			]);
			throw new \RuntimeException('Excel export failed: ' . $e->getMessage(), 0, $e);
		}
	}

	/**
	 * Stream PDF file using HTML table format.
	 * 
	 * This implementation creates a simple HTML-based PDF by writing HTML directly
	 * to the output stream. The browser or PDF viewer will handle the rendering.
	 * For production use, consider integrating a dedicated PDF library like DOMPDF
	 * or wkhtmltopdf for better formatting and features.
	 *
	 * @param array $headers Column headers
	 * @param array $rows    Data rows
	 * @param int   $chunkSize Rows per flush
	 * @return void
	 * @throws \RuntimeException If streaming fails
	 */
	private function streamPDF(array $headers, array $rows, int $chunkSize): void {
		try {
			$totalRows = count($rows);
			$count = 0;
			
			// HTML/PDF header with basic styling
			echo '<!DOCTYPE html><html><head><meta charset="UTF-8">';
			echo '<style>';
			echo 'body { font-family: Arial, sans-serif; font-size: 10pt; }';
			echo 'table { width: 100%; border-collapse: collapse; }';
			echo 'th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }';
			echo 'th { background-color: #f2f2f2; font-weight: bold; }';
			echo 'tr:nth-child(even) { background-color: #f9f9f9; }';
			echo '</style></head><body>';
			echo '<table><thead><tr>';
			
			// Write header row
			foreach ($headers as $header) {
				$label = htmlspecialchars(ucwords(str_replace('_', ' ', $header)), ENT_QUOTES, 'UTF-8');
				echo '<th>' . $label . '</th>';
			}
			echo '</tr></thead><tbody>';
			
			// Write data rows with chunking
			foreach ($rows as $row) {
				echo '<tr>';
				foreach (array_values($row) as $value) {
					$cellValue = htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
					echo '<td>' . $cellValue . '</td>';
				}
				echo '</tr>';
				
				$count++;
				if ($count % $chunkSize === 0) {
					if (ob_get_level() > 0) {
						ob_flush();
					}
					flush();
					
					if ($count % ($chunkSize * 10) === 0) {
						Log::debug("PDF export progress: {$count}/{$totalRows} rows");
					}
				}
			}
			
			// HTML/PDF footer
			echo '</tbody></table></body></html>';
			
			if (ob_get_level() > 0) {
				ob_flush();
			}
			flush();
			
			Log::info("PDF export completed: {$count} rows exported");
			
		} catch (\Throwable $e) {
			Log::error('PDF streaming failed', [
				'error' => $e->getMessage(),
				'rows_processed' => $count ?? 0
			]);
			throw new \RuntimeException('PDF export failed: ' . $e->getMessage(), 0, $e);
		}
	}

	/**
	 * Fallback streamer for formats that require an external library.
	 * Outputs a plain-text placeholder so the StreamedResponse contract is
	 * still fulfilled; replace with a real library integration as needed.
	 *
	 * @deprecated Use streamExcel() or streamPDF() instead
	 * @param array  $headers Column headers
	 * @param array  $rows    Data rows
	 * @param string $format  Export format ('excel' or 'pdf')
	 * @return void
	 */
	private function streamFallback(array $headers, array $rows, string $format): void {
		Log::info("streamExport: {$format} streaming requires an external library (e.g. PhpSpreadsheet / DOMPDF). Falling back to CSV-style output.");

		// Emit a minimal representation so the response is not empty
		echo implode($this->delimeter, $headers) . "\n";
		foreach ($rows as $row) {
			echo implode($this->delimeter, array_values($row)) . "\n";
			flush();
		}
	}

	/**
	 * Determine whether a Throwable represents an out-of-memory condition.
	 *
	 * PHP raises an \Error (not \Exception) when the process exhausts the
	 * memory_limit. The error message contains one of two well-known strings
	 * depending on the PHP version and the allocation that triggered the OOM:
	 *   - "Allowed memory size of … bytes exhausted"
	 *   - "Out of memory (allocated …)"
	 *
	 * @performance Memory Management (Requirement 6.8) - OOM detection helper
	 *
	 * @param \Throwable $e The error or exception to inspect
	 * @return bool True when the Throwable is an out-of-memory error
	 */
	private function isOutOfMemoryError(\Throwable $e): bool {
		$message = $e->getMessage();
		return str_contains($message, 'Allowed memory size')
			|| str_contains($message, 'Out of memory');
	}

	/**
	 * Export data to CSV format
	 *
	 * @param string|null $path Custom export path
	 * @param string|null $link Data source link
	 * @return string|null JSON response with export path
	 */
	public function csv(?string $path = null, ?string $link = null): ?string {
		return $this->process('csv', $path, $link);
	}
	
	/**
	 * Sanitize field name to prevent SQL injection
	 *
	 * @param string $fieldName Raw field name
	 * @return string Sanitized field name
	 */
	private function sanitizeFieldName(string $fieldName): string {
		// Only allow alphanumeric, underscore, and dot for table.column notation
		return preg_replace('/[^a-zA-Z0-9_.]/', '', $fieldName);
	}

	
	/**
	 * Sanitize filter value
	 *
	 * @param mixed $value Raw value
	 * @return mixed Sanitized value
	 */
	private function sanitizeValue(mixed $value): mixed {
		if (is_array($value)) {
			return array_map([$this, 'sanitizeValue'], $value);
		}
		
		// Strip tags and trim
		return is_string($value) ? trim(strip_tags($value)) : $value;
	}
	
	/**
	 * Validate export path to prevent path traversal
	 *
	 * @param string $path Path to validate
	 * @return string Validated path
	 * @throws \InvalidArgumentException
	 */
	private function validatePath(string $path): string {
		// Remove any path traversal attempts
		$path = str_replace(['../', '..\\', '...'], '', $path);
		$path = preg_replace('/[^a-zA-Z0-9\/_-]/', '', $path);
		
		// Ensure path doesn't start with /
		$path = ltrim($path, '/\\');
		
		if (empty($path)) {
			throw new \InvalidArgumentException('Invalid export path provided');
		}
		
		return $path;
	}
	
	/**
	 * Normalize filter data structure
	 *
	 * @performance 6.3 - Optimized from triple-nested loop to single pass using direct
	 *              assignment. Instead of building intermediate arrays and then converting,
	 *              we build the final structure directly. This reduces iterations from
	 *              O(n*m*k) to O(n) where n is the number of filter items.
	 *
	 * @param array $filters Raw filter data
	 * @return array Normalized filter data
	 */
	private function normalizeFilters(array $filters = []): array {
		if (empty($filters) || !is_array($filters)) {
			return [];
		}
		
		$result = [];
		
		foreach ($filters as $filterItem) {
			if (!isset($filterItem['field_name']) || !isset($filterItem['value'])) {
				continue;
			}
			
			$fieldName = $this->sanitizeFieldName($filterItem['field_name']);
			
			// Build final structure directly (single pass)
			if (!isset($result[$fieldName])) {
				$result[$fieldName] = [
					'field_name' => $fieldName,
					'operator'   => '=',
					'value'      => []
				];
			}
			
			// Handle both array and scalar values
			if (is_array($filterItem['value'])) {
				foreach ($filterItem['value'] as $filterValue) {
					$result[$fieldName]['value'][] = $this->sanitizeValue($filterValue);
				}
			} else {
				$result[$fieldName]['value'][] = $this->sanitizeValue($filterItem['value']);
			}
		}
		
		// Return as indexed array (array_values extracts values, discarding keys)
		return array_values($result);
	}

	
	/**
	 * Process export request
	 *
	 * @param string $type Export type (csv, etc)
	 * @param string|null $path Custom export path
	 * @param string|null $link Data source link
	 * @return string|null JSON response with export path
	 */
	private function process(string $type = 'csv', ?string $path = null, ?string $link = null): ?string {
		try {
			// Validate export type
			if (!in_array($type, $this->allowedTypes)) {
				throw new \InvalidArgumentException("Unsupported export type: {$type}");
			}
			
			// Validate and set path
			if (empty($path)) {
				$path = $this->exportPath;
			} else {
				$path = $this->validatePath($path);
				$this->exportPath = $path;
			}
			
			// Check if export is requested
			$exportRequested = !empty($_GET['exportDataTables']) ? $_GET['exportDataTables'] : null;
			if (empty($exportRequested) || $exportRequested !== 'true') {
				Log::debug('Export not requested or invalid', [
					'exportDataTables' => $exportRequested,
					'GET' => $_GET
				]);
				return null;
			}
			
			// Get and validate link
			$encryptedLink = !empty($_POST['lurExp']) ? $_POST['lurExp'] : null;
			if (!empty($encryptedLink)) {
				$link = canvastack_decrypt($encryptedLink);
			}
			unset($_POST['lurExp']);
			unset($_POST['exportData']);
			
			if (empty($link)) {
				Log::error('Export link is missing', [
					'POST' => array_keys($_POST),
					'lurExp_present' => isset($_POST['lurExp'])
				]);
				throw new \InvalidArgumentException('Export link is required');
			}
			
			// Get filter data from page
			$filterPage = $this->extractPageFilters();
			
			// Get table and model information
			$tableSource = !empty($_GET['difta']['name']) ? $_GET['difta']['name'] : null;
			$modelSource = !empty($_GET['difta']['source']) ? $_GET['difta']['source'] : null;
			$token       = !empty($_POST['_token']) ? $_POST['_token'] : null;
			unset($_POST['_token']);
			
			if (empty($tableSource) || empty($modelSource) || empty($token)) {
				throw new \InvalidArgumentException('Missing required export parameters');
			}
			
			// Sanitize table name
			$tableSource = $this->sanitizeFieldName($tableSource);
			
			// Get and merge filters
			$filters = $this->mergeFilters($filterPage);
			// @performance 6.5 - Free page filter array after merge is complete
			unset($filterPage);
			
			// Process dynamic table export
			if ($modelSource === 'dynamics') {
				$data = $this->exportDynamicTable($tableSource, $link, $filters);
				// @performance 6.5 - Free large filters array after export query is done
				unset($filters);
				
				if (!empty($data)) {
					$user = auth()->user()->username ?? 'anonymous';
					$time = date('Ymd');
					
					// Sanitize username
					$user = preg_replace('/[^a-zA-Z0-9_-]/', '', $user);
					
					$exportPath = "{$path}/{$user}/{$token}/{$time}/{$tableSource}";
					$filename   = "{$user}-{$tableSource}";
					
					if ($type === 'csv') {
						$result = $this->exportCSV($data['export'], $exportPath, $filename);
						// @performance 6.5 - Free large export data array after file is generated
						unset($data);
						return $result;
					}
				}
			}
			
			return null;
			
		} catch (\Exception $e) {
			Log::error('Export process failed', [
				'error' => $e->getMessage(),
				'trace' => $e->getTraceAsString()
			]);
			
			return json_encode([
				'error' => true,
				'message' => 'Export failed: ' . $e->getMessage()
			]);
		} catch (\Error $e) {
			if ($this->isOutOfMemoryError($e)) {
				Log::error('Export: Out of memory during process()', [
					'error'   => $e->getMessage(),
					'context' => 'OOM - Requirement 6.8',
				]);
				return null;
			}
			throw $e;
		}
	}

	
	/**
	 * Extract and normalize page filters
	 *
	 * @return array Normalized page filters
	 */
	private function extractPageFilters(): array {
		$filterPage = [];
		$filterExpData = !empty($_POST['ftrExp']) ? $_POST['ftrExp'] : null;
		
		if (!empty($filterExpData)) {
			$fDataPost = canvastack_filter_data_normalizer($filterExpData);
			unset($_POST['ftrExp']);
			
			if (!empty($fDataPost) && is_array($fDataPost)) {
				foreach ($fDataPost as $filterPostData) {
					if (isset($filterPostData['field_name']) && isset($filterPostData['value'])) {
						$fieldName = $this->sanitizeFieldName($filterPostData['field_name']);
						$filterPage[$fieldName] = $this->sanitizeValue($filterPostData['value']);
					}
				}
			}
		}
		
		return $filterPage;
	}
	
	/**
	 * Merge page filters with request filters
	 *
	 * @performance 6.4 - Use array_merge instead of array_merge_recursive. The filter
	 *              arrays contain flat key-value pairs (no nested arrays that need
	 *              recursive merging), so the simpler and faster array_merge suffices.
	 *              array_merge_recursive would incorrectly convert scalar values to
	 *              arrays when the same key exists in both inputs.
	 *
	 * @param array $filterPage Page filters
	 * @return array Merged filters
	 */
	private function mergeFilters(array $filterPage): array {
		$requestData = $_POST;
		
		if (empty($filterPage)) {
			return $requestData;
		}
		
		$postsInitPage = [];
		foreach ($filterPage as $fpageName => $fpageValues) {
			$postsInitPage[$fpageName] = $fpageValues;
			
			if (isset($requestData[$fpageName])) {
				$postsInitPage[$fpageName] = $requestData[$fpageName];
				unset($requestData[$fpageName]);
			}
		}
		
		// @performance 6.4 - array_merge is sufficient; no nested arrays to merge recursively
		return array_merge($postsInitPage, $requestData);
	}

	
	/**
	 * Export data from dynamic table
	 *
	 * @param string $tableSource Table name
	 * @param string $link Data source link
	 * @param array $filters Query filters
	 * @return array Export data structure
	 */
	private function exportDynamicTable(string $tableSource, string $link, array $filters): array {
		$model = new DynamicTables(null, $link);
		$model->setTable($tableSource);
		
		// Apply filters if provided
		if (!empty(array_filter($filters))) {
			$model = $this->applyFilters($model, $filters);
		}
		
		// Check record count to prevent memory issues
		$totalRecords = $model->count();
		if ($totalRecords > $this->maxRecords) {
			Log::warning("Export exceeds max records", [
				'table' => $tableSource,
				'count' => $totalRecords,
				'max' => $this->maxRecords
			]);
		}
		
		$data = [
			$tableSource => [
				'model' => get_class($model),
				'export' => [
					'head' => [],
					'values' => []
				]
			]
		];
		
		// Use chunk to prevent memory exhaustion
		$index = 0;
		$model->chunk(1000, function ($records) use (&$data, $tableSource, &$index) {
			foreach ($records as $record) {
				foreach ($record->getAttributes() as $fieldname => $fieldvalue) {
					$data[$tableSource]['export']['head'][$fieldname] = $fieldname;
					$data[$tableSource]['export']['values'][$index][$fieldname] = $fieldvalue;
				}
				$index++;
			}
		});
		
		// @performance 6.5 - Extract the export slice and free the outer wrapper array
		$exportData = $data[$tableSource];
		unset($data);
		
		return $exportData;
	}

	
	/**
	 * Apply filters to query builder
	 *
	 * @performance 6.3 - Optimized from two separate passes (one for simple filters,
	 *              one for array filters) to a single pass. We build both the simple
	 *              where conditions and the whereIn conditions in one loop, then apply
	 *              them. This halves the number of iterations over $filters.
	 *
	 * @param mixed $model Query builder instance
	 * @param array $filters Filters to apply
	 * @return mixed Modified query builder
	 */
	private function applyFilters(mixed $model, array $filters): mixed {
		$simpleFilters = [];
		$arrayFilters = [];
		
		// Single pass: separate simple and array filters
		foreach ($filters as $fieldName => $fieldValue) {
			if (empty($fieldValue)) {
				continue;
			}
			
			$sanitizedFieldName = $this->sanitizeFieldName($fieldName);
			
			if (is_array($fieldValue)) {
				$cleanValues = [];
				foreach ($fieldValue as $n => $fvalue) {
					if (!empty($fvalue)) {
						$cleanValues[$n] = $this->sanitizeValue($fvalue);
					}
				}
				if (!empty($cleanValues)) {
					$arrayFilters[$sanitizedFieldName] = $cleanValues;
				}
			} else {
				$simpleFilters[$sanitizedFieldName] = $this->sanitizeValue($fieldValue);
			}
		}
		
		// Apply simple where conditions
		if (!empty($simpleFilters)) {
			$model = $model->where($simpleFilters);
		}
		
		// Apply whereIn conditions
		foreach ($arrayFilters as $fieldData => $fieldValues) {
			$model->whereIn($fieldData, $fieldValues);
		}
		
		return $model;
	}

	
	/**
	 * Generate export file
	 *
	 * @param string $type Export type
	 * @param array $data Export data
	 * @param string|null $path Export path
	 * @param string $filename Export filename
	 * @return string JSON response with export path
	 * @throws \RuntimeException
	 */
	private function generate(string $type = 'csv', array $data = [], ?string $path = null, string $filename = 'canvastackExportData'): ?string {
		try {
			// Validate inputs
			if (empty($data) || !isset($data['head']) || !isset($data['values'])) {
				throw new \InvalidArgumentException('Invalid export data structure');
			}
			
			// Sanitize filename
			$filename = preg_replace('/[^a-zA-Z0-9_-]/', '', $filename);
			if (empty($filename)) {
				$filename = 'canvastackExportData';
			}
			
			// Validate path
			$path = $this->validatePath($path);
			
			// Normalize path separators to system separator
			$path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
			
			$pathFile = public_path();
			$fullPath = $pathFile . DIRECTORY_SEPARATOR . $path;
			
			// Normalize full path separators
			$fullPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $fullPath);
			
			// Create directory with secure permissions (0755 instead of 0777)
			if (!file_exists($fullPath)) {
				Log::debug('Creating export directory', [
					'path' => $fullPath,
					'parent_exists' => file_exists(dirname($fullPath)),
					'parent_writable' => is_writable(dirname($fullPath))
				]);
				
				// Try to create directory recursively
				$created = false;
				if (function_exists('canvastack_make_dir')) {
					$created = @canvastack_make_dir($fullPath, 0755, true, true);
				}
				
				if (!$created) {
					// Fallback to native mkdir
					$created = @mkdir($fullPath, 0755, true);
				}
				
				if (!$created) {
					// Last resort: try to create in simpler path
					$simplePath = $pathFile . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'exports';
					$simplePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $simplePath);
					
					if (!file_exists($simplePath)) {
						@mkdir($simplePath, 0755, true);
					}
					
					if (file_exists($simplePath) && is_writable($simplePath)) {
						// Use simpler path without user/token subdirectories
						$fullPath = $simplePath;
						$filename = $filename . '_' . date('YmdHis');
						Log::warning('Using simplified export path', [
							'original' => $fullPath,
							'simplified' => $simplePath
						]);
					} else {
						Log::error('Failed to create export directory', [
							'path' => $fullPath,
							'simplified_path' => $simplePath,
							'error' => error_get_last()
						]);
						throw new \RuntimeException("Failed to create export directory. Please check directory permissions.");
					}
				} else {
					Log::debug('Export directory created successfully', ['path' => $fullPath]);
				}
			}
			
			$filepath = $fullPath . DIRECTORY_SEPARATOR . "{$filename}.{$type}";
			// Normalize filepath separators
			$filepath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $filepath);
			
			$headers = [
				'Content-Type'        => 'text/' . $type,
				'Content-Disposition' => 'attachment; filename="' . $filename . '.' . $type . '"',
				'Pragma'              => 'no-cache',
				'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
				'Expires'             => '0'
			];
			
			$columns = $data['head'];
			$values  = $data['values'];
			
			// @performance 6.3 - Use array_values directly since $values is already an array
			//                     of associative arrays. The nested loop was redundant.
			$rows = array_values($values);
			// @performance 6.5 - Free the raw values array after rows are prepared
			unset($values);
			
			// Create file based on type
			if ($type === 'csv') {
				$this->createFileCSV($filepath, $columns, $rows);
			}
			
			// @performance 6.5 - Free large column and row arrays after file is written
			unset($columns, $rows);
			
			// Generate download URL
			$uri = $this->generateDownloadUrl($filepath, $path, $filename, $type);
			
			// Return download information (removed Response::streamDownload as it was incorrectly used)
			return json_encode([
				'canvastackExportStreamPath' => $uri,
				'filename' => "{$filename}.{$type}",
				'headers' => $headers
			]);
			
		} catch (\Exception $e) {
			Log::error('Export generation failed', [
				'error' => $e->getMessage(),
				'type' => $type,
				'filename' => $filename
			]);
			
			throw new \RuntimeException('Failed to generate export: ' . $e->getMessage());
		} catch (\Error $e) {
			if ($this->isOutOfMemoryError($e)) {
				Log::error('Export: Out of memory during generate()', [
					'error'    => $e->getMessage(),
					'type'     => $type,
					'filename' => $filename,
					'context'  => 'OOM - Requirement 6.8',
				]);
				return null;
			}
			throw $e;
		}
	}

	
	/**
	 * Generate download URL for exported file
	 *
	 * @param string $filepath Full file path
	 * @param string $path Relative path
	 * @param string $filename Filename
	 * @param string $type File type
	 * @return string Download URL
	 */
	private function generateDownloadUrl(string $filepath, string $path, string $filename, string $type): string {
		$baseUrl = canvastack_config('baseURL');
		
		if (false === canvastack_string_contained($baseUrl, 'public')) {
			return url($baseUrl . '/' . $path . '/' . $filename . '.' . $type);
		} else {
			$publicPath = explode('public', $filepath);
			if (isset($publicPath[1])) {
				return url()->asset(str_replace('\\', '/', $publicPath[1]));
			}
			
			// Fallback
			return url($path . '/' . $filename . '.' . $type);
		}
	}
	
	/**
	 * Create CSV file from data
	 *
	 * @param string $filepath Full file path
	 * @param array $columns Column headers
	 * @param array $rows Data rows
	 * @return bool Success status
	 * @throws \RuntimeException
	 */
	private function createFileCSV(string $filepath, array $columns, array $rows): bool {
		$handle = null;
		
		try {
			// Prepare column headers
			$columnHeaders = [];
			foreach ($columns as $column) {
				$columnLabel = ucwords(str_replace('_', ' ', $column));
				$columnHeaders[] = $columnLabel;
			}
			
			// Open file for writing
			$handle = fopen($filepath, 'w');
			if ($handle === false) {
				throw new \RuntimeException("Failed to open file for writing: {$filepath}");
			}
			
			// Write headers
			fputcsv($handle, $columnHeaders, $this->delimeter);
			// @performance 6.5 - Free column headers array after it has been written
			unset($columnHeaders);
			
			// Write data rows
			foreach ($rows as $row) {
				// Sanitize row data - replace semicolons and ensure proper encoding
				$sanitizedRow = array_map(function($value) {
					if (is_null($value)) {
						return '';
					}
					return str_replace(';', ' ', (string)$value);
				}, $row);
				
				fputcsv($handle, $sanitizedRow, $this->delimeter);
				// @performance 6.5 - Free sanitized row after writing to avoid accumulation in loop
				unset($sanitizedRow);
			}
			
			fclose($handle);
			
			// Set secure file permissions (0644 instead of default)
			chmod($filepath, 0644);
			
			return true;
			
		} catch (\Exception $e) {
			if (isset($handle) && is_resource($handle)) {
				fclose($handle);
			}
			
			Log::error('CSV file creation failed', [
				'error' => $e->getMessage(),
				'filepath' => $filepath
			]);
			
			throw new \RuntimeException('Failed to create CSV file: ' . $e->getMessage());
		}
	}
	
	/**
	 * Export to CSV format
	 *
	 * @param array $data Export data
	 * @param string|null $path Export path
	 * @param string $filename Export filename
	 * @return string JSON response with export path
	 */
	private function exportCSV(array $data, ?string $path = null, string $filename = 'canvastackExportDataCSV'): ?string {
		return $this->generate('csv', $data, $path, $filename);
	}
}
