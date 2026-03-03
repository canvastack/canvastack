<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Form\Fields;

/**
 * HiddenField - Hidden input field.
 */
class HiddenField extends BaseField
{
    public function getType(): string
    {
        return 'hidden';
    }

    public function render(): string
    {
        return '';
    }
}
