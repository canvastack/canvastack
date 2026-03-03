<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Form\Fields;

/**
 * TextareaField - Textarea field.
 */
class TextareaField extends BaseField
{
    protected ?int $rows = 4;

    protected ?int $maxLength = null;

    protected bool $wysiwyg = false;

    public function getType(): string
    {
        return 'textarea';
    }

    /**
     * Set number of rows.
     */
    public function rows(int $rows): self
    {
        $this->rows = $rows;
        $this->attributes['rows'] = $rows;

        return $this;
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
     * Enable WYSIWYG editor.
     */
    public function wysiwyg(bool $enable = true): self
    {
        $this->wysiwyg = $enable;
        if ($enable) {
            $this->addClass('wysiwyg-editor');
        }

        return $this;
    }

    public function getRows(): int
    {
        return $this->rows;
    }

    public function getMaxLength(): ?int
    {
        return $this->maxLength;
    }

    public function isWysiwyg(): bool
    {
        return $this->wysiwyg;
    }

    public function render(): string
    {
        return '';
    }
}
