<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Form\Features\Tabs;

use Canvastack\Canvastack\Components\Form\Fields\BaseField;
use Illuminate\Support\Str;

/**
 * Tab - Represents a single tab with its fields and content.
 *
 * Responsibilities:
 * - Store tab properties (label, id, classes)
 * - Manage fields associated with the tab
 * - Manage custom HTML content
 * - Detect validation errors in tab fields
 *
 * Requirements: 1.1, 1.2, 1.4, 1.11
 */
class Tab
{
    /**
     * @var string Tab label displayed in navigation
     */
    protected string $label;

    /**
     * @var string Unique tab identifier (slug)
     */
    protected string $id;

    /**
     * @var array<string> CSS classes for the tab
     */
    protected array $classes = [];

    /**
     * @var BaseField[] Fields associated with this tab
     */
    protected array $fields = [];

    /**
     * @var string[] Custom HTML content blocks
     */
    protected array $content = [];

    /**
     * @var bool Whether this tab is active by default
     */
    protected bool $active = false;

    /**
     * Create a new Tab instance.
     *
     * Requirement 1.1: Create tab with label and class
     * Requirement 1.2: Initialize tab properties
     *
     * @param string $label Tab label displayed in navigation
     * @param string|bool $class Optional CSS class or 'active' flag
     */
    public function __construct(string $label, string|bool $class = false)
    {
        $this->label = $label;
        $this->id = 'tab-' . Str::slug($label);

        if ($class !== false) {
            if (is_string($class)) {
                $this->classes[] = $class;
                if ($class === 'active') {
                    $this->active = true;
                }
            } elseif ($class === true) {
                $this->classes[] = 'active';
                $this->active = true;
            }
        }
    }

    /**
     * Get tab label.
     *
     * @return string Tab label
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * Get tab ID.
     *
     * @return string Tab identifier
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get tab CSS classes.
     *
     * @return array<string> Array of CSS classes
     */
    public function getClasses(): array
    {
        return $this->classes;
    }

    /**
     * Check if tab is active.
     *
     * @return bool True if tab is active, false otherwise
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * Set tab as active.
     *
     * @return self
     */
    public function setActive(bool $active = true): self
    {
        $this->active = $active;

        if ($active && !in_array('active', $this->classes)) {
            $this->classes[] = 'active';
        } elseif (!$active) {
            $this->classes = array_filter($this->classes, fn ($class) => $class !== 'active');
        }

        return $this;
    }

    /**
     * Add a field to this tab.
     *
     * Requirement 1.2: Manage fields associated with tab
     *
     * @param BaseField $field Field to add
     * @return void
     */
    public function addField(BaseField $field): void
    {
        $this->fields[] = $field;
    }

    /**
     * Get all fields in this tab.
     *
     * @return BaseField[] Array of fields
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Get count of fields in this tab.
     *
     * @return int Number of fields
     */
    public function getFieldCount(): int
    {
        return count($this->fields);
    }

    /**
     * Add custom HTML content to this tab.
     *
     * Requirement 1.4: Manage custom HTML content
     *
     * @param string $html HTML content to add
     * @return void
     */
    public function addContent(string $html): void
    {
        $this->content[] = $html;
    }

    /**
     * Get all custom content in this tab.
     *
     * @return string[] Array of HTML content blocks
     */
    public function getContent(): array
    {
        return $this->content;
    }

    /**
     * Check if tab has a specific CSS class.
     *
     * @param string $class Class name to check
     * @return bool True if class exists, false otherwise
     */
    public function hasClass(string $class): bool
    {
        return in_array($class, $this->classes);
    }

    /**
     * Add a CSS class to the tab.
     *
     * @param string $class Class name to add
     * @return self
     */
    public function addClass(string $class): self
    {
        if (!$this->hasClass($class)) {
            $this->classes[] = $class;
        }

        return $this;
    }

    /**
     * Remove a CSS class from the tab.
     *
     * @param string $class Class name to remove
     * @return self
     */
    public function removeClass(string $class): self
    {
        $this->classes = array_filter($this->classes, fn ($c) => $c !== $class);

        return $this;
    }

    /**
     * Check if this tab contains fields with validation errors.
     *
     * Iterates through all fields in the tab and checks if any field name
     * exists in the validation errors array. Used for error highlighting.
     *
     * Requirement 1.11: Detect validation errors for highlighting
     *
     * @param array<string, mixed> $errors Validation errors array (field_name => error_message)
     * @return bool True if tab contains errors, false otherwise
     */
    public function hasErrors(array $errors): bool
    {
        foreach ($this->fields as $field) {
            $fieldName = $field->getName();

            // Check if field name exists in errors array
            if (isset($errors[$fieldName])) {
                return true;
            }

            // Handle array field names (e.g., 'items[]', 'items[0]')
            $baseFieldName = rtrim($fieldName, '[]');
            foreach (array_keys($errors) as $errorKey) {
                if (str_starts_with($errorKey, $baseFieldName)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get fields with validation errors.
     *
     * @param array<string, mixed> $errors Validation errors array
     * @return BaseField[] Array of fields with errors
     */
    public function getFieldsWithErrors(array $errors): array
    {
        $fieldsWithErrors = [];

        foreach ($this->fields as $field) {
            $fieldName = $field->getName();

            if (isset($errors[$fieldName])) {
                $fieldsWithErrors[] = $field;
                continue;
            }

            // Handle array field names
            $baseFieldName = rtrim($fieldName, '[]');
            foreach (array_keys($errors) as $errorKey) {
                if (str_starts_with($errorKey, $baseFieldName)) {
                    $fieldsWithErrors[] = $field;
                    break;
                }
            }
        }

        return $fieldsWithErrors;
    }

    /**
     * Check if tab is empty (no fields and no content).
     *
     * @return bool True if tab is empty, false otherwise
     */
    public function isEmpty(): bool
    {
        return empty($this->fields) && empty($this->content);
    }

    /**
     * Get tab data as array.
     *
     * Useful for debugging and serialization
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'id' => $this->id,
            'classes' => $this->classes,
            'active' => $this->active,
            'field_count' => count($this->fields),
            'content_count' => count($this->content),
            'is_empty' => $this->isEmpty(),
        ];
    }
}
