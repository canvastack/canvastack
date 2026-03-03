<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Form\Fields;

/**
 * DateTimeField - DateTime input field.
 */
class DateTimeField extends DateField
{
    public function __construct(string $name, ?string $label = null, $value = null, array $attributes = [])
    {
        parent::__construct($name, $label, $value, $attributes);
        $this->format = 'Y-m-d H:i:s';
    }

    public function getType(): string
    {
        return 'datetime';
    }
}
