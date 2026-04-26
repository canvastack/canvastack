<?php

namespace Canvastack\Canvastack\Library\Exceptions\Table;

/**
 * Exception thrown when relationship loading or processing fails
 * 
 * This exception is thrown when:
 * - Relationship definition is invalid
 * - Related model doesn't exist
 * - Foreign key is missing or invalid
 * - Eager loading fails
 * - Circular relationship detected
 * - Relationship data cannot be loaded
 * 
 * @package Canvastack\Canvastack\Library\Exceptions\Table
 * @author Canvastack Team
 * @since 1.0.0
 * 
 * @example Validating relationship definition
 * ```php
 * public function validateRelationship(array $relationship): void
 * {
 *     if (!isset($relationship['model'])) {
 *         throw new RelationshipException(
 *             'Relationship definition must include model class',
 *             0,
 *             null,
 *             ['relationship' => $relationship]
 *         );
 *     }
 *     
 *     if (!class_exists($relationship['model'])) {
 *         throw new RelationshipException(
 *             "Related model class does not exist: {$relationship['model']}",
 *             0,
 *             null,
 *             ['model' => $relationship['model']]
 *         );
 *     }
 * }
 * ```
 * 
 * @example Handling relationship errors
 * ```php
 * try {
 *     $data = $this->loadWithRelationships($model, $relationships);
 * } catch (RelationshipException $e) {
 *     // Log relationship error
 *     Log::error('Relationship loading failed', [
 *         'message' => $e->getMessage(),
 *         'relationship' => $e->getRelationshipName(),
 *         'model' => $e->getModelClass()
 *     ]);
 *     
 *     // Load without relationships as fallback
 *     return $this->loadWithoutRelationships($model);
 * }
 * ```
 */
class RelationshipException extends TableDataException
{
    /**
     * The name of the relationship that failed
     *
     * @var string|null
     */
    protected ?string $relationshipName = null;

    /**
     * The model class involved in the relationship
     *
     * @var string|null
     */
    protected ?string $modelClass = null;

    /**
     * The related model class
     *
     * @var string|null
     */
    protected ?string $relatedModelClass = null;

    /**
     * Create a new RelationshipException instance
     *
     * @param string $message The exception message
     * @param int $code The exception code (default: 0)
     * @param \Exception|null $previous The previous exception for chaining
     * @param array $context Additional context data for debugging
     */
    public function __construct(
        string $message = "Relationship error",
        int $code = 0,
        ?\Exception $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
        $this->operationType = 'relationship';
    }

    /**
     * Set the relationship name
     *
     * @param string $name The relationship name
     * @return self
     */
    public function setRelationshipName(string $name): self
    {
        $this->relationshipName = $name;
        return $this;
    }

    /**
     * Get the relationship name
     *
     * @return string|null The relationship name
     */
    public function getRelationshipName(): ?string
    {
        return $this->relationshipName;
    }

    /**
     * Set the model class
     *
     * @param string $class The model class name
     * @return self
     */
    public function setModelClass(string $class): self
    {
        $this->modelClass = $class;
        return $this;
    }

    /**
     * Get the model class
     *
     * @return string|null The model class name
     */
    public function getModelClass(): ?string
    {
        return $this->modelClass;
    }

    /**
     * Set the related model class
     *
     * @param string $class The related model class name
     * @return self
     */
    public function setRelatedModelClass(string $class): self
    {
        $this->relatedModelClass = $class;
        return $this;
    }

    /**
     * Get the related model class
     *
     * @return string|null The related model class name
     */
    public function getRelatedModelClass(): ?string
    {
        return $this->relatedModelClass;
    }
}
