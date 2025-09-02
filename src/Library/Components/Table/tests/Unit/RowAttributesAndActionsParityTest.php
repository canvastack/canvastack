<?php

declare(strict_types=1);

namespace Canvastack\Table\Tests\Unit;

use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Pipeline\DatatablesPipeline;
use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Support\LegacyContextAdapter;
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../Support/LegacyHelperStubs.php';

final class RowAttributesAndActionsParityTest extends TestCase
{
    public function test_enrich_rows_stage_adds_minimal_attributes_and_action_key(): void
    {
        // Arrange: minimal legacy-like inputs
        $method = ['difta' => ['name' => 'users']];
        $datatables = (object) [
            'columns' => [
                'users' => [
                    'lists' => ['id', 'name'],
                    'clickable' => ['id'],
                    'actions' => ['view'],
                ],
            ],
            'conditions' => [],
        ];

        // Fake request environment for skeleton pipeline (no DB assertions here)
        $_GET = ['draw' => 1, 'start' => 0, 'length' => 1];

        $adapter = new LegacyContextAdapter();
        $context = $adapter->fromLegacyInputs($method, $datatables, [], []);

        // Inject a simple response to bypass DB fetch in ResolveModelStage
        $context->response = [
            'draw' => 1,
            'recordsTotal' => 1,
            'recordsFiltered' => 1,
            'data' => [['id' => 1, 'name' => 'Alice']],
        ];

        // Act
        $pipeline = new DatatablesPipeline();
        $context = $pipeline->run($context);

        // Assert
        $this->assertIsArray($context->response);
        $this->assertArrayHasKey('data', $context->response);
        $this->assertNotEmpty($context->response['data']);

        $row = $context->response['data'][0];
        $this->assertArrayHasKey('_row_attr', $row, 'Row attributes should be present');
        $this->assertArrayHasKey('class', $row['_row_attr']);
        $this->assertSame('row-list-url', $row['_row_attr']['class']);

        // In unit test environment, stub returns empty string, but key should exist
        $this->assertArrayHasKey('action', $row);
    }
}
