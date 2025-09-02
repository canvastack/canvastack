<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Pipeline\Stages;

use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Contracts\PipelineStageInterface;
use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\DTO\DatatablesContext;

/**
 * EnrichRowsStage
 * - Minimal parity for row attributes (class, rlp) and action column rendering.
 * - Reads legacy-like hints from $context->datatables configuration if available.
 * - Does not perform privileges hardening; that is covered in Action 7.
 */
class EnrichRowsStage implements PipelineStageInterface
{
    public function execute(DatatablesContext $context): DatatablesContext
    {
        // Require a response with data to enrich
        if (empty($context->response) || ! is_array($context->response) || ! isset($context->response['data'])) {
            return $context;
        }

        $table = $context->tableName;
        $dt = $context->datatables;

        // Row attributes minimal mapping (clickable => class=row-list-url, rlp=encoded id)
        $rowAttributes = [];
        try {
            if ($table && isset($dt->columns[$table]['clickable'])) {
                $rowAttributes['class'] = 'row-list-url';
                // 'rlp' closure cannot be serialized; instead, compute per-row below
            }
        } catch (\Throwable $e) {
            // ignore
        }

        $data = $context->response['data'];
        $enriched = [];
        foreach ($data as $row) {
            // Normalize row array
            $arr = (array) $row;

            // Per-row attributes
            $attrs = $rowAttributes;
            if (! empty($rowAttributes)) {
                if (function_exists('encode_id')) {
                    $rid = isset($arr['id']) ? (int) $arr['id'] : null;
                    $attrs['rlp'] = $rid ? \canvastack_unescape_html(encode_id($rid)) : null;
                } else {
                    $attrs['rlp'] = null;
                }
            }

            // Actions rendering via safe renderer
            try {
                $actionHtml = \Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Support\ActionButtonsRenderer::render($arr, $dt, $table);
            } catch (\Throwable $e) {
                $actionHtml = '';
            }

            // Attach to row
            $arr['_row_attr'] = $attrs;
            $arr['action'] = $actionHtml;
            $enriched[] = $arr;
        }

        $context->rowAttributes = $rowAttributes;
        $context->response['data'] = $enriched;

        return $context;
    }
}
