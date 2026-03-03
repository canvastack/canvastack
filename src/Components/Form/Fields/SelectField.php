<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Form\Fields;

/**
 * SelectField - Select dropdown field.
 */
class SelectField extends BaseField
{
    protected array $options = [];

    protected mixed $selected = null;

    protected bool $multiple = false;

    protected bool $searchable = false;

    public function __construct(string $name, ?string $label = null, array $options = [], array $attributes = [])
    {
        parent::__construct($name, $label, null, $attributes);
        $this->options = $options;
    }

    public function getType(): string
    {
        return 'select';
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
     * Set selected value.
     */
    public function setSelected(mixed $selected): self
    {
        $this->selected = $selected;

        return $this;
    }

    /**
     * Get selected value (from model if available).
     */
    public function getSelected(): mixed
    {
        if ($this->model && property_exists($this->model, $this->name)) {
            return $this->model->{$this->name};
        }

        return $this->selected;
    }

    /**
     * Enable multiple selection.
     */
    public function multiple(bool $multiple = true): self
    {
        $this->multiple = $multiple;
        if ($multiple) {
            $this->attributes['multiple'] = 'multiple';
        }

        return $this;
    }

    /**
     * Enable searchable dropdown.
     */
    public function searchable(bool $searchable = true): self
    {
        $this->searchable = $searchable;
        if ($searchable) {
            $this->addClass('searchable-select');
        }

        return $this;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function isMultiple(): bool
    {
        return $this->multiple;
    }

    public function isSearchable(): bool
    {
        return $this->searchable;
    }

    public function render(): string
    {
        return '';
    }
}
