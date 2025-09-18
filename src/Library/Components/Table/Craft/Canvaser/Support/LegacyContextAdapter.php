<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Support;

use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Contracts\ContextAdapterInterface;
use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\DTO\DatatablesContext;
use Canvastack\Canvastack\Core\Craft\Includes\SafeLogger;

class LegacyContextAdapter implements ContextAdapterInterface
{
    public function fromLegacyInputs(array $method, object $datatables, array $filters = [], array $filter_page = []): DatatablesContext
    {
        if (app()->environment(['local', 'testing'])) {
            SafeLogger::debug('LegacyContextAdapter: Converting legacy inputs to context', [
                'method_keys' => array_keys($method),
                'has_difta_name' => !empty($method['difta']['name']),
                'filters_count' => count($filters),
                'filter_page_count' => count($filter_page)
            ]);
        }

        $context = new DatatablesContext($datatables);
        $context->method = $method;
        $context->filters = $filters;
        $context->filterPage = $filter_page;

        // Prefer explicit table name from legacy method (difta)
        if (! empty($method['difta']['name'])) {
            $context->tableName = (string) $method['difta']['name'];
        } else {
            // Minimal discovery (Phase 2 placeholder)
            if (! empty($datatables->columns)) {
                // Try infer table name from first defined columns key
                $keys = array_keys((array) $datatables->columns);
                $context->tableName = $keys[0] ?? null;
            }
        }

        // Propagate connection hints from legacy data object when available
        try {
            if (isset($datatables->variables['connection']) && is_string($datatables->variables['connection'])) {
                $datatables->connection = $datatables->variables['connection'];
            }
        } catch (\Throwable $e) { /* ignore */
        }

        // Server-side params
        $req = RequestInput::fromGlobals();
        $context->start = $req['start'];
        $context->length = $req['length'];

        return $context;
    }
}
