<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Form\Features\Ajax;

use Closure;
use InvalidArgumentException;

/**
 * EloquentSyncBuilder - Fluent interface for building Eloquent-based sync relationships.
 *
 * Provides a developer-friendly API for creating cascading dropdown relationships
 * using Eloquent models instead of raw SQL queries. Maintains backward compatibility
 * with the existing SQL-based sync() method.
 */
class EloquentSyncBuilder
{
    protected string $sourceField;

    protected string $targetField;

    protected string|Closure $model;

    protected ?string $relationship = null;

    protected string $displayColumn = 'name';

    protected string $valueColumn = 'id';

    protected array $constraints = [];

    protected ?string $orderByColumn = null;

    protected string $orderDirection = 'asc';

    protected $selected = null;

    protected ?AjaxSync $ajaxSync = null;

    protected ?EloquentSyncAdapter $adapter = null;

    protected bool $built = false;

    /**
     * Create new Eloquent sync builder.
     *
     * @param string $sourceField Source field name (e.g., 'province_id')
     * @param string|Closure $model Model class name or query closure
     */
    public function __construct(string $sourceField, string|Closure $model)
    {
        $this->sourceField = $sourceField;
        $this->model = $model;

        // Auto-generate target field name from source field
        // e.g., 'province_id' -> 'city_id' (assumes next level in hierarchy)
        $this->targetField = $this->generateTargetFieldName($sourceField);

        // Initialize adapter
        $this->adapter = app(EloquentSyncAdapter::class);
    }

    /**
     * Set the relationship name to use.
     *
     * @param string $name Relationship method name on the model
     * @return self
     */
    public function relationship(string $name): self
    {
        $this->relationship = $name;

        return $this;
    }

    /**
     * Set the target field name explicitly.
     *
     * @param string $fieldName Target field name
     * @return self
     */
    public function target(string $fieldName): self
    {
        $this->targetField = $fieldName;

        return $this;
    }

    /**
     * Set the display column name.
     *
     * @param string $column Column name to use as option label
     * @return self
     */
    public function display(string $column): self
    {
        $this->displayColumn = $column;

        return $this;
    }

    /**
     * Set the value column name.
     *
     * @param string $column Column name to use as option value
     * @return self
     */
    public function value(string $column): self
    {
        $this->valueColumn = $column;

        return $this;
    }

    /**
     * Add a where constraint to the query.
     *
     * @param string $column Column name
     * @param mixed $operator Operator or value if using = operator
     * @param mixed $value Value (optional if operator is the value)
     * @return self
     */
    public function where(string $column, $operator, $value = null): self
    {
        // Handle where($column, $value) syntax
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->constraints[] = [
            'type' => 'where',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
        ];

        return $this;
    }

    /**
     * Add a whereIn constraint to the query.
     *
     * @param string $column Column name
     * @param array $values Array of values
     * @return self
     */
    public function whereIn(string $column, array $values): self
    {
        $this->constraints[] = [
            'type' => 'whereIn',
            'column' => $column,
            'values' => $values,
        ];

        return $this;
    }

    /**
     * Add a whereNull constraint to the query.
     *
     * @param string $column Column name
     * @return self
     */
    public function whereNull(string $column): self
    {
        $this->constraints[] = [
            'type' => 'whereNull',
            'column' => $column,
        ];

        return $this;
    }

    /**
     * Add a whereNotNull constraint to the query.
     *
     * @param string $column Column name
     * @return self
     */
    public function whereNotNull(string $column): self
    {
        $this->constraints[] = [
            'type' => 'whereNotNull',
            'column' => $column,
        ];

        return $this;
    }

    /**
     * Set the order by column and direction.
     *
     * @param string $column Column name to order by
     * @param string $direction Sort direction (asc or desc)
     * @return self
     */
    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $this->orderByColumn = $column;
        $this->orderDirection = strtolower($direction);

