<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Compatibility\Traits;

use Canvastack\Canvastack\Components\Form\FormBuilder;
use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Components\Chart\ChartBuilder;

/**
 * UsesCanvastack Trait.
 *
 * Provides backward compatibility for controllers using old CanvaStack Origin API.
 * Add this trait to your controller to access $this->form, $this->table, $this->chart.
 *
 * @property FormBuilder $form
 * @property TableBuilder $table
 * @property ChartBuilder $chart
 */
trait UsesCanvastack
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
     * Initialize CanvaStack components.
     *
     * Call this method in your controller constructor or method
     * to initialize the components.
     *
     * @param string $context Context for components ('admin' or 'public')
     * @return void
     */
    protected function initializeCanvastack(string $context = 'admin'): void
    {
        $this->form = app(FormBuilder::class);
        $this->form->setContext($context);

        $this->table = app(TableBuilder::class);
        $this->table->setContext($context);

        $this->chart = app(ChartBuilder::class);
        $this->chart->setContext($context);
    }

    /**
     * Get form builder instance.
     *
     * @return FormBuilder
     */
    protected function getForm(): FormBuilder
    {
        if ($this->form === null) {
            $this->form = app(FormBuilder::class);
            $this->form->setContext('admin');
        }

        return $this->form;
    }

    /**
     * Get table builder instance.
     *
     * @return TableBuilder
     */
    protected function getTable(): TableBuilder
    {
        if ($this->table === null) {
            $this->table = app(TableBuilder::class);
            $this->table->setContext('admin');
        }

        return $this->table;
    }

    /**
     * Get chart builder instance.
     *
     * @return ChartBuilder
     */
    protected function getChart(): ChartBuilder
    {
        if ($this->chart === null) {
            $this->chart = app(ChartBuilder::class);
            $this->chart->setContext('admin');
        }

        return $this->chart;
    }

    /**
     * Magic getter for backward compatibility.
     *
     * Allows accessing $this->form, $this->table, $this->chart
     * without explicit initialization.
     *
     * @param string $name
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'form' => $this->getForm(),
            'table' => $this->getTable(),
            'chart' => $this->getChart(),
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
