<?php

namespace Canvastack\Canvastack\Library\Exceptions\Table;

/**
 * Exception thrown when data export fails
 * 
 * This exception is thrown when:
 * - Export format is not supported
 * - Export file cannot be created
 * - Export data is too large
 * - Export library is missing
 * - Export generation fails
 * - File write permission denied
 * 
 * @package Canvastack\Canvastack\Library\Exceptions\Table
 * @author Canvastack Team
 * @since 1.0.0
 * 
 * @example Validating export request
 * ```php
 * public function validateExportRequest(string $format, array $data): void
 * {
 *     $supportedFormats = ['csv', 'excel', 'pdf'];
 *     
 *     if (!in_array($format, $supportedFormats)) {
 *         throw new ExportException(
 *             "Export format '{$format}' is not supported",
 *             0,
 *             null,
 *             [
 *                 'format' => $format,
 *                 'supported_formats' => $supportedFormats
 *             ]
 *         );
 *     }
 *     
 *     if (count($data) > 10000) {
 *         throw new ExportException(
 *             'Export data exceeds maximum allowed rows',
 *             0,
 *             null,
 *             [
 *                 'row_count' => count($data),
 *                 'max_rows' => 10000
 *             ]
 *         );
 *     }
 * }
 * ```
 * 
 * @example Handling export errors
 * ```php
 * try {
 *     $file = $export->generate($format, $data);
 * } catch (ExportException $e) {
 *     // Log export error
 *     Log::error('Export failed', [
 *         'message' => $e->getMessage(),
 *         'format' => $e->getExportFormat(),
 *         'row_count' => $e->getRowCount()
 *     ]);
 *     
 *     // Return error response
 *     return response()->json([
 *         'error' => 'Export failed',
 *         'message' => $e->getMessage()
 *     ], 500);
 * }
 * ```
 */
class ExportException extends TableDataException
{
    /**
     * The export format that failed
     * 
     * Possible values: 'csv', 'excel', 'pdf'
     *
     * @var string|null
     */
    protected ?string $exportFormat = null;

    /**
     * The number of rows being exported
     *
     * @var int|null
     */
    protected ?int $rowCount = null;

    /**
     * The file path where export was attempted
     *
     * @var string|null
     */
    protected ?string $filePath = null;

    /**
     * Create a new ExportException instance
     *
     * @param string $message The exception message
     * @param int $code The exception code (default: 0)
     * @param \Exception|null $previous The previous exception for chaining
     * @param array $context Additional context data for debugging
     */
    public function __construct(
        string $message = "Export error",
        int $code = 0,
        ?\Exception $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
        $this->operationType = 'export';
    }

    /**
     * Set the export format
     *
     * @param string $format The export format
     * @return self
     */
    public function setExportFormat(string $format): self
    {
        $this->exportFormat = $format;
        return $this;
    }

    /**
     * Get the export format
     *
     * @return string|null The export format
     */
    public function getExportFormat(): ?string
    {
        return $this->exportFormat;
    }

    /**
     * Set the row count
     *
     * @param int $count The number of rows
     * @return self
     */
    public function setRowCount(int $count): self
    {
        $this->rowCount = $count;
        return $this;
    }

    /**
     * Get the row count
     *
     * @return int|null The number of rows
     */
    public function getRowCount(): ?int
    {
        return $this->rowCount;
    }

    /**
     * Set the file path
     *
     * @param string $path The file path
     * @return self
     */
    public function setFilePath(string $path): self
    {
        $this->filePath = $path;
        return $this;
    }

    /**
     * Get the file path
     *
     * @return string|null The file path
     */
    public function getFilePath(): ?string
    {
        return $this->filePath;
    }
}
