<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Pipeline;

use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\DTO\DatatablesContext;

class DatatablesPipeline
{
    /**
     * Run the pipeline over the given context.
     * Phase 3B: include minimal row enrichment for parity (attributes + actions).
     */
    public function run(DatatablesContext $context): DatatablesContext
    {
        // Execute stages to produce and enrich response
        $stages = [
            new Stages\ResolveModelStage(),
            new Stages\FinalizeResponseStage(),
            new Stages\EnrichRowsStage(),
        ];
        foreach ($stages as $stage) {
            $context = $stage->execute($context);
        }

        return $context;
    }
}
