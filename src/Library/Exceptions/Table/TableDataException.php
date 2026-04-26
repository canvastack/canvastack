<?php

namespace Canvastack\Canvastack\Library\Exceptions\Table;

/**
 * Exception thrown when data-related issues occur in Table Components
 * 
 * This is the base class for all data-related exceptions. It covers:
 * - Relationship loading errors
 * - Formula calculation errors
 * - Export generation errors
 * - Data transformation errors
 * - Data integrity issues
 * 
 * @package Canvastack\Canvastack\Library\Exceptions\Table
 * @author Canvastack Team
 * @since 1.0.0
 * 
 * @example Handling data errors
 * ```php
 * try {
 *     $table->lists('users', $fields, $actions);
 * } catch (TableDataException $e) {
 *     // Log data error
 *     Log::error('Table data error', [
 *         'message' => $e->getMessage(),
 *         'context' => $e->getContext()
 *     ]);
 *     
 *     // Return error response
 *     return response()->json([
 *         'error' => 'Data processing error',
 *         'message' => 'Unable to process table data'
 *     ], 500);
 * }
 * ```
 * 
 * @example Catching specific data exceptions
 * ```php
 * try {
 *     $table->lists('users', $fields, $actions);
 * } catch (RelationshipException $e) {
 *     // Handle relationship errors
 *     Log::error('Relationship error: ' . $e->getMessage());
 * } catch (FormulaException $e) {
 *     // Handle formula errors
 *     Log::error('Formula error: ' . $e->getMessage());
 * } catch (ExportException $e) {
 *     // Handle export errors
 *     Log::error('Export error: ' . $e->getMessage());
 * } catch (TableDataException $e) {
 *     // Handle all other data errors
 *     Log::error('Data error: ' . $e->getMessage());
 * }
 * ```
 */
class TableDataException extends TableComponentException
{
    /**
     * The type of data operation that failed
     * 
     * Possible values:
     * - 'fetch': Data fetching operation
     * - 'transform': Data transformation
     * - 'relationship': Relationship loading
     * - 'formula': Formula calculation
     * - 'export': Data export
     * - 'validation': Data validation
     *
     * @var string|null
     */
    protected ?string $operationType = null;

    /**
     * The affected data or record identifier
     *
     * @var mixed
     */
    protected mixed $affectedData = null;

    /**
     * Set the operation type
     *
     * @param string $type The operation type
     * @return self
     */
    public function setOperationType(string $type): self
    {
        $this->operationType = $type;
        return $this;
    }

    /**
     * Get the operation type
     *
     * @return string|null The operation type
     */
    public function getOperationType(): ?string
    {
        return $this->operationType;
    }

    /**
     * Set the affected data
     *
     * @param mixed $data The affected data
     * @return self
     */
    public function setAffectedData(mixed $data): self
    {
        $this->affectedData = $data;
        return $this;
    }

    /**
     * Get the affected data
     *
     * @return mixed The affected data
     */
    public function getAffectedData(): mixed
    {
        return $this->affectedData;
    }

    /**
     * Get a string representation with data details
     *
     * @return string
     */
    public function __toString(): string
    {
        $string = "[DATA ERROR] " . $this->getMessage();
        
        if ($this->operationType) {
            $string .= "\nOperation Type: " . $this->operationType;
        }
        
        if ($this->affectedData !== null) {
            $data = is_scalar($this->affectedData) 
                ? $this->affectedData 
                : json_encode($this->affectedData);
            $string .= "\nAffected Data: " . $data;
        }
        
        $string .= "\nFile: " . $this->getFile() . ":" . $this->getLine();
        
        if (!empty($this->context)) {
            $string .= "\nContext: " . json_encode($this->context, JSON_PRETTY_PRINT);
        }
        
        return $string;
    }
}
