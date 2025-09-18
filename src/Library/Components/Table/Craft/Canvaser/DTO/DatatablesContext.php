<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\DTO;

use Canvastack\Canvastack\Core\Craft\Includes\SafeLogger;

class DatatablesContext
{
    // Minimal fields for Phase 2 (skeleton only)
    public array $method = [];

    public object $datatables;

    public array $filters = [];

    public array $filterPage = [];

    // Resolved basics
    public ?string $tableName = null;

    public mixed $modelSource = null; // Eloquent builder/model or SQL descriptor

    // Server-side params
    public int $start = 0;

    public int $length = 10;

    // Outputs for later stages / HybridCompare
    public mixed $response = null; // DataTables-like array or Response

    public mixed $payload = null;  // Alternative container for partial results

    // Row enrichment (Phase 3B minimal parity)
    // - attributes: optional map for DataTables row attributes (e.g., class, data-*)
    // - actions:    optional rendered HTML or data structure for action column
    public array $rowAttributes = [];

    public array $rowActions = [];

    public function __construct(object $datatables)
    {
        if (app()->environment(['local', 'testing'])) {
            SafeLogger::debug('DatatablesContext: Creating new context', [
                'datatables_type' => get_class($datatables)
            ]);
        }

        $this->datatables = $datatables;
    }
}
