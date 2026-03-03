<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Form\Fields;

/**
 * NumberField - Number input field.
 */
class NumberField extends TextField
{
    protected ?float $min = null;

    protected ?float $max = null;

    protected ?float $step = null;

    public function getType(): string
    {
        return 'number';
    }

    /**
     * Set minimum value.
     */
    public function min(float $min): self
    {
        $this->min = $min;
        $this->attributes['min'] = $min;

        return $this;
    }

    /**
     * Set maximum value.
     */
    public function max(float $max): self
    {
        $this->max = $max;
        $this->attributes['max'] = $max;

        return $this;
    }

    /**
     * Set step value.
     */
    public function step(float $step): self
    {
        $this->step = $step;
        $this->attributes['step'] = $step;

        return $this;
    }

    public function getMin(): ?float
    {
        return $this->min;
    }

    public function getMax(): ?float
    {
        return $this->max;
    }

    public function getStep(): ?float
    {
        return $this->step;
    }
}
