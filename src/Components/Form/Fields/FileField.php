<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Form\Fields;

/**
 * FileField - File upload field.
 */
class FileField extends BaseField
{
    protected ?string $accept = null;

    protected bool $multiple = false;

    protected bool $preview = false;

    protected ?int $maxSize = null; // in KB

    public function __construct(string $name, ?string $label = null, $value = null, array $attributes = [])
    {
        parent::__construct($name, $label, $value, $attributes);

        // Check for legacy 'imagepreview' attribute
        if (in_array('imagepreview', $attributes, true)) {
            $this->preview = true;
        }
    }

    public function getType(): string
    {
        return 'file';
    }

    /**
     * Set accepted file types.
     */
    public function accept(string $accept): self
    {
        $this->accept = $accept;
        $this->attributes['accept'] = $accept;

        return $this;
    }

    /**
     * Enable multiple file upload.
     */
    public function multiple(bool $multiple = true): self
    {
        $this->multiple = $multiple;
        if ($multiple) {
            $this->attributes['multiple'] = 'multiple';
        }

        return $this;
    }

    /**
     * Enable file preview.
     */
    public function preview(bool $preview = true): self
    {
        $this->preview = $preview;

        return $this;
    }

    /**
     * Set max file size in KB.
     */
    public function maxSize(int $sizeInKB): self
    {
        $this->maxSize = $sizeInKB;

        return $this;
    }

    public function getAccept(): ?string
    {
        return $this->accept;
    }

    public function isMultiple(): bool
    {
        return $this->multiple;
    }

    public function hasPreview(): bool
    {
        return $this->preview;
    }

    public function getMaxSize(): ?int
    {
        return $this->maxSize;
    }

    public function render(): string
    {
        return '';
    }
}
