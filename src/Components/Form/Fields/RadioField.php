<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Form\Fields;

/**
 * RadioField - Radio button field.
 */
class RadioField extends BaseField
{
    protected array $options = [];

    protected mixed $checked = null;

    protected bool $inline = false;

    public function __construct(string $name, ?string $label = null, array $options = [], array $attributes = [])
    {
        parent::__construct($name, $label, null, $attributes);
        $this->options = $options;
    }

    public function getType(): string
    {
        return 'radio';
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
     * Set checked value.
     */
    public function setChecked(mixed $checked): self
    {
        $this->checked = $checked;

        return $this;
    }

    /**
     * Get checked value (from model if available).
     */
    public function getChecked(): mixed
    {
        if ($this->model && property_exists($this->model, $this->name)) {
            return $this->model->{$this->name};
        }

        return $this->checked;
    }

    /**
     * Display radio buttons inline.
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

    public function render(): string
    {
        return '';
    }
}
