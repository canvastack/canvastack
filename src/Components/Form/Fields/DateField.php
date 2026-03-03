<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Form\Fields;

/**
 * DateField - Date input field.
 */
class DateField extends TextField
{
    protected ?string $minDate = null;

    protected ?string $maxDate = null;

    protected ?string $format = 'Y-m-d';

    public function getType(): string
    {
        return 'date';
    }

    /**
     * Set minimum date.
     */
    public function minDate(string $date): self
    {
        $this->minDate = $date;
        $this->attributes['min'] = $date;

        return $this;
    }

    /**
     * Set maximum date.
     */
    public function maxDate(string $date): self
    {
        $this->maxDate = $date;
        $this->attributes['max'] = $date;

        return $this;
    }

    /**
     * Set date format.
     */
    public function format(string $format): self
    {
        $this->format = $format;

        return $this;
    }

    public function getMinDate(): ?string
    {
        return $this->minDate;
    }

    public function getMaxDate(): ?string
    {
        return $this->maxDate;
    }

    public function getFormat(): string
    {
        return $this->format;
    }
}
