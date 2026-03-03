<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Form\Fields;

/**
 * CheckboxField - Checkbox input field.
 */
class CheckboxField extends BaseField
{
    protected array $options = [];

    protected mixed $checked = null;

    protected bool $inline = false;

    protected ?string $checkType = null;

    public function __construct(string $name, ?string $label = null, array $options = [], array $attributes = [])
    {
        parent::__construct($name, $label, null, $attributes);
        $this->options = $options;

        // Detect check_type from attributes
        if (isset($attributes['check_type'])) {
            $this->checkType = $attributes['check_type'];
        }
    }

    public function getType(): string
    {
        return 'checkbox';
    }

    /**
     * Set options.
     */
    public function options(array $options): self
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Set checked values.
     */
    public function setChecked(mixed $checked): self
    {
        $this->checked = $checked;

        return $this;
    }

    /**
     * Get checked values (from model if available).
     */
    public function getChecked(): mixed
    {
        if ($this->model && property_exists($this->model, $this->name)) {
            $value = $this->model->{$this->name};

            // Handle comma-separated string
            if (is_string($value) && str_contains($value, ',')) {
                return array_map('intval', explode(',', $value));
            }

            return $value;
        }

        return $this->checked;
    }

    /**
     * Display checkboxes inline.
     */
    public function inline(bool $inline = true): self
    {
        $this->inline = $inline;

        return $this;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function isInline(): bool
    {
        return $this->inline;
    }

    /**
     * Get check type (standard or switch).
     */
    public function getCheckType(): ?string
    {
        return $this->checkType;
    }

    /**
     * Set check type to switch.
     */
    public function asSwitch(): self
    {
        $this->checkType = 'switch';

        return $this;
    }

    /**
     * Alias for asSwitch() - Set check type to switch.
     */
    public function switch(): self
    {
        return $this->asSwitch();
    }

    public function render(): string
    {
        return '';
    }
}
