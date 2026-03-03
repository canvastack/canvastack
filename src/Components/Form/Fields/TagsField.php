<?php

namespace Canvastack\Canvastack\Components\Form\Fields;

/**
 * TagsField Component.
 *
 * Represents a tags input field with add/remove functionality.
 */
class TagsField extends BaseField
{
    protected array $whitelist = [];

    protected ?int $maxTags = null;

    protected string $delimiters = ',';

    protected bool $enforceWhitelist = false;

    /**
     * Set autocomplete whitelist.
     */
    public function whitelist(array $whitelist): self
    {
        $this->whitelist = $whitelist;

        return $this;
    }

    /**
     * Set maximum number of tags.
     */
    public function maxTags(int $max): self
    {
        $this->maxTags = $max;

        return $this;
    }

    /**
     * Set tag delimiters.
     */
    public function delimiters(string $delimiters): self
    {
        $this->delimiters = $delimiters;

        return $this;
    }

    /**
     * Enforce whitelist (only allow tags from whitelist).
     */
    public function enforceWhitelist(bool $enforce = true): self
    {
        $this->enforceWhitelist = $enforce;

        return $this;
    }

    /**
     * Get tags input configuration.
     */
    public function getTagsConfig(): array
    {
        return [
            'whitelist' => $this->whitelist,
            'maxTags' => $this->maxTags,
            'delimiters' => $this->delimiters,
            'enforceWhitelist' => $this->enforceWhitelist,
            'autocomplete' => !empty($this->whitelist),
        ];
    }

    /**
     * Get field type.
     */
    public function getType(): string
    {
        return 'tags';
    }

    /**
     * Render the tags field.
     */
    public function render(): string
    {
        $name = $this->getName();
        $label = $this->getLabel();
        $value = $this->getValue();
        $attributes = $this->getAttributesString();
        $config = $this->getTagsConfig();

        // Convert value to JSON if it's an array
        $jsonValue = is_array($value) ? json_encode($value) : $value;

        $html = '<div class="form-group mb-4">';

        if ($label) {
            $html .= '<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">';
            $html .= htmlspecialchars($label);
            $html .= '</label>';
        }

        $html .= '<input type="text" ';
        $html .= 'name="' . htmlspecialchars($name) . '" ';
        $html .= 'id="' . htmlspecialchars($name) . '" ';
        $html .= 'value="' . htmlspecialchars($jsonValue ?? '') . '" ';
        $html .= $attributes;
        $html .= ' class="tags-input form-input w-full" ';
        $html .= ' data-tags-config=\'' . json_encode($config) . '\' ';
        $html .= '/>';

        $html .= '</div>';

        return $html;
    }
}
