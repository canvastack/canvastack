<?php

namespace Canvastack\Table\Tests\Feature;

use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Support\HybridCompare;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DatatablesUsersSnapshotTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Fresh DB
        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);
        Artisan::call('migrate');
        try {
            Artisan::call('db:seed');
        } catch (\Throwable $e) { /* ignore */
        }
    }

    private function makeUsersDatatablesDescriptor(): object
    {
        if (! class_exists(__NAMESPACE__.'\\UsersModel')) {
            eval('namespace '.str_replace('\\\\', '\\', __NAMESPACE__).'; class UsersModel extends \\Illuminate\\Database\\Eloquent\\Model { protected $table = "users"; public $timestamps = false; protected $guarded = []; }');
        }

        return (object) [
            'datatables' => (object) [
                'model' => [
                    'users' => [
                        'type' => 'model',
                        'source' => new UsersModel(),
                    ],
                ],
                'columns' => [
                    'users' => [
                        'lists' => ['id', 'name', 'email'],
                    ],
                ],
                'records' => ['index_lists' => false],
                'button_removed' => [],
                'useFieldTargetURL' => null,
            ],
        ];
    }

    public function test_users_table_snapshot_summary()
    {
        $method = ['difta' => ['name' => 'users']];
        $data = $this->makeUsersDatatablesDescriptor();

        $result = HybridCompare::run($method, $data, [], []);
        $diff = $result['diff'] ?? [];

        if (($diff['note'] ?? '') === 'pipeline_output_unavailable') {
            $this->markTestSkipped('Pipeline output unavailable.');
        }

        $this->assertArrayHasKey('summary', $diff);
        $summary = $diff['summary'];
        $this->assertArrayHasKey('recordsTotal', $summary);
        $this->assertArrayHasKey('recordsFiltered', $summary);
        $this->assertArrayHasKey('data_length', $summary);

        foreach (['recordsTotal', 'recordsFiltered'] as $k) {
            if (isset($summary[$k])) {
                if (is_array($summary[$k])) {
                    foreach (['legacy', 'pipeline'] as $side) {
                        if (isset($summary[$k][$side]) && $summary[$k][$side] !== null) {
                            $this->assertIsInt($summary[$k][$side]);
                        }
                    }
                } elseif ($summary[$k] !== null) {
                    $this->assertIsInt($summary[$k]);
                }
            }
        }
    }
}
