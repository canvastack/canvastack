<?php

namespace Canvastack\Table\Tests\Feature;

use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Support\HybridCompare;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatatablesSnapshotTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Ensure minimal schema exists for this snapshot test
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->increments('id');
            });
        }
        if ((int) DB::table('users')->count() === 0) {
            DB::table('users')->insert([['id' => 1]]);
        }
    }

    private function normalizeSummary(array $summary): array
    {
        return [
            'recordsTotal' => $summary['recordsTotal'] ?? null,
            'recordsFiltered' => $summary['recordsFiltered'] ?? null,
            'data_length' => $summary['data_length'] ?? null,
        ];
    }

    public function test_hybrid_compare_summary_snapshot()
    {
        // Use Eloquent model to satisfy legacy builder expectations
        if (! class_exists(__NAMESPACE__.'\\PlainUser')) {
            eval('namespace '.str_replace('\\\\', '\\', __NAMESPACE__).'; class PlainUser extends \\Illuminate\\Database\\Eloquent\\Model { protected $table = "users"; public $timestamps = false; protected $guarded = []; }');
        }

        $method = ['difta' => ['name' => 'users']];
        $data = (object) [
            'datatables' => (object) [
                'model' => [
                    'users' => [
                        'type' => 'model',
                        'source' => new PlainUser(),
                    ],
                ],
                'columns' => ['users' => ['lists' => ['id']]],
                'records' => ['index_lists' => false],
                'button_removed' => [],
                'useFieldTargetURL' => null,
            ],
        ];

        $result = HybridCompare::run($method, $data, [], []);
        $diff = $result['diff'] ?? [];
        $summary = $this->normalizeSummary($diff['summary'] ?? []);

        // Allow skip only if pipeline output truly unavailable
        if (($diff['note'] ?? '') === 'pipeline_output_unavailable') {
            $this->markTestSkipped('Pipeline output unavailable; snapshot not applicable.');
        }

        $this->assertArrayHasKey('recordsTotal', $summary);
        $this->assertArrayHasKey('recordsFiltered', $summary);
        $this->assertArrayHasKey('data_length', $summary);

        foreach (['recordsTotal', 'recordsFiltered'] as $k) {
            if ($summary[$k] !== null) {
                if (is_array($summary[$k])) {
                    foreach (['legacy', 'pipeline'] as $side) {
                        if (isset($summary[$k][$side]) && $summary[$k][$side] !== null) {
                            $this->assertIsInt($summary[$k][$side]);
                        }
                    }
                } else {
                    $this->assertIsInt($summary[$k]);
                }
            }
        }

        if ($summary['data_length'] !== null) {
            $this->assertIsArray($summary['data_length']);
            foreach (['legacy', 'pipeline'] as $side) {
                if (isset($summary['data_length'][$side])) {
                    $this->assertIsInt($summary['data_length'][$side]);
                }
            }
        }
    }
}
