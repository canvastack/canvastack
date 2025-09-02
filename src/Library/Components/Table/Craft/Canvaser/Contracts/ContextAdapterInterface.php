<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Contracts;

use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\DTO\DatatablesContext;

interface ContextAdapterInterface
{
    /**
     * Adapt legacy inputs to DatatablesContext DTO.
     *
     * @param  array  $method          Legacy method array
     * @param  object  $datatables     Legacy datatables config object
     * @param  array  $filters         Request filters (GET/POST normalized)
     * @param  array  $filter_page     Additional model_filters/page filters
     */
    public function fromLegacyInputs(array $method, object $datatables, array $filters = [], array $filter_page = []): DatatablesContext;
}
