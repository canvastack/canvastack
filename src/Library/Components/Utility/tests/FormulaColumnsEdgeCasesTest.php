<?php

namespace Tests\Utility;

use PHPUnit\Framework\TestCase;
use Canvastack\Canvastack\Library\Components\Utility\Table\FormulaColumns;

class FormulaColumnsEdgeCasesTest extends TestCase
{
    public function test_without_action_and_number_lists_appends_on_last_when_node_after_true()
    {
        $columns = ['id', 'name'];
        $data = [[
            'name' => 'total',
            'label' => 'Total',
            'field_lists' => ['id'],
            'node_location' => 'last',
            'node_after' => true,
        ]];
        $this->assertSame(['id','name','total'], FormulaColumns::set($columns, $data));
    }

    public function test_custom_target_not_found_is_skipped()
    {
        $columns = ['id', 'name'];
        $data = [[
            'name' => 'x',
            'label' => 'X',
            'field_lists' => ['zzz'],
            'node_location' => 'no_such_field',
            'node_after' => true,
        ]];
        $this->assertSame(['id','name'], FormulaColumns::set($columns, $data));
    }

    public function test_first_with_number_lists_inserts_after_number_lists()
    {
        $columns = ['number_lists','id','name'];
        $data = [[
            'name' => 'rank',
            'label' => 'Rank',
            'field_lists' => ['id'],
            'node_location' => 'first',
            'node_after' => false,
        ]];
        $this->assertSame(['number_lists','rank','id','name'], FormulaColumns::set($columns, $data));
    }

    public function test_last_node_after_true_with_action_present_inserts_before_action()
    {
        $columns = ['id','name','action'];
        $data = [[
            'name' => 'total',
            'label' => 'Total',
            'field_lists' => ['name'],
            'node_location' => 'last',
            'node_after' => true,
        ]];
        $this->assertSame(['id','name','total','action'], FormulaColumns::set($columns, $data));
    }

    public function test_last_node_after_false_inserts_before_last_column()
    {
        $columns = ['id','name'];
        $data = [[
            'name' => 'before_last',
            'label' => 'BeforeLast',
            'field_lists' => ['id'],
            'node_location' => 'last',
            'node_after' => false,
        ]];
        $this->assertSame(['id','before_last','name'], FormulaColumns::set($columns, $data));
    }
}