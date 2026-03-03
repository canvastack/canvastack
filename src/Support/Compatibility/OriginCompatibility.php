<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Compatibility;

use Canvastack\Canvastack\Components\Form\FormBuilder;
use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Components\Chart\ChartBuilder;

/**
 * OriginCompatibility.
 *
 * Provides backward compatibility with CanvaStack Origin API.
 * This class acts as a bridge between old and new API calls.
 */
class OriginCompatibility
{
    /**
     * Form builder instance.
     *
     * @var FormBuilder|null
     */
    protected ?FormBuilder $form = null;

    /**
     * Table builder instance.
     *
     * @var TableBuilder|null
     */
    protected ?TableBuilder $table = null;

    /**
     * Chart builder instance.
     *
     * @var ChartBuilder|null
     */
    protected ?ChartBuilder $chart = null;

    /**
     * Get form builder instance.
     *
     * @return FormBuilder
     */
    public function form(): FormBuilder
    {
        if ($this->form === null) {
            $this->form = app(FormBuilder::class);
            $this->form->setContext('admin'); // Default to admin context
        }

        return $this->form;
    }

    /**
     * Get table builder instance.
     *
     * @return TableBuilder
     */
    public function table(): TableBuilder
    {
        if ($this->table === null) {
            $this->table = app(TableBuilder::class);
            $this->table->setContext('admin'); // Default to admin context
        }

        return $this->table;
    }

    /**
     * Get chart builder instance.
     *
     * @return ChartBuilder
     */
    public function chart(): ChartBuilder
    {
        if ($this->chart === null) {
            $this->chart = app(ChartBuilder::class);
            $this->chart->setContext('admin'); // Default to admin context
        }

        return $this->chart;
    }

    /**
     * Magic getter for backward compatibility.
     *
     * Allows accessing $this->form, $this->table, $this->chart
     * in old controllers.
     *
     * @param string $name
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'form' => $this->form(),
            'table' => $this->table(),
            'chart' => $this->chart(),
            default => null,
        };
    }

    /**
     * Check if property exists.
     *
     * @param string $name
     * @return bool
     */
    public function __isset(string $name): bool
    {
        return in_array($name, ['form', 'table', 'chart']);
    }
}
