<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Form\Fields;

/**
 * TimeField - Time input field.
 */
class TimeField extends TextField
{
    protected ?string $format = 'H:i:s';

    public function getType(): string
    {
        return 'time';
    }

    /**
     * Set time format.
     */
    public function format(string $format): self
    {
        $this->format = $format;

        return $this;
    }

    public function getFormat(): string
    {
        return $this->format;
    }
}
