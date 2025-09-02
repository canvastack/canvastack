<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Contracts;

use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\DTO\DatatablesContext;

interface PipelineStageInterface
{
    /** Execute this stage and return the mutated context (Phase 2: no-op default). */
    public function execute(DatatablesContext $context): DatatablesContext;
}