        return $this;
    }

    /**
     * Set the pre-selected value.
     *
     * @param mixed $value Value to pre-select in target field
     * @return self
     */
    public function selected($value): self
    {
        $this->selected = $value;

        return $this;
    }

    /**
     * Set the AjaxSync instance.
     *
     * @param AjaxSync $ajaxSync AjaxSync instance
     * @return self
     */
    public function setAjaxSync(AjaxSync $ajaxSync): self
    {
        $this->ajaxSync = $ajaxSync;

        return $this;
    }

    /**
     * Build and register the sync relationship.
     *
     * Converts the Eloquent configuration to SQL and registers it
     * with the AjaxSync component.
     *
     * @return array Configuration array
     * @throws InvalidArgumentException If configuration is invalid
     */
    public function build(): array
    {
        // Validate configuration
        $this->validate();

        // Convert to SQL based on model type
        if ($this->model instanceof Closure) {
            // Closure-based query
            $result = $this->adapter->closureToSql($this->model);
            $sql = $result['sql'];
            $foreignKey = $this->sourceField; // Use source field as foreign key
        } else {
            // Model class with relationship
            if ($this->relationship === null) {
                throw new InvalidArgumentException(
                    'Relationship name must be specified when using model class. ' .
                    'Call ->relationship("relationshipName") before build()'
                );
            }

            $config = [
                'display' => $this->displayColumn,
                'value' => $this->valueColumn,
                'constraints' => $this->constraints,
                'orderBy' => $this->orderByColumn,
                'orderDirection' => $this->orderDirection,
            ];

            $result = $this->adapter->modelToSql($this->model, $this->relationship, $config);
            $sql = $result['sql'];
            $foreignKey = $result['foreign_key'];
        }

        // Register with AjaxSync if available
        if ($this->ajaxSync !== null) {
            $this->ajaxSync->register(
                $this->sourceField,
                $this->targetField,
                $this->valueColumn,
                $this->displayColumn,
                $sql,
                $this->selected
            );
        }

        // Mark as built to prevent double-building
        $this->built = true;

        return [
            'source' => $this->sourceField,
            'target' => $this->targetField,
            'sql' => $sql,
            'foreign_key' => $foreignKey,
            'display' => $this->displayColumn,
            'value' => $this->valueColumn,
            'selected' => $this->selected,
        ];
    }

    /**
     * Validate the configuration.
     *
     * @throws InvalidArgumentException If configuration is invalid
     */
    protected function validate(): void
    {
        if (empty($this->sourceField)) {
            throw new InvalidArgumentException('Source field name is required');
        }

        if (empty($this->targetField)) {
            throw new InvalidArgumentException('Target field name is required');
        }

        if (is_string($this->model)) {
            if (!class_exists($this->model)) {
                throw new InvalidArgumentException("Model class {$this->model} does not exist");
            }
        } elseif (!$this->model instanceof Closure) {
            throw new InvalidArgumentException('Model must be a class name string or Closure');
        }
    }

    /**
     * Generate target field name from source field name.
     *
     * Attempts to intelligently generate the target field name based on
     * common naming conventions.
     *
     * @param string $sourceField Source field name
     * @return string Generated target field name
     */
    protected function generateTargetFieldName(string $sourceField): string
    {
        // Remove _id suffix if present
        $baseName = preg_replace('/_id$/', '', $sourceField);

        // Common hierarchical relationships
        $hierarchies = [
            'country' => 'province',
            'province' => 'city',
            'state' => 'city',
            'category' => 'subcategory',
            'parent' => 'child',
        ];

        // Check if we have a known hierarchy
        if (isset($hierarchies[$baseName])) {
            return $hierarchies[$baseName] . '_id';
        }

        // Default: append _child or use generic naming
        return $baseName . '_child_id';
    }

    /**
     * Get the source field name.
     *
     * @return string
     */
    public function getSourceField(): string
    {
        return $this->sourceField;
    }

    /**
     * Get the target field name.
     *
     * @return string
     */
    public function getTargetField(): string
    {
        return $this->targetField;
    }

    /**
     * Get the model class or closure.
     *
     * @return string|Closure
     */
    public function getModel(): string|Closure
    {
        return $this->model;
    }

    /**
     * Get the relationship name.
     *
     * @return string|null
     */
    public function getRelationship(): ?string
    {
        return $this->relationship;
    }

    /**
     * Get the display column name.
     *
     * @return string
     */
    public function getDisplayColumn(): string
    {
        return $this->displayColumn;
    }

    /**
     * Get the value column name.
     *
     * @return string
     */
    public function getValueColumn(): string
    {
        return $this->valueColumn;
    }

    /**
     * Get all constraints.
     *
     * @return array
     */
    public function getConstraints(): array
    {
        return $this->constraints;
    }

    /**
     * Get the selected value.
     *
     * @return mixed
     */
    public function getSelected()
    {
        return $this->selected;
    }

    /**
     * Destructor - auto-build when builder goes out of scope.
     *
     * This allows the builder to work both with explicit build() calls
     * and with implicit building when the variable is destroyed.
     */
    public function __destruct()
    {
        // Only auto-build if not already built and configuration is valid
        if (!$this->built &&
            $this->ajaxSync !== null &&
            ($this->relationship !== null || $this->model instanceof Closure)) {
            try {
                $this->build();
            } catch (\Throwable $e) {
                // Silently ignore errors during destruction to prevent fatal errors
                // This can happen during test teardown when Laravel app is already destroyed
            }
        }
    }
}
