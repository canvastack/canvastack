<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Form\Fields;

/**
 * BaseField - Abstract base class for all form fields.
 *
 * Provides common functionality and fluent interface for field configuration.
 */
abstract class BaseField
{
    protected string $name;

    protected ?string $label;

    protected mixed $value;

    protected array $attributes = [];

    protected ?object $model = null;

    protected bool $required = false;

    protected ?string $placeholder = null;

    protected ?string $icon = null;

    protected string $iconPosition = 'left';

    protected ?string $helpText = null;

    protected array $validationRules = [];

    public function __construct(string $name, ?string $label = null, mixed $value = null, array $attributes = [])
    {
        $this->name = $name;
        $this->label = $label ?? $this->generateLabel($name);
        $this->value = $value;
        $this->attributes = $attributes;

        // Extract common attributes
        $this->extractCommonAttributes();
    }

    /**
     * Generate label from field name.
     */
    protected function generateLabel(string $name): string
    {
        return ucwords(str_replace(['_', '-'], ' ', $name));
    }

    /**
     * Extract common attributes from attributes array.
     */
    protected function extractCommonAttributes(): void
    {
        if (isset($this->attributes['required'])) {
            $this->required = true;
            unset($this->attributes['required']);
        }

        if (isset($this->attributes['placeholder'])) {
            $this->placeholder = $this->attributes['placeholder'];
            unset($this->attributes['placeholder']);
        }
    }

    /**
     * Set model for value binding.
     */
    public function setModel(?object $model): self
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Get field value (from model if available).
     */
    public function getValue(): mixed
    {
        if ($this->model) {
            // Check if model is an Eloquent model
            if (method_exists($this->model, 'getAttribute')) {
                // Use Eloquent's getAttribute method which handles attributes array
                return $this->model->getAttribute($this->name);
            }

            // Fallback to property_exists for non-Eloquent models
            if (property_exists($this->model, $this->name)) {
                return $this->model->{$this->name};
            }
        }

        return $this->value;
    }

    /**
     * Set field value.
     */
    public function setValue(mixed $value): self
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Set placeholder.
     */
    public function placeholder(string $placeholder): self
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    /**
     * Set icon.
     */
    public function icon(string $icon, string $position = 'left'): self
    {
        $this->icon = $icon;
        $this->iconPosition = $position;

        return $this;
    }

    /**
     * Mark field as required.
     */
    public function required(bool $required = true): self
    {
        $this->required = $required;

        return $this;
    }

    /**
     * Set help text.
     */
    public function help(string $text): self
    {
        $this->helpText = $text;

        return $this;
    }

    /**
     * Add CSS class.
     */
    public function addClass(string $class): self
    {
        if (isset($this->attributes['class'])) {
            $this->attributes['class'] .= ' ' . $class;
        } else {
            $this->attributes['class'] = $class;
        }

        return $this;
    }

    /**
     * Set attribute.
     */
    public function attribute(string $key, mixed $value): self
    {
        $this->attributes[$key] = $value;

        return $this;
    }

    /**
     * Set multiple attributes.
     */
    public function attributes(array $attributes): self
    {
        $this->attributes = array_merge($this->attributes, $attributes);

        return $this;
    }

    /**
     * Add validation rule.
     */
    public function rule(string $rule): self
    {
        $this->validationRules[] = $rule;

        return $this;
    }

    /**
     * Set validation rules.
     */
    public function rules(array $rules): self
    {
        $this->validationRules = $rules;

        return $this;
    }

    // Getters
    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function getPlaceholder(): ?string
    {
        return $this->placeholder;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function getIconPosition(): string
    {
        return $this->iconPosition ?? 'left';
    }

    public function getHelpText(): ?string
    {
        return $this->helpText;
    }

    public function getValidationRules(): array
    {
        return $this->validationRules;
    }

    /**
     * Get field type.
     */
    abstract public function getType(): string;

    /**
     * Render the field (to be implemented by renderers).
     */
    abstract public function render(): string;
}
