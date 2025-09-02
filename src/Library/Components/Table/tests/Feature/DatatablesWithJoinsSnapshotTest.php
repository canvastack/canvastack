<?php

namespace Canvastack\Table\Tests\Feature;

use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Support\HybridCompare;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatatablesWithJoinsSnapshotTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Force sqlite connection for this test
        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);
        // Use explicit schema only; avoid global migrations
        \Illuminate\Support\Facades\Schema::create('users', function (\Illuminate\Database\Schema\Blueprint $t) {
            $t->increments('id');
            $t->string('name')->nullable();
            $t->string('email')->nullable();
        });
        \Illuminate\Support\Facades\Schema::create('posts', function (\Illuminate\Database\Schema\Blueprint $t) {
            $t->increments('id');
            $t->integer('user_id');
            $t->string('title')->nullable();
            $t->text('body')->nullable();
        });
        if (0 === (int) DB::table('users')->count()) {
            DB::table('users')->insert(['id' => 1, 'name' => 'Alice', 'email' => 'a@example.com']);
        }
        if (0 === (int) DB::table('posts')->count()) {
            DB::table('posts')->insert(['id' => 101, 'user_id' => 1, 'title' => 'Hello', 'body' => 'World']);
        }
    }

    private function makePostsDescriptor(): object
    {
        if (! class_exists(__NAMESPACE__.'\\PostsModel')) {
            eval('namespace '.str_replace('\\\\', '\\', __NAMESPACE__).'; class PostsModel extends \\Illuminate\\Database\\Eloquent\\Model { protected $table = "posts"; public $timestamps = false; protected $guarded = []; }');
        }

        return (object) [
            'datatables' => (object) [
                'model' => [
                    'posts' => [
                        'type' => 'model',
                        'source' => new PostsModel(),
                    ],
                ],
                'columns' => [
                    'posts' => [
                        'lists' => ['id', 'title'],
                        'foreign_keys' => ['users.id' => 'posts.user_id'],
                    ],
                ],
                'records' => ['index_lists' => false],
                'button_removed' => [],
                'useFieldTargetURL' => null,
            ],
        ];
    }

    public function test_posts_with_joins_snapshot_summary()
    {
        $method = ['difta' => ['name' => 'posts']];
        $data = $this->makePostsDescriptor();

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
    }
}
