<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Compatibility\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Chart Facade.
 *
 * Backward compatibility facade for old CanvaStack Origin Chart API.
 *
 * @method static \Canvastack\Canvastack\Components\Chart\ChartBuilder line(array $series, array $categories = [], array $options = [])
 * @method static \Canvastack\Canvastack\Components\Chart\ChartBuilder bar(array $series, array $categories = [], array $options = [])
 * @method static \Canvastack\Canvastack\Components\Chart\ChartBuilder pie(array $data, array $labels = [], array $options = [])
 * @method static \Canvastack\Canvastack\Components\Chart\ChartBuilder donut(array $data, array $labels = [], array $options = [])
 * @method static \Canvastack\Canvastack\Components\Chart\ChartBuilder area(array $series, array $categories = [], array $options = [])
 * @method static \Canvastack\Canvastack\Components\Chart\ChartBuilder cache(int $ttl)
 * @method static \Canvastack\Canvastack\Components\Chart\ChartBuilder height(int $height)
 * @method static \Canvastack\Canvastack\Components\Chart\ChartBuilder width(int $width)
 * @method static \Canvastack\Canvastack\Components\Chart\ChartBuilder colors(array $colors)
 * @method static string render()
 * @method static void setContext(string $context)
 *
 * @see \Canvastack\Canvastack\Components\Chart\ChartBuilder
 */
class Chart extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'canvastack.chart';
    }
}
