<?php

namespace Canvastack\Canvastack\Library\Exceptions\Table;

/**
 * Exception thrown when formula calculation fails
 * 
 * This exception is thrown when:
 * - Formula syntax is invalid
 * - Formula references non-existent fields
 * - Formula calculation produces an error
 * - Division by zero in formula
 * - Invalid operator in formula
 * - Circular reference in formula
 * 
 * @package Canvastack\Canvastack\Library\Exceptions\Table
 * @author Canvastack Team
 * @since 1.0.0
 * 
 * @example Validating formula syntax
 * ```php
 * public function validateFormula(string $formula): void
 * {
 *     // Check for invalid operators
 *     if (preg_match('/[^a-zA-Z0-9_+\-*\/().\s]/', $formula)) {
 *         throw new FormulaException(
 *             'Formula contains invalid characters',
 *             0,
 *             null,
 *             ['formula' => $formula]
 *         );
 *     }
 *     
 *     // Check for balanced parentheses
 *     if (substr_count($formula, '(') !== substr_count($formula, ')')) {
 *         throw new FormulaException(
 *             'Formula has unbalanced parentheses',
 *             0,
 *             null,
 *             ['formula' => $formula]
 *         );
 *     }
 * }
 * ```
 * 
 * @example Handling formula errors
 * ```php
 * try {
 *     $result = $this->calculateFormula($formula, $row);
 * } catch (FormulaException $e) {
 *     // Log formula error
 *     Log::warning('Formula calculation failed', [
 *         'message' => $e->getMessage(),
 *         'formula' => $e->getFormula(),
 *         'row' => $row
 *     ]);
 *     
 *     // Return null or default value
 *     return null;
 * }
 * ```
 */
class FormulaException extends TableDataException
{
    /**
     * The formula expression that failed
     *
     * @var string|null
     */
    protected ?string $formula = null;

    /**
     * The field name for the formula column
     *
     * @var string|null
     */
    protected ?string $fieldName = null;

    /**
     * The row data being processed when the error occurred
     *
     * @var array|null
     */
    protected ?array $rowData = null;

    /**
     * Create a new FormulaException instance
     *
     * @param string $message The exception message
     * @param int $code The exception code (default: 0)
     * @param \Exception|null $previous The previous exception for chaining
     * @param array $context Additional context data for debugging
     */
    public function __construct(
        string $message = "Formula calculation error",
        int $code = 0,
        ?\Exception $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
        $this->operationType = 'formula';
    }

    /**
     * Set the formula expression
     *
     * @param string $formula The formula expression
     * @return self
     */
    public function setFormula(string $formula): self
    {
        $this->formula = $formula;
        return $this;
    }

    /**
     * Get the formula expression
     *
     * @return string|null The formula expression
     */
    public function getFormula(): ?string
    {
        return $this->formula;
    }

    /**
     * Set the field name
     *
     * @param string $fieldName The field name
     * @return self
     */
    public function setFieldName(string $fieldName): self
    {
        $this->fieldName = $fieldName;
        return $this;
    }

    /**
     * Get the field name
     *
     * @return string|null The field name
     */
    public function getFieldName(): ?string
    {
        return $this->fieldName;
    }

    /**
     * Set the row data
     *
     * @param array $rowData The row data
     * @return self
     */
    public function setRowData(array $rowData): self
    {
        $this->rowData = $rowData;
        return $this;
    }

    /**
     * Get the row data
     *
     * @return array|null The row data
     */
    public function getRowData(): ?array
    {
        return $this->rowData;
    }
}
