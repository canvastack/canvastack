<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Form\Fields;

/**
 * EmailField - Email input field.
 */
class EmailField extends TextField
{
    public function getType(): string
    {
        return 'email';
    }
}
