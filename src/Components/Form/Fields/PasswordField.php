<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Form\Fields;

/**
 * PasswordField - Password input field.
 */
class PasswordField extends TextField
{
    public function getType(): string
    {
        return 'password';
    }

    /**
     * Password fields should never return values.
     */
    public function getValue(): mixed
    {
        return null;
    }
}
