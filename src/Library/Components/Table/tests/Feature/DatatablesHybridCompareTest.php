<?php

// Define minimal global helper stubs required by legacy Datatables logic

namespace {
    if (! function_exists('get_object_called_name')) {
        function get_object_called_name($obj)
        {
            // Legacy code expects 'builder' when operating on query/eloquent builder before ->get()
            return 'builder';
        }
    }
    if (! function_exists('canvastack_current_url')) {
        function canvastack_current_url()
        {
            return '/';
        }
    }
    if (! function_exists('canvastack_table_action_button')) {
        function canvastack_table_action_button($model, $field_target, $current_url, $actions, $removed)
        {
            // Return empty action HTML to avoid dependency on full UI helpers
            return '';
        }
    }
}

// Back to test namespace

namespace Tests\Feature {
    use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Support\HybridCompare;
    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Schema;
    use Tests\TestCase;

    final class DatatablesHybridCompareTest extends TestCase
    {
        protected function setUp(): void
        {
            parent::setUp();

            // Prepare in-memory schema for users
            Schema::create('users', function (Blueprint $t) {
                $t->increments('id');
                $t->string('name')->nullable();
                $t->string('email')->nullable();
            });

            DB::table('users')->insert([
                ['name' => 'Alice', 'email' => 'alice@example.test'],
                ['name' => 'Bob',   'email' => 'bob@example.test'],
                ['name' => 'Cara',  'email' => 'cara@example.test'],
            ]);
        }

        protected function tearDown(): void
        {
            Schema::dropIfExists('users');
            parent::tearDown();
        }

        public function test_hybrid_compare_runs_preflight_without_errors(): void
        {
            // Eloquent model minimal
            $model = new class extends Model
            {
                protected $table = 'users';

                public $timestamps = false;

                protected $guarded = [];
            };

            $method = ['difta' => ['name' => 'users']];
            $data = (object) [
                'datatables' => (object) [
                    'model' => [
                        'users' => [
                            'type' => 'model',
                            'source' => $model,
                        ],
                    ],
                    'columns' => [
                        'users' => [
                            'lists' => ['id', 'name', 'email'],
                            'orderby' => ['column' => 'id', 'order' => 'desc'],
                            // Keep actions/clickable/formatting unset to avoid optional helpers
                        ],
                    ],
                    'records' => ['index_lists' => false],
                    // Provide optional fields to satisfy legacy access
                    'button_removed' => [],
                    'useFieldTargetURL' => 'id',
                ],
            ];

            $result = HybridCompare::run($method, $data, [], []);

            $this->assertIsArray($result);
            $this->assertArrayHasKey('legacy_result', $result);
            $this->assertArrayHasKey('diff', $result);
        }
    }
}
