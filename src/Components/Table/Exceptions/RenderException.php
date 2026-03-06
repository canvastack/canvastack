<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Table\Exceptions;

/**
 * Exception thrown when table rendering fails.
 *
 * This exception is thrown when:
 * - Template rendering fails
 * - View compilation errors occur
 * - Asset loading fails
 * - JavaScript initialization fails
 * - Invalid renderer configuration
 *
 * @package Canvastack\Canvastack\Components\Table\Exceptions
 */
class RenderException extends TableException
{
    /**
     * Create exception for template rendering failure.
     *
     * @param string $templateName The template that failed to render
     * @param \Throwable|null $previous The previous exception
     * @return static
     */
    public static function templateFailed(string $templateName, ?\Throwable $previous = null): static
    {
        return new static(
            "Failed to render table template '{$templateName}'.",
            0,
            $previous
        );
    }

    /**
     * Create exception for missing template.
     *
     * @param string $templateName The missing template name
     * @param string $engineName The engine that requires the template
     * @return static
     */
    public static function templateNotFound(string $templateName, string $engineName): static
    {
        return new static(
            "Table template '{$templateName}' not found for engine '{$engineName}'."
        );
    }

    /**
     * Create exception for view compilation error.
     *
     * @param string $viewName The view that failed to compile
     * @param \Throwable|null $previous The previous exception
     * @return static
     */
    public static function viewCompilationFailed(string $viewName, ?\Throwable $previous = null): static
    {
        return new static(
            "Failed to compile table view '{$viewName}'.",
            0,
            $previous
        );
    }

    /**
     * Create exception for asset loading failure.
     *
     * @param string $assetType The type of asset (css, js)
     * @param string $assetPath The path to the asset
     * @return static
     */
    public static function assetLoadFailed(string $assetType, string $assetPath): static
    {
        return new static(
            "Failed to load table {$assetType} asset: {$assetPath}"
        );
    }

    /**
     * Create exception for JavaScript initialization failure.
     *
     * @param string $reason The reason for initialization failure
     * @return static
     */
    public static function jsInitFailed(string $reason): static
    {
        return new static(
            "Failed to initialize table JavaScript: {$reason}"
        );
    }

    /**
     * Create exception for invalid renderer configuration.
     *
     * @param string $reason The reason why renderer configuration is invalid
     * @return static
     */
    public static function invalidRendererConfig(string $reason): static
    {
        return new static(
            "Invalid table renderer configuration: {$reason}"
        );
    }

    /**
     * Create exception for missing renderer.
     *
     * @param string $engineName The engine missing a renderer
     * @return static
     */
    public static function rendererNotFound(string $engineName): static
    {
        return new static(
            "Renderer not found for table engine '{$engineName}'."
        );
    }

    /**
     * Create exception for data rendering failure.
     *
     * @param string $reason The reason for data rendering failure
     * @param \Throwable|null $previous The previous exception
     * @return static
     */
    public static function dataRenderFailed(string $reason, ?\Throwable $previous = null): static
    {
        return new static(
            "Failed to render table data: {$reason}",
            0,
            $previous
        );
    }

    /**
     * Create exception for column rendering failure.
     *
     * @param string $columnName The column that failed to render
     * @param \Throwable|null $previous The previous exception
     * @return static
     */
    public static function columnRenderFailed(string $columnName, ?\Throwable $previous = null): static
    {
        return new static(
            "Failed to render table column '{$columnName}'.",
            0,
            $previous
        );
    }
}
