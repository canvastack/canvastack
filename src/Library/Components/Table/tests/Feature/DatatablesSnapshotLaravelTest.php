<?php

namespace Canvastack\Table\Tests\Feature;

use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Support\HybridCompare;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatatablesSnapshotLaravelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->increments('id');
            });
        }
        if ((int) DB::table('users')->count() === 0) {
            DB::table('users')->insert([['id' => 1]]);
        }
    }

    public function test_hybrid_compare_summary_snapshot_with_laravel_app()
    {
        if (! class_exists(__NAMESPACE__.'\\TestUser')) {
            eval('namespace '.str_replace('\\\\', '\\', __NAMESPACE__).'; class TestUser extends \\Illuminate\\Database\\Eloquent\\Model { protected $table = "users"; public $timestamps = false; protected $guarded = []; }');
        }

        $method = ['difta' => ['name' => 'users']];
        $data = (object) [
            'datatables' => (object) [
                'model' => [
                    'users' => [
                        'type' => 'model',
                        'source' => new TestUser(),
                    ],
                ],
                'columns' => [
                    'users' => [
                        'lists' => ['id'],
                    ],
                ],
                'records' => ['index_lists' => false],
                'button_removed' => [],
                'useFieldTargetURL' => null,
            ],
        ];

        $result = HybridCompare::run($method, $data, [], []);
        $diff = $result['diff'] ?? [];
        $summary = $diff['summary'] ?? [];

        if (($diff['note'] ?? '') === 'pipeline_output_unavailable') {
            $this->markTestSkipped('Pipeline output unavailable; snapshot not applicable.');
        }

        $this->assertIsArray($result);
        $this->assertArrayHasKey('legacy_result', $result);
        $this->assertArrayHasKey('diff', $result);

        $this->assertIsArray($summary);
        // Summary keys may be arrays when coming from JsonDiff summary; validate inner ints
        foreach (['recordsTotal', 'recordsFiltered'] as $k) {
            if (isset($summary[$k])) {
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
    }
}
