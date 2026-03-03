<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Form\Renderers;

use Canvastack\Canvastack\Components\Form\Features\Enhancements\CharacterCounter;
use Canvastack\Canvastack\Components\Form\Fields\BaseField;
use Canvastack\Canvastack\Components\Form\Fields\TextareaField;
use Canvastack\Canvastack\Components\Form\Fields\TextField;

/**
 * PublicRenderer - Renders form fields for public frontend.
 *
 * Extends AdminRenderer with public-specific styling adjustments.
 */
class PublicRenderer extends AdminRenderer
{
    use TabRenderingTrait;

    /*
     * Public renderer uses same rendering logic as admin
     * but can be customized for public-facing forms
     *
     * Override specific methods here if needed for public styling
     */

    /**
     * Get current rendering context.
     */
    protected function getContext(): string
    {
        return 'public';
    }

    /**
     * Render character counter with public context styling.
     */
    protected function renderCharacterCounter(BaseField $field): string
    {
        // Only render for TextField and TextareaField with maxLength
        if (!($field instanceof TextField || $field instanceof TextareaField)) {
            return '';
        }

        $maxLength = $field->getMaxLength();
        if (!$maxLength) {
            return '';
        }

        $counter = new CharacterCounter();

        return $counter->render($field->getName(), $maxLength, 'public');
    }
}
