<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Chart\Renderers;

use Canvastack\Canvastack\Components\Chart\ChartBuilder;

/**
 * RendererInterface - Contract for chart renderers.
 *
 * Defines the interface for rendering charts in different contexts
 * (admin panel, public frontend).
 */
interface RendererInterface
{
    /**
     * Render a chart.
     *
     * @param ChartBuilder $chart Chart instance to render
     * @return string HTML output
     */
    public function render(ChartBuilder $chart): string;

    /**
     * Render chart container.
     *
     * @param ChartBuilder $chart Chart instance
     * @return string HTML container
     */
    public function renderContainer(ChartBuilder $chart): string;

    /**
     * Render chart script.
     *
     * @param ChartBuilder $chart Chart instance
     * @return string JavaScript code
     */
    public function renderScript(ChartBuilder $chart): string;

    /**
     * Render chart assets (CSS/JS includes).
     *
     * @return string HTML asset tags
     */
    public function renderAssets(): string;
}
