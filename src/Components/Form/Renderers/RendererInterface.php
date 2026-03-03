<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Form\Renderers;

use Canvastack\Canvastack\Components\Form\Fields\BaseField;

/**
 * RendererInterface - Contract for form field renderers.
 */
interface RendererInterface
{
    /**
     * Render a form field.
     *
     * @param BaseField $field
     * @return string
     */
    public function render(BaseField $field): string;

    /**
     * Render field label.
     *
     * @param BaseField $field
     * @return string
     */
    public function renderLabel(BaseField $field): string;

    /**
     * Render field input.
     *
     * @param BaseField $field
     * @return string
     */
    public function renderInput(BaseField $field): string;

    /**
     * Render field help text.
     *
     * @param BaseField $field
     * @return string
     */
    public function renderHelpText(BaseField $field): string;

    /**
     * Render field errors.
     *
     * @param BaseField $field
     * @return string
     */
    public function renderErrors(BaseField $field): string;

    /**
     * Render tabs with navigation and content.
     *
     * @param array<\Canvastack\Canvastack\Components\Form\Features\Tabs\Tab> $tabs
     * @return string
     */
    public function renderTabs(array $tabs): string;
}
