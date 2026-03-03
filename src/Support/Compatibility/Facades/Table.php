<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Compatibility\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Table Facade.
 *
 * Backward compatibility facade for old CanvaStack Origin Table API.
 *
 * @method static \Canvastack\Canvastack\Components\Table\TableBuilder setModel(mixed $model)
 * @method static \Canvastack\Canvastack\Components\Table\TableBuilder setCollection(\Illuminate\Support\Collection $collection)
 * @method static \Canvastack\Canvastack\Components\Table\TableBuilder setData(array $data)
 * @method static \Canvastack\Canvastack\Components\Table\TableBuilder setFields(array $fields)
 * @method static \Canvastack\Canvastack\Components\Table\TableBuilder addAction(string $name, string $url, string $icon, string $label, string $method = 'GET')
 * @method static \Canvastack\Canvastack\Components\Table\TableBuilder setActions(array $actions, bool $includeDefaults = true)
 * @method static \Canvastack\Canvastack\Components\Table\TableBuilder eager(array $relations)
 * @method static \Canvastack\Canvastack\Components\Table\TableBuilder cache(int $ttl)
 * @method static \Canvastack\Canvastack\Components\Table\TableBuilder chunk(int $size)
 * @method static \Canvastack\Canvastack\Components\Table\TableBuilder orderBy(string $column, string $direction = 'asc')
 * @method static \Canvastack\Canvastack\Components\Table\TableBuilder format()
 * @method static \Canvastack\Canvastack\Components\Table\TableBuilder runModel(mixed $model)
 * @method static string render()
 * @method static void setContext(string $context)
 *
 * @see \Canvastack\Canvastack\Components\Table\TableBuilder
 */
class Table extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'canvastack.table';
    }
}
