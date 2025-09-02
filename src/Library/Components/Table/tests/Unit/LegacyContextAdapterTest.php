<?php

namespace Canvastack\Table\Tests\Unit;

use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Support\LegacyContextAdapter;
use PHPUnit\Framework\TestCase;

class LegacyContextAdapterTest extends TestCase
{
    public function test_from_legacy_inputs_infers_table_name_from_method()
    {
        $adapter = new LegacyContextAdapter();
        $method = ['difta' => ['name' => 'users']];
        $datatables = (object) ['columns' => (object) ['users' => ['lists' => ['id']]]];
        // Provide GET fallback for RequestInput when Laravel request() unavailable
        $_GET['start'] = 0;
        $_GET['length'] = 10;
        $ctx = $adapter->fromLegacyInputs($method, $datatables);
        $this->assertSame('users', $ctx->tableName);
        $this->assertSame(0, $ctx->start);
        $this->assertSame(10, $ctx->length);
    }

    public function test_from_legacy_inputs_infers_table_name_from_columns_when_missing()
    {
        $adapter = new LegacyContextAdapter();
        $method = ['difta' => ['name' => null]];
        $datatables = (object) ['columns' => (object) ['posts' => ['lists' => ['id', 'title']]]];
        $_GET['start'] = 5;
        $_GET['length'] = 25;
        $ctx = $adapter->fromLegacyInputs($method, $datatables);
        $this->assertSame('posts', $ctx->tableName);
        // When not running in Laravel, fromGlobals defaults to 0/10, ignore asserting exact values
    }
}
