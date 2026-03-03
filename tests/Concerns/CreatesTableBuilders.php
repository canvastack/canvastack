<?php

namespace Canvastack\Canvastack\Tests\Concerns;

use Canvastack\Canvastack\Components\Table\Query\FilterBuilder;
use Canvastack\Canvastack\Components\Table\Query\QueryOptimizer;
use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Components\Table\Validation\ColumnValidator;
use Canvastack\Canvastack\Components\Table\Validation\SchemaInspector;

/**
 * Trait for creating TableBuilder instances in tests.
 *
 * This trait provides helper methods for creating properly configured
 * TableBuilder instances with all required dependencies.
 */
trait CreatesTableBuilders
{
    /**
     * Create a properly configured TableBuilder instance with all dependencies.
     *
     * This helper method creates a TableBuilder with all required dependencies
     * properly instantiated in the correct order.
     *
     * @return TableBuilder Fully configured TableBuilder instance
     */
    protected function createTableBuilder(): TableBuilder
    {
        // Create dependencies in correct order (bottom-up)
        $schemaInspector = new SchemaInspector();
        $columnValidator = new ColumnValidator($schemaInspector);
        $filterBuilder = new FilterBuilder($columnValidator);
        $queryOptimizer = new QueryOptimizer($filterBuilder, $columnValidator);

        // Create TableBuilder with all dependencies
        return new TableBuilder(
            $queryOptimizer,
            $filterBuilder,
            $schemaInspector,
            $columnValidator
        );
    }

    /**
     * Create a TableBuilder with a model set.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return TableBuilder
     */
    protected function createTableBuilderWithModel($model): TableBuilder
    {
        $table = $this->createTableBuilder();
        $table->setModel($model);

        return $table;
    }

    /**
     * Create a TableBuilder with collection data.
     *
     * @param \Illuminate\Support\Collection $collection
     * @return TableBuilder
     */
    protected function createTableBuilderWithCollection($collection): TableBuilder
    {
        $table = $this->createTableBuilder();
        $table->setCollection($collection);

        return $table;
    }

    /**
     * Create a TableBuilder with array data.
     *
     * @param array $data
     * @return TableBuilder
     */
    protected function createTableBuilderWithData(array $data): TableBuilder
    {
        $table = $this->createTableBuilder();
        $table->setData($data);

        return $table;
    }

    /**
     * Create a fully configured TableBuilder for testing.
     *
     * @param array $config Configuration options
     * @return TableBuilder
     */
    protected function createConfiguredTableBuilder(array $config = []): TableBuilder
    {
        $table = $this->createTableBuilder();

        // Set context
        if (isset($config['context'])) {
            $table->setContext($config['context']);
        }

        // Set model or data
        if (isset($config['model'])) {
            $table->setModel($config['model']);
        } elseif (isset($config['collection'])) {
            $table->setCollection($config['collection']);
        } elseif (isset($config['data'])) {
            $table->setData($config['data']);
        }

        // Set fields
        if (isset($config['fields'])) {
            $table->setFields($config['fields']);
        }

        // Set actions
        if (isset($config['actions'])) {
            $table->setActions($config['actions']);
        }

        // Set options
        if (isset($config['cache'])) {
            $table->cache($config['cache']);
        }

        if (isset($config['eager'])) {
            $table->eager($config['eager']);
        }

        if (isset($config['orderBy'])) {
            $table->orderBy($config['orderBy'][0], $config['orderBy'][1] ?? 'asc');
        }

        if (isset($config['hiddenColumns'])) {
            $table->setHiddenColumns($config['hiddenColumns']);
        }

        // Format the table
        if ($config['format'] ?? true) {
            $table->format();
        }

        return $table;
    }

    /**
     * Assert that a TableBuilder is properly configured.
     *
     * @param TableBuilder $table
     * @return void
     */
    protected function assertTableBuilderConfigured(TableBuilder $table): void
    {
        $this->assertInstanceOf(TableBuilder::class, $table);
        $this->assertNotNull($table->getContext());
    }

    /**
     * Assert that a TableBuilder has a model set.
     *
     * @param TableBuilder $table
     * @return void
     */
    protected function assertTableBuilderHasModel(TableBuilder $table): void
    {
        $this->assertNotNull($table->getModel(), 'TableBuilder should have a model set');
    }

    /**
     * Assert that a TableBuilder has data set.
     *
     * @param TableBuilder $table
     * @return void
     */
    protected function assertTableBuilderHasData(TableBuilder $table): void
    {
        $data = $table->getData();
        $this->assertNotEmpty($data, 'TableBuilder should have data set');
    }
}
