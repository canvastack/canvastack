<?php

namespace Canvastack\Canvastack\Library\Exceptions\Table;

/**
 * Exception thrown when table configuration or data validation fails
 * 
 * This is the base class for all validation-related exceptions in the Table
 * Components system. It covers validation issues such as:
 * - Invalid column configurations
 * - Invalid pagination parameters
 * - Invalid sort parameters
 * - Invalid filter configurations
 * - Invalid data formats
 * 
 * @package Canvastack\Canvastack\Library\Exceptions\Table
 * @author Canvastack Team
 * @since 1.0.0
 * 
 * @example Validating table configuration
 * ```php
 * public function validateConfiguration(array $config): void
 * {
 *     if (empty($config['fields'])) {
 *         throw new TableValidationException(
 *             'Table configuration must include fields',
 *             0,
 *             null,
 *             ['config' => $config]
 *         );
 *     }
 *     
 *     if (isset($config['actions']) && !is_array($config['actions'])) {
 *         throw new TableValidationException(
 *             'Actions must be an array',
 *             0,
 *             null,
 *             [
 *                 'actions' => $config['actions'],
 *                 'type' => gettype($config['actions'])
 *             ]
 *         );
 *     }
 * }
 * ```
 * 
 * @example Validating field definitions
 * ```php
 * public function validateFields(array $fields): void
 * {
 *     foreach ($fields as $field => $label) {
 *         if (!is_string($field) || empty($field)) {
 *             throw new TableValidationException(
 *                 'Field name must be a non-empty string',
 *                 0,
 *                 null,
 *                 [
 *                     'field' => $field,
 *                     'label' => $label
 *                 ]
 *             );
 *         }
 *         
 *         if (!is_string($label)) {
 *             throw new TableValidationException(
 *                 'Field label must be a string',
 *                 0,
 *                 null,
 *                 [
 *                     'field' => $field,
 *                     'label' => $label,
 *                     'type' => gettype($label)
 *                 ]
 *             );
 *         }
 *     }
 * }
 * ```
 * 
 * @example Handling validation errors
 * ```php
 * try {
 *     $table->setFields($fields);
 * } catch (TableValidationException $e) {
 *     // Log validation error
 *     Log::warning('Table validation failed', [
 *         'message' => $e->getMessage(),
 *         'context' => $e->getContext(),
 *         'validation_errors' => $e->getValidationErrors()
 *     ]);
 *     
 *     // Return validation errors to user
 *     return response()->json([
 *         'error' => 'Validation failed',
 *         'message' => $e->getMessage(),
 *         'errors' => $e->getValidationErrors()
 *     ], 422);
 * }
 * ```
 */
class TableValidationException extends TableComponentException
{
    /**
     * Array of validation errors
     * 
     * Format: ['field' => 'error message', ...]
     *
     * @var array
     */
    protected array $validationErrors = [];

    /**
     * The field or parameter that failed validation
     *
     * @var string|null
     */
    protected ?string $failedField = null;

    /**
     * The value that failed validation
     *
     * @var mixed
     */
    protected mixed $failedValue = null;

    /**
     * Get the validation errors
     *
     * @return array The validation errors
     * 
     * @example
     * ```php
     * try {
     *     $table->validateConfiguration($config);
     * } catch (TableValidationException $e) {
     *     $errors = $e->getValidationErrors();
     *     foreach ($errors as $field => $message) {
     *         echo "Error in {$field}: {$message}\n";
     *     }
     * }
     * ```
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    /**
     * Set the validation errors
     *
     * @param array $errors The validation errors
     * @return self
     * 
     * @example
     * ```php
     * $exception = new TableValidationException('Multiple validation errors');
     * $exception->setValidationErrors([
     *     'fields' => 'Fields array cannot be empty',
     *     'actions' => 'Actions must be an array'
     * ]);
     * throw $exception;
     * ```
     */
    public function setValidationErrors(array $errors): self
    {
        $this->validationErrors = $errors;
        return $this;
    }

    /**
     * Add a single validation error
     *
     * @param string $field The field name
     * @param string $message The error message
     * @return self
     * 
     * @example
     * ```php
     * $exception = new TableValidationException('Validation failed');
     * $exception->addValidationError('page_length', 'Must be between 1 and 100')
     *           ->addValidationError('start', 'Must be a non-negative integer');
     * throw $exception;
     * ```
     */
    public function addValidationError(string $field, string $message): self
    {
        $this->validationErrors[$field] = $message;
        return $this;
    }

    /**
     * Set the field that failed validation
     *
     * @param string $field The field name
     * @return self
     */
    public function setFailedField(string $field): self
    {
        $this->failedField = $field;
        return $this;
    }

    /**
     * Get the field that failed validation
     *
     * @return string|null The field name
     */
    public function getFailedField(): ?string
    {
        return $this->failedField;
    }

    /**
     * Set the value that failed validation
     *
     * @param mixed $value The failed value
     * @return self
     */
    public function setFailedValue(mixed $value): self
    {
        $this->failedValue = $value;
        return $this;
    }

    /**
     * Get the value that failed validation
     *
     * @return mixed The failed value
     */
    public function getFailedValue(): mixed
    {
        return $this->failedValue;
    }

    /**
     * Check if there are multiple validation errors
     *
     * @return bool True if multiple errors exist
     */
    public function hasMultipleErrors(): bool
    {
        return count($this->validationErrors) > 1;
    }

    /**
     * Get a string representation with validation details
     *
     * @return string
     */
    public function __toString(): string
    {
        $string = "[VALIDATION ERROR] " . $this->getMessage();
        
        if ($this->failedField) {
            $string .= "\nFailed Field: " . $this->failedField;
        }
        
        if ($this->failedValue !== null) {
            $value = is_scalar($this->failedValue) 
                ? $this->failedValue 
                : json_encode($this->failedValue);
            $string .= "\nFailed Value: " . $value;
        }
        
        if (!empty($this->validationErrors)) {
            $string .= "\nValidation Errors:";
            foreach ($this->validationErrors as $field => $message) {
                $string .= "\n  - {$field}: {$message}";
            }
        }
        
        $string .= "\nFile: " . $this->getFile() . ":" . $this->getLine();
        
        if (!empty($this->context)) {
            $string .= "\nContext: " . json_encode($this->context, JSON_PRETTY_PRINT);
        }
        
        return $string;
    }
}
