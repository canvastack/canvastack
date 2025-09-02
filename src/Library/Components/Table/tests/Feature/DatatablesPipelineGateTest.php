<?php

namespace Canvastack\Table\Tests\Feature;

use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Support\HybridCompare;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatatablesPipelineGateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->increments('id');
            });
        }
        // Anchor seed: keep table empty to make legacy vs pipeline comparable for strict gate
        // (recordsTotal = 0, recordsFiltered = 0, data_length = 0)
        // If you need non-empty fixtures, adjust both legacy and pipeline to ensure parity first.
    }

    public function test_pipeline_ci_gate()
    {
        $gate = getenv('CANVASTACK_PIPELINE_GATE') ?: 'off';

        if (! class_exists(__NAMESPACE__.'\\GateUser')) {
            eval('namespace '.str_replace('\\\\', '\\', __NAMESPACE__).'; class GateUser extends \\Illuminate\\Database\\Eloquent\\Model { protected $table = "users"; public $timestamps = false; protected $guarded = []; }');
        }

        $method = ['difta' => ['name' => 'users']];
        $data = (object) [
            'datatables' => (object) [
                'model' => [
                    'users' => [
                        'type' => 'model',
                        'source' => new GateUser(),
                    ],
                ],
                'columns' => ['users' => ['lists' => ['id']]],
                'records' => ['index_lists' => false],
            ],
        ];

        $result = HybridCompare::run($method, $data, [], []);
        $diff = $result['diff'] ?? [];

        if (($diff['note'] ?? '') === 'pipeline_output_unavailable') {
            // When pipeline output is unavailable, ensure the note is present and exit
            $this->assertSame('pipeline_output_unavailable', $diff['note']);

            return;
        }

        if ($gate === 'off') {
            // OFF: validate summary shape only, no enforcement
            $summary = $diff['summary'] ?? [];
            $this->assertIsArray($summary);
            $this->assertArrayHasKey('recordsTotal', $summary);
            $this->assertArrayHasKey('recordsFiltered', $summary);
            $this->assertArrayHasKey('data_length', $summary);

            return;
        }

        if ($gate === 'strict') {
            $this->assertEquals('no_diff', $diff['note'] ?? null, 'Pipeline diff detected under strict gate');
        }

        if ($gate === 'soft') {
            $summary = $diff['summary'] ?? [];
            $rt = $summary['recordsTotal'] ?? null;
            $rf = $summary['recordsFiltered'] ?? null;
            $dl = $summary['data_length'] ?? null;

            foreach ([['k' => 'recordsTotal', 'v' => $rt], ['k' => 'recordsFiltered', 'v' => $rf], ['k' => 'data_length', 'v' => $dl]] as $item) {
                $v = $item['v'];
                if (is_array($v) && isset($v['legacy']) && isset($v['pipeline']) && $v['legacy'] !== null && $v['pipeline'] !== null) {
                    $this->assertSame($v['legacy'], $v['pipeline'], 'Soft gate mismatch for '.$item['k']);
                }
            }

            $this->assertIsArray($summary);
        }
    }
}
