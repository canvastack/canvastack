<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Security\Table;

use Canvastack\Canvastack\Components\Table\Query\FilterBuilder;
use Canvastack\Canvastack\Components\Table\Query\QueryOptimizer;
use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Components\Table\Validation\ColumnValidator;
use Canvastack\Canvastack\Components\Table\Validation\SchemaInspector;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Base class for security tests with proper dependency injection.
 */
abstract class SecurityTestCase extends TestCase
{
    use RefreshDatabase;

    protected SchemaInspector $schemaInspector;

    protected ColumnValidator $columnValidator;

    protected FilterBuilder $filterBuilder;

    protected QueryOptimizer $queryOptimizer;

    protected function setUp(): void
    {
        parent::setUp();

        // Initialize dependencies
        $this->schemaInspector = new SchemaInspector();
        $this->columnValidator = new ColumnValidator($this->schemaInspector);
        $this->filterBuilder = new FilterBuilder($this->columnValidator);
        $this->queryOptimizer = new QueryOptimizer($this->filterBuilder, $this->columnValidator);
    }

    /**
     * Create a TableBuilder instance with all required dependencies.
     */
    protected function createTableBuilder(): TableBuilder
    {
        return new TableBuilder(
            $this->queryOptimizer,
            $this->filterBuilder,
            $this->schemaInspector,
            $this->columnValidator
        );
    }
}
