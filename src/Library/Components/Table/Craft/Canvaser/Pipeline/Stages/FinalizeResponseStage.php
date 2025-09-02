<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Pipeline\Stages;

use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Contracts\PipelineStageInterface;
use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\DTO\DatatablesContext;

class FinalizeResponseStage implements PipelineStageInterface
{
    /**
     * Ensure response shape exists if no previous stage filled it.
     * Keeps existing data to avoid overwriting actual results.
     */
    public function execute(DatatablesContext $context): DatatablesContext
    {
        if (empty($context->response)) {
            $draw = (int) (function_exists('request') ? request()->get('draw', 0) : 0);
            $context->response = [
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ];
        }

        return $context;
    }
}
