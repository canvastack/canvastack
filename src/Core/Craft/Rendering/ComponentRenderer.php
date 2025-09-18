<?php

namespace Canvastack\Canvastack\Core\Craft\Rendering;

/**
 * ComponentRenderer Trait
 * 
 * Menangani rendering komponen form, table, dan chart.
 * Extracted from View.php untuk better separation of concerns.
 * 
 * Responsibilities:
 * - Form component rendering dan validation setup
 * - Table component rendering
 * - Chart component rendering dan initialization
 * - Script injection dari elements
 * - Special case handling (AJAX processing)
 * 
 * @author wisnuwidi@canvastack.com - 2021
 */
trait ComponentRenderer
{
    /**
     * Render form, table, and chart components.
     *
     * @return array Rendered elements
     */
    private function renderComponents()
    {
        $formElements = [];
        if (! empty($this->data['components']->form->elements)) {
            $this->form->setValidations($this->validations);
            $formElements = $this->form->render($this->data['components']->form->elements);
        }

        $tableElements = [];
        if (! empty($this->data['components']->table->elements)) {
            $tableElements = $this->table->render($this->data['components']->table->elements);
        }

        $chartElements = [];
        if (! empty($this->data['components']->chart->elements)) {
            $chartElements = $this->chart->render($this->data['components']->chart->elements);
        }

        return [
            'form' => $formElements,
            'table' => $tableElements,
            'chart' => $chartElements,
        ];
    }

    /**
     * Initialize chart rendering.
     *
     * @param array $method
     * @param array $data
     * @param array $model_filters
     */
    public function initRenderCharts($method, $data = [], $model_filters = [])
    {
        if (! empty($data)) {
            $dataChart = $data;
        } else {
            $dataChart = $this->data['components']->chart;
        }

        if (! empty($dataChart)) {
            //	dd($this->chart);
        }
    }

    /**
     * Handle special cases like AJAX.
     *
     * @param \Illuminate\Http\Request $request
     * @return mixed Return early if special case handled
     */
    private function handleSpecialCases(\Illuminate\Http\Request $request)
    {
        $validated = $request->validate([
            'ajaxfproc' => 'nullable|in:true,false,1,0',
        ]);

        if (! empty($validated['ajaxfproc'])) {
            return $this->form->ajaxProcessing();
        }

        return null;
    }
}