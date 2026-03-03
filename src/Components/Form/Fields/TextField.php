<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Form\Fields;

/**
 * TextField - Text input field.
 */
class TextField extends BaseField
{
    protected ?int $maxLength = null;

    protected ?int $minLength = null;

    public function getType(): string
    {
        return 'text';
    }

    /**
     * Set max length.
     */
    public function maxLength(int $length): self
    {
        $this->maxLength = $length;
        $this->attributes['maxlength'] = $length;

        return $this;
    }

    /**
     * Set min length.
     */
    public function minLength(int $length): self
    {
        $this->minLength = $length;
        $this->attributes['minlength'] = $length;

        return $this;
    }

    public function getMaxLength(): ?int
    {
        return $this->maxLength;
    }

    public function getMinLength(): ?int
    {
        return $this->minLength;
    }

    public function render(): string
    {
        // Rendering is handled by renderer classes
        return '';
    }
}
