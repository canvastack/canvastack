<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Table\Renderers;

/**
 * RendererInterface - Contract for table renderers.
 *
 * Supports multiple rendering strategies (Admin/Public).
 */
interface RendererInterface
{
    /**
     * Render table HTML.
     *
     * @param array $data Table data and configuration
     * @return string Rendered HTML
     */
    public function render(array $data): string;

    /**
     * Render table header.
     *
     * @param array $columns Column definitions
     * @return string Rendered HTML
     */
    public function renderHeader(array $columns): string;

    /**
     * Render table body.
     *
     * @param array $rows Data rows
     * @param array $columns Column definitions
     * @return string Rendered HTML
     */
    public function renderBody(array $rows, array $columns): string;

    /**
     * Render table footer.
     *
     * @param array $data Footer data
     * @return string Rendered HTML
     */
    public function renderFooter(array $data): string;

    /**
     * Render action buttons for row.
     *
     * @param mixed $row Row data
     * @param array $actions Available actions
     * @return string Rendered HTML
     */
    public function renderActions($row, array $actions): string;
}
