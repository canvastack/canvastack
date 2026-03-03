<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Exceptions;

use Exception;

/**
 * Exception thrown when attempting to create a duplicate translation.
 *
 * A duplicate translation occurs when trying to create a translation
 * for the same model, attribute, and locale combination that already exists.
 */
class DuplicateTranslationException extends Exception
{
    /**
     * Create a new duplicate translation exception.
     *
     * @param  string  $translatableType  The model class name
     * @param  int  $translatableId  The model ID
     * @param  string  $attribute  The attribute name
     * @param  string  $locale  The locale code
     * @return static
     */
    public static function forTranslation(
        string $translatableType,
        int $translatableId,
        string $attribute,
        string $locale
    ): static {
        $modelName = class_basename($translatableType);

        return new static(
            "A translation for {$modelName}#{$translatableId} attribute '{$attribute}' in locale '{$locale}' already exists. " .
            "Use updateOrCreate() or delete the existing translation first."
        );
    }

    /**
     * Create a new duplicate translation exception from a database exception.
     *
     * @param  \Illuminate\Database\QueryException  $exception
     * @return static
     */
    public static function fromQueryException(\Illuminate\Database\QueryException $exception): static
    {
        return new static(
            'A translation with the same model, attribute, and locale already exists. ' .
            'Use updateOrCreate() or delete the existing translation first.',
            0,
            $exception
        );
    }
}
